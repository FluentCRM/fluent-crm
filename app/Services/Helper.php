<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberPivot;
use FluentCrm\App\Models\SystemLog;
use FluentCrm\App\Models\Tag;
use FluentCrm\App\Models\UrlStores;
use FluentCrm\App\Models\Webhook;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Support\Str;

class Helper
{
    public static function getLinksFromString($string)
    {
        preg_match_all('/<a[^>]+(href\=["|\'](http.*?)["|\'])/m', $string, $urls);

        if (!empty($urls[2])) {
            return $urls[2];
        }

        return [];
    }

    public static function urlReplaces($string)
    {
        preg_match_all('/<a[^>]+(href=["\'](http[^"\']*)["\'])/m', $string, $urls);
        $replaces = $urls[1];
        $urls = $urls[2];

        // Replace '|' with '%7C' in the URLs
        $urls = array_map(function ($url) {
            return str_replace('|', '%7C', $url);
        }, $urls);

        $formatted = [];
        $baseUrl = self::getSiteUrl();

        foreach ($urls as $index => $url) {
            $urlSlug = UrlStores::getUrlSlug($url);
            $formatted[$replaces[$index]] = add_query_arg([
                'ns_url' => $urlSlug
            ], $baseUrl);
        }
        return $formatted;
    }

    public static function attachUrls($html, $campaignUrls, $insertId, $hash = false)
    {
        $hasSmartUrl = strpos($html, 'smart_url') !== false;

        foreach ($campaignUrls as $src => $url) {
            $url .= '&mid=' . $insertId;
            if ($hash) {
                $url .= '&fch=' . substr($hash, 0, 8);
            }

            if ($hasSmartUrl && strpos($src, 'smart_url') !== false) {
                $url .= '&signed_hash=' . rawurlencode(wp_hash_password($hash));
            }

            $campaignUrls[$src] = 'href="' . $url . '"';
        }
        return str_replace(array_keys($campaignUrls), array_values($campaignUrls), $html);
    }

    public static function generateEmailHash($insertId)
    {
        return wp_generate_uuid4();
    }

    public static function injectTrackerPixel($emailBody, $hash, $emailId = null)
    {
        if (!$hash) {
            return $emailBody;
        }

        /**
         * Filter to disable email open tracking in FluentCRM.
         *
         * This filter allows you to disable the email open tracking feature in FluentCRM.
         *
         * @param bool Whether to disable email open tracking. Default false.
         * @since 2.0.0
         *
         */
        if (apply_filters('fluentcrm_disable_email_open_tracking', false)) {
            return $emailBody;
        }

        $trackImageUrl = add_query_arg([
            'fluentcrm' => 1,
            'route'     => 'open',
            '_e_hash'   => $hash,
            '_e_id'     => $emailId
        ], self::getSiteUrl());
        $trackPixelHtml = '<img src="' . esc_url($trackImageUrl) . '" alt="" />';

        if (strpos($emailBody, '{fluent_track_pixel}') !== false) {
            $emailBody = str_replace('{fluent_track_pixel}', $trackPixelHtml, $emailBody);
        } else {
            // we have to inject this
            $emailBody = str_replace('</body>', $trackPixelHtml . '</body>', $emailBody);
        }

        return $emailBody;
    }

    public static function getProfileSections()
    {
        $sections = [
            'subscriber'        => [
                'name'    => 'subscriber',
                'title'   => __('Overview', 'fluent-crm'),
                'handler' => 'route'
            ],
            'subscriber_emails' => [
                'name'    => 'subscriber_emails',
                'title'   => __('Emails', 'fluent-crm'),
                'handler' => 'route'
            ],
        ];

        if (self::getPurchaseHistoryProviders()) {
            $sections['subscriber_purchases'] = [
                'name'    => 'subscriber_purchases',
                'title'   => __('Purchase History', 'fluent-crm'),
                'handler' => 'route'
            ];
        }

        if (defined('FLUENTFORM')) {
            $sections['subscriber_form_submissions'] = [
                'name'    => 'subscriber_form_submissions',
                'title'   => __('Form Submissions', 'fluent-crm'),
                'handler' => 'route'
            ];
        }

        /**
         * Filter the list of support ticket providers.
         *
         * This filter allows you to modify the array of support ticket providers used in FluentCRM.
         *
         * @param array An array of support ticket providers.
         * @since 2.5.1
         *
         */
        $supportProviders = apply_filters('fluentcrm-support_tickets_providers', []);
        if ($supportProviders) {
            $sections['subscriber_support_tickets'] = [
                'name'    => 'subscriber_support_tickets',
                'title'   => __('Support Tickets', 'fluent-crm'),
                'handler' => 'route'
            ];
        }

        $sections['subscriber_notes'] = [
            'name'    => 'subscriber_notes',
            'title'   => __('Notes & Activities', 'fluent-crm'),
            'handler' => 'route'
        ];

        /**
         * Filter the contact profile sections in FluentCRM.
         *
         * This filter allows modification of the contact profile sections array in FluentCRM.
         *
         * @param array $sections An array of profile sections.
         * @since 2.2.0
         *
         */
        return apply_filters('fluentcrm_profile_sections', $sections);
    }

    public static function getDefaultEmailTemplate()
    {
        /**
         * Filter the default email design template.
         *
         * This filter allows you to modify the default email design template used by FluentCRM.
         *
         * @param string The default email design template. Default 'simple'.
         * @since 2.7.0
         *
         */
        return apply_filters('fluent_crm/default_email_design_template', 'simple');
    }

    public static function getGlobalSmartCodes()
    {
        $subscriberCodes = [
            'key'        => 'contact',
            'title'      => __('Contact', 'fluent-crm'),
            /**
             * Filter the smartcodes available for FluentCRM contacts.
             *
             * This filter allows modification of the smartcodes that can be used for FluentCRM contacts.
             *
             * @param array $smartcodes An associative array of smartcodes and their descriptions.
             *     Default smartcodes:
             *     - '{{contact.full_name}}'      => 'Full Name'
             *     - '{{contact.prefix}}'         => 'Name Prefix'
             *     - '{{contact.first_name}}'     => 'First Name'
             *     - '{{contact.last_name}}'      => 'Last Name'
             *     - '{{contact.email}}'          => 'Contact Email'
             *     - '{{contact.id}}'             => 'Contact ID'
             *     - '{{contact.user_id}}'        => 'User ID'
             *     - '{{contact.address_line_1}}' => 'Address Line 1'
             *     - '{{contact.address_line_2}}' => 'Address Line 2'
             *     - '{{contact.city}}'           => 'City'
             *     - '{{contact.state}}'          => 'State'
             *     - '{{contact.postal_code}}'    => 'Postal Code'
             *     - '{{contact.country}}'        => 'Country'
             *     - '{{contact.phone}}'          => 'Phone Number'
             *     - '{{contact.status}}'         => 'Status'
             *     - '{{contact.date_of_birth}}'  => 'Date of Birth'
             * @since 1.0.0
             *
             */
            'shortcodes' => apply_filters('fluentcrm_contact_smartcodes', [
                '{{contact.full_name}}'      => __('Full Name', 'fluent-crm'),
                '{{contact.prefix}}'         => __('Name Prefix', 'fluent-crm'),
                '{{contact.first_name}}'     => __('First Name', 'fluent-crm'),
                '{{contact.last_name}}'      => __('Last Name', 'fluent-crm'),
                '{{contact.email}}'          => __('Contact Email', 'fluent-crm'),
                '{{contact.id}}'             => __('Contact ID', 'fluent-crm'),
                '{{contact.user_id}}'        => __('User ID', 'fluent-crm'),
                '{{contact.address_line_1}}' => __('Address Line 1', 'fluent-crm'),
                '{{contact.address_line_2}}' => __('Address Line 2', 'fluent-crm'),
                '{{contact.city}}'           => __('City', 'fluent-crm'),
                '{{contact.state}}'          => __('State', 'fluent-crm'),
                '{{contact.postal_code}}'    => __('Postal Code', 'fluent-crm'),
                '{{contact.country}}'        => __('Country', 'fluent-crm'),
                '{{contact.phone}}'          => __('Phone Number', 'fluent-crm'),
                '{{contact.status}}'         => __('Status', 'fluent-crm'),
                '{{contact.date_of_birth}}'  => __('Date of Birth', 'fluent-crm')
            ])
        ];

        if (self::isCompanyEnabled()) {
            $subscriberCodes['shortcodes']['{{contact.company.name}}'] = __('Company Name', 'fluent-crm');
            $subscriberCodes['shortcodes']['{{contact.company.industry}}'] = __('Company Industry', 'fluent-crm');
            $subscriberCodes['shortcodes']['{{contact.company.address}}'] = __('Company Address', 'fluent-crm');
        }

        $smartCodes[] = $subscriberCodes;

        $customFields = fluentcrm_get_option('contact_custom_fields', []);

        if ($customFields) {
            $shortcodes = [];
            foreach ($customFields as $item) {
                $shortcodes['{{contact.custom.' . $item['slug'] . '}}'] = $item['label'];
            }
            $smartCodes[] = [
                'key'        => 'contact_custom_fields',
                'title'      => __('Custom Fields', 'fluent-crm'),
                'shortcodes' => $shortcodes
            ];
        }

        $smartCodes[] = [
            'key'        => 'general',
            'title'      => __('General', 'fluent-crm'),
            /**
             * Filter to modify the general smartcodes used in FluentCRM.
             *
             * @param array $shortcodes An associative array of smartcodes and their descriptions.
             *
             *        Default smartcodes:
             *        - '{{crm.business_name}}' => 'Business Name'
             *        - '{{crm.business_address}}' => 'Business Address'
             *        - '{{wp.admin_email}}' => 'Admin Email'
             *        - '##wp.url##' => 'Site URL'
             *        - '{{other.date.+2 days}}' => 'Dynamic Date (ex: +2 days from now)'
             *        - '{{other.date_format.D, d M, Y}}' => 'Custom Date Format (Any PHP Date Format)'
             *        - '{{other.latest_post.title}}' => 'Latest Post Title (Published)'
             *        - '##crm.unsubscribe_url##' => 'Unsubscribe URL'
             *        - '##crm.manage_subscription_url##' => 'Manage Subscription URL'
             *        - '##web_preview_url##' => 'View On Browser URL'
             *        - '{{crm.unsubscribe_html|Unsubscribe}}' => 'Unsubscribe Hyperlink HTML'
             *        - '{{crm.manage_subscription_html|Manage Preference}}' => 'Manage Subscription Hyperlink HTML'
             * @since 2.7.0
             *
             */
            'shortcodes' => apply_filters('fluent_crm/general_smartcodes', [
                '{{crm.business_name}}'                              => __('Business Name', 'fluent-crm'),
                '{{crm.business_address}}'                           => __('Business Address', 'fluent-crm'),
                '{{wp.admin_email}}'                                 => __('Admin Email', 'fluent-crm'),
                '##wp.url##'                                         => __('Site URL', 'fluent-crm'),
                '{{other.date.+2 days}}'                             => __('Dynamic Date (ex: +2 days from now)', 'fluent-crm'),
                '{{other.date_format.D, d M, Y}}'                    => __('Custom Date Format (Any PHP Date Format)', 'fluent-crm'),
                '{{other.latest_post.title}}'                        => __('Latest Post Title (Published)', 'fluent-crm'),
                '##crm.unsubscribe_url##'                            => __('Unsubscribe URL', 'fluent-crm'),
                '##crm.manage_subscription_url##'                    => __('Manage Subscription URL', 'fluent-crm'),
                '##web_preview_url##'                                => __('View On Browser URL', 'fluent-crm'),
                '{{crm.unsubscribe_html|Unsubscribe}}'               => __('Unsubscribe Hyperlink HTML', 'fluent-crm'),
                '{{crm.manage_subscription_html|Manage Preference}}' => __('Manage Subscription Hyperlink HTML', 'fluent-crm'),
            ])
        ];

        /**
         * Filter the smart code groups.
         *
         * This filter allows modification of the smart code groups array.
         *
         * @param array $smartCodes An array of smart code groups.
         * @since 2.7.0
         *
         */
        return apply_filters('fluent_crm/smartcode_groups', $smartCodes);
    }

    public static function getExtendedSmartCodes()
    {
        /**
         * Filter the extended smart codes for FluentCRM.
         *
         * This filter allows you to modify the array of extended smart codes used in FluentCRM.
         *
         * @param array An array of extended smart codes.
         * @since 2.7.0
         *
         */
        return array_values(apply_filters('fluent_crm/extended_smart_codes', []));
    }

    public static function getDoubleOptinSettings()
    {
        if ($settings = fluentcrm_get_option('double_optin_settings', [])) {
            if (empty($settings['after_confirmation_type'])) {
                $settings['after_confirmation_type'] = 'message';
                $settings['after_conf_redirect_url'] = '';
            }
            return $settings;
        }

        $businessName = '';
        $businessEmail = '';
        $businessAddress = '';
        $subject = 'Please Confirm Subscription';
        $business = fluentcrmGetGlobalSettings('business_settings', []);

        if (!empty($business['business_name'])) {
            $businessName = $business['business_name'];
            $subject = "{$businessName} : Please Confirm Subscription";
            if (!empty($business['business_address'])) {
                $businessAddress = $business['business_address'];
            }
        }

        $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);
        if (!empty($emailSettings['from_email'])) {
            $businessEmail = $emailSettings['from_email'];
        }

        return [
            'email_subject'           => $subject,
            'email_pre_header'        => '',
            'design_template'         => 'simple',
            'email_body'              => '<h2>Please Confirm Subscription</h2><p><a style="color: #ffffff; background-color: #454545; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: normal; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;" href="#activate_link#">Yes, subscribe me to the mailing list</a></p><p>&nbsp;</p><p>If you received this email by mistake, simply delete it. You won\'t be subscribed if you don\'t click the confirmation link above.</p><p>For questions about this list, please contact:<br />' . $businessEmail . '</p>',
            'after_confirmation_type' => 'message',
            'after_confirm_message'   => '<h2>Subscription Confirmed</h2><p>Your subscription to our list has been confirmed.</p><p>Thank you for subscribing!</p><p>&nbsp;</p><p>' . $businessName . '</p><p>' . $businessAddress . '</p><p>&nbsp;</p><p><a style="color: #ffffff; background-color: #404040; font-size: 16px; border-radius: 5px; text-decoration: none; font-weight: normal; font-style: normal; padding: 0.8rem 1rem; border-color: #0072ff;" href="' . site_url() . '">Continue to our Website</a></p>',
            'after_conf_redirect_url' => '',
        ];
    }

    public static function getEmailDesignTemplates()
    {
        $defaultDesignConfig = [
            'content_width'         => 700,
            'content_padding'       => 20,
            'headings_font_family'  => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
            'text_color'            => '#202020',
            'link_color'            => '',
            'body_bg_color'         => '#FAFAFA',
            'content_bg_color'      => '#FFFFFF',
            'footer_text_color'     => '#202020',
            'content_font_family'   => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
            'paragraph_color'       => '',
            'paragraph_font_size'   => '',
            'paragraph_font_family' => '',
            'paragraph_line_height' => '',
            'headings_color'        => '#202020'
        ];


        $classicConfig = [
            'content_font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
        ];

        if (defined('FLUENTCAMPAIGN')) {
            $defaultDesignConfig['disable_footer'] = 'no';
            $classicConfig['disable_footer'] = 'no';
        }

        $plainConfig = $defaultDesignConfig;
        $plainConfig['body_bg_color'] = '#FFFFFF';

        /**
         * Filter the email design templates available in FluentCRM.
         *
         * @param array {
         *     An array of email design templates.
         *
         * @type array $simple {
         * @type string $id The template ID.
         * @type string $label The template label.
         * @type string $image The URL to the template image.
         * @type array $config The configuration array for the template.
         * @type bool $use_gutenberg Whether to use Gutenberg editor.
         *     }
         * @type array $plain {
         * @type string $id The template ID.
         * @type string $label The template label.
         * @type string $image The URL to the template image.
         * @type array $config The configuration array for the template.
         * @type bool $use_gutenberg Whether to use Gutenberg editor.
         *     }
         * @type array $classic {
         * @type string $id The template ID.
         * @type string $label The template label.
         * @type string $image The URL to the template image.
         * @type array $config The configuration array for the template.
         * @type bool $use_gutenberg Whether to use Gutenberg editor.
         *     }
         * @type array $raw_classic {
         * @type string $id The template ID.
         * @type string $label The template label.
         * @type string $image The URL to the template image.
         * @type array $config The configuration array for the template.
         * @type bool $use_gutenberg Whether to use Gutenberg editor.
         * @type string $template_type The type of the template.
         * @type string $template_info Additional information about the template.
         *     }
         * @type array $raw_html {
         * @type string $id The template ID.
         * @type string $label The template label.
         * @type string $image The URL to the template image.
         * @type array $config The configuration array for the template.
         * @type bool $use_gutenberg Whether to use Gutenberg editor.
         * @type string $template_type The type of the template.
         * @type string $template_info Additional information about the template.
         *     }
         * }
         * @since 2.6.51
         *
         */
        $templates = apply_filters('fluent_crm/email_design_templates', [
            'simple'      => [
                'id'            => 'simple',
                'label'         => __('Simple Boxed', 'fluent-crm'),
                'image'         => fluentCrmMix('images/simple.png'),
                'config'        => $defaultDesignConfig,
                'use_gutenberg' => true
            ],
            'plain'       => [
                'id'            => 'plain',
                'label'         => __('Plain Centered', 'fluent-crm'),
                'image'         => fluentCrmMix('images/plain-centered.png'),
                'config'        => $plainConfig,
                'use_gutenberg' => true
            ],
            'classic'     => [
                'id'            => 'classic',
                'label'         => __('Plain Left', 'fluent-crm'),
                'image'         => fluentCrmMix('images/classic.png'),
                'config'        => $plainConfig,
                'use_gutenberg' => true
            ],
            'raw_classic' => [
                'id'            => 'raw_classic',
                'label'         => __('Classic Editor', 'fluent-crm'),
                'image'         => fluentCrmMix('images/classic_raw.png'),
                'config'        => $classicConfig,
                'use_gutenberg' => false,
                'template_type' => 'classic_editor',
                'template_info' => '<h3>Classic Text Based Email</h3><p>Type your simple email and FluentCRM will send that without altering any design processing. The default footer will be injected after your content if footer is not disabled.</p>'
            ],
            'raw_html'    => [
                'id'            => 'raw_html',
                'label'         => __('Raw HTML', 'fluent-crm'),
                'image'         => fluentCrmMix('images/raw-html.png'),
                'config'        => [],
                'use_gutenberg' => false,
                'template_type' => 'raw_text_box',
                'template_info' => '<h3>Raw HTML Template</h3><p>You can use any type of valid html and FluentCRM will send that without altering any design processing.</p>'
            ]
        ]);

        if (!defined('FLUENTCAMPAIGN')) {
            $templates['visual_builder'] = [
                'id'            => 'visual_builder',
                'label'         => __('Visual Builder', 'fluent-crm'),
                'image'         => fluentCrmMix('images/drag-drop.png'),
                'config'        => $classicConfig,
                'use_gutenberg' => false,
                'template_type' => 'visual_builder_demo'
            ];
        }

        return $templates;
    }

    public static function getTemplateConfig($templateName = '', $withGlobal = true)
    {
        if (!$templateName) {
            $templateName = self::getDefaultEmailTemplate();
        }
        $config = Arr::get(self::getEmailDesignTemplates(), $templateName . '.config', []);

        if ($withGlobal) {
            $globalSettings = fluentcrm_get_option('global_email_style_config', []);
            return wp_parse_args($globalSettings, $config);
        }

        return $config;

    }

    public static function getActivatedFeatures()
    {
        return [
            'fluentcampaign'       => defined('FLUENTCAMPAIGN_FRAMEWORK_VERSION'),
            'company_module'       => self::isCompanyEnabled(),
            'event_tracking'       => self::isExperimentalEnabled('event_tracking'),
            /**
             * Filter to disable email open tracking in FluentCRM.
             *
             * This filter allows to disable email open tracking globally.
             *
             * @param bool  Whether to disable email open tracking. Default is false.
             * @return bool Filtered value to enable or disable email open tracking.
             * @since 2.8.0
             *
             */
            'email_open_tracking'  => !apply_filters('fluentcrm_disable_email_open_tracking', false),
            /**
             * Filter to enable or disable email click tracking.
             *
             * This filter allows you to control whether email click tracking is enabled or disabled.
             *
             * @param bool Whether to enable email click tracking. Default true.
             * @since 2.8.0
             *
             */
            'email_click_tracking' => apply_filters('fluent_crm/track_click', true),
        ];
    }

    public static function getContactPrefixes($withKeyed = false)
    {
        /**
         * Base contact prefixes with translatable labels.
         * These will show up in Loco Translate under the 'fluent-crm' domain.
         */
        $prefixes = [
            __('Mr', 'fluent-crm'),
            __('Mrs', 'fluent-crm'),
            __('Ms', 'fluent-crm')
        ];

        /**
         * Filter the contact name prefixes.
         *
         * This filter is deprecated. Please use fluent_crm/contact_name_prefixes instead.
         *
         * @param array An array of contact name prefixes.
         * @deprecated 2.7.0 Use fluent_crm/contact_name_prefixes instead.
         *
         * @since 2.5.5
         *
         */
        $prefixes = apply_filters('fluentcrm_contact_name_prefixes', $prefixes);

        /**
         * Filter the contact name prefixes.
         *
         * @param array $prefixes An array of contact name prefixes.
         * @since 2.7.0
         *
         */
        $prefixes = apply_filters('fluent_crm/contact_name_prefixes', $prefixes);

        if ($withKeyed) {
            $keyedNames = [];
            foreach ($prefixes as $prefix) {
                $keyedNames[$prefix] = $prefix;
            }
            return $keyedNames;
        }
        return $prefixes;
    }

    public static function getGlobalEmailSettings()
    {
        $defaultFooter = '{{crm.business_name}}, {{crm.business_address}}<br>Don\'t like these emails? <a href="##crm.unsubscribe_url##">Unsubscribe</a> or <a href="##crm.manage_subscription_url##">Manage Email Subscriptions</a>';

        if ($settings = fluentcrmGetGlobalSettings('email_settings', [])) {
            if (empty($settings['email_footer'])) {
                $settings['email_footer'] = $defaultFooter;
            }

            if (empty($settings['pref_form'])) {
                $settings['pref_form'] = 'no';
                $settings['pref_general'] = ['prefix', 'first_name', 'last_name'];
                $settings['pref_custom'] = [];
            }

            if (!isset($settings['pref_general'])) {
                $settings['pref_general'] = [];
            }

            if (!isset($settings['pref_custom'])) {
                $settings['pref_custom'] = [];
            }

            return $settings;
        }

        return [
            'from_name'         => '',
            'from_email'        => '',
            'emails_per_second' => 15,
            'email_footer'      => $defaultFooter,
            'pref_list_type'    => 'no',
            'pref_list_items'   => [],
            'pref_form'         => 'no',
            'pref_general'      => ['prefix', 'first_name', 'last_name'],
            'pref_custom'       => []
        ];
    }

    public static function getPurchaseHistoryProviders()
    {
        $validProviders = [];

        if(defined('FLUENTCART_VERSION')) {
            $validProviders['fluent_cart'] = [
                'title' => __('FluentCart Purchase History', 'fluent-crm'),
                'name'  => __('FluentCart', 'fluent-crm')
            ];
        }

        if (defined('WC_PLUGIN_FILE')) {
            $validProviders['woocommerce'] = [
                'title' => __('Woocommerce Purchase History', 'fluent-crm'),
                'name'  => __('WooCommerce', 'fluent-crm')
            ];
        }

        if (class_exists('\Easy_Digital_Downloads')) {
            $validProviders['edd'] = [
                'title' => __('EDD Purchase History', 'fluent-crm'),
                'name'  => __('Easy Digital Downloads', 'fluent-crm')
            ];
        }

        if (defined('WPPAYFORM_VERSION')) {
            $validProviders['payform'] = [
                'title' => __('Paymattic Purchase History', 'fluent-crm'),
                'name'  => __('Paymattic', 'fluent-crm')
            ];
        }

        if (defined('PMPRO_VERSION') && defined('FLUENTCAMPAIGN')) {
            $validProviders['pmpro'] = [
                'title' => __('Paid Membership Pro Purchase History', 'fluent-crm'),
                'name'  => __('Paid Membership Pro', 'fluent-crm')
            ];
        }

        /**
         * Filter the list of valid purchase history providers.
         *
         * This filter allows modification of the valid purchase history providers used in FluentCRM.
         *
         * @param array $validProviders An array of valid purchase history providers.
         * @since 2.7.0
         *
         */
        return apply_filters('fluent_crm/purchase_history_providers', $validProviders);
    }

    public static function getThemePrefScheme()
    {
        static $pref;
        if (!$pref) {

            $color_palette = [
                [
                    "name"  => __("Black", "fluent-crm"),
                    "slug"  => "black",
                    "color" => "#000000"
                ],
                [
                    "name"  => __("Cyan bluish gray", "fluent-crm"),
                    "slug"  => "cyan-bluish-gray",
                    "color" => "#abb8c3"
                ],
                [
                    "name"  => __("White", "fluent-crm"),
                    "slug"  => "white",
                    "color" => "#ffffff"
                ],
                [
                    "name"  => __("Pale pink", "fluent-crm"),
                    "slug"  => "pale-pink",
                    "color" => "#f78da7"
                ],
                [
                    "name"  => __("Luminous vivid orange", "fluent-crm"),
                    "slug"  => "luminous-vivid-orange",
                    "color" => "#ff6900"
                ],
                [
                    "name"  => __("Luminous vivid amber", "fluent-crm"),
                    "slug"  => "luminous-vivid-amber",
                    "color" => "#fcb900"
                ],
                [
                    "name"  => __("Light green cyan", "fluent-crm"),
                    "slug"  => "light-green-cyan",
                    "color" => "#7bdcb5"
                ],
                [
                    "name"  => __("Vivid green cyan", "fluent-crm"),
                    "slug"  => "vivid-green-cyan",
                    "color" => "#00d084"
                ],
                [
                    "name"  => __("Pale cyan blue", "fluent-crm"),
                    "slug"  => "pale-cyan-blue",
                    "color" => "#8ed1fc"
                ],
                [
                    "name"  => __("Vivid cyan blue", "fluent-crm"),
                    "slug"  => "vivid-cyan-blue",
                    "color" => "#0693e3"
                ],
                [
                    "name"  => __("Vivid purple", "fluent-crm"),
                    "slug"  => "vivid-purple",
                    "color" => "#9b51e0"
                ]
            ];

            $font_sizes = [
                [
                    'name'      => __('Small', 'fluent-crm'),
                    'shortName' => 'S',
                    'size'      => 14,
                    'slug'      => 'small'
                ],
                [
                    'name'      => __('Medium', 'fluent-crm'),
                    'shortName' => 'M',
                    'size'      => 18,
                    'slug'      => 'medium'
                ],
                [
                    'name'      => __('Large', 'fluent-crm'),
                    'shortName' => 'L',
                    'size'      => 24,
                    'slug'      => 'large'
                ],
                [
                    'name'      => __('Larger', 'fluent-crm'),
                    'shortName' => 'XL',
                    'size'      => 32,
                    'slug'      => 'larger'
                ]
            ];

            /**
             * Filter the theme preferences for FluentCRM.
             *
             * This filter allows modification of the theme preferences, including colors and font sizes.
             *
             * @param array {
             *     The theme preferences.
             *
             * @type array $colors The color palette.
             * @type array $font_sizes The font sizes.
             * }
             * @since 2.6.51
             *
             */
            $pref = apply_filters('fluent_crm/theme_pref', [
                'colors'     => (array)$color_palette,
                'font_sizes' => (array)$font_sizes
            ]);
        }

        return $pref;

    }

    public static function funnelLabelColors()
    {
        $colors = [
            '#D6D8FF',
            '#D4ECD6',
            '#FEE8B5',
            '#D7E8EF',
            '#FFCACA',
            '#F8D7C4',
            '#D4D7DC',
            '#FFD9E3'
        ];

        /**
         * Filter the funnel label colors.
         *
         * This filter allows modification of the funnel label colors.
         *
         * @param array $colors An array of colors for the funnel labels.
         * @since 2.9.30
         *
         */
        return apply_filters('fluent_crm/funnel_label_color', $colors);
    }

    public static function getColorSchemeValue($colorName)
    {
        static $colorMap = [];
        if (isset($colorMap[$colorName])) {
            return $colorMap[$colorName];
        }
        $pref = self::getThemePrefScheme();
        $colors = $pref['colors'];
        foreach ($colors as $color) {
            $colorMap[$color['slug']] = $color['color'];
            if ($color['slug'] == $colorName) {
                return $color['color'];
            }
        }

        $color_palette = self::getThemeColorPalette();
        return self::getColorBySlug($color_palette, $colorName);
    }

    public static function getColorBySlug($color_palette, $slug)
    {
        if (!$color_palette || !is_array($color_palette)) {
            return null;
        }

        foreach ($color_palette as $color) {
            if (isset($color['slug']) && isset($color['color']) && $color['slug'] === $slug) {
                return $color['color'];
            }
        }

        return null;
    }

    public static function getThemeColorPalette()
    {
        $color_palette = current((array)get_theme_support('editor-color-palette'));
        $theme_json_path = get_theme_file_path('theme.json');

        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);

            if (isset($theme_json['settings']['color']['palette'])) {
                $color_palette = $theme_json['settings']['color']['palette'];
            }
        }
        if (!$color_palette) {
            $color_palette = [];
        }

        return (array)$color_palette;
    }

    public static function getThemeFontSizes()
    {
        $font_sizes = current((array)get_theme_support('editor-font-sizes'));
        $theme_json_path = get_theme_file_path('theme.json');

        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);

            if (isset($theme_json['settings']['typography']['fontSizes'])) {
                $font_sizes = $theme_json['settings']['typography']['fontSizes'];
            }
        }

        return $font_sizes;
    }


    public static function generateThemePrefCss()
    {
        static $color_css;
        if ($color_css) {
            return $color_css;
        }
        $pref = self::getThemePrefScheme();

        $css = '';
        if (isset($pref['colors'])) {
            foreach ($pref['colors'] as $color) {
                if (isset($color['slug']) && isset($color['color'])) {
                    $slug = self::kebabCase($color['slug']);
                    $css .= '.has-' . $slug . '-color  { color: ' . $color['color'] . ';} ';
                    $css .= '.has-' . $slug . '-background-color  { background-color: ' . $color['color'] . '; background: ' . $color['color'] . '; } ';
                    $css .= 'a.has-' . $slug . '-background-color  { border: 1px solid ' . $color['color'] . '; } ';
                }
            }
        }

        if (isset($pref['font_sizes'])) {
            foreach ($pref['font_sizes'] as $size) {
                if (isset($size['slug']) && isset($size['size'])) {
                    $slug = self::kebabCase($size['slug']);
                    $css .= '.fc_email_body .has-' . $slug . '-font-size  { font-size: ' . $size['size'] . 'px !important;} ';
                }
            }
        }

        // Generate CSS for theme color palette
        $themeColors = self::getThemeColorPalette();
        if (!empty($themeColors)) {
            foreach ($themeColors as $themeColor) {
                $color = $themeColor['color'];

                // Converts 'palette1' to 'palette-1'
                $slug = self::normalizeColorSlug($themeColor['slug']);

                // Stores the original slug value without modification
                $originalSlug = $themeColor['slug'];

                $css .= ".fc_email_body .has-{$originalSlug}-background-color { background-color: {$color};}";
                $css .= ".fc_email_body .has-{$originalSlug}-color { color: {$color};}";
                $css .= ".fc_email_body .has-{$slug}-background-color { background-color: {$color};}";
                $css .= ".fc_email_body .has-{$slug}-color { color: {$color};}";
            }
        }

        // Generate CSS for theme font sizes
        $themeFontSizes = self::getThemeFontSizes();
        if (!empty($themeFontSizes)) {
            foreach ($themeFontSizes as $themeFontSize) {
                $size = $themeFontSize['size'];
                $slug = $themeFontSize['slug'];
                $css .= ".fc_email_body .has-{$slug}-font-size { font-size: {$size} !important;}";
            }
        }

        $color_css = $css;
        return $color_css;
    }

    private static function normalizeColorSlug($slug)
    {
        // Normalize the slug
        $slug = strtolower($slug);

        // If the slug already follows "text-number" format, return it as is
        if (preg_match('/^(.*?)-(\d+)$/', $slug, $matches)) {
            return $slug;
        }

        // Otherwise, fix cases like "theme-palette1" -> "theme-palette-1"
        $parts = preg_split('/(\d+)/', $slug, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (count($parts) > 1 && ctype_digit(trim($parts[count($parts) - 2]))) {
            return implode('-', array_filter($parts));
        }

        return $slug;
    }

    public static function kebabCase($string)
    {
        return implode('-', array_filter(preg_split('/(\d)/', strtolower(strval($string)), -1, PREG_SPLIT_DELIM_CAPTURE)));
    }

    public static function getMailHeadersFromSettings($emailSettings = [])
    {
        if (empty($emailSettings) || Arr::get($emailSettings, 'is_custom') == 'no') {
            $emailSettings = fluentcrmGetGlobalSettings('email_settings', []);
        }

        if (empty($emailSettings)) {
            return [];
        }

        $headers = [];
        if (Arr::get($emailSettings, 'from_name') && Arr::get($emailSettings, 'from_email')) {
            $headers['From'] = $emailSettings['from_name'] . ' <' . $emailSettings['from_email'] . '>';
        } else if ($fromEmail = Arr::get($emailSettings, 'from_email')) {
            $headers['From'] = $fromEmail;
        }

        if (Arr::get($emailSettings, 'reply_to_name') && Arr::get($emailSettings, 'reply_to_email')) {
            $headers['Reply-To'] = $emailSettings['reply_to_name'] . ' <' . $emailSettings['reply_to_email'] . '>';
        } else if ($replyTo = Arr::get($emailSettings, 'reply_to_email')) {
            $headers['Reply-To'] = $replyTo;
        }

        return $headers;
    }

    public static function getMailHeader($existingHeader = [])
    {
        if (!empty($existingHeader['From'])) {
            return $existingHeader;
        }

        if (!empty($existingHeader['Reply-To'])) {
            return $existingHeader;
        }

        $headers = [];
        static $globalHeaders;
        if ($globalHeaders) {
            return $globalHeaders;
        }

        $globalEmailSettings = fluentcrmGetGlobalSettings('email_settings', []);

        $fromName = Arr::get($globalEmailSettings, 'from_name');
        $fromEmail = Arr::get($globalEmailSettings, 'from_email');

        if ($fromName && $fromEmail) {
            $headers['From'] = $fromName . ' <' . $fromEmail . '>';
        } else if ($fromEmail) {
            $headers['From'] = $fromEmail;
        }

        $replyName = Arr::get($globalEmailSettings, 'reply_to_name');
        $replyEmail = Arr::get($globalEmailSettings, 'reply_to_email');

        if ($replyName && $replyEmail) {
            $headers['Reply-To'] = $replyName . ' <' . $replyEmail . '>';
        } else if ($replyEmail) {
            $headers['Reply-To'] = $replyEmail;
        }

        $globalHeaders = $headers;

        return $globalHeaders;
    }

    public static function recordCampaignRevenue($campaignId, $amount, $orderId, $currency = 'USD', $isRefunded = false)
    {
        $currency = strtolower($currency);
        $existing = fluentcrm_get_campaign_meta($campaignId, '_campaign_revenue');
        $data = ['orderIds' => []];

        if ($existing && isset($existing->value['orderIds']) && $existing->value['orderIds']) {
            $data['orderIds'] = $existing->value['orderIds'];
            $data[$currency] = $existing->value[$currency];
        } else {
            $data[$currency] = 0;
        }
        if (!in_array($orderId, $data['orderIds'])) {
            $data['orderIds'][] = $orderId;
        }

        if ($isRefunded) {
            if ($data[$currency] > $amount) {
                $data[$currency] -= $amount;
                if (in_array($orderId, $data['orderIds'])) {
                    unset($data['orderIds'][$orderId]);
                }
            }
        } else {
            if ($existing && isset($existing->value['orderIds']) && in_array($orderId, $existing->value['orderIds'])) {
                $data[$currency] = $existing->value[$currency];
            } else {
                $data[$currency] += $amount;
            }
        }

        return fluentcrm_update_campaign_meta($campaignId, '_campaign_revenue', $data);
    }

    public static function getWPMapUserInfo($user)
    {
        if (is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }

        if (!$user) {
            return [];
        }

        $subscriber = array_filter([
            'user_id'    => $user->ID,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->user_email
        ]);

        if ($address1 = get_user_meta($user->ID, 'billing_address_1', true)) {
            $subscriber['address_line_1'] = $address1;
        }

        if ($address2 = get_user_meta($user->ID, 'billing_address_2', true)) {
            $subscriber['address_line_2'] = $address2;
        }

        if ($city = get_user_meta($user->ID, 'billing_city', true)) {
            $subscriber['city'] = $city;
        }

        if ($postalCode = get_user_meta($user->ID, 'billing_postcode', true)) {
            $subscriber['postal_code'] = $postalCode;
        }

        if ($country = get_user_meta($user->ID, 'billing_country', true)) {
            $subscriber['country'] = $country;
        }

        if ($state = get_user_meta($user->ID, 'billing_state', true)) {
            $subscriber['state'] = $state;
        }

        if ($phone = get_user_meta($user->ID, 'billing_phone', true)) {
            $subscriber['phone'] = $phone;
        }

        /**
         * Filter the subscriber data before it is processed.
         *
         * This filter allows you to modify the subscriber data before it is processed.
         *
         * @param array $subscriber The subscriber data.
         * @param object $user The WordPress user object.
         * @since 2.5.3
         *
         */
        $subscriber = apply_filters('fluentcrm_user_map_data', $subscriber, $user);

        $fillables = (new Subscriber)->getFillable();

        $subscriber = Arr::only($subscriber, $fillables);

        return array_filter($subscriber);
    }

    public static function isUserSyncEnabled()
    {
        static $result = null;
        if ($result === null) {
            $settings = fluentcrm_get_option('user_syncing_settings', []);
            $result = $settings && isset($settings['status']) && $settings['status'] == 'yes';
        }

        return $result;
    }

    public static function isContactDeleteOnUserDeleteEnabled()
    {
        static $result = null;
        if ($result === null) {
            $settings = fluentcrm_get_option('user_syncing_settings', []);
            $result = $settings && isset($settings['delete_contact_on_user_delete']) && $settings['delete_contact_on_user_delete'] == 'yes';
        }

        return $result;
    }

    public static function deleteContacts($contactIds)
    {
        if (!$contactIds) {
            return false;
        }
        if (!is_array($contactIds)) {
            $contactIds = (array)$contactIds;
        }

        do_action('fluentcrm_before_subscribers_deleted', $contactIds);
        Subscriber::whereIn('id', $contactIds)->delete();
        do_action('fluentcrm_after_subscribers_deleted', $contactIds);
        return true;
    }

    public static function sendDoubleOptin($contactIds)
    {
        if (!$contactIds) {
            return false;
        }
        if (!is_array($contactIds)) {
            $contactIds = (array)$contactIds;
        }

        $subscribers = Subscriber::whereIn('id', $contactIds)->where('status', 'pending')->get();
        foreach ($subscribers as $subscriber) {
            $subscriber->sendDoubleOptinEmail();
        }
        return true;
    }

    public static function hasComplianceText($text)
    {
        /*
         * @deprecated fluencrm_disable_check_compliance_string since 2.8.33
         * please use fluent_crm/disable_check_compliance_string instead
         * this snippet checks if the email has any compliance text
         * the filter can be used to disable the check such as if filter returns true then it will not check the compliance text
         */

        $result = apply_filters_deprecated('fluencrm_disable_check_compliance_string', [false, $text], '2.8.33', 'fluent_crm/disable_check_compliance_string');
        /**
         * Filters the compliance check string result.
         *
         * This filter allows you to modify the result of the compliance check string.
         *
         * @param mixed $result The result of the compliance check string.
         * @param string $text The text being checked for compliance.
         * @since 2.8.33
         *
         */
        $result = apply_filters('fluent_crm/disable_check_compliance_string', $result, $text);

        if ($result) {
            return true; // directly return true if the filter returns true, would be better if we could return the $result of the filter
        }

        $lookUpTexts = [
            '##crm.manage_subscription_url##',
            '##crm.unsubscribe_url##',
            '{{crm.unsubscribe_html',
            '{{crm.manage_subscription_html',
            '{{crm_global_email_footer}}'
        ];

        foreach ($lookUpTexts as $lookUpText) {
            if (strpos($text, $lookUpText) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function maybeDisableEmojiOnEmail()
    {
        static $disabled;
        if ($disabled) {
            return;
        }
        /**
         * Filter to disable emoji conversion to images in FluentCRM.
         *
         * This filter allows you to disable the conversion of emojis to images.
         * By default, this filter is set to true, meaning the conversion is enabled.
         * You can use this filter to return false if you want to disable the conversion.
         *
         * @param bool Whether to disable emoji conversion to images. Default true.
         * @since 2.7.0
         *
         */
        if (apply_filters('fluent_crm/disable_emoji_to_image', true)) {
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        }
        $disabled = true;
    }

    public static function getPublicLists()
    {
        $emailSettings = self::getGlobalEmailSettings();
        $lists = [];
        $preListType = Arr::get($emailSettings, 'pref_list_type', 'none');
        if ($preListType == 'filtered_only') {
            $prefListItems = Arr::get($emailSettings, 'pref_list_items', []);
            if ($prefListItems) {
                $lists = Lists::whereIn('id', $prefListItems)->get();
                if ($lists->isEmpty()) {
                    return [];
                }
            }
        } else if ($preListType == 'all') {
            $lists = Lists::get();
            if ($lists->isEmpty()) {
                return [];
            }
        }

        return $lists;
    }

    public static function getAdvancedFilterOptions()
    {
        $groups = [
            'subscriber' => [
                'label'    => __('Contact', 'fluent-crm'),
                'value'    => 'subscriber',
                'children' => [
                    [
                        'label' => __('General Properties', 'fluent-crm'),
                        'value' => 'search',
                    ],
                    [
                        'label' => __('First Name', 'fluent-crm'),
                        'value' => 'first_name',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('Last Name', 'fluent-crm'),
                        'value' => 'last_name',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('Email', 'fluent-crm'),
                        'value' => 'email',
                    ],
                    [
                        'label' => __('Address Line 1', 'fluent-crm'),
                        'value' => 'address_line_1',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('Address Line 2', 'fluent-crm'),
                        'value' => 'address_line_2',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('City', 'fluent-crm'),
                        'value' => 'city',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('State', 'fluent-crm'),
                        'value' => 'state',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('Postal Code', 'fluent-crm'),
                        'value' => 'postal_code',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label'             => __('Country', 'fluent-crm'),
                        'value'             => 'country',
                        'type'              => 'selections',
                        'component'         => 'options_selector',
                        'option_key'        => 'countries',
                        'is_multiple'       => true,
                        'is_singular_value' => true
                    ],
                    [
                        'label' => __('Phone', 'fluent-crm'),
                        'value' => 'phone',
                        'type'  => 'nullable_text',
                    ],
                    [
                        'label' => __('WP User ID', 'fluent-crm'),
                        'value' => 'user_id',
                        'type'  => 'numeric',
                    ],
                    [
                        'label'       => __('Name Prefix (Title)', 'fluent-crm'),
                        'value'       => 'prefix',
                        'type'        => 'selections',
                        'options'     => self::getContactPrefixes(true),
                        'is_multiple' => true,
                        'is_only_in'  => true
                    ],
                    [
                        'label' => __('Source', 'fluent-crm'),
                        'value' => 'source'
                    ],
                    [
                        'label' => __('Date of Birth', 'fluent-crm'),
                        'value' => 'date_of_birth',
                        'type'  => 'dates',
                    ],
                    [
                        'label' => __('Last Activity', 'fluent-crm'),
                        'value' => 'last_activity',
                        'type'  => 'dates',
                    ],
                    [
                        'label' => __('Created At', 'fluent-crm'),
                        'value' => 'created_at',
                        'type'  => 'dates',
                    ],
                    [
                        'label' => __('Date of Birth', 'fluent-crm'),
                        'value' => 'date_of_birth',
                        'type'  => 'dates',
                    ],

                ],
            ],
            'segment'    => [
                'label'    => __('Contact Segment', 'fluent-crm'),
                'value'    => 'segment',
                'children' => [
                    [
                        'label'             => __('Status', 'fluent-crm'),
                        'value'             => 'status',
                        'type'              => 'selections',
                        'component'         => 'options_selector',
                        'option_key'        => 'statuses',
                        'is_multiple'       => true,
                        'is_singular_value' => true
                    ],
                    [
                        'label'             => __('Type', 'fluent-crm'),
                        'value'             => 'contact_type',
                        'type'              => 'selections',
                        'component'         => 'options_selector',
                        'option_key'        => 'contact_types',
                        'is_multiple'       => false,
                        'is_singular_value' => true
                    ],
                    [
                        'label'       => __('Tags', 'fluent-crm'),
                        'value'       => 'tags',
                        'type'        => 'selections',
                        'component'   => 'options_selector',
                        'option_key'  => 'tags',
                        'is_multiple' => true,
                    ],
                    [
                        'label'       => __('Lists', 'fluent-crm'),
                        'value'       => 'lists',
                        'type'        => 'selections',
                        'component'   => 'options_selector',
                        'option_key'  => 'lists',
                        'is_multiple' => true,
                    ],
                    [
                        'label'             => __('WP User Role', 'fluent-crm'),
                        'value'             => 'user_role',
                        'type'              => 'selections',
                        'component'         => 'options_selector',
                        'option_key'        => 'user_roles_options',
                        'is_multiple'       => false,
                        'is_singular_value' => true,
                        'help'              => 'Filter by user role, please make sure your users are synced with your FluentCRM contacts'
                    ],
                ],
            ],
            'activities' => [
                'label'    => __('Contact Activities', 'fluent-crm'),
                'value'    => 'activities',
                'children' => [
                    [
                        'label' => __('Last Email Sent', 'fluent-crm'),
                        'value' => 'email_sent',
                        'type'  => 'dates',
                    ],
                    [
                        'label' => __('Last Email Open', 'fluent-crm'),
                        'value' => 'email_opened',
                        'type'  => 'dates',
                        'help'  => 'Please note that, some email clients send false-positive for email open pixel tracking so it may not 100% correct.'
                    ],
                    [
                        'label' => __('Last Email Clicked', 'fluent-crm'),
                        'value' => 'email_link_clicked',
                        'type'  => 'dates',
                    ],
                    [
                        'label'              => __('Campaign Email -', 'fluent-crm'),
                        'value'              => 'campaign_email_activity',
                        'type'               => 'selections',
                        'component'          => 'ajax_selector',
                        'option_key'         => 'campaigns',
                        'is_multiple'        => false,
                        'custom_operators'   => [
                            'clicked'     => 'link clicked',
                            'not_clicked' => 'did not click',
                            'open'        => 'opened',
                            'no_open'     => 'did not open yet',
                            'in'          => 'in (email sent)',
                            'not_in'      => 'not in (regardless of status)'
                        ],
                        'experimental_cache' => true,
                        'help'               => 'This will get only the contacts who got email in the selected campaign and then filter by email open/link clicked or not. <br />Please note that, some email clients send false-positive for email open pixel tracking so it may not 100% correct.'
                    ],
                    [
                        'label'              => __('Automation Activity -', 'fluent-crm'),
                        'value'              => 'automation_activity',
                        'type'               => 'selections',
                        'component'          => 'ajax_selector',
                        'option_key'         => 'funnels',
                        'is_multiple'        => false,
                        'custom_operators'   => [
                            'completed' => 'status completed',
                            'active'    => 'status active',
                            'cancelled' => 'status cancelled',
                            'waiting'   => 'status waiting',
                            'in'        => 'in (regardless of status)',
                            'not_in'    => 'not in (regardless of status)'
                        ],
                        'experimental_cache' => true,
                        'help'               => 'You can filter your contacts based on activity in a specific automation funnel.'
                    ],
                    [
                        'label'              => __('Email Sequence Activity -', 'fluent-crm'),
                        'value'              => 'email_sequence_activity',
                        'type'               => 'selections',
                        'component'          => 'ajax_selector',
                        'option_key'         => 'email_sequences',
                        'is_multiple'        => false,
                        'custom_operators'   => [
                            'completed' => 'status completed',
                            'active'    => 'status active',
                            'cancelled' => 'status cancelled',
                            'in'        => 'in (regardless of status)',
                            'not_in'    => 'not in (regardless of status)'
                        ],
                        'experimental_cache' => true,
                        'help'               => 'You can filter your contacts based on activity in a specific email sequences.'
                    ]
                ]
            ]
        ];

        if (self::isCompanyEnabled()) {
            $groups['segment']['children'][] = [
                'label'              => __('Company', 'fluent-crm'),
                'value'              => 'companies',
                'type'               => 'selections',
                'component'          => 'ajax_selector',
                'option_key'         => 'companies',
                'is_multiple'        => true,
                'is_singular_value'  => true,
                'experimental_cache' => true
            ];
            $groups['segment']['children'][] = [
                'label'              => __('Company - Industry', 'fluent-crm'),
                'value'              => 'company_industry',
                'type'               => 'selections',
                'component'          => 'ajax_selector',
                'option_key'         => 'company_industries',
                'is_multiple'        => true,
                'is_singular_value'  => true,
                'experimental_cache' => true
            ];
            $groups['segment']['children'][] = [
                'label'              => __('Company - Type', 'fluent-crm'),
                'value'              => 'company_type',
                'type'               => 'selections',
                'component'          => 'ajax_selector',
                'option_key'         => 'company_types',
                'is_multiple'        => true,
                'is_singular_value'  => true,
                'experimental_cache' => true
            ];
        }

        if ($customFields = fluentcrm_get_custom_contact_fields()) {
            // form data for custom fields in groups
            $children = [];
            foreach ($customFields as $field) {
                $item = [
                    'label' => $field['label'],
                    'value' => $field['slug'],
                    'type'  => $field['type'],
                ];

                if ($item['type'] == 'number') {
                    $item['type'] = 'numeric';
                } else if ($item['type'] == 'date') {
                    $item['type'] = 'dates';
                    $item['date_type'] = 'date';
                    $item['value_format'] = 'yyyy-MM-dd';
                } else if ($item['type'] == 'date_time') {
                    $item['type'] = 'dates';
                    $item['has_time'] = 'yes';
                    $item['date_type'] = 'datetime';
                    $item['value_format'] = 'yyyy-MM-dd HH:mm:ss';
                } else if (isset($field['options'])) {
                    $item['type'] = 'selections';
                    $options = $field['options'];
                    $formattedOptions = [];
                    foreach ($options as $option) {
                        $formattedOptions[$option] = $option;
                    }
                    $item['options'] = $formattedOptions;
                    $isMultiple = in_array($field['type'], ['checkbox', 'select-multi']);
                    $item['is_multiple'] = $isMultiple;
                    if ($isMultiple) {
                        $item['is_singular_value'] = true;
                    }

                } else {
                    $item['type'] = 'text';
                }

                $children[] = $item;

            }

            $groups['custom_fields'] = [
                'label'    => __('Custom Fields', 'fluent-crm'),
                'value'    => 'custom_fields',
                'children' => $children
            ];
        }

        if (!defined('FLUENTCAMPAIGN')) {
            $disabled = true;
            if (defined('WC_PLUGIN_FILE')) {
                $groups['woo'] = [
                    'label'    => __('WooCommerce', 'fluent-crm'),
                    'value'    => 'woo',
                    'children' => [
                        [
                            'value'    => 'total_order_count',
                            'label'    => __('Total Order Count (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => true
                        ],
                        [
                            'value'    => 'total_order_value',
                            'label'    => __('Total Order value (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => true
                        ],
                        [
                            'value'    => 'last_order_date',
                            'label'    => __('Last Order Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => true
                        ],
                        [
                            'value'    => 'first_order_date',
                            'label'    => __('First Order Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => true
                        ],
                        [
                            'value'       => 'purchased_items',
                            'label'       => __('Purchased Products (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'product_selector',
                            'is_multiple' => true,
                            'disabled'    => true
                        ],
                        [
                            'value'             => 'commerce_exist',
                            'label'             => 'Is a customer? (Pro Required)',
                            'type'              => 'selections',
                            'is_multiple'       => false,
                            'disable_values'    => true,
                            'value_description' => 'This filter will check if a contact has at least one shop order or not',
                            'custom_operators'  => [
                                'exist'     => 'Yes',
                                'not_exist' => 'No',
                            ],
                            'disabled'          => true
                        ]
                    ],
                ];
            }

            if (class_exists('\Easy_Digital_Downloads')) {
                $groups['edd'] = [
                    'label'    => __('EDD', 'fluent-crm'),
                    'value'    => 'edd',
                    'children' => [
                        [
                            'value'    => 'total_order_count',
                            'label'    => __('Total Order Count (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => true
                        ],
                        [
                            'value'    => 'total_order_value',
                            'label'    => __('Total Order Value (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => true
                        ],
                        [
                            'value'    => 'last_order_date',
                            'label'    => __('Last Order Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => true
                        ],
                        [
                            'value'    => 'first_order_date',
                            'label'    => __('First Order Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => true
                        ],
                        [
                            'value'       => 'purchased_items',
                            'label'       => __('Purchased Products (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'product_selector',
                            'is_multiple' => true,
                            'disabled'    => true
                        ],
                    ],
                ];
            }

            if (class_exists('\Affiliate_WP')) {
                $groups['aff_wp'] = [
                    'label'    => 'AffiliateWP',
                    'value'    => 'aff_wp',
                    'children' => [
                        [
                            'value'    => 'is_affiliate',
                            'label'    => __('Is Affiliate (Pro Required)', 'fluent-crm'),
                            'type'     => 'single_assert_option',
                            'options'  => [
                                'yes' => __('Yes', 'fluent-crm'),
                                'no'  => __('No', 'fluent-crm')
                            ],
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'affiliate_id',
                            'label'    => __('Affiliate ID (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'referrals',
                            'label'    => __('Total Referrals (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'status',
                            'label'    => __('Status (Pro Required)', 'fluent-crm'),
                            'type'     => 'single_assert_option',
                            'options'  => [
                                'active'   => __('Active', 'fluent-crm'),
                                'inactive' => __('Inactive', 'fluent-crm'),
                                'pending'  => __('Pending', 'fluent-crm')
                            ],
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'earnings',
                            'label'    => __('Earnings (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'unpaid_earnings',
                            'label'    => __('Unpaid Earnings (Pro Required)', 'fluent-crm'),
                            'type'     => 'numeric',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'date_registered',
                            'label'    => __('Registration Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'last_payment_date',
                            'label'    => __('Last Payout Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => $disabled
                        ]
                    ]
                ];
            }

            if (defined('LEARNDASH_VERSION')) {
                $groups['learndash'] = [
                    'label'    => __('LearnDash', 'fluent-crm'),
                    'value'    => 'learndash',
                    'children' => [
                        [
                            'value'    => 'last_order_date',
                            'label'    => __('Last Enrollment Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'first_order_date',
                            'label'    => __('First Enrollment Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => $disabled
                        ],
                        [
                            'value'       => 'purchased_items',
                            'label'       => __('Enrollment Courses (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'product_selector',
                            'is_multiple' => true,
                            'disabled'    => $disabled
                        ],
                        [
                            'value'        => 'purchased_groups',
                            'label'        => __('Enrollment Groups (Pro Required)', 'fluent-crm'),
                            'type'         => 'selections',
                            'component'    => 'product_selector',
                            'is_multiple'  => true,
                            'extended_key' => 'groups',
                            'disabled'     => $disabled,
                            'options'      => []
                        ],
                        [
                            'value'       => 'purchased_categories',
                            'label'       => __('Enrollment Categories (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'tax_selector',
                            'taxonomy'    => 'ld_course_category',
                            'is_multiple' => true,
                            'disabled'    => $disabled
                        ],
                        [
                            'value'       => 'purchased_tags',
                            'label'       => __('Enrollment Tags (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'tax_selector',
                            'taxonomy'    => 'ld_course_tag',
                            'is_multiple' => true,
                            'disabled'    => $disabled
                        ]
                    ]
                ];
            }

            if (defined('LLMS_PLUGIN_FILE')) {
                $groups['lifterlms'] = [
                    'label'    => __('LifterLMS', 'fluent-crm'),
                    'value'    => 'lifterlms',
                    'children' => [
                        [
                            'value'    => 'last_order_date',
                            'label'    => __('Last Enrollment Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => $disabled
                        ],
                        [
                            'value'    => 'first_order_date',
                            'label'    => __('First Enrollment Date (Pro Required)', 'fluent-crm'),
                            'type'     => 'dates',
                            'disabled' => $disabled
                        ],
                        [
                            'value'       => 'purchased_items',
                            'label'       => __('Enrollment Courses (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'product_selector',
                            'is_multiple' => true,
                            'disabled'    => $disabled
                        ],
                        [
                            'value'        => 'purchased_groups',
                            'label'        => __('Enrollment Memberships (Pro Required)', 'fluent-crm'),
                            'type'         => 'selections',
                            'component'    => 'product_selector',
                            'extended_key' => 'groups',
                            'is_multiple'  => true,
                            'disabled'     => $disabled
                        ],
                        [
                            'value'       => 'purchased_categories',
                            'label'       => __('Enrollment Categories (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'tax_selector',
                            'taxonomy'    => 'course_cat',
                            'is_multiple' => true,
                            'disabled'    => $disabled
                        ],
                        [
                            'value'       => 'purchased_tags',
                            'label'       => __('Enrollment Tags (Pro Required)', 'fluent-crm'),
                            'type'        => 'selections',
                            'component'   => 'tax_selector',
                            'taxonomy'    => 'course_tag',
                            'is_multiple' => true,
                            'disabled'    => $disabled
                        ]
                    ],
                ];
            }
        }

        /**
         * Filter the advanced filter options for FluentCRM.
         *
         * This filter allows modification of the advanced filter options used in FluentCRM.
         *
         * @param array $groups The current filter options.
         * @since 2.5.1
         *
         */
        $groups = apply_filters('fluentcrm_advanced_filter_options', $groups);

        return array_values($groups);
    }

    public static function getComplianceSettings()
    {
        $defaults = [
            'anonymize_ip'           => 'no',
            'delete_contact_on_user' => 'no',
            'personal_data_export'   => 'yes',
            'one_click_unsubscribe'  => 'no',
            'enable_gravatar'        => 'no',
            'gravatar_fallback'      => 'no',
        ];

        $settings = get_option('_fluentcrm_compliance_settings', []);

        return wp_parse_args($settings, $defaults);
    }

    public static function getSiteUrl($path = '', $scheme = null)
    {
        return site_url($path, $scheme);
    }

    public static function isExperimentalEnabled($module)
    {
        $settings = self::getExperimentalSettings();
        return Arr::get($settings, $module) === 'yes';
    }

    public static function getExperimentalSettings()
    {
        static $settings;
        if ($settings) {
            return $settings;
        }

        $defaults = [
            'quick_contact_navigation' => 'yes',
            'campaign_archive'         => 'no',
            'campaign_group_by_month'  => 'no',
            'campaign_search'          => '',
            'campaign_max_number'      => 50,
            'campaign_ids'             => [],
            'campaign_status'          => 'archived',
            'classic_date_time'        => 'no',
            'full_navigation'          => 'no',
            'company_module'           => 'no',
            'company_auto_logo'        => 'no',
            'disable_visual_ai'        => 'no',
            'multi_threading_emails'   => 'no',
            'system_logs'              => 'no',
            'event_tracking'           => 'no',
            'abandoned_cart'           => 'no',
            'activity_log'             => 'no'
        ];

        $settings = get_option('_fluentcrm_experimental_settings', []);

        if (!$settings || !is_array($settings)) {
            $settings = $defaults;
            return $settings;
        }

        $settings = wp_parse_args($settings, $defaults);

        return $settings;
    }

    public static function willMultiThreadEmail($minPendingLimit = 300)
    {
        if (!self::isExperimentalEnabled('multi_threading_emails')) {
            return false;
        }

        $rowcount = self::getUpcomingEmailCount();

        return $rowcount >= $minPendingLimit;
    }

    public static function getUpcomingEmailCount()
    {
        global $wpdb;
        return $wpdb->get_var("SELECT count(*) as aggregate FROM `{$wpdb->prefix}fc_campaign_emails` WHERE `status` IN ('pending', 'scheduled') AND `scheduled_at` <= '" . current_time('mysql') . "'");
    }

    public static function sanitizeHtml($html)
    {
        if (!$html) {
            return $html;
        }

        // Return $html if it's just a plain text
        if (!preg_match('/<[^>]*>/', $html)) {
            return $html;
        }

        $tags = wp_kses_allowed_html('post');
        $tags['style'] = [
            'types' => [],
        ];
        // iframe
        $tags['iframe'] = [
            'width'           => [],
            'height'          => [],
            'src'             => [],
            'srcdoc'          => [],
            'title'           => [],
            'frameborder'     => [],
            'allow'           => [],
            'class'           => [],
            'id'              => [],
            'allowfullscreen' => [],
            'style'           => [],
        ];
        //button
        $tags['button']['onclick'] = [];

        //svg
        if (empty($tags['svg'])) {
            $svg_args = [
                'svg'   => [
                    'class'           => true,
                    'aria-hidden'     => true,
                    'aria-labelledby' => true,
                    'role'            => true,
                    'xmlns'           => true,
                    'width'           => true,
                    'height'          => true,
                    'viewbox'         => true,
                ],
                'g'     => ['fill' => true],
                'title' => ['title' => true],
                'path'  => [
                    'd'         => true,
                    'fill'      => true,
                    'transform' => true,
                ],
            ];
            $tags = array_merge($tags, $svg_args);
        }

        /**
         * Filter the allowed HTML tags.
         *
         * This filter allows modification of the HTML tags that are allowed.
         *
         * @param array $tags An array of allowed HTML tags.
         * @since 2.7.0
         *
         */
        $tags = apply_filters('fluent_crm/allowed_html_tags', $tags);

        return wp_kses($html, $tags);
    }

    public static function hasConditionOnString($string)
    {
        return strpos($string, 'conditional-group') || strpos($string, 'fc-cond-blocks') || strpos($string, 'fc_vis_cond');
    }

    public static function getEmailFooterContent($campaign = null)
    {
        if ($campaign && isset($campaign->settings)) {

            if (Arr::get($campaign->settings, 'is_transactional') == 'yes') {
                return '';
            }

            $customFooter = Arr::get($campaign->settings, 'footer_settings.custom_footer');
            $emailFooter = Arr::get($campaign->settings, 'footer_settings.footer_content');

            if ($customFooter === 'yes' && $emailFooter) {
                return $emailFooter;
            }
        }

        return Arr::get(self::getGlobalEmailSettings(), 'email_footer', '');
    }

    public static function isCompanyEnabled()
    {
        return self::isExperimentalEnabled('company_module');
    }

    public static function companyCategories()
    {
        /**
         * Filter the list of company categories.
         *
         * This filter allows modification of the company categories list.
         *
         * @param array An array of company categories.
         * @since 2.8.0
         *
         */
        return apply_filters('fluent_crm/company_categories', [
            'Accounting',
            'Airlines/Aviation',
            'Alternative Dispute Resolution',
            'Alternative Medicine',
            'Animation',
            'Apparel & Fashion',
            'Architecture & Planning',
            'Arts and Crafts',
            'Automotive',
            'Aviation & Aerospace',
            'Banking',
            'Biotechnology',
            'Broadcast Media',
            'Building Materials',
            'Business Supplies and Equipment',
            'Capital Markets',
            'Chemicals',
            'Civic & Social Organization',
            'Civil Engineering',
            'Commercial Real Estate',
            'Computer & Network Security',
            'Computer Games',
            'Computer Hardware',
            'Computer Networking',
            'Computer Software',
            'Internet',
            'Construction',
            'Consumer Electronics',
            'Consumer Goods',
            'Consumer Services',
            'Cosmetics',
            'Dairy',
            'Defense & Space',
            'Design',
            'Education Management',
            'E-Learning',
            'Electrical/Electronic Manufacturing',
            'Entertainment',
            'Environmental Services',
            'Events Services',
            'Executive Office',
            'Facilities Services',
            'Farming',
            'Financial Services',
            'Fine Art',
            'Fishery',
            'Food & Beverages',
            'Food Production',
            'Fund-Raising',
            'Furniture',
            'Gambling & Casinos',
            'Glass, Ceramics & Concrete',
            'Government Administration',
            'Government Relations',
            'Graphic Design',
            'Health, Wellness and Fitness',
            'Higher Education',
            'Hospital & Health Care',
            'Hospitality',
            'Human Resources',
            'Import and Export',
            'Individual & Family Services',
            'Industrial Automation',
            'Information Services',
            'Information Technology and Services',
            'Insurance',
            'International Affairs',
            'International Trade and Development',
            'Investment Banking',
            'Investment Management',
            'Judiciary',
            'Law Enforcement',
            'Law Practice',
            'Legal Services',
            'Legislative Office',
            'Leisure, Travel & Tourism',
            'Libraries',
            'Logistics and Supply Chain',
            'Luxury Goods & Jewelry',
            'Machinery',
            'Management Consulting',
            'Maritime',
            'Market Research',
            'Marketing and Advertising',
            'Mechanical or Industrial Engineering',
            'Media Production',
            'Medical Devices',
            'Medical Practice',
            'Mental Health Care',
            'Military',
            'Mining & Metals',
            'Motion Pictures and Film',
            'Museums and Institutions',
            'Music',
            'Nanotechnology',
            'Newspapers',
            'Non-Profit Organization Management',
            'Oil & Energy',
            'Online Media',
            'Outsourcing/Offshoring',
            'Package/Freight Delivery',
            'Packaging and Containers',
            'Paper & Forest Products',
            'Performing Arts',
            'Pharmaceuticals',
            'Philanthropy',
            'Photography',
            'Plastics',
            'Political Organization',
            'Primary/Secondary Education',
            'Printing',
            'Professional Training & Coaching',
            'Program Development',
            'Public Policy',
            'Public Relations and Communications',
            'Public Safety',
            'Publishing',
            'Railroad Manufacture',
            'Ranching',
            'Real Estate',
            'Recreational Facilities and Services',
            'Religious Institutions',
            'Renewables & Environment',
            'Research',
            'Restaurants',
            'Retail',
            'Security and Investigations',
            'Semiconductors',
            'Shipbuilding',
            'Sporting Goods',
            'Sports',
            'Staffing and Recruiting',
            'Supermarkets',
            'Telecommunications',
            'Textiles',
            'Think Tanks',
            'Tobacco',
            'Translation and Localization',
            'Transportation/Trucking/Railroad',
            'Utilities',
            'Venture Capital & Private Equity',
            'Veterinary',
            'Warehousing',
            'Wholesale',
            'Wine and Spirits',
            'Wireless',
            'Writing and Editing'
        ]);
    }

    public static function companyTypes()
    {
        /**
         * Filter the list of company types.
         *
         * This filter allows modification of the company types array.
         *
         * @param array An array of company types.
         * @since 2.8.0
         *
         */
        return apply_filters('fluent_crm/company_types', [
            'Prospect',
            'Partner',
            'Reseller',
            'Vendor',
            'Other'
        ]);
    }

    public static function getCompanyProfileSections()
    {
        $sections = [
            'overview'   => [
                'name'    => 'view_company',
                'title'   => __('Contacts', 'fluent-crm'),
                'handler' => 'route'
            ],
            'activities' => [
                'name'    => 'company_activities',
                'title'   => __('Notes & Activities', 'fluent-crm'),
                'handler' => 'route'
            ],
        ];

        /**
         * Filter the company profile sections.
         *
         * This filter allows modification of the company profile sections.
         *
         * @param array The array of company profile sections.
         * @since 2.8.0
         *
         */
        return apply_filters('fluent_crm/company_profile_sections', $sections);
    }

    public static function maybeParseAndFilterWebhookData(Webhook $webhook, $postData, $key)
    {
        $data = Arr::get($webhook->value, $key, []);
        if (!empty($postData[$key])) {
            $postedData = Arr::get($postData, $key, []);

            if (is_string($postedData)) {
                $postedData = explode(',', $postedData);
                $postedData = map_deep($postedData, 'intval');
            }

            $newData = [];
            foreach ($postedData as $item) {
                if (is_numeric($item)) {
                    $newData[] = $item;
                }
            }

            if (!empty($newData)) {
                $data = $newData;
            }

            $data = array_filter($data);
        }

        return $data;
    }

    public static function getNoteSyncFields()
    {
        $fields = array(
            'type'        => array(
                'type'    => 'input-option',
                'label'   => __('Type', 'fluent-crm'),
                'id'      => 'fc_note_type',
                'name'    => 'type',
                'options' => fluentcrm_activity_types()
            ),
            'created_at'  => array(
                'type'         => 'input-date',
                'data_type'    => 'datetime',
                'name'         => 'created_at',
                'label'        => __('Date Time', 'fluent-crm'),
                'id'           => 'fc_note_title',
                'value_format' => 'yyyy-MM-dd HH:mm:ss',
                'help'         => __('keep blank for current time', 'fluent-crm')
            ),
            'title'       => array(
                'type'        => 'input-text',
                'name'        => 'title',
                'label'       => __('Title', 'fluent-crm'),
                'id'          => 'fc_note_title',
                'placeholder' => __('Your Note Title', 'fluent-crm')
            ),
            'description' => array(
                'type'  => 'wp-editor',
                'name'  => 'description',
                'label' => __('Description', 'fluent-crm'),
                'id'    => 'fc_note_desc'
            ),
        );

        /**
         * Filter the contact note fields.
         *
         * This filter allows modification of the contact note fields.
         *
         * @param array $fields The contact note fields.
         * @since 2.8.40
         *
         */
        return apply_filters('fluent_crm/contact_note_fields', $fields);
    }

    public static function debugLog($title, $description = '', $type = 'info')
    {
        static $isEnabled = null;

        if ($isEnabled === null) {
            $isEnabled = (defined('FLUENT_CRM_DEBUG_LOG') && FLUENT_CRM_DEBUG_LOG) || self::isExperimentalEnabled('system_logs');
        }

        if (!$isEnabled) {
            return null;
        }

        if (!is_string($description)) {
            $description = json_encode($description);
        }

        return SystemLog::create([
            'title'       => sanitize_text_field($title),
            'description' => wp_kses_post($description)
        ]);
    }

    public static function getNextMinuteTaskTimeStamp()
    {
        $lastRunAt = fluentCrmGetOptionCache('_fcrm_last_scheduler');

        if ($lastRunAt) {
            $nextRun = $lastRunAt + 60;
        } else {
            $nextRun = as_next_scheduled_action('fluentcrm_scheduled_every_minute_tasks');
        }

        if ($nextRun === true || !$nextRun) {
            $nextRun = time() + 60;
        }

        return $nextRun;
    }

    public static function isWooHposEnabled()
    {
        static $enabled = null;
        if ($enabled !== null) {
            return $enabled;
        }

        $enabled = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

        return $enabled;
    }

    public static function searchWPUsers($searchQuery, $limit = 20)
    {
        $search = sanitize_text_field($searchQuery);

        // Search by user login, email, and nicename
        $args = array(
            'role__not_in' => array('Administrator'),
            'search'       => '*' . $search . '*',
            'number'       => $limit
        );

        // Get users by login, email, and nicename
        $user_query = new \WP_User_Query($args);
        $users_by_login = $user_query->get_results();
        $users = array_unique($users_by_login, SORT_REGULAR);

        return $users;
    }

    public static function latestListIdOfSubscriber($contactId)
    {
        $listId = SubscriberPivot::where('subscriber_id', $contactId)
            ->where('object_type', 'FluentCrm\App\Models\Lists')
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->value('object_id');

        return $listId;
    }

    public static function createNewTags($tagsArray)
    {
        $tags = [];
        foreach ($tagsArray as $tag) {
            $tag = sanitize_text_field($tag);
            //if that tag already exists then I need only it's id
            $sameTag = Tag::where('title', $tag)->first();
            if ($sameTag) {
                $tags[] = $sameTag->id;
                continue;
            }

            $tagModel = Helper::createTag($tag);

            if ($tagModel) {
                $tags[] = $tagModel->id;
            }
        }

        return $tags;
    }

    public static function createNewLists($listsArray)
    {
        $lists = [];
        foreach ($listsArray as $list) {
            $list = sanitize_text_field($list);
            //if that list already exists then I need only it's id
            $sameList = Lists::where('title', $list)->first();
            if ($sameList) {
                $lists[] = $sameList->id;
                continue;
            }

            $listModel = Helper::createList($list);

            if ($listModel) {
                $lists[] = $listModel->id;
            }
        }

        return $lists;
    }

    public static function getNewAttachableLists($listsArray, $currentListIds, $ListsForAllContacts)
    {
        $listIds = [];

        foreach ($listsArray as $listTitle) {
            $listTitle = sanitize_text_field($listTitle);

            $existinglist = Lists::where('title', $listTitle)->first();
            if ($existinglist) {
                if (!in_array($existinglist->id, $currentListIds) && !in_array($existinglist->id, $ListsForAllContacts)) {
                    //if that existing list is not already in user's list and not in those lists that will be applied to all subscribers
                    $listIds[] = $existinglist->id;
                }
            } else {
                $newList = Helper::createList($listTitle);
                $listIds[] = $newList->id;
            }
        }

        return $listIds;
    }

    public static function getNewAttachableTags($tagsArray, $currentTagIds, $TagsForAllContacts)
    {
        $tagIds = [];

        foreach ($tagsArray as $tagTitle) {
            $tagTitle = sanitize_text_field($tagTitle);

            $existingTag = Tag::where('title', $tagTitle)->first();
            if ($existingTag) {
                if (!in_array($existingTag->id, $currentTagIds) && !in_array($existingTag->id, $TagsForAllContacts)) {
                    //if that existing tag is not already in user's tag and not in those tags that will be applied to all subscribers
                    $tagIds[] = $existingTag->id;
                }
            } else {
                $newList = Helper::createTag($tagTitle);
                $tagIds[] = $newList->id;
            }
        }

        return $tagIds;
    }

    private static function createList($listTitle) {
        $baseSlug = Str::slug($listTitle);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure unique slug
        while (Lists::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return Lists::create(
            [
                'title' => $listTitle,
                'slug' => $slug
            ]
        );
    }

    private static function createTag($tagTitle) {
        $baseSlug = Str::slug($tagTitle);
        $slug = $baseSlug;
        $counter = 1;

        // Ensure unique slug
        while (Tag::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return Tag::create(
            [
                'title' => $tagTitle,
                'slug' => $slug
            ]
        );
    }

    /**
     * Converts text into a URL-friendly slug, handling Latin and non-Latin scripts.
     *
     * @param string $text Input text to slugify
     * @param string $fallback Fallback slug if input is empty or invalid
     * @return string Sanitized slug
     */
    public static function slugify($text, $fallback = '')
    {
        // Normalize input: cast to string and trim whitespace
        $text = trim((string) $text);

        // Handle empty input
        if (empty($text)) {
            return sanitize_title($fallback ?: self::generateUniqueId(), $fallback);
        }

        // Process as Latin-based text
        $slug = remove_accents($text); // Convert accents (e.g., é → e)
        $slug = strtolower($slug); // Convert to lowercase
        $slug = preg_replace('/[^a-z0-9\-_]/', '-', $slug); // Replace non-alphanumeric with dashes
        $slug = preg_replace('/[\-_]{2,}/', '-', $slug); // Collapse multiple dashes/underscores
        $slug = trim($slug, '-_'); // Trim leading/trailing dashes/underscores

        // Check for empty result or non-Latin scripts
        if (empty($slug) || preg_match('/[^\p{Latin}\p{N}\-_ ]/u', $text)) {
            $slug = self::generateUniqueId();
        }

        // Final cleanup with WordPress sanitize_title
        return sanitize_title($slug, $fallback);
    }

    /**
     * Generates a unique, hyphenated identifier (~11-12 characters).
     *
     * @return string Unique ID, e.g., '6f1a2-xyz12'
     */
    public static function generateUniqueId()
    {
        return sprintf('%s-%s', substr(uniqid(), -5), wp_generate_password(5, false, false));
    }
}
