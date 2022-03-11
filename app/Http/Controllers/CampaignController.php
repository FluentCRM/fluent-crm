<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Template;
use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Libs\Mailer\Mailer;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  CampaignController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class CampaignController extends Controller
{
    public function campaigns(Request $request)
    {
        $search = $request->get('searchBy');
        $status = $request->get('statuses');
        $order = $request->get('sort_type') ?: 'DESC';
        $orderBy = $request->get('sort_by') ?: 'id';
        $with = $request->get('with', []);

        $campaigns = Campaign::when($status, function ($query) use ($status) {
            return $query->whereIn('status', $status);
        })->when($search, function ($query) use ($search) {
            return $query->where('title', 'LIKE', "%$search%");
        })
            ->orderBy($orderBy, ($order == 'ASC') ? 'ASC' : 'DESC')
            ->paginate();

        if (in_array('stats', $with)) {
            foreach ($campaigns as $campaign) {
                $campaign->stats = $campaign->stats();
                $campaign->next_step = fluentcrm_get_campaign_meta($campaign->id, '_next_config_step', true);
            }
        }

        return [
            'campaigns' => $campaigns
        ];
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


        do_action('fluentcrm_campaign_status_active', $campaign);

        $templates = Template::emailTemplates()
            ->select(['ID', 'post_title'])
            ->orderBy('ID', 'desc')
            ->get();

        $campaign->server_time = current_time('mysql');

        return $this->sendSuccess(compact('campaign', 'templates'));
    }

    public function campaignEmails(Request $request, $campaignId)
    {
        $filterType = $request->get('filter_type');
        $search = $request->get('search');

        $emailsQuery = CampaignEmail::with(['subscriber'])->where('campaign_id', $campaignId);

        if ($search) {
            $emailsQuery->whereHas('subscriber', function ($q) use ($search) {
                $q->searchBy($search);
            });
        }

        if ($filterType == 'click') {
            $emailsQuery = $emailsQuery->whereNotNull('click_counter')
                ->orderBy('click_counter', 'DESC');
        } else if ($filterType == 'view') {
            $emailsQuery = $emailsQuery->where('is_open', '>', 0)
                ->orderBy('is_open', 'DESC');
        }

        $emails = $emailsQuery->paginate();

        return $this->sendSuccess([
            'emails'        => $emails,
            'failed_counts' => CampaignEmail::where('campaign_id', $campaignId)->where('status', 'failed')->count()
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

        do_action('fluentcrm_campaign_status_active', $campaign);

        $campaign->fill($updateData)->save();

        if (isset($data['update_subjects'])) {
            $campaignSubjects = Arr::get($data, 'subjects', []);
            $campaign->syncSubjects($campaignSubjects);
            $campaign = Campaign::with(['subjects'])->find($id);
        } else {
            $campaign = Campaign::find($id);
        }

        if (isset($data['next_step'])) {
            $nextStep = $data['next_step'];
            if ($nextStep == 1 && $request->get('check_compliance')) {
                $footerDisabled = Arr::get($campaign->settings, 'template_config.disable_footer') == 'yes';
                if ($footerDisabled && !Helper::hasComplianceText($campaign->email_body)) {
                    return $this->sendError([
                        'message' => '##crm.manage_subscription_url## or ##crm.unsubscribe_url## string is required for compliance. Please include unsubscription or manage subscription link'
                    ]);
                }
            }
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
            'message' => __('step saved', 'fluent-crm')
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
                'message' => __('Sorry! No subscribers found based on your selection', 'fluent-crm'),
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

        do_action('fluentcrm_campaign_status_active', $campaign);

        $page = (int)$this->request->get('page', 1);
        $limit = (int)apply_filters('fluentcrm_process_subscribers_per_request', 90);

        $subscribersSettings = [
            'subscribers'         => $request->get('subscribers'),
            'excludedSubscribers' => $request->get('excludedSubscribers'),
            'sending_filter'      => $request->get('sending_filter', 'list_tag'),
            'dynamic_segment'     => $request->get('dynamic_segment'),
            'advanced_filters'    => $request->getJson('advanced_filters', [])
        ];

        $offset = 0;
        $runTime = 40;

        if ($page == 1) {
            $runTime = 15;
            $campaign->campaign_emails()->delete();
            $campaign->settings = wp_parse_args($subscribersSettings, $campaign->settings);
            $campaign->save();
            fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', 0);
        } else {
            $offset = (int)fluentcrm_get_campaign_meta($campaign->id, '_recipient_processed', true);
        }

        $subscribeStatus = $campaign->subscribeBySegment($subscribersSettings, $limit, $offset);
        fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', $campaign->recipients_count);

        $willRun = true;

        while ($willRun && ((microtime(true) - $startTime) < $runTime) && !fluentCrmIsMemoryExceeded()) {
            $campaign = Campaign::find($campaignId);
            $willRun = !!$subscribeStatus['result'];

            if ($willRun) {
                $subscribeStatus = $campaign->subscribeBySegment($subscribersSettings, $limit, $campaign->recipients_count);
                fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', $campaign->recipients_count);
            }
        }

        $hasMore = !!$subscribeStatus['result'];

        if (!$hasMore) {
            $campaign = Campaign::find($campaignId);
            fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', 0);
            if (!$campaign->recipients_count) {
                return $this->sendError([
                    'message' => __('Sorry, No subscribers found based on your filters', 'fluent-crm')
                ]);
            }
            $campaign->maybeDeleteDuplicates();
        }

        if ($subscribeStatus['total_items']) {
            return $this->sendSuccess([
                'has_more'       => $hasMore,
                'count'          => $campaign->recipients_count,
                'total_items'    => $subscribeStatus['total_items'],
                'page_total'     => ceil($subscribeStatus['total_items'] / $limit),
                'next_page'      => $page + 1,
                'execution_time' => microtime(true) - $startTime,
                'memory'         => fluentCrmIsMemoryExceeded(),
                'memory_limit'   => fluentCrmGetMemoryLimit(),
                'memory_usage'   => memory_get_usage(true)
            ]);
        }

        if ($campaign->recipients_count) {
            return [
                'has_more' => false,
                'count'    => $campaign->recipients_count
            ];
        }

        return $this->sendError([
            'message' => __('Sorry, No subscribers found based on your filters', 'fluent-crm')
        ]);
    }

    public function getContactEstimation(Request $request)
    {
        $start_time = microtime(true);

        $filterType = $request->get('sending_filter', 'list_tag');

        $subscribersSettings = [
            'sending_filter' => $filterType
        ];

        if ($filterType == 'list_tag') {
            $subscribersSettings['subscribers'] = $request->get('subscribers', []);
            $subscribersSettings['excludedSubscribers'] = $request->get('excludedSubscribers', []);
        } else if ($filterType == 'dynamic_segment') {
            $subscribersSettings['dynamic_segment'] = $request->get('dynamic_segment', []);
        } else if ($filterType == 'advanced_filters') {
            $subscribersSettings['advanced_filters'] = $request->getJson('advanced_filters', []);
        } else {
            return [
                'count' => 0
            ];
        }

        $count = (new Campaign())->getSubscriberIdsCountBySegmentSettings($subscribersSettings);

        return [
            'count'          => $count,
            'execution_time' => microtime(true) - $start_time
        ];
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
        do_action('fluentcrm_campaign_status_active', $campaign);

        if ($scheduleAt) {
            CampaignEmail::where('campaign_id', $campaignId)
                ->where('status', '!=', 'sent')
                ->update([
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
            CampaignEmail::where('campaign_id', $campaignId)
                ->where('status', '!=', 'sent')
                ->update([
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
                    'time'        => time(),
                    'action'      => 'fluentcrm-post-campaigns-send-now'
                ]
            ]);
        }

        $campaign = Campaign::find($campaignId);

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
            return $this->sendError([
                'message' => $wpError->get_error_message()
            ]);
        }, 10, 1);

        if ($isTest) {
            $campaign = (object)$this->request->get('campaign');

            if (empty($campaign->settings)) {
                $campaign->settings = [
                    'template_config' => []
                ];
            }

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

        $subscriber = Subscriber::where('email', $email)->first();
        if (!$subscriber) {
            $subscriber = Subscriber::where('status', 'subscribed')->first();
        }

        $designTemplate = $campaign->design_template;
        if ($designTemplate == 'raw_html' || $designTemplate == 'raw_classic') {
            $emailBody = $campaign->email_body;
        } else {
            $emailBody = (new BlockParser($subscriber))->parse($emailBody);
        }

        $emailFooter = Arr::get(Helper::getGlobalEmailSettings(), 'email_footer', '');

        $emailSubject = $campaignEmail->email_subject;


        if ($subscriber) {
            $emailBody = apply_filters('fluentcrm_parse_campaign_email_text', $emailBody, $subscriber);
            $emailFooter = apply_filters('fluentcrm_parse_campaign_email_text', $emailFooter, $subscriber);
            $emailSubject = apply_filters('fluentcrm_parse_campaign_email_text', $emailSubject, $subscriber);
        }

        $templateData = [
            'preHeader'   => $campaign->email_pre_header,
            'email_body'  => $emailBody,
            'footer_text' => $emailFooter,
            'config'      => wp_parse_args(Arr::get($campaign->settings, 'template_config', []), Helper::getTemplateConfig($campaign->design_template))
        ];


        $emailBody = apply_filters(
            'fluentcrm_email-design-template-' . $campaign->design_template,
            $emailBody,
            $templateData,
            $campaign,
            false
        );

        $data = [
            'to'      => [
                'email' => $email
            ],
            'subject' => 'TEST: ' . $emailSubject,
            'body'    => $emailBody,
            'headers' => Helper::getMailHeadersFromSettings(Arr::get($campaign->settings, 'mailer_settings', []))
        ];


        Helper::maybeDisableEmojiOnEmail();
        $result = Mailer::send($data);

        return [
            'message' => __('Test email successfully sent to ', 'fluent-crm') . $email . __(', The dynamic tags may not replaced in test email', 'fluent-crm'),
            'result'  => $result
        ];
    }

    public function getEmailPreviewBody()
    {
        $campaign = $this->request->get('campaign');

        if (isset($campaign['post_content'])) {
            $campaign['email_body'] = $campaign['post_content'];
        }

        if (isset($campaign['post_excerpt'])) {
            $campaign['email_pre_header'] = $campaign['post_excerpt'];
        }

        $campaign = (object)$campaign;

        $emailBody = $campaign->email_body;


        $subscriber = fluentcrm_get_current_contact();
        if (!$subscriber) {
            $subscriber = Subscriber::where('status', 'subscribed')->first();
        }

        $designTemplate = $campaign->design_template;

        if ($designTemplate == 'raw_html' || $designTemplate == 'raw_classic') {
            $emailBody = $campaign->email_body;
        } else {
            $emailBody = (new BlockParser($subscriber))->parse($emailBody);
        }

        $emailFooter = Arr::get(Helper::getGlobalEmailSettings(), 'email_footer', '');

        if ($subscriber) {
            $emailBody = apply_filters('fluentcrm_parse_campaign_email_text', $emailBody, $subscriber);
        }

        $templateData = [
            'preHeader'   => $campaign->email_pre_header,
            'email_body'  => $emailBody,
            'footer_text' => $emailFooter,
            'config'      => wp_parse_args(Arr::get($campaign->settings, 'template_config', []), Helper::getTemplateConfig($campaign->design_template))
        ];

        $emailBody = apply_filters(
            'fluentcrm_email-design-template-' . $campaign->design_template,
            $emailBody,
            $templateData,
            $campaign,
            false
        );

        return [
            'preview_html' => $emailBody
        ];
    }

    public function unsubscribe()
    {
        $campaignId = $this->request->get('campaign_id');

        $subscriberIds = (array)$this->request->get('subscriber_ids');

        $campaign = Campaign::findOrFail($campaignId);

        do_action('fluentcrm_campaign_status_active', $campaign);

        $campaign->unsubscribe($subscriberIds);

        return $this->sendSuccess(compact('campaign'));
    }

    public function delete(Request $request, $campaignId)
    {
        $campaign = Campaign::find($campaignId);
        do_action('fluentcrm_campaign_status_working', $campaign);

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

        do_action('fluentcrm_campaign_status_active', $campaign);

        return $this->send([
            'id' => Template::create([
                'post_type'    => fluentcrmCampaignTemplateCPTSlug(),
                'post_content' => Template::emailTemplates()->find($templateId)->post_content
            ])->ID
        ]);
    }

    public function previewEmail(Request $request, $emailId)
    {
        if (!defined('FLUENTCRM_PREVIEWING_EMAIL')) {
            define('FLUENTCRM_PREVIEWING_EMAIL', true);
        }

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

        if ($campaign->status == 'scheduled' && $campaign->scheduled_at) {
            if (strtotime($campaign->scheduled_at) < strtotime(current_time('mysql'))) {
                $campaign->status = 'working';
                $campaign->save();
            }
        }

        if ($campaign->status == 'working') {
            $lastEmailTimestamp = get_option(FLUENTCRM . '_is_sending_emails');
            if (!$lastEmailTimestamp || (time() - $lastEmailTimestamp) > 70) {
                // Looks like it's in stuck so we are resetting this
                update_option(FLUENTCRM . '_is_sending_emails', null);
                wp_remote_post(admin_url('admin-ajax.php'), [
                    'sslverify' => false,
                    'blocking'  => false,
                    'body'      => [
                        'campaign_id' => $campaignId,
                        'retry'       => 1,
                        'time'        => time(),
                        'action'      => 'fluentcrm-post-campaigns-send-now'
                    ]
                ]);
            }
        }

        $analytics = [];
        $subjectsAnalytics = [];
        if ($campaign->status == 'archived') {
            $analytics = $campaignUrlMetric->getCampaignAnalytics($campaignId);
            $subjectsAnalytics = $campaignUrlMetric->getSubjectStats($campaign);
        }

        $sentCount = CampaignEmail::select('id')
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();

        if ($campaign->status == 'working') {
            $processingCount = CampaignEmail::select('id')
                ->where('campaign_id', $campaignId)
                ->where('status', 'processing')
                ->count();

            if ($processingCount) {
                $maximumProcessingTime = apply_filters('fluentcrm_max_email_sending_time', 50);
                CampaignEmail::where('campaign_id', $campaignId)
                    ->where('status', 'processing')
                    ->where('updated_at', '<', date('Y-m-d H:i:s', (time() - $maximumProcessingTime)))
                    ->update([
                        'status' => 'pending'
                    ]);
            } else if ($sentCount) {
                $futureCount = CampaignEmail::select('id')
                    ->where('campaign_id', $campaignId)
                    ->whereIn('status', ['pending', 'scheduled', 'paused', 'processing'])
                    ->count();

                if (!$futureCount) {
                    Campaign::where('id', $campaign->id)->update([
                        'status'     => 'archived',
                        'updated_at' => current_time('mysql')
                    ]);
                    $campaign = Campaign::find($campaignId);
                }
            }
        }

        $stat = CampaignEmail::select('status', fluentCrmDb()->raw('count(*) as total'))
            ->where('campaign_id', $campaignId)
            ->groupBy('status')
            ->get();

        return $this->sendSuccess([
            'current_timestamp' => fluentCrmTimestamp(),
            'stat'              => $stat,
            'campaign'          => $campaign,
            'sent_count'        => $sentCount,
            'analytics'         => $analytics,
            'subject_analytics' => $subjectsAnalytics
        ], 200);
    }

    public function pauseCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status != 'working') {
            return $this->sendError([
                'message' => __('You can only pause a campaign if it is on "Working" state, Please reload this page', 'fluent-crm')
            ]);
        }

        $campaign->status = 'paused';
        $campaign->save();

        CampaignEmail::where('campaign_id', $campaign->id)
            ->whereNotIn('status', ['sent', 'failed', 'bounced'])
            ->update([
                'status' => 'paused'
            ]);

        return [
            'message'  => __('Campaign has been successfully marked as paused', 'fluent-crm'),
            'campaign' => Campaign::find($id)
        ];
    }

    public function resumeCampaign(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status != 'paused') {
            return $this->sendError([
                'message' => __('You can only resume a campaign if it is on "paused" state, Please reload this page', 'fluent-crm')
            ]);
        }

        $campaign->status = 'working';
        $campaign->save();

        CampaignEmail::where('campaign_id', $campaign->id)
            ->where('status', 'paused')
            ->update([
                'status'       => 'scheduled',
                'scheduled_at' => current_time('mysql')
            ]);

        return [
            'message'  => __('Campaign has been successfully resumed', 'fluent-crm'),
            'campaign' => Campaign::find($id)
        ];
    }

    public function updateCampaignTitle(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->title = sanitize_text_field($request->get('title'));
        $campaign->save();

        if ($campaign->status == 'scheduled') {
            $newTime = $request->get('scheduled_at');
            if ($newTime != $campaign->scheduled_at) {
                $campaign->scheduled_at = $newTime;
                $campaign->save();
                CampaignEmail::where('campaign_id', $campaign->id)
                    ->whereNotIn('status', ['sent', 'failed', 'bounced'])
                    ->update([
                        'status'       => 'scheduled',
                        'scheduled_at' => $newTime
                    ]);
            }
        }

        return [
            'message'  => __('Campaign has been updated', 'fluent-crm'),
            'campaign' => Campaign::find($id)
        ];
    }

    public function duplicateCampaign(Request $request, $id)
    {
        $oldCampaign = Campaign::findOrFail($id);
        $newCampaign = [
            'title'            => __('[Duplicate] ', 'fluent-crm') . $oldCampaign->title,
            'slug'             => $oldCampaign->slug . '-' . time(),
            'email_body'       => $oldCampaign->email_body,
            'status'           => 'draft',
            'template_id'      => $oldCampaign->template_id,
            'email_subject'    => $oldCampaign->email_subject,
            'email_pre_header' => $oldCampaign->email_pre_header,
            'utm_status'       => $oldCampaign->utm_status,
            'utm_source'       => $oldCampaign->utm_source,
            'utm_medium'       => $oldCampaign->utm_medium,
            'utm_campaign'     => $oldCampaign->utm_campaign,
            'utm_term'         => $oldCampaign->utm_term,
            'utm_content'      => $oldCampaign->utm_content,
            'design_template'  => $oldCampaign->design_template,
            'created_by'       => get_current_user_id(),
            'settings'         => $oldCampaign->settings
        ];

        $campaign = Campaign::create($newCampaign);

        return [
            'campaign' => $campaign,
            'message'  => __('Campaign has been successfully duplicated', 'fluent-crm')
        ];

    }

    public function unSchedule(Request $request, $id)
    {
        $campaign = Campaign::findOrFail($id);

        if ($campaign->status != 'scheduled') {
            return $this->sendError([
                'message' => __('You can only un-schedule a campaign if it is on "scheduled" state, Please reload this page', 'fluent-crm')
            ]);
        }

        $campaign->status = 'draft';
        $campaign->save();

        CampaignEmail::where('campaign_id', $campaign->id)
            ->where('status', 'scheduled')
            ->update([
                'status' => 'draft'
            ]);

        return [
            'message' => __('Campaign has been successfully un-scheduled', 'fluent-crm')
        ];
    }
}
