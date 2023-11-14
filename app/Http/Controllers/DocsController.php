<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Request\Request;
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
        $formattedDocs = fluentCrmGetFromCache('fluentcrm_all_docs', function () {
            $request = wp_remote_get($this->restApi . 'docs?per_page=100');

            if (is_wp_error($request)) {
                return [];
            }

            $docs = json_decode(wp_remote_retrieve_body($request), true);

            $formattedDocs = [];

            foreach ($docs as $doc) {
                $primaryCategory = Arr::get($doc, 'taxonomy_info.doc_category.0', ['value' => 'none', 'label' => 'Other']);
                $formattedDocs[] = [
                    'title'    => sanitize_text_field($doc['title']['rendered']),
                    'content'  => links_add_target(Helper::sanitizeHtml($doc['content']['rendered'])),
                    'link'     => esc_url($doc['link']),
                    'category' => wp_kses_post_deep($primaryCategory)
                ];
            }
            return $formattedDocs;
        }, 86400);

        return [
            'docs' => $formattedDocs
        ];
    }

    public function getDoc($docId)
    {
        $doc = fluentCrmGetFromCache('fluentcrm_all_docs', function () use ($docId) {
            $request = wp_remote_get($this->restApi . 'docs/' . $docId);
            if (is_wp_error($request)) {
                return [
                    'content'  => 'sorry, we could not fetch the doc at this moment. Please try again',
                    'is_error' => true
                ];
            }

            $doc = json_decode(wp_remote_retrieve_body($request), true);

            return [
                'title'   => sanitize_text_field($doc['title']['rendered']),
                'content' => links_add_target(Helper::sanitizeHtml($doc['content']['rendered'])),
                'link'    => esc_url($doc['link']),
                'id'      => $doc['id']
            ];

        }, 86400);

        return $doc;
    }

    public function getAddons(Request $request)
    {
        $addOns = [
            'fluentform'    => [
                'title'          => __('Fluent Forms', 'fluent-crm'),
                'logo'           => fluentCrmMix('images/fluentform.png'),
                'is_installed'   => defined('FLUENTFORM'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluentform/',
                'settings_url'   => admin_url('admin.php?page=fluent_forms'),
                'action_text'    => $this->isPluginInstalled( 'fluent-form/fluent-form.php' ) ? __('Active Fluent Forms', 'fluent-crm') : __('Install Fluent Forms', 'fluent-crm'),
                'description'    => __('Collect leads and build any type of forms, accept payments, connect with your CRM with the Fastest Contact Form Builder Plugin for WordPress', 'fluent-crm')
            ],
            'fluentsmtp'    => [
                'title'          => __('Fluent SMTP', 'fluent-crm'),
                'logo'           => fluentCrmMix('images/fluent-smtp.svg'),
                'is_installed'   => defined('FLUENTMAIL'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluent-smtp/',
                'settings_url'   => admin_url('options-general.php?page=fluent-mail#/'),
                'action_text'    => $this->isPluginInstalled( 'fluent-smtp/fluent-smtp.php' ) ? __('Active Fluent SMTP', 'fluent-crm') : __('Install Fluent SMTP', 'fluent-crm'),
                'description'    => __('The Ultimate SMTP and SES Plugin for WordPress. Connect with any SMTP, SendGrid, Mailgun, SES, Sendinblue, PepiPost, Google, Microsoft and more.', 'fluent-crm')
            ],
            'fluent-support' => [
                'title'          => __('Fluent Support', 'fluent-crm'),
                'logo'           => fluentCrmMix('images/fluent-support.svg'),
                'is_installed'   => defined('FLUENT_SUPPORT_VERSION'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluent-connect/',
                'settings_url'   => admin_url('admin.php?page=fluent-support#/'),
                'action_text'    => $this->isPluginInstalled( 'fluent-support/fluent-support.php' ) ? __('Active Fluent Support', 'fluent-crm') : __('Install Fluent Support', 'fluent-crm'),
                'description'    => __('WordPress Helpdesk and Customer Support Ticket Plugin. Provide awesome support and manage customer queries right from your WordPress dashboard.', 'fluent-crm')
            ],
            'fluentconnect' => [
                'title'          => __('Fluent Connect', 'fluent-crm'),
                'logo'           => fluentCrmMix('images/fluent-connect.svg'),
                'is_installed'   => defined('FLUENT_CONNECT_PLUGIN_VERSION'),
                'learn_more_url' => 'https://wordpress.org/plugins/fluent-connect/',
                'settings_url'   => admin_url('admin.php?page=fluent-connect#/'),
                'action_text'    => $this->isPluginInstalled( 'fluent-connect/fluent-connect.php' ) ? __('Active Fluent Connect', 'fluent-crm') : __('Install Fluent Connect', 'fluent-crm'),
                'description'    => __('Connect FluentCRM with ThriveCart and create, segment contact and run automation on ThriveCart purchase events.', 'fluent-crm')
            ],
        ];

        $data = [
            'addons' => $addOns
        ];

        if(in_array('experimental_features', $request->get('with', []))) {
            $data['experimental_features'] = Helper::getExperimentalSettings();
        }

        return $data;
    }

    private function isPluginInstalled($plugin)
    {
        return file_exists( WP_PLUGIN_DIR . '/' . $plugin );
    }

}
