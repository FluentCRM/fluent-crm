<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Template;
use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\BlockParserHelper;
use FluentCrm\App\Services\CampaignProcessor;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Libs\Mailer\Handler;
use FluentCrm\App\Services\Libs\Mailer\Mailer;
use FluentCrm\App\Services\Sanitize;
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
        $search = sanitize_text_field($request->get('searchBy'));
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

        $data['title'] = sanitize_text_field($data['title']);

        $campaign = Campaign::create($data)->load([
            'template', 'subjects'
        ]);

        do_action('fluent_crm/campaign_created', $campaign);

        return $this->sendSuccess(
            $campaign
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
            $campaign = Campaign::findOrFail($id);
        }

        $campaign = apply_filters('fluent_crm/campaign_data', $campaign);

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
        $search = $request->getSafe('search');

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

    public function updateSingleCampaignSimulate(Request $request)
    {
        $id = $request->get('campaign_id');
        return $this->update($request, $id);
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

        $updateData = Sanitize::campaign($updateData);

        $campaign = Campaign::findOrFail($id);

        $campaign->fill($updateData)->save();

        if (isset($data['update_subjects'])) {
            $campaignSubjects = Arr::get($data, 'subjects', []);
            $campaign->syncSubjects($campaignSubjects);
            $campaign = Campaign::with(['subjects'])->find($id);
        } else {
            $campaign = Campaign::findOrFail($id);
        }

        $nextStep = Arr::get($data, 'next_step');

        if ($nextStep) {

            if ($nextStep == 1) {
                do_action('fluent_crm/update_campaign_compose', $data, $campaign);
            } else if ($nextStep == 2) {
                $footerDisabled = Arr::get($campaign->settings, 'template_config.disable_footer') == 'yes';
                if (($footerDisabled || $campaign->design_template === 'visual_builder') && !Helper::hasComplianceText($campaign->email_body)) {
                    return $this->sendError([
                        'compliance_failed' => true,
                        'message'           => '##crm.manage_subscription_url## or ##crm.unsubscribe_url## or {{crm_global_email_footer}} string is required for compliance. Please include unsubscription or manage subscription link. <br />Please go to the previous screen and add the unsubscribe link'
                    ]);
                }
                do_action('fluent_crm/update_campaign_subjects', $data, $campaign);
            }

            fluentcrm_update_campaign_meta($id, '_next_config_step', $nextStep);
        }

        do_action('fluent_crm/campaign_data_updated', $campaign, $data);

        return $this->sendSuccess([
            'campaign' => $campaign
        ]);
    }

    public function updateStep(Request $request, $id)
    {
        $step = $request->getSafe('next_step');
        fluentcrm_update_campaign_meta($id, '_next_config_step', $step);
        return [
            'message' => __('step saved', 'fluent-crm')
        ];
    }

    public function validateRecipientsSelection(Request $request)
    {
        $items = $request->get('items');
        $campaignId = $request->get('campaign_id');
        $campaign = Campaign::findOrFail($campaignId);
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

    public function draftRecipients(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);

        $subscribersSettings = [
            'subscribers'         => $request->get('subscribers'),
            'excludedSubscribers' => $request->get('excludedSubscribers'),
            'sending_filter'      => $request->get('sending_filter', 'list_tag'),
            'dynamic_segment'     => $request->get('dynamic_segment'),
            'advanced_filters'    => $request->getJson('advanced_filters', [])
        ];

        $count = (new Campaign())->getSubscriberIdsCountBySegmentSettings($subscribersSettings);

        if (!$count) {
            return $this->sendError([
                'message' => 'Sorry no subscribers found based on your selection'
            ]);
        }

        $campaign->campaign_emails()->delete();
        $campaign->status = 'draft';
        $campaign->recipients_count = $count;
        $campaign->settings = wp_parse_args($subscribersSettings, $campaign->settings);
        $campaign->save();
        fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', 0);
        fluentcrm_update_campaign_meta($campaign->id, '_last_recipient_id', 0);

        do_action('fluent_crm/campaign_recipients_query_updated', $campaign);

        return [
            'message' => __('Recipient settings has been updated', 'fluent-crm'),
            'count'   => $count
        ];
    }

    public function recipientsCount(Request $request, $campaignId)
    {
        $campaign = Campaign::withoutGlobalScope('type')->findOrFail($campaignId);
        $preProcessedStatuses = [
            'draft',
            'processing',
            'pending-scheduled'
        ];

        if (in_array($campaign->status, $preProcessedStatuses)) {
            $count = $campaign->getSubscribersModel()->count();
        } else {
            $count = $campaign->recipients_count;
        }

        return [
            'estimated_count' => $count
        ];
    }

    public function subscribe(Request $request, $campaignId)
    {
        $startTime = microtime(true);
        $campaign = Campaign::findOrFail($campaignId);

        do_action('fluentcrm_campaign_status_active', $campaign);

        $page = (int)$this->request->get('page', 1);
        $limit = (int)apply_filters('fluent_crm/process_subscribers_per_request', 90);

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
            $campaign = Campaign::findOrFail($campaignId);
            $willRun = !!$subscribeStatus['result'];

            if ($willRun) {
                $subscribeStatus = $campaign->subscribeBySegment($subscribersSettings, $limit, $campaign->recipients_count);
                fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', $campaign->recipients_count);
            }
        }

        $hasMore = !!$subscribeStatus['result'];

        if (!$hasMore) {
            $campaign = Campaign::findOrFail($campaignId);
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
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status != 'draft') {
            return $this->sendError([
                'message' => __('Campaign status is not in draft status. Please reload the page', 'fluent-crm')
            ], 423);
        }

        do_action('fluentcrm_campaign_status_active', $campaign);

        // Remove Emails if there has any pre-processed
        CampaignEmail::where('campaign_id', $campaignId)->delete();
        fluentcrm_update_campaign_meta($campaign->id, '_recipient_processed', 0);
        fluentcrm_update_campaign_meta($campaign->id, '_last_recipient_id', 0);

        if ($scheduleAt) {

            $sendingType = $request->get('sending_type', 'schedule');

            if ($sendingType == 'range_schedule') {
                $isInvalid = true;
                if (is_array($scheduleAt) && count($scheduleAt) == 2) {
                    $scheduleStartAt = sanitize_text_field($scheduleAt[0]);

                    if($scheduleStartAt && strtotime($scheduleStartAt) < current_time('timestamp')) {
                        $scheduleStartAt = current_time('mysql');
                    }

                    $scheduleEndAt = sanitize_text_field($scheduleAt[1]);

                    if ($scheduleEndAt && $scheduleEndAt && strtotime($scheduleStartAt) < strtotime($scheduleEndAt)) {
                        $isInvalid = false;
                    }

                    $scheduleAt = [$scheduleStartAt, $scheduleEndAt];
                }

                if ($isInvalid) {
                    return $this->sendError([
                        'message' => 'Invalid schedule date range'
                    ], 423);
                }

                $settings = $campaign->settings;
                $settings['sending_type'] = 'range_schedule';
                $settings['schedule_range'] = [strtotime($scheduleAt[0]), strtotime($scheduleAt[1])];

                $data = [
                    'status'           => 'pending-scheduled',
                    'updated_at'       => fluentCrmTimestamp(),
                    'scheduled_at'     => $scheduleAt[0],
                    'recipients_count' => 0,
                    'settings'         => maybe_serialize($settings)
                ];

            } else {
                $scheduleAt = sanitize_text_field($scheduleAt);
                if (!$scheduleAt) {
                    return $this->sendError([
                        'message' => __('Invalid schedule date', 'fluent-crm')
                    ], 423);
                }

                $settings = $campaign->settings;
                $settings['sending_type'] = 'schedule';

                $data = [
                    'status'           => 'pending-scheduled',
                    'updated_at'       => fluentCrmTimestamp(),
                    'scheduled_at'     => $scheduleAt,
                    'recipients_count' => 0,
                    'settings'         =>  maybe_serialize($settings)
                ];
            }

            $message = __('Your campaign email has been scheduled', 'fluent-crm');
            Campaign::where('id', $campaignId)->update($data);

        } else {
            $message = __('Email Sending will be started soon', 'fluent-crm');

            $settings = $campaign->settings;
            $settings['sending_type'] = 'instant';

            $data = [
                'status'           => 'processing',
                'updated_at'       => fluentCrmTimestamp(),
                'scheduled_at'     => fluentCrmTimestamp(),
                'recipients_count' => 0,
                'settings'         =>  maybe_serialize($settings)
            ];

            Campaign::where('id', $campaignId)->update($data);
        }

        if (!$scheduleAt) {
            wp_remote_post(admin_url('admin-ajax.php'), [
                'sslverify' => false,
                'blocking'  => false,
                'body'      => [
                    'campaign_id' => $campaignId,
                    'time'        => time(),
                    'action'      => 'fluentcrm-post-campaigns-emails-processing'
                ]
            ]);
        }

        $campaign = Campaign::findOrFail($campaignId);

        if ($scheduleAt) {
            do_action('fluent_crm/campaign_scheduled', $campaign, $campaign->scheduled_at);
        } else {
            do_action('fluent_crm/campaign_set_send_now', $campaign);
        }

        return $this->sendSuccess([
            'campaign'          => $campaign,
            'message'           => $message,
            'current_timestamp' => fluentCrmTimestamp()
        ]);
    }

    public function processingStat(Request $request, $campaignId)
    {
        $campaign = Campaign::withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->findOrFail($campaignId);

        if ($campaign->status == 'pending-scheduled' && (strtotime($campaign->scheduled_at) - current_time('timestamp')) < 360) {
            $campaign->status = 'processing';
            $campaign->recipients_count = 0;
            $campaign->save();
            do_action('fluent_crm/campaign_processing_start', $campaign);
        }

        if ($campaign->status != 'processing') {

            if ($campaign->status == 'scheduled' && current_time('timestamp') - strtotime($campaign->scheduled_at) > 300) {
                if ((new Handler())->finishProcessing()) {
                    $campaign = Campaign::withoutGlobalScope('type')
                        ->findOrFail($campaignId);
                }
            }

            return [
                'reload'   => true,
                'campaign' => $campaign
            ];
        }

        // This is the processing status

        $processor = (new CampaignProcessor($campaign->id));
        $processedCampaign = $processor->processEmails(1, 1);

        $didRun = false;
        if ($processedCampaign) {
            $campaign = $processedCampaign;
            $didRun = true;
        }

        $campaign->scheduling_range = $campaign->rangedScheduleDates();

        return [
            'campaign'          => $campaign,
            'didRun'            => $didRun,
            'scheduling_method' => $processor->getSchedulingMethod()
        ];
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
            $campaign = Campaign::findOrFail($campaignId);
            if (!$campaignEmail) {
                $campaignEmail = (object)[
                    'email_subject'    => $campaign->email_subject,
                    'email_pre_header' => $campaign->email_pre_header,
                    'email_body'       => $campaign->email_body
                ];
            }
        }

        $email = $this->request->getSafe('email', '', 'sanitize_email');

        if (!$email) {
            $user = get_user_by('ID', get_current_user_id());
            $email = $user->user_email;
        }

        $emailBody = $campaignEmail->email_body;

        $subscriber = Subscriber::where('email', $email)->first();
        if (!$subscriber) {
            $subscriber = Subscriber::where('status', 'subscribed')->first();
        }

        if (!$subscriber) {
            return $this->sendError([
                'message' => __('No subscriber found to send test. Please add atleast one contact as subscribed status', 'fluent-crm')
            ]);
        }

        $designTemplate = $campaign->design_template;

        $rawTemplates = [
            'raw_html',
            'visual_builder'
        ];

        if (in_array($designTemplate, $rawTemplates)) {
            $emailBody = $campaign->email_body;
        } else {
            $emailBody = (new BlockParser($subscriber))->parse($emailBody);
        }

        $emailFooter = Helper::getEmailFooterContent($campaign);

        $emailSubject = $campaignEmail->email_subject;

        $preHeader = (!empty($campaign->email_pre_header)) ? $campaign->email_pre_header : '';

        if ($subscriber) {
            $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
            $emailFooter = apply_filters('fluent_crm/parse_campaign_email_text', $emailFooter, $subscriber);
            $emailSubject = apply_filters('fluent_crm/parse_campaign_email_text', $emailSubject, $subscriber);
            $preHeader = apply_filters('fluent_crm/parse_campaign_email_text', $preHeader, $subscriber);
        }

        $templateData = [
            'preHeader'   => $preHeader,
            'email_body'  => $emailBody,
            'footer_text' => $emailFooter,
            'config'      => wp_parse_args(Arr::get($campaign->settings, 'template_config', []), Helper::getTemplateConfig($campaign->design_template))
        ];

        $emailBody = apply_filters(
            'fluent_crm/email-design-template-' . $campaign->design_template,
            $emailBody,
            $templateData,
            $campaign,
            $subscriber
        );


        $emailBody = str_replace('{{crm_global_email_footer}}', $emailFooter, $emailBody);
        $emailBody = str_replace('{{crm_preheader_text}}', $preHeader, $emailBody);

        $data = [
            'to'      => [
                'email' => $email,
                'name'  => $subscriber->full_name
            ],
            'subject' => 'TEST: ' . $emailSubject,
            'body'    => $emailBody,
            'headers' => Helper::getMailHeadersFromSettings(Arr::get($campaign->settings, 'mailer_settings', []))
        ];

        Helper::maybeDisableEmojiOnEmail();
        $result = Mailer::send($data, $subscriber);

        return [
            'message' => __('Test email successfully sent to ', 'fluent-crm') . $email . __(', The dynamic tags may not replaced in test email', 'fluent-crm'),
            'result'  => $result
        ];
    }

    public function getEmailPreviewBody()
    {

        $campaignId = $this->request->get('campaign_id');

        if ($campaignId) {
            $campaignId = (int)$campaignId;
            $campaign = Campaign::withoutGlobalScope('type')->findOrfail($campaignId);
        } else {
            $campaign = $this->request->get('campaign');
            if (isset($campaign['post_content'])) {
                $campaign['email_body'] = $campaign['post_content'];
            }

            if (isset($campaign['post_excerpt'])) {
                $campaign['email_pre_header'] = sanitize_text_field($campaign['post_excerpt']);
            }

            $campaign = (object)$campaign;
        }

        $emailBody = $campaign->email_body;


        if ($this->request->get('contact_id')) {
            $subscriber = Subscriber::find($this->request->getSafe('contact_id'));
        } else {
            $subscriber = fluentcrm_get_current_contact();
        }

        if (!$subscriber) {
            $subscriber = Subscriber::where('status', 'subscribed')->first();
        }

        if ($this->request->get('disable_subscriber') == 'yes') {
            $subscriber = null;
        }

        $designTemplate = $campaign->design_template;

        $rawTemplates = [
            'raw_html',
            'visual_builder'
        ];

        if (in_array($designTemplate, $rawTemplates)) {
            $emailBody = wp_unslash($campaign->email_body);
        } else {
            $emailBody = (new BlockParser($subscriber))->parse($emailBody);
        }

        $emailFooter = Helper::getEmailFooterContent($campaign);

        if ($subscriber) {
            $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
            $emailFooter = apply_filters('fluent_crm/parse_campaign_email_text', $emailFooter, $subscriber);
        }

        if (empty($campaign->settings)) {
            $campaign->settings = [];
        }

        $preHeader = (!empty($campaign->email_pre_header)) ? $campaign->email_pre_header : '';

        if ($preHeader && $subscriber) {
            $preHeader = apply_filters('fluent_crm/parse_campaign_email_text', $preHeader, $subscriber);
        }

        $templateData = [
            'preHeader'   => $preHeader,
            'email_body'  => $emailBody,
            'footer_text' => $emailFooter,
            'config'      => wp_parse_args(Arr::get($campaign->settings, 'template_config', []), Helper::getTemplateConfig($campaign->design_template))
        ];

        $emailBody = apply_filters(
            'fluent_crm/email-design-template-' . $designTemplate,
            $emailBody,
            $templateData,
            $campaign,
            $subscriber
        );

        if (strpos($emailBody, '{{crm') || strpos($emailBody, '##crm')) {
            $emailBody = str_replace(['{{crm_global_email_footer}}', '{{crm_preheader_text}}'], [$emailFooter, $preHeader], $emailBody);
            if (strpos($emailBody, '##crm.') || strpos($emailBody, '{{crm.')) {
                // we have CRM specific smartcodes
                $emailBody = apply_filters('fluent_crm/parse_extended_crm_text', $emailBody, $subscriber);
            }
        }

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
        $campaign = Campaign::findOrFail($campaignId);

        $campaign->deleteCampaignData();
        $campaign->delete();
        do_action('fluent_crm/campaign_deleted', $campaignId);

        return $this->send(['success' => true]);
    }

    public function handleBulkAction(Request $request)
    {
        $campaignIds = $request->get('campaign_ids', []);
        $campaignIds = array_map(function ($id) {
            return (int)$id;
        }, $campaignIds);

        $campaignIds = array_unique(array_filter($campaignIds));

        if (!$campaignIds) {
            return $this->sendError([
                'message' => __('Please provide campaign IDs', 'fluent-crm')
            ]);
        }

        $campaigns = Campaign::whereIn('id', $campaignIds)->get();
        foreach ($campaigns as $campaign) {
            $campaignId = $campaign->id;
            $campaign->deleteCampaignData();
            $campaign->delete();
            do_action('fluent_crm/campaign_deleted', $campaignId);
        }

        return $this->sendSuccess([
            'message' => __('Selected Campaigns has been deleted permanently', 'fluent-crm'),
        ]);

    }

    public function createTemplate()
    {
        $templateId = $this->request->get('template_id');
        $campaignId = $this->request->get('campaign_id');
        $campaign = Campaign::findOrFail($campaignId);

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
        $campaign = Campaign::withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->findOrFail($campaignId);

        if ($campaign->status == 'processing' || $campaign->status == 'pending-scheduled') {
            return [
                'current_timestamp' => fluentCrmTimestamp(),
                'stat'              => [],
                'campaign'          => $campaign,
                'sent_count'        => 0,
                'analytics'         => [],
                'subject_analytics' => []
            ];
        }

        if ($campaign->status == 'scheduled' && $campaign->scheduled_at) {
            if (strtotime($campaign->scheduled_at) < strtotime(current_time('mysql'))) {
                $campaign->status = 'working';
                $campaign->save();
            }
        }

        $ranged = null;

        if ($campaign->status == 'working') {

            $ranged = $campaign->rangedScheduleDates();

            if(!$ranged) {
                if (($requestCounter % 4) === 0) {
                    CampaignEmail::where('status', 'processing')
                        ->where('updated_at', '<', date('Y-m-d H:i:s', (current_time('timestamp') - 70)))
                        ->update([
                            'status' => 'pending'
                        ]);
                }

                $lastEmailTimestamp = get_option(FLUENTCRM . '_is_sending_emails');
                if (!$lastEmailTimestamp || (time() - $lastEmailTimestamp) > 63) {
                    // Looks like it's in stuck so we are resetting this
                    update_option(FLUENTCRM . '_is_sending_emails', null);
                    wp_remote_post(admin_url('admin-ajax.php'), [
                        'sslverify' => false,
                        'blocking'  => false,
                        'cookies'   => array(),
                        'body'      => [
                            'campaign_id' => $campaignId,
                            'retry'       => 1,
                            'time'        => time(),
                            'action'      => 'fluentcrm-post-campaigns-send-now'
                        ]
                    ]);
                }
            }

        }

        $analytics = [];
        $subjectsAnalytics = [];

        $sentCount = CampaignEmail::select('id')
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();

        if ($campaign->status == 'archived') {
            $analytics = $campaignUrlMetric->getCampaignAnalytics($campaignId, $sentCount);

            if (isset($analytics['open']) && $analytics['open']['total'] > $sentCount) {
                $analytics['open']['total'] = $sentCount;
            }

            if (isset($analytics['click']) && $analytics['click']['total'] > $sentCount) {
                $analytics['click']['total'] = $sentCount;
            }

            $subjectsAnalytics = $campaignUrlMetric->getSubjectStats($campaign);
        }

        if ($campaign->status == 'working') {

            $campaign->scheduling_range = $ranged;

            $processingCount = CampaignEmail::select('id')
                ->where('campaign_id', $campaignId)
                ->where('status', 'processing')
                ->count();

            if ($processingCount) {
                $maximumProcessingTime = fluentCrmMaxRunTime() + 40;
                CampaignEmail::where('campaign_id', $campaignId)
                    ->where('status', 'processing')
                    ->where('updated_at', '<', date('Y-m-d H:i:s', (current_time('timestamp') - $maximumProcessingTime)))
                    ->update([
                        'status' => 'pending'
                    ]);
            } else if ($sentCount) {
                $futureCount = CampaignEmail::select('id')
                    ->where('campaign_id', $campaignId)
                    ->whereIn('status', ['pending', 'scheduled', 'paused', 'processing', 'draft'])
                    ->count();

                if (!$futureCount) {
                    Campaign::withoutGlobalScope('type')->where('id', $campaign->id)->update([
                        'status'     => 'archived',
                        'updated_at' => current_time('mysql')
                    ]);
                    $campaign = Campaign::withoutGlobalScope('type')->findOrFail($campaignId);
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

    public function getOverviewStats(Request $request, CampaignUrlMetric $campaignUrlMetric, $campaignId)
    {
        $campaign = Campaign::withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->findOrFail($campaignId);

        $sentCount = CampaignEmail::select('id')
            ->where('campaign_id', $campaignId)
            ->where('status', 'sent')
            ->count();


        $analytics = $campaignUrlMetric->getCampaignAnalytics($campaignId, $sentCount);

        if (isset($analytics['open']) && $analytics['open']['total'] > $sentCount) {
            $analytics['open']['total'] = $sentCount;
        }

        if (isset($analytics['click']) && $analytics['click']['total'] > $sentCount) {
            $analytics['click']['total'] = $sentCount;
        }

        $stat = CampaignEmail::select('status', fluentCrmDb()->raw('count(*) as total'))
            ->where('campaign_id', $campaignId)
            ->groupBy('status')
            ->get();

        return [
            'sent_count' => $sentCount,
            'stat' => $stat,
            'analytics' => $analytics
        ];
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
            'campaign' => Campaign::findOrFail($id)
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
            'campaign' => Campaign::findOrFail($id)
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
            'campaign' => Campaign::findOrFail($id)
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

        $campaign->duplicateSubjects($oldCampaign);

        do_action('fluent_crm/campaign_duplicated', $campaign, $oldCampaign);

        return [
            'campaign' => $campaign,
            'message'  => __('Campaign has been successfully duplicated', 'fluent-crm')
        ];

    }

    public function unSchedule(Request $request, $id)
    {
        $campaign = Campaign::withoutGlobalScope('type')
            ->whereIn('type', fluentCrmAutoProcessCampaignTypes())
            ->findOrFail($id);

        $validStatuses = [
            'scheduled',
            'pending-scheduled',
            'processing'
        ];

        if (!in_array($campaign->status, $validStatuses)) {
            return $this->sendError([
                'message' => __('You can only un-schedule a campaign if it is on "scheduled" state, Please reload this page', 'fluent-crm')
            ]);
        }

        if ($campaign->status == 'processing' && strtotime($campaign->scheduled_at) < current_time('timestamp')) {
            return $this->sendError([
                'message' => __('You can only un-schedule a campaign if it is on "scheduled" state, Please reload this page', 'fluent-crm')
            ]);
        }

        $campaign->status = 'draft';
        $campaign->save();

        // check if there has any emails, if yes then delete all of them
        CampaignEmail::where('campaign_id', $campaign->id)
            ->delete();

        CampaignEmail::withoutGlobalScope('type')->where('campaign_id', $campaign->id)
            ->whereIn('status', ['scheduled', 'scheduling'])
            ->delete();

        return [
            'message' => __('Campaign has been successfully un-scheduled', 'fluent-crm')
        ];
    }
}
