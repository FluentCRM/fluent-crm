<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\Framework\Support\Arr;

/**
 *  DocsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class DocsController extends Controller
{
    private $restApi = 'https://fluentcrm.com/wp-json/wp/v2/';

    public function index()
    {
        $request = wp_remote_get($this->restApi.'docs?per_page=100');

        $docs = json_decode(wp_remote_retrieve_body($request), true);

        $formattedDocs = [];

        foreach ($docs as $doc) {
            $primaryCategory = Arr::get($doc, 'taxonomy_info.doc_category.0', ['value' => 'none', 'label' => 'Other']);
            $formattedDocs[] = [
                'title' => $doc['title']['rendered'],
                'content' => $doc['content']['rendered'],
                'link' => $doc['link'],
                'category' => $primaryCategory
            ];
        }

        return [
            'docs' => $formattedDocs
        ];
    }

    public function getAddons()
    {
        $addOns = [
            'fluentform' => [
                'title' => __('Fluent Forms', 'fluent-crm'),
                'logo' => fluentCrmMix('images/fluentform.png'),
                'is_installed' => defined('FLUENTFORM'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluentform/',
                'settings_url' => admin_url('admin.php?page=fluent_forms'),
                'action_text' => __('Install Fluent Forms', 'fluent-crm'),
                'description' => __('Collect leads and build any type of forms, accept payments, connect with your CRM with the Fastest Contact Form Builder Plugin for WordPress', 'fluent-crm')
            ],
            'fluentsmtp' => [
                'title' => __('Fluent SMTP', 'fluent-crm'),
                'logo' => fluentCrmMix('images/fluent-smtp.svg'),
                'is_installed' => defined('FLUENTMAIL'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluent-smtp/',
                'settings_url' => admin_url('options-general.php?page=fluent-mail#/'),
                'action_text' => __('Install Fluent SMTP', 'fluent-crm'),
                'description' => __('The Ultimate SMTP and SES Plugin for WordPress. Connect with any SMTP, SendGrid, Mailgun, SES, Sendinblue, PepiPost, Google, Microsoft and more.', 'fluent-crm')
            ],
            'fluentconnect' => [
                'title' => __('Fluent Connect', 'fluent-crm'),
                'logo' => fluentCrmMix('images/fluent-connect.svg'),
                'is_installed' => defined('FLUENT_CONNECT_PLUGIN_VERSION'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluent-connect/',
                'settings_url' => admin_url('admin.php?page=fluent-connect#/'),
                'action_text' => __('Install Fluent Connect', 'fluent-crm'),
                'description' => __('Connect FluentCRM with ThriveCart and create, segment contact and run automation on ThriveCart purchase events.', 'fluent-crm')
            ]
        ];

        return [
            'addons' => $addOns
        ];
    }
}
