<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Template;
use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Mailer\Handler;
use FluentCrm\Includes\Mailer\Mailer;
use FluentCrm\Includes\Request\Request;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;

class CampaignController extends Controller
{
    public function campaigns(Request $request)
    {
        $search = $request->get('searchBy');
        $status = $request->get('statuses');
        $order = $request->get('order') ?: 'desc';
        $orderBy = $request->get('orderBy') ?: 'id';
        $with = $request->get('with', []);

        $campaigns = Campaign::when($status, function ($query) use ($status) {
            $query->whereIn('status', $status);
        })->when($search, function ($query) use ($search) {
            $query->where('title', 'LIKE', "%$search%");
        })->orderBy($orderBy, ($order == 'ascending' ? 'asc' : 'desc'))->paginate();

        if (in_array('stats', $with)) {
            foreach ($campaigns as $campaign) {
                $campaign->stats = $campaign->stats();
                $campaign->next_step = fluentcrm_get_campaign_meta($campaign->id, '_next_config_step', true);
            }
        }

        return $this->sendSuccess(compact('campaigns'));
    }

    public function create(Request $request)
    {
        $data = $this->validate($request->only('title'), [
            'title' => 'required|unique:fc_campaigns',
        ]);

        return $this->sendSuccess(
            Campaign::create($data)->load([
                'template', 'subjects'
            ])
        );
    }

    public function campaign(Request $request, $id)
    {
        if ($request->exists('viewCampaign')) {
            $campaign = Campaign::findOrFail($id);
            $emails = $campaign->emails()->with('subscriber')->paginate();
            return $this->sendSuccess(['campaign' => $campaign, 'emails' => $emails]);
        }

        if ($with = $request->get('with', [])) {
            $campaign = Campaign::with($with)->find($id);
        } else {
            $campaign = Campaign::find($id);
        }

        $this->app->doCustomAction('campaign_status_active', $campaign);

        $templates = Template::emailTemplates()
            ->select(['ID', 'post_title'])
            ->orderBy([
                'post_status', 'ID'
            ], 'desc')
            ->get();


        $campaign->server_time = current_time('mysql');

        return $this->sendSuccess(compact('campaign', 'templates'));
    }

    public function campaignEmails(Request $request, $campaignId)
    {
        $filterType = $request->get('filter_type');

        $emailsQuery = CampaignEmail::with(['subscriber' => function ($query) {
            $query->with('tags', 'lists');
        }])->where('campaign_id', $campaignId);

        if ($filterType == 'click') {
            $emailsQuery = $emailsQuery->whereNotNull('click_counter')
                ->orderBy('click_counter', 'DESC');
        } else if ($filterType == 'view') {
            $emailsQuery = $emailsQuery->where('is_open', '>', 0)
                ->orderBy('is_open', 'DESC');
        }

        $emails = $emailsQuery->paginate();

        return $this->sendSuccess([
            'emails' => $emails
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $this->validate($this->request->except(['action']), [
            "title" => "required|unique:fc_campaigns,title,{$id},id",
        ]);

        $updateData = Arr::only($data, [
            'title',
            'slug',
            'template_id',
            'email_subject',
            'email_pre_header',
            'email_body',
            'utm_status',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'scheduled_at',
            'design_template'
        ]);

        if (!empty($data['settings'])) {
            $updateData['settings'] = $data['settings'];
        }

        $updateData['utm_status'] = intval($updateData['utm_status']);

        $campaign = Campaign::find($id);

        $this->app->doCustomAction('campaign_status_active', $campaign);

        $campaign->fill($updateData)->save();

        if (isset($data['update_subjects'])) {
            $campaignSubjects = Arr::get($data, 'subjects', []);
            $campaign->syncSubjects($campaignSubjects);
            $campaign = Campaign::with(['subjects'])->find($id);
        } else {
            $campaign = Campaign::find($id);
        }

        if (isset($data['next_step'])) {
            fluentcrm_update_campaign_meta($id, '_next_config_step', $data['next_step']);
        }

        return $this->sendSuccess([
            'campaign' => $campaign
        ]);
    }

    public function updateStep(Request $request, $id)
    {
        $step = $request->get('next_step');
        fluentcrm_update_campaign_meta($id, '_next_config_step', $step);
        return [
            'message' => 'step saved'
        ];
    }

    public function validateRecipientsSelection(Request $request)
    {
        $items = $request->get('items');
        $campaignId = $request->get('campaign_id');
        $campaign = Campaign::find($campaignId);
        $subscribersIds = $campaign->getSubscribeIdsByList($items);

        if (!$subscribersIds) {
            return $this->sendError([
                'message' => __('Sorry! No subscribers found based on your selection'),
                'count'   => 0
            ]);
        }

        $settings = $campaign->settings;
        $settings['subscribers'] = $items;
        $campaign->settings = $settings;
        $campaign->save();

        return $this->sendSuccess([
            'count' => count($subscribersIds)
        ]);
    }

    public function subscribe(Request $request, $campaignId)
    {
        $startTime = microtime(true);
        $campaign = Campaign::find($campaignId);

        $this->app->doCustomAction('campaign_status_active', $campaign);

        $page = intval($this->request->get('page', 1));
        $limit = intval(apply_filters('fluentcrm_process_subscribers_per_request', 300));
        $subscribersSettings = [
            'subscribers'         => $request->get('subscribers'),
            'excludedSubscribers' => $request->get('excludedSubscribers'),
            'sending_filter'      => $request->get('sending_filter', 'list_tag'),
            'dynamic_segment'     => $request->get('dynamic_segment')
        ];

        if ($page == 1) {
            $campaign->campaign_emails()->delete();
            $campaign->settings = array_merge(
                $campaign->settings, $subscribersSettings
            );
            $campaign->save();
        }

        $offset = ($page - 1) * $limit;

        $subscribeStatus = $campaign->subscribeBySegment($subscribersSettings, $limit, $offset);

        $hasMore = !!$subscribeStatus['result'];

        if (!$hasMore) {
            $campaign = Campaign::find($campaignId);
            if (!$campaign->recipients_count) {
                return $this->sendError([
                    'message' => 'Sorry, No subscribers found based on your filters'
                ]);
            }
        }

        if ($subscribeStatus['total_items']) {
            return $this->sendSuccess([
                'has_more'    => $hasMore,
                'count'       => $campaign->recipients_count,
                'total_items' => $subscribeStatus['total_items'],
                'page_total'  => ceil($subscribeStatus['total_items'] / $limit),
                'next_page'   => $page + 1,
                'execution_time' => microtime(true) - $startTime
            ]);
        }

        if($campaign->recipients_count) {
            return [
                'has_more' => false,
                'count' => $campaign->recipients_count
            ];
        }

        return $this->sendError([
            'message' => 'Sorry, No subscribers found based on your filters'
        ]);
    }

    public function getContactEstimation(Request $request)
    {
        $subscribersSettings = [
            'subscribers'         => $request->get('subscribers'),
            'excludedSubscribers' => $request->get('excludedSubscribers'),
            'sending_filter'      => $request->get('sending_filter', 'list_tag'),
            'dynamic_segment'     => $request->get('dynamic_segment')
        ];

        $subscribers = (new Campaign())->getSubscriberIdsBySegmentSettings($subscribersSettings);
        return $subscribers['total_count'];
    }

    public function deleteCampaignEmails(Request $request, $campaignId)
    {
        $selectionIds = array_filter($request->get('email_ids'), 'intval');

        if ($selectionIds) {
            CampaignEmail::where('campaign_id', $campaignId)
                ->whereIn('id', $selectionIds)
                ->delete();
        }

        $newCount = CampaignEmail::where('campaign_id', $campaignId)
            ->count();

        Campaign::where('id', $campaignId)->update([
            'recipients_count' => $newCount
        ]);

        return $this->sendSuccess([
            'message'          => __('Selected emails are deleted', 'fluent-crm'),
            'recipients_count' => $newCount
        ]);
    }

    public function schedule(Request $request, $campaignId)
    {
        $scheduleAt = $request->get('scheduled_at');

        $campaign = Campaign::find($campaignId);
        $this->app->doCustomAction('campaign_status_active', $campaign);

        if ($scheduleAt) {
            CampaignEmail::where('campaign_id', $campaignId)->update([
                'status'       => 'scheduled',
                'updated_at'   => fluentCrmTimestamp(),
                'scheduled_at' => $scheduleAt
            ]);

            $message = __('Your campaign email has been scheduled', 'fluent-crm');

            $data = [
                'status'       => 'scheduled',
                'updated_at'   => fluentCrmTimestamp(),
                'scheduled_at' => $scheduleAt
            ];
        } else {
            CampaignEmail::where('campaign_id', $campaignId)->update([
                'status'       => 'pending',
                'updated_at'   => fluentCrmTimestamp(),
                'scheduled_at' => fluentCrmTimestamp()
            ]);

            $message = __('Email Sending has been started', 'fluent-crm');

            $data = [
                'status'       => 'working',
                'updated_at'   => fluentCrmTimestamp(),
                'scheduled_at' => fluentCrmTimestamp()
            ];
        }

        Campaign::where('id', $campaignId)->update($data);

        if (!$scheduleAt) {
            wp_remote_post(admin_url('admin-ajax.php'), [
                'sslverify' => false,
                'blocking'  => false,
                'body'      => [
                    'campaign_id' => $campaignId,
                    'action'      => 'fluentcrm-post-campaigns-send-now'
                ]
            ]);
        }

        return $this->sendSuccess([
            'campaign'          => $campaign,
            'message'           => $message,
            'current_timestamp' => fluentCrmTimestamp()
        ]);
    }

    public function sendTestEmail()
    {
        $isTest = $this->request->get('test_campaign') == 'yes';

        add_action('wp_mail_failed', function ($wpError) {
            $this->sendError([
                'message' => $wpError->get_error_message()
            ]);
        }, 10, 1);

        if ($isTest) {
            $campaign = (object)$this->request->get('campaign');
            $campaignEmail = (object)[
                'email_subject'    => $campaign->email_subject,
                'email_pre_header' => $campaign->email_pre_header,
                'email_body'       => $campaign->email_body
            ];
        } else {
            $campaignId = $this->request->get('campaign_id');
            $campaignEmail = CampaignEmail::where('campaign_id', $campaignId)->first();
            $campaign = Campaign::find($campaignId);
        }

        $email = $this->request->get('email');

        if (!$email) {
            $user = get_user_by('ID', get_current_user_id());
            $email = $user->user_email;
        }

        $emailBody = $campaignEmail->email_body;

        $emailBody = (new BlockParser())->parse($emailBody);

        $subscriber = Subscriber::where('email', $email)->first();
        if (!$subscriber) {
            $subscriber = Subscriber::where('status', 'subscribed')->first();
        }

        if ($subscriber) {
            $emailBody = apply_filters('fluentcrm-parse_campaign_email_text', $emailBody, $subscriber);
        }

        $templateData = [
            'preHeader'   => $campaign->email_pre_header,
            'email_body'  => $emailBody,
            'footer_text' => Arr::get(Helper::getGlobalEmailSettings(), 'email_footer', ''),
            'config'      => wp_parse_args($campaign->settings['template_config'], Helper::getTemplateConfig($campaign->design_template))
        ];

        $emailBody = $this->app->applyCustomFilters(
            'email-design-template-' . $campaign->design_template,
            $emailBody,
            $templateData,
            $campaign,
            false
        );

        $data = [
            'to'      => [
                'email' => $email
            ],
            'subject' => 'TEST: ' . $campaignEmail->email_subject,
            'body'    => $emailBody,
            'headers' => Helper::getMailHeadersFromSettings(Arr::get($campaign->settings, 'mailer_settings', []))
        ];

        add_action('wp_mail_failed', function ($error) {
            return $this->sendError([
                'message' => $error->get_error_message()
            ], 423);
        });

        $result = Mailer::send($data);

        return [
            'message' => 'Test email successfully sent to ' . $email . ', The dynamic tags may not replaced in test email',
            'result'  => $result
        ];
    }

    public function unsubscribe()
    {
        $campaignId = $this->request->get('campaign_id');

        $subscriberIds = (array)$this->request->get('subscriber_ids');

        $campaign = Campaign::findOrFail($campaignId);

        $this->app->doCustomAction('campaign_status_active', $campaign);

        $campaign->unsubscribe($subscriberIds);

        return $this->sendSuccess(compact('campaign'));
    }

    public function delete(Request $request, $campaignId)
    {
        $campaign = Campaign::find($campaignId);
        $this->app->doCustomAction('campaign_status_working', $campaign);

        $campaign->delete();
        CampaignEmail::where('campaign_id', $campaignId)->delete();
        CampaignUrlMetric::where('campaign_id', $campaignId)->delete();
        do_action('fluentcrm_campaign_deleted', $campaignId);

        return $this->send(['success' => true]);
    }

    public function createTemplate()
    {
        $templateId = $this->request->get('template_id');
        $campaignId = $this->request->get('campaign_id');
        $campaign = Campaign::find($campaignId);

        $this->app->doCustomAction('campaign_status_active', $campaign);

        return $this->send([
            'id' => Template::create([
                'post_type'    => fluentcrmCampaignTemplateCPTSlug(),
                'post_content' => Template::emailTemplates()->find($templateId)->post_content
            ])->ID
        ]);
    }

    public function previewEmail(Request $request, $emailId)
    {
        $email = CampaignEmail::findOrFail($emailId);

        $emailData = $email->previewData();
        $emailData['clicks'] = $email->getClicks();

        return $this->sendSuccess([
            'info'  => $email,
            'email' => $emailData
        ]);
    }

    public function getCampaignStatus(Request $request, CampaignUrlMetric $campaignUrlMetric, $campaignId)
    {
        $requestCounter = $request->get('request_counter');
        $campaign = Campaign::find($campaignId);

        $stat = CampaignEmail::select('status', wpFluent()->raw('count(*) as total'))
            ->where('campaign_id', $campaignId)
            ->groupBy('status')
            ->get();

        $sentCount = CampaignEmail::select('id')
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();

        if ($campaign->status == 'scheduled' && $campaign->scheduled_at) {
            if (strtotime($campaign->scheduled_at) < strtotime(fluentCrmTimestamp())) {
                $campaign->status = 'working';
                $campaign->save();
            }
        }


        if ($campaign->status == 'working') {
            $lastEmailTimestamp = get_option(FLUENTCRM . '_is_sending_emails');
            if (!$lastEmailTimestamp || (time() - $lastEmailTimestamp) > 120) {
                // Looks like it's in stuck so we are resetting this
                update_option(FLUENTCRM . '_is_sending_emails', null);
                if ($requestCounter % 3 == 0) {
                    (new Handler())->handle($campaignId);
                } else {
                    wp_remote_post(admin_url('admin-ajax.php'), [
                        'sslverify' => false,
                        'blocking'  => false,
                        'body'      => [
                            'campaign_id' => $campaignId,
                            'retry'       => 1,
                            'action'      => 'fluentcrm-post-campaigns-send-now'
                        ]
                    ]);
                }
            }

            $onPrecessingCount = CampaignEmail::select('id')
                ->where('campaign_id', $campaignId)
                ->whereIn('status', ['pending', 'scheduled'])
                ->count();

            if (!$onPrecessingCount) {
                (new Handler())->handle($campaignId);
            }

        }

        $analytics = [];
        $subjectsAnalytics = [];
        if ($campaign->status == 'archived') {
            $analytics = $campaignUrlMetric->getCampaignAnalytics($campaignId);
            $subjectsAnalytics = $campaignUrlMetric->getSubjectStats($campaign);
        }

        return $this->sendSuccess([
            'current_timestamp' => fluentCrmTimestamp(),
            'stat'              => $stat,
            'campaign'          => $campaign,
            'sent_count'        => $sentCount,
            'analytics'         => $analytics,
            'subject_analytics' => $subjectsAnalytics
        ], 200);
    }
}
