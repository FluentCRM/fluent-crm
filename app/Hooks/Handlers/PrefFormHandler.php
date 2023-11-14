<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

/**
 *  Integrations Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 2.5.94
 */
class PrefFormHandler
{
    public function handleShortCode($atts, $noContactContent = '')
    {

        if (isset($_REQUEST['_fc_secure_hash'])) {
            $hash = sanitize_text_field($_REQUEST['_fc_secure_hash']);
            if ($hash) {
                $_COOKIE['fc_hash_secure'] = $hash;
            }
        }

        $settings = Helper::getGlobalEmailSettings();

        if (Arr::get($settings, 'pref_form') != 'yes' || empty(Arr::get($settings, 'pref_general'))) {
            return '';
        }

        do_action('fluent_crm/rendering_pref_form_shortcode');

        $subscriber = FluentCrmApi('contacts')->getCurrentContact(true, true);

        if (!$subscriber) {
            return $noContactContent;
        }

        $labels = apply_filters('fluent_crm/pref_labels', [
            'first_name'      => __('First Name', 'fluent-crm'),
            'last_name'       => __('Last Name', 'fluent-crm'),
            'prefix'          => __('Title', 'fluent-crm'),
            'email'           => __('Email', 'fluent-crm'),
            'phone'           => __('Phone/Mobile', 'fluent-crm'),
            'dob'             => __('Date of Birth', 'fluent-crm'),
            'address_line_1'  => __('Address Line 1', 'fluent-crm'),
            'address_line_2'  => __('Address Line 2', 'fluent-crm'),
            'city'            => __('City', 'fluent-crm'),
            'state'           => __('State', 'fluent-crm'),
            'postal_code'     => __('ZIP Code', 'fluent-crm'),
            'country'         => __('Country', 'fluent-crm'),
            'update'          => __('Update info', 'fluent-crm'),
            'address_heading' => __('Address Information', 'fluent-crm'),
            'list_label'      => __('Mailing List Groups', 'fluent-crm'),
        ]);

        $formFields = $this->getFormFields($settings, $subscriber, $labels, false);

        $listOptions = [];
        $lists = Helper::getPublicLists();
        if ($lists) {
            foreach ($lists as $list) {
                $listOptions[strval($list->id)] = $list->title;
            }

            $formattedLists = [];
            foreach ($subscriber->lists as $list) {
                $formattedLists[] = $list->id;
            }

            $formFields['lists'] = [
                'type'            => 'checkboxes',
                'name'            => 'lists',
                'container_class' => 'fc_inline_checkboxes',
                'options'         => $listOptions,
                'value'           => $formattedLists,
                'id'              => 'mailing_lists',
                'label'           => Arr::get($labels, 'list_label', 'Mailing List Groups'),
            ];
        }

        $formFields = apply_filters('fluent_crm/pref_form_fields', $formFields, $subscriber);

        $formFields[] = [
            'type' => 'hidden',
            'atts' => [
                'name'  => 'action',
                'value' => 'fluent_crm_account_form'
            ]
        ];

        if (isset($_REQUEST['_fc_secure_hash'])) {
            $hash = sanitize_text_field($_REQUEST['_fc_secure_hash']);
            if($hash) {
                $formFields[] = [
                    'type' => 'hidden',
                    'atts' => [
                        'name'  => '_fc_hash_secure',
                        'value' => $hash
                    ]
                ];
            }
        }

        wp_enqueue_style(
            'fluentcrm_public_pref',
            FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.css',
            [],
            FLUENTCRM_PLUGIN_VERSION
        );

        wp_enqueue_script('fluentcrm_public_pref', FLUENTCRM_PLUGIN_URL . 'assets/public/public_pref.js', ['jquery'], FLUENTCRM_PLUGIN_VERSION, true);

        wp_localize_script('fluentcrm_public_pref', 'fluentcrm_sub_pref', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);

        return fluentCrm('view')->make('external.pref_form', [
            'fields'     => $formFields,
            'submitBtn'  => [
                'container_class' => 'fc_pref_submit',
                'btn_text'        => __('Update info', 'fluent-crm'),
                'atts'            => [
                    'type'  => 'submit',
                    'id'    => 'fluentcrm_preferences_submit',
                    'class' => 'btn fc_pref_submit'
                ]
            ],
            'subscriber' => $subscriber
        ]);
    }

    public function handleDynamicContentShortCode($atts, $text = '')
    {
        if(!$text) {
            return '';
        }

        $defaults = [
            'hide_for_guest' => 'no'
        ];

        $atts = shortcode_atts($defaults, $atts, 'fluentcrm_content');

        $subscriber = FluentCrmApi('contacts')->getCurrentContact(true, true);

        if(!$subscriber) {
            if($atts['hide_for_guest'] == 'yes') {
                return '';
            }
            return preg_replace_callback('/({{|##)+(.*?)(}}|##)/', function ($matches) {
                if(isset($matches[2])) {
                    $token = $matches[2];
                    $tokens = explode('|', $token);
                    if(isset($tokens[1])) {
                        return $tokens[1];
                    }
                }
                return '';
            }, $text);
        }

        return \FluentCrm\App\Services\Libs\Parser\Parser::parse($text, $subscriber);
    }

    public function handleAjax()
    {
        if (!isset($_POST['_fc_nonce']) || !wp_verify_nonce($_POST['_fc_nonce'], 'fluent_crm_account_form_fields')) {
            wp_send_json_error([
                'message' => 'Sorry, your nonce did not verify.'
            ], 423);
        }

        $settings = Helper::getGlobalEmailSettings();

        if (Arr::get($settings, 'pref_form') != 'yes' || empty(Arr::get($settings, 'pref_general'))) {
            wp_send_json_error([
                'message' => 'Sorry! you can not update your profile'
            ], 423);
        }

        if (isset($_REQUEST['_fc_hash_secure']) && !is_user_logged_in()) {
            $hash = sanitize_text_field($_REQUEST['_fc_hash_secure']);
            if ($hash) {
                $_COOKIE['fc_hash_secure'] = $hash;
            }
        }

        $subscriber = FluentCrmApi('contacts')->getCurrentContact(false, true);

        if (!$subscriber) {
            wp_send_json_error([
                'message' => 'Sorry! you can not update your profile'
            ], 423);
        }

        $validInputs = $this->getFormFields($settings, $subscriber, [], true);

        $validKeys = array_keys($validInputs);

        $validData = Arr::only($_REQUEST, $validKeys);
        if (empty($validData['email'])) {
            $validData['email'] = $subscriber->email;
        }

        $errors = [];
        foreach ($validInputs as $key => $input) {
            if (Arr::get($input, 'required') && empty($validData[$key])) {
                $errors[] = $key . ' is required';
            }
            $validData[$key] = sanitize_text_field(Arr::get($validData, $key, ''));
        }

        if ($errors) {
            wp_send_json_error([
                'message' => __('Please fill up all required fields', 'fluent-crm'),
                'errors'  => $errors,
                'inputs'  => $validData
            ], 423);
        }

        $subscriber->fill($validData);

        $updateData = $subscriber->getDirty();

        if($updateData) {
            $subscriber->save();
        }

        if (isset($_REQUEST['lists'])) {
            $publicLists = Helper::getPublicLists();
            $publicListIds = [];
            foreach ($publicLists as $publicList) {
                $publicListIds[] = $publicList->id;
            }

            $selectedListIds = map_deep($_REQUEST['lists'], 'intval');
            $attachLists = [];
            $detachLists = [];

            foreach ($subscriber->lists as $list) {
                if (!in_array($list->id, $publicListIds)) {
                    continue;
                }

                if (!in_array($list->id, $selectedListIds)) {
                    $detachLists[] = $list->id;
                }
            }

            foreach ($selectedListIds as $selectedListId) {
                if (in_array($selectedListId, $publicListIds)) {
                    $attachLists[] = $selectedListId;
                }
            }

            if ($attachLists) {
                $subscriber->attachLists($attachLists);
            }

            if ($detachLists) {
                $subscriber->detachLists($detachLists);
            }

        } else {
            $listIds = $subscriber->lists()->get()->pluck('id')->toArray();
            $subscriber->detachLists($listIds);
        }

        do_action('fluent_crm/pref_form_self_contact_updated', $subscriber, $_REQUEST);


        if ($updateData) {
            /*
            * deprecated
            */
            do_action('fluentcrm_contact_updated', $subscriber, $updateData);

            do_action('fluent_crm/contact_updated', $subscriber, $updateData);
        }

        wp_send_json_success([
            'message' => __('Your information has been updated', 'fluent-crm'),
            'data'    => $validData
        ], 200);

    }

    private function getFormFields($settings, $subscriber, $labels, $inputOnly = false)
    {
        $generalFields = Arr::get($settings, 'pref_general');

        $formFields = [];

        if (array_intersect($generalFields, ['first_name', 'last_name'])) {

            $nameFields = [];

            if (in_array('prefix', $generalFields)) {
                $nameFields['prefix'] = [
                    'type'            => 'select',
                    'name'            => 'prefix',
                    'container_class' => 'fc_name_prefix',
                    'id'              => 'fc_name_prefix',
                    'label'           => Arr::get($labels, 'prefix', 'Prefix'),
                    'placeholder'     => '--',
                    'options'         => Helper::getContactPrefixes(true),
                    'value'           => $subscriber->prefix
                ];
            }

            if (in_array('first_name', $generalFields)) {
                $nameFields['first_name'] = [
                    'type'     => 'input',
                    'name'     => 'first_name',
                    'id'       => 'fc_first_name',
                    'atts'     => [
                        'type'        => 'text',
                        'placeholder' => __('First Name', 'fluent-crm')
                    ],
                    'required' => true,
                    'label'    => Arr::get($labels, 'first_name', 'First Name'),
                    'value'    => $subscriber->first_name
                ];
            }

            if (in_array('last_name', $generalFields)) {
                $nameFields['last_name'] = [
                    'type'     => 'input',
                    'name'     => 'last_name',
                    'id'       => 'fc_last_name',
                    'atts'     => [
                        'type'        => 'text',
                        'placeholder' => __('Last Name', 'fluent-crm')
                    ],
                    'required' => true,
                    'label'    => Arr::get($labels, 'last_name', 'Last Name'),
                    'value'    => $subscriber->last_name
                ];
            }

            $formFields['name'] = [
                'type'            => 'container',
                'container_class' => 'fc_names fc_' . count($nameFields) . '_col',
                'fields'          => $nameFields
            ];
        }

        $formFields[] = [
            'type' => 'raw_html',
            'html' => '<div class="fc_2_col fc_email_phone_date">'
        ];

        $formFields['email'] = [
            'type'     => 'input',
            'name'     => 'email',
            'id'       => 'fc_email',
            'atts'     => [
                'type'        => 'email',
                'placeholder' => __('Email', 'fluent-crm'),
                'disabled'    => true
            ],
            'required' => true,
            'label'    => Arr::get($labels, 'email', 'Email'),
            'value'    => $subscriber->email
        ];

        if (in_array('phone', $generalFields)) {
            $formFields['phone'] = [
                'type'     => 'input',
                'name'     => 'phone',
                'id'       => 'fc_phone',
                'atts'     => [
                    'type'        => 'tel',
                    'placeholder' => __('Phone', 'fluent-crm')
                ],
                'required' => false,
                'label'    => Arr::get($labels, 'phone', 'Phone/Mobile'),
                'value'    => $subscriber->phone
            ];
        }

        if (in_array('date_of_birth', $generalFields)) {
            $formFields['date_of_birth'] = [
                'type'     => 'date',
                'name'     => 'date_of_birth',
                'id'       => 'fc_date_of_birth',
                'atts'     => [
                    'type'          => 'text',
                    'data-max-year' => date('Y'),
                    'data-min-year' => date('Y') - 120,
                    'class'         => 'fc_date_item',
                    'data-format'   => 'YYYY-MM-DD',
                    'placeholder'   => __('Date of Birth', 'fluent-crm'),
                    'data-template' => 'DD - MM - YYYY'
                ],
                'required' => false,
                'label'    => Arr::get($labels, 'dob', 'Date of Birth'),
                'value'    => $subscriber->date_of_birth
            ];
        }

        $formFields[] = [
            'type' => 'raw_html',
            'html' => '</div>'
        ];

        if (in_array('address_fields', $generalFields)) {
            $formFields[] = [
                'type' => 'raw_html',
                'html' => '<h4 class="fc_address_info_heading">' . Arr::get($labels, 'address_heading', 'Address Information') . '</h4>'
            ];

            $countryNames = apply_filters('fluent_crm/countries', []);

            $formattedCountries = [];
            foreach ($countryNames as $country) {
                $formattedCountries[$country['code']] = $country['title'];
            }

            $formFields['address'] = [
                'type'            => 'container',
                'container_class' => 'fc_addresses fc_2_col',
                'fields'          => [
                    'address_line_1' => [
                        'type'  => 'input',
                        'name'  => 'address_line_1',
                        'id'    => 'fc_address_line_1',
                        'atts'  => [
                            'type'        => 'text',
                            'placeholder' => __('Address Line 1', 'fluent-crm')
                        ],
                        'label' => Arr::get($labels, 'address_line_1', 'Address Line 1'),
                        'value' => $subscriber->address_line_1
                    ],
                    'address_line_2' => [
                        'type'  => 'input',
                        'name'  => 'address_line_2',
                        'id'    => 'fc_address_line_2',
                        'atts'  => [
                            'type'        => 'text',
                            'placeholder' => __('Address Line 2', 'fluent-crm')
                        ],
                        'label' => Arr::get($labels, 'address_line_2', 'Address Line 2'),
                        'value' => $subscriber->address_line_2
                    ],
                    'city'           => [
                        'type'  => 'input',
                        'name'  => 'city',
                        'id'    => 'fc_address_city',
                        'atts'  => [
                            'type'        => 'text',
                            'placeholder' => __('City', 'fluent-crm')
                        ],
                        'label' => Arr::get($labels, 'city', 'City'),
                        'value' => $subscriber->city
                    ],
                    'state'          => [
                        'type'  => 'input',
                        'name'  => 'state',
                        'id'    => 'fc_address_state',
                        'atts'  => [
                            'type'        => 'text',
                            'placeholder' => __('State', 'fluent-crm')
                        ],
                        'label' => Arr::get($labels, 'state', 'State'),
                        'value' => $subscriber->state
                    ],
                    'postal_code'    => [
                        'type'  => 'input',
                        'name'  => 'postal_code',
                        'id'    => 'fc_address_postal_code',
                        'atts'  => [
                            'type'        => 'text',
                            'placeholder' => __('Zip Code', 'fluent-crm')
                        ],
                        'label' => Arr::get($labels, 'postal_code', 'Zip Code'),
                        'value' => $subscriber->postal_code
                    ],
                    'country'        => [
                        'type'        => 'select',
                        'name'        => 'country',
                        'id'          => 'fc_address_country',
                        'placeholder' => __('Select Country', 'fluent-crm'),
                        'label'       => Arr::get($labels, 'country', 'Country'),
                        'value'       => $subscriber->country,
                        'options'     => $formattedCountries
                    ],
                ]
            ];
        }

        if (!$inputOnly) {
            return $formFields;
        }

        return $this->parseInputs($formFields);
    }

    private function parseInputs($fields)
    {
        $inputFields = [];

        $inputTypes = ['hidden', 'input', 'checkboxes', 'select', 'radio', 'date'];

        foreach ($fields as $inputKey => $field) {
            $type = Arr::get($field, 'type');
            if ($type == 'container') {
                $inputFields = array_merge($this->parseInputs($field['fields']), $inputFields);
            } else if (in_array($type, $inputTypes)) {
                $inputFields[$inputKey] = $field;
            }
        }

        return $inputFields;
    }

}
