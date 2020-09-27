<?php

namespace FluentCrm\Includes\Mailer;

use Exception;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;

class Handler
{
    protected $startedAt = null;

    protected $campaignId = false;

    protected $processedCampaigns = [];

    protected $emailLimitPerSecond = 0;

    protected $maximumProcessingTime = 20;

    protected $dispatchedWithinOneSecond = 0;

    public function handle($campaignId = null)
    {
        try {
            $this->campaignId = $campaignId;
            if ($this->isProcessing()) {
                return;
            }
            $this->processing();
            $this->handleFailedLog();
            $this->startedAt = microtime(true);
            foreach ((new CampaignEmailIterator($campaignId)) as $emailCollection) {
                $this->updateProcessTime();
                $this->sendEmails($emailCollection);
            }
            $this->finishProcessing();
        } catch (\Exception $e) {
            $this->finishProcessing();
        }
    }

    public function processSubscriberEmail($subscriberId)
    {
        $emailCollection = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->where('scheduled_at', '<=', current_time('mysql'))
            ->whereNotNull('scheduled_at')
            ->with('campaign', 'subscriber')
            ->where('subscriber_id', $subscriberId)
            ->get();
        $this->sendEmails($emailCollection);
    }

    protected function isProcessing()
    {
        $lastProcessStartedAt = get_option(FLUENTCRM . '_is_sending_emails');

        if (!$lastProcessStartedAt) {
            return false;
        }

        if ($this->seemsStuck($lastProcessStartedAt)) {
            return false;
        }

        return true;
    }

    protected function processing()
    {
        update_option(FLUENTCRM . '_is_sending_emails', time());

        Campaign::where('status', 'pending')
            ->when($this->campaignId, function ($query) {
                $query->where('id', $this->campaignId);
            })
            ->where('scheduled_at', '<=', fluentCrmUTCTimestamp())
            ->update(['status' => 'working']);
    }

    protected function updateProcessTime()
    {
        update_option(FLUENTCRM . '_is_sending_emails', time());
    }

    protected function seemsStuck($lastProcessStartedAt)
    {
        if ($lastProcessStartedAt && time() - $lastProcessStartedAt > 60) {
            return true;
        }
        return false;
    }

    protected function sendEmails($campaignEmails)
    {
        $sentIds = [];
        $failedIds = [];
        foreach ($campaignEmails as $email) {
            if ($this->reachedEmailLimitPerSecond()) {
                $this->restartWhenOneSecondExceeds();
            } else {
                $response = Mailer::send($email->data());
                $this->dispatchedWithinOneSecond++;
                if (is_wp_error($response)) {
                    $failedIds[] = $email->id;
                }
                $sentIds[] = $email->id;
                $this->processedCampaigns[$email->campaign->id] = $email->campaign->id;
            }
        }
        if ($sentIds) {
            CampaignEmail::whereIn('id', $sentIds)->whereNot('status', 'failed')->update([
                'status'     => 'sent',
                'updated_at' => current_time('mysql')
            ]);
        }
        if ($failedIds) {
            CampaignEmail::whereIn('id', $failedIds)->update(['status' => 'failed']);
        }
    }

    protected function reachedEmailLimitPerSecond()
    {
        $emailLimitPerSecond = $this->getEmailLimitPerSecond();
        return ($emailLimitPerSecond && $this->dispatchedWithinOneSecond >= $emailLimitPerSecond);
    }

    protected function restartWhenOneSecondExceeds()
    {
        $sleepMicroSecond = 1000000 - (microtime(true) - $this->startedAt) * 1000000;
        if ($sleepMicroSecond > 0) {
            usleep(ceil($sleepMicroSecond));
        }

        $this->dispatchedWithinOneSecond = 0;
        $this->startedAt = microtime(true);
    }

    protected function getEmailLimitPerSecond()
    {
        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        if (!empty($emailSettings['emails_per_second'])) {
            $limit = $emailSettings['emails_per_second'];
        } else {
            $limit = 30;
        }

        return ($limit > $this->emailLimitPerSecond) ? ($limit - 1) : $limit;
    }

    protected function finishProcessing()
    {
        $this->markIncompleteCampaigns();

        $this->markArchiveCampaigns();

        $this->jobCompleted();
    }

    protected function markIncompleteCampaigns()
    {
        $campaigns = Campaign::where('status', 'working')->whereHas('emails', function ($query) {
            $query->where('status', 'failed');
        })->whereIn('id', array_unique($this->processedCampaigns))->get();

        if (!$campaigns->empty()) {
            Campaign::whereIn(
                'id', array_unique($campaigns->pluck('id'))
            )->update(['status' => 'incomplete']);
        }
    }

    protected function markArchiveCampaigns()
    {
        $campaigns = Campaign::where('status', 'working')->whereDoesNotHave('emails', function ($query) {
            $query->whereIn('status', ['pending', 'failed']);
        })->whereIn('id', array_unique($this->processedCampaigns))->get();

        if (!$campaigns->empty()) {
            Campaign::whereIn(
                'id', array_unique($campaigns->pluck('id'))
            )->update(['status' => 'archived']);
        }
    }

    protected function jobCompleted()
    {
        // If we've still some campaigns in working mode then thay are stuck so
        // Mark those campaigns and their pending emails as purged, so we can show
        // those campaigns in the campaign's page (index) allowed to edit the campaign.
        foreach (Campaign::where('status', 'working')->get() as $campaign) {
            $hasSent = $campaign->emails()->where('status', 'sent')->count();
            $hasFailed = $campaign->emails()->where('status', 'failed')->count();
            $hasPending = $campaign->emails()->where('status', 'pending')->count();

            if (!$hasPending) {
                $campaign->status = 'archived';
                $campaign->save();
            } else if (!$hasSent && !$hasFailed) {
                $campaign->emails()->update(['status' => 'purged']);
                $campaign->status = 'purged';
                $campaign->save();
            }
        }

        update_option(FLUENTCRM . '_is_sending_emails', null);
    }

    protected function handleFailedLog()
    {
        $campaignId = $this->campaignId;
        add_action('wp_mail_failed', function ($error) use ($campaignId) {
            $data = $error->get_error_data();
            $to = Arr::get($data, 'to');
            if ($to) {
                if (is_array($to)) {
                    $to = $to[0];
                }
            }

            if (!$to) {
                return;
            }

            CampaignEmail::where('campaign_id', $campaignId)
                ->where('email_address', $to)
                ->limit(1)
                ->orderBy('id', 'DESC')
                ->update([
                    'status' => 'failed',
                    'note'   => $error->get_error_message()
                ]);
        });
    }

    public function sendDoubleOptInEmail($subscriber)
    {
        if ($subscriber->status == 'subscribed') {
            return false; // already subscribed
        }
        $config = Helper::getDoubleOptinSettings();
        if (!Arr::get($config, 'email_subject') || !Arr::get($config, 'email_body')) {
            return false; // is not valid
        }

        $emailBody = apply_filters('fluentcrm-parse_campaign_email_text', $config['email_body'], $subscriber);
        $emailSubject = apply_filters('fluentcrm-parse_campaign_email_text', $config['email_subject'], $subscriber);
        $url = site_url('?fluentcrm=1&route=confirmation&s_id=' . $subscriber->id . '&hash=' . $subscriber->hash);
        $emailBody = str_replace('#activate_link#', $url, $emailBody);

        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        $templateData = [
            'preHeader'   => '',
            'email_body'  => $emailBody,
            'footer_text' => ''
        ];

        $emailBody = apply_filters('fluentcrm-email-design-template-' . $config['design_template'], $emailBody, $templateData, false, $subscriber);

        $data = [
            'to'      => [
                'email' => $subscriber->email
            ],
            'subject' => $emailSubject,
            'body'    => $emailBody,
            'from'    => [
                'name'  => Arr::get($emailSettings, 'from_name'),
                'email' => Arr::get($emailSettings, 'from_email')
            ]
        ];
        Mailer::send($data);
        return true;
    }
}
