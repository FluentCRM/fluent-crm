<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\BlockParser;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

/**
 *  CampaignEmail Model - DB Model for Campaign Emails
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class CampaignEmail extends Model
{
    protected $table = 'fc_campaign_emails';

    protected $guarded = ['id'];

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
                'email' => $this->email_address,
                'name'  => ($this->subscriber) ? $this->subscriber->full_name : ''
            ],
            'headers'       => $headers,
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

        /**
         * Determine the campaign email body content text.
         *
         * This filter allows you to modify the email body content before it is sent to the subscriber.
         *
         * @since 2.7.0
         *
         * @param string $emailBody        The email body content.
         * @param object $this->subscriber The subscriber object.
         */
        $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $this->subscriber);

        $designTemplate = 'classic';
        if ($this->campaign) {
            $designTemplate = $this->campaign->design_template;
        }

        if (!$designTemplate) {
            $designTemplate = 'classic';
        }

        if ($this->campaign && Arr::get($this->campaign->settings, 'template_config')) {
            $templateConfig = wp_parse_args($this->campaign->settings['template_config'], Helper::getTemplateConfig($this->campaign->design_template));
        } else {
            $templateConfig = Helper::getTemplateConfig();
        }


        /**
         * Filter the email body content using a specific email design template.
         *
         * This filter allows customization of the email body content by applying a specific design template.
         * 
         * @since 1.0.0
         * 
         * @param string $emailBody The original email body content before applying the design template.
         * @param array {
         *     Contextual information for the email design template.
         *
         *     @type string $preHeader The pre-header text for the email, if available.
         *     @type string $email_body The original email body content.
         *     @type string $footer_text The footer text for the email, if any.
         *     @type array $config Configuration settings for the email template.
         * }
         * @param object|null $this->campaign   The campaign object, if available.
         * @param object|null $this->subscriber The subscriber object, if available.
         */
        $email_body = apply_filters(
            'fluent_crm/email-design-template-' . $designTemplate,
            $emailBody,
            [
                'preHeader'   => ($this->campaign) ? $this->campaign->email_pre_header : '',
                'email_body'  => $emailBody,
                'footer_text' => '',
                'config'      => $templateConfig
            ],
            $this->campaign,
            $this->subscriber
        );


        if (strpos($email_body, '##crm.') || strpos($email_body, '{{crm.')) {
            /**
             * Filter the email body content for a campaign email and parse SmartCodes.
             *
             * This filter allows customization of the email body content before it is sent to the subscriber. There are FluentCRM-specific SmartCodes.
             * 
             * @since 2.7.0
             *
             * @param string $email_body       The email body content to be filtered.
             * @param object $this->subscriber The subscriber object containing subscriber details.
             */
            $email_body = apply_filters('fluent_crm/parse_extended_crm_text', $email_body, $this->subscriber);
        }

        $footerText = '';
        if ($this->subscriber) {
            $subscriber = $this->subscriber;
            $subscriber->campaign_id = $this->campaign_id;
            $footerText = Arr::get(Helper::getGlobalEmailSettings(), 'email_footer', '');
            /**
             * Determine the footer text of a campaign email for previewing.
             *
             * This filter allows you to modify the footer text of a campaign email before it is sent to the subscriber.
             *
             * @since 2.7.0
             *
             * @param string $footerText The footer text of the campaign email.
             * @param object $subscriber The subscriber object.
             */
            $footerText = apply_filters('fluent_crm/parse_campaign_email_text', $footerText, $subscriber);
        }

        $preViewUrl = site_url('?fluentcrm=1&route=email_preview&_e_hash=' . $this->email_hash);
        $email_body = str_replace(['##web_preview_url##', '{{crm_global_email_footer}}', '{{crm_preheader_text}}'], [$preViewUrl, $footerText, ''], $email_body);


        $email_body = str_replace(['https://fonts.googleapis.com/css2', 'https://fonts.googleapis.com/css'], 'https://fonts.bunny.net/css', $email_body);

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

        if ($subscriber) {
            $subscriber->email_id = $this->id;
        }

        $designTemplate = 'classic';

        if ($this->campaign) {
            $designTemplate = $this->campaign->design_template;
        }

        if (!$this->is_parsed) {
            $rawTemplates = [
                'raw_html',
                'visual_builder'
            ];

            $emailBody = ($this->campaign_id) ? $this->campaign->email_body : $this->email_body;

            $canCache = !Helper::hasConditionOnString($emailBody);

            if (in_array($designTemplate, $rawTemplates)) {
                $emailBody = $this->campaign->email_body;
            } else {
                $emailBody = $this->getParsedEmailBody();
            }

            $emailBody = str_replace(['https://fonts.googleapis.com/css2', 'https://fonts.googleapis.com/css'], 'https://fonts.bunny.net/css', $emailBody);

            /**
             * Parse the campaign email body content text before it is sent.
             *
             * This filter allows you to modify the email body content for a campaign email.
             *
             * @since 2.7.0
             *
             * @param string $emailBody  The email body content.
             * @param object $subscriber The subscriber object.
             */
            $emailBody = apply_filters('fluent_crm/parse_campaign_email_text', $emailBody, $subscriber);
            /**
             * Determine or finalize the email body content text before sending.
             *
             * This filter allows modification of the email body text before it is sent to the subscriber.
             *
             * @since 1.1.4
             *
             * @param string $emailBody  The email body text.
             * @param object $subscriber The subscriber object.
             * @param object $this       The current instance of the CampaignEmail class.
             */
            $emailBody = apply_filters('fluentcrm_email_body_text', $emailBody, $subscriber, $this);
            $campaignUrls = $this->getCampaignUrls($emailBody, $canCache);

            if ($campaignUrls) {
                $emailBody = Helper::attachUrls($emailBody, $campaignUrls, $this->id, $this->email_hash);
            }

            $this->email_body = $emailBody;
            $this->is_parsed = 1;
            $this->save();
        }

        $footerText = '';
        if ($subscriber) {
            $subscriber->campaign_id = $this->campaign_id;
            $footerText = Helper::getEmailFooterContent($this->campaign);

            if($footerText) {
                /**
                 * Filter the footer text of the campaign email.
                 *
                 * This filter allows you to modify the footer text of the campaign email before it is sent to the subscriber.
                 *
                 * @since 2.7.0
                 *
                 * @param string $footerText The footer text of the campaign email.
                 * @param object $subscriber The subscriber object.
                 */
                $footerText = apply_filters('fluent_crm/parse_campaign_email_text', $footerText, $subscriber);

                $preViewUrl = site_url('?fluentcrm=1&route=email_preview&_e_hash=' . $this->email_hash);
                $footerText = str_replace('##web_preview_url##', $preViewUrl, $footerText);
            }
        }

        if ($this->campaign && Arr::get($this->campaign->settings, 'template_config')) {
            $templateConfig = wp_parse_args($this->campaign->settings['template_config'], Helper::getTemplateConfig($this->campaign->design_template));
        } else {
            $templateConfig = Helper::getTemplateConfig();
        }

        $preHeader = ($this->campaign) ? $this->campaign->email_pre_header : '';

        if ($preHeader && $subscriber) {
            /**
             * Filter the pre-header text of a campaign email.
             *
             * This filter allows you to modify the pre-header text of a campaign email before it is sent.
             *
             * @since 2.7.0
             *
             * @param string $preHeader  The pre-header text of the campaign email.
             * @param object $subscriber The subscriber object containing subscriber details.
             */
            $preHeader = apply_filters('fluent_crm/parse_campaign_email_text', $preHeader, $subscriber);
        }

        $footerUrls = $this->getCampaignUrls($footerText, false);

        if ($footerUrls) {
            $footerText = Helper::attachUrls($footerText, $footerUrls, $this->id, $this->email_hash);
        }

        $templateData = [
            'preHeader'   => $preHeader,
            'email_body'  => $this->email_body,
            'footer_text' => $footerText,
            'config'      => $templateConfig
        ];

        /**
         * Filter the email design template content.
         *
         * This filter allows customization of the email design template content based on the template type.
         *
         * @since 1.0.0
         * 
         * @param string $this->email_body The original email body content.
         * @param array  $templateData     The data used for the template.
         * @param object $this->campaign   The campaign object.
         * @param object $this->subscriber The subscriber object.
         */
        $content = apply_filters(
            'fluent_crm/email-design-template-' . $designTemplate,
            $this->email_body,
            $templateData,
            $this->campaign,
            $this->subscriber
        );

        $preViewUrl = site_url('?fluentcrm=1&route=email_preview&_e_hash=' . $this->email_hash);
        $content = str_replace(['##web_preview_url##', '{{crm_global_email_footer}}', '{{crm_preheader_text}}'], [$preViewUrl, $footerText, $preHeader], $content);

        if (strpos($content, '##crm.') || strpos($content, '{{crm')) {
            /**
             * Filter the content to parse extended CRM text such as SmartCodes.
             *
             * This filter allows you to modify the content by parsing extended CRM text. There are FluentCRM-specific SmartCodes available.
             *
             * @since 2.7.0
             *
             * @param string $content    The content to be filtered.
             * @param object $subscriber The subscriber object.
             */
            $content = apply_filters('fluent_crm/parse_extended_crm_text', $content, $subscriber);
        }

        return Helper::injectTrackerPixel($content, $this->email_hash, $this->id);
    }

    private function getParsedEmailBody()
    {
        if (!$this->campaign_id || !$this->campaign) {
            return (new BlockParser($this->subscriber))->parse($this->email_body);
        }

        static $parsedEmailBody = [];
        $originalBody = $this->campaign->email_body;

        $hasConditions = Helper::hasConditionOnString($originalBody);

        if (isset($parsedEmailBody[$this->campaign_id]) && !$hasConditions) {
            return $parsedEmailBody[$this->campaign_id];
        }

        if ($this->campaign->status == 'archived' && !$hasConditions) {
            $cachedEmailBody = fluentcrm_get_campaign_meta($this->campaign_id, '_cached_email_body', true);
            if ($cachedEmailBody) {
                $parsedEmailBody[$this->campaign_id] = $cachedEmailBody;
                return $parsedEmailBody[$this->campaign_id];
            }
        }

        $rawTemplates = [
            'raw_html',
            'visual_builder'
        ];

        if (in_array($this->campaign->design_template, $rawTemplates)) {
            $emailBody = $originalBody;
        } else {
            $emailBody = (new BlockParser($this->subscriber))->parse($originalBody);
        }

        if ($hasConditions) {
            return $emailBody;
        }

        $parsedEmailBody[$this->campaign_id] = $emailBody;
        return $emailBody;
    }

    public function getCampaignUrls($emailBody, $cached = false)
    {
        if (!fluentcrmTrackClicking()) {
            return [];
        }

        if (!$cached || !$this->campaign_id) {
            return Helper::urlReplaces($emailBody);
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
        return fluentCrmDb()->table('fc_campaign_url_metrics')
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
            fluentCrmDb()->raw('count(*) as total'),
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
