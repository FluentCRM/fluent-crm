<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;

class CampaignEmail extends Model
{
    protected $table = 'fc_campaign_emails';

    /**
     * One2One: CampaignEmail belongs to one Campaign
     * @return Model
     */
    public function campaign()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Campaign', 'campaign_id', 'id'
        )->withoutGlobalScope('type');
    }

    /**
     * One2One: CampaignEmail belongs to one Subscriber
     * @return Model
     */
    public function subscriber()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Subscriber', 'subscriber_id', 'id'
        );
    }

    /**
     * One2One: CampaignEmail belongs to one Subject
     *
     * Note: The email_subject_id will be inserted by calculating the prioroty
     * from subjects table where the subjects are related to a parent Campaign.
     * So, when creating a campaign email, there will be an option to select a
     * subject from a list and that list will contain subjects related to the
     * parent campaign because a campaign can have many subjects and the campaign
     * email will get only one from that list by calculating the priority from subjects.
     *
     * @return Model
     */
    public function subject()
    {
        return $this->belongsTo(
            __NAMESPACE__ . '\Subject', 'email_subject_id', 'id'
        );
    }

    public function markAs($status)
    {
        $this->status = $status;
        $this->save();
        return $this;
    }

    public function markAsSent($status = 'sent')
    {
        return $this->markAs($status);
    }

    public function markAsFailed($status = 'failed')
    {
        return $this->markAs($status);
    }

    /**
     * Data for the email to be sent
     * @return array
     */
    public function data()
    {
        $email_subject = $this->getEmailSubject();
        $email_body = $this->getEmailBody();
        $headers = Helper::getMailHeader($this->email_headers);
        
        return [
            'to'            => [
                'email' => $this->email_address
            ],
            'headers'          => $headers,
            'subject'       => $email_subject,
            'body'          => $email_body,
            'campaign_id'   => $this->campaign_id,
            'id'            => $this->id,
            'subscriber_id' => $this->subscriber_id
        ];
    }

    public function previewData()
    {
        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        $emailBody = ($this->email_body) ? $this->email_body : $this->campaign->email_body;

        $emailBody = (new BlockParser($this->subscriber))->parse($emailBody);

        $emailBody = apply_filters('fluentcrm-parse_campaign_email_text', $emailBody, $this->subscriber);

        $designTemplate = 'classic';
        if($this->campaign) {
            $designTemplate = $this->campaign->design_template;
        }

        if($this->campaign && $this->campaign->settings['template_config']) {
            $templateConfig = wp_parse_args($this->campaign->settings['template_config'], Helper::getTemplateConfig($this->campaign->design_template));
        } else {
            $templateConfig = Helper::getTemplateConfig();
        }

        $email_body = apply_filters(
            'fluentcrm-email-design-template-' . $designTemplate,
            $this->email_body,
            [
                'preHeader'   => ($this->campaign) ? $this->campaign->email_pre_header : '',
                'email_body'  => $emailBody,
                'footer_text' => '',
                'config'      => $templateConfig
            ],
            $this->campaign,
            $this->subscriber
        );

        return [
            'to'            => [
                'email' => $this->email_address,
                'name'  => $this->subscriber->full_name
            ],
            'from'          => [
                'name'  => Arr::get($emailSettings, 'from_name'),
                'email' => Arr::get($emailSettings, 'from_email')
            ],
            'reply'         => null,
            'subject'       => $this->email_subject,
            'body'          => $email_body,
            'campaign_id'   => $this->campaign_id,
            'id'            => $this->id,
            'subscriber_id' => $this->subscriber_id
        ];
    }

    public function getEmailSubject()
    {
        return $this->email_subject;
    }

    public function getEmailBody()
    {
        $subscriber = $this->subscriber;
        $subscriber->email_id = $this->id;

        if (!$this->is_parsed) {
            $emailBody = $this->getParsedEmailBody();
            $emailBody = apply_filters('fluentcrm-parse_campaign_email_text', $emailBody, $subscriber);
            $emailBody = apply_filters('fluentcrm_email_body_text', $emailBody, $subscriber, $this);
            $campaignUrls = $this->getCampaignUrls($emailBody);
            if ($campaignUrls) {
                $emailBody = Helper::attachUrls($emailBody, $campaignUrls, $this->id);
            }
            $this->email_body = $emailBody;
            $this->is_parsed = 1;
            $this->save();
        }

        $subscriber->campaign_id = $this->campaign_id;
        $footerText = Arr::get(Helper::getGlobalEmailSettings(), 'email_footer', '');
        $footerText = apply_filters('fluentcrm-parse_campaign_email_text', $footerText, $subscriber);

        if($this->campaign && $this->campaign->settings['template_config']) {
            $templateConfig = wp_parse_args($this->campaign->settings['template_config'], Helper::getTemplateConfig($this->campaign->design_template));
        } else {
            $templateConfig = Helper::getTemplateConfig();
        }

        $templateData = [
            'preHeader'   => ($this->campaign) ? $this->campaign->email_pre_header : '',
            'email_body'  => $this->email_body,
            'footer_text' => $footerText,
            'config'      => $templateConfig
        ];

        $designTemplate = 'classic';

        if($this->campaign) {
            $designTemplate = $this->campaign->design_template;
        }

        $content = apply_filters(
            'fluentcrm-email-design-template-' . $designTemplate,
            $this->email_body,
            $templateData,
            $this->campaign,
            $this->subscriber
        );

        return Helper::injectTrackerPixel($content, $this->email_hash);
    }

    private function getParsedEmailBody()
    {
        if (!$this->campaign_id) {
            return (new BlockParser($this->subscriber))->parse($this->email_body);
        }

        static $parsedEmailBody = [];

        if (isset($parsedEmailBody[$this->campaign_id])) {
            return $parsedEmailBody[$this->campaign_id];
        }

        $originalBody = $this->campaign->email_body;

        $emailBody = (new BlockParser($this->subscriber))->parse($originalBody);

        if(strpos($originalBody, 'fc-cond-blocks')) {
            return $emailBody;
        }

        $parsedEmailBody[$this->campaign_id] = $emailBody;;
        return $parsedEmailBody[$this->campaign_id];
    }

    private function getCampaignUrls($emailBody)
    {
        if (!fluentcrmTrackClicking()) {
            return [];
        }

        static $campaignUrls = [];
        if (isset($campaignUrls[$this->campaign_id])) {
            return $campaignUrls[$this->campaign_id];
        }
        $campaignUrls[$this->campaign_id] = Helper::urlReplaces($emailBody);
        return $campaignUrls[$this->campaign_id];
    }

    public function getClicks()
    {
        return wpFluent()->table('fc_campaign_url_metrics')
            ->select(['fc_campaign_url_metrics.counter', 'fc_url_stores.url', 'fc_campaign_url_metrics.id'])
            ->where('type', 'click')
            ->where('fc_campaign_url_metrics.subscriber_id', $this->subscriber_id)
            ->where('fc_campaign_url_metrics.campaign_id', $this->campaign_id)
            ->join('fc_url_stores', 'fc_url_stores.id', '=', 'fc_campaign_url_metrics.url_id')
            ->get();
    }

    public function getSubjectCount($campaignId)
    {
        return static::select(
            'fc_campaign_emails.email_subject_id',
            wpFluent()->raw('count(*) as total'),
            'fc_meta.value',
            'fc_meta.key'
        )
            ->where('fc_campaign_emails.campaign_id', $campaignId)
            ->groupBy('fc_campaign_emails.email_subject_id')
            ->join('fc_meta', 'fc_meta.id', '=', 'fc_campaign_emails.email_subject_id')
            ->get();
    }

    public function getOpenCount($subjectId)
    {
        return static::where('email_subject_id', $subjectId)
            ->where('is_open', '>', 0)
            ->count();
    }

    public function setEmailHeadersAttribute($headers)
    {
        $this->attributes['email_headers'] = \maybe_serialize($headers);
    }

    public function getEmailHeadersAttribute($settings)
    {
        return \maybe_unserialize($settings);
    }
}
