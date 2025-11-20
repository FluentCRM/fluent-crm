<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Models\CustomContactField as CustomFields;

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

        /**
         * Determine the preference form labels in FluentCRM.
         *
         * This filter allows modification of the labels used in the preference form.
         *
         * @since 2.5.95
         *
         * @param array {
         *     An associative array of labels.
         *
         *     @type string $first_name      Label for the first name field.
         *     @type string $last_name       Label for the last name field.
         *     @type string $prefix          Label for the title field.
         *     @type string $email           Label for the email field.
         *     @type string $phone           Label for the phone/mobile field.
         *     @type string $dob             Label for the date of birth field.
         *     @type string $address_line_1  Label for the address line 1 field.
         *     @type string $address_line_2  Label for the address line 2 field.
         *     @type string $city            Label for the city field.
         *     @type string $state           Label for the state field.
         *     @type string $postal_code     Label for the ZIP code field.
         *     @type string $country         Label for the country field.
         *     @type string $update          Label for the update info button.
         *     @type string $address_heading Label for the address information section.
         *     @type string $list_label      Label for the mailing list groups section.
         * }
         */
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
            'custom_fields'   => __('Custom Fields', 'fluent-crm')
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

        /**
         * Determine the preference form fields against a subscriber or contact data in FluentCRM.
         *
         * This filter allows modification of the preference form fields before they are displayed.
         *
         * @since 2.5.95
         *
         * @param array $formFields The current form fields.
         * @param object $subscriber The subscriber object.
         * @return array Modified form fields.
         */
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
            ], 422);
        }

        $settings = Helper::getGlobalEmailSettings();

        if (Arr::get($settings, 'pref_form') != 'yes' || empty(Arr::get($settings, 'pref_general'))) {
            wp_send_json_error([
                'message' => 'Sorry! you can not update your profile'
            ], 422);
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
            ], 422);
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
            
            // Handle array values for multi-select and checkboxes
            if (isset($validData[$key]) && is_array($validData[$key])) {
                $validData[$key] = array_map('sanitize_text_field', $validData[$key]);
            } else {
                $validData[$key] = sanitize_text_field(Arr::get($validData, $key, ''));
            }
        }

        if ($errors) {
            wp_send_json_error([
                'message' => __('Please fill up all required fields', 'fluent-crm'),
                'errors'  => $errors,
                'inputs'  => $validData
            ], 422);
        }

        // Handle custom fields
        $enabledCustomFieldSlugs = Arr::get($settings, 'pref_custom', []);
        $allCustomFields = (new CustomFields)->getGlobalFields()['fields'];
        
        if (!empty($allCustomFields) && !empty($enabledCustomFieldSlugs)) {
            foreach ($allCustomFields as $field) {
                $fieldKey = $field['slug'];
                
                // Only process fields that are enabled in pref_custom
                if (!in_array($fieldKey, $enabledCustomFieldSlugs)) {
                    continue;
                }
                
                if (isset($validData[$fieldKey])) {
                    $value = $validData[$fieldKey];
                    
                    // Handle different field types
                    switch ($field['type']) {
                        case 'checkbox':
                            if (is_array($value)) {
                                $value = array_map('sanitize_text_field', $value);
                            }
                            break;
                            

                        case 'select-multi':

                            if (is_array($value)) {
                                $value = array_map('sanitize_text_field', $value);
                            } else {
                                $value = [];
                            }
                            break;

                        case 'number':
                            $value = floatval($value);
                            break;
                            
                        case 'textarea':
                            $value = isset($_POST[$fieldKey]) ? sanitize_textarea_field($_POST[$fieldKey]) : '';
                            break;
                            
                        case 'date':
                            $value = sanitize_text_field($value);
                            break;
                            
                        default:
                            $value = sanitize_text_field($value);
                    }
                    
                    // Update the meta with proper type
                    $subscriber->updateMeta($field['slug'], $value, 'custom_field');
                    unset($validData[$fieldKey]); // Remove from main data
                }
            }
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
        $customFields = Arr::get($settings, 'pref_custom');

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
                    'data-max-year' => gmdate('Y'),
                    'data-min-year' => gmdate('Y') - 120,
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

            /**
             * Filter to modify the list of country names for the Preference Form Field in FluentCRM.
             *
             * This filter allows you to modify the list of country names used in FluentCRM.
             *
             * @since 2.7.0
             * 
             * @param array An array of country names.
             */
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

        // Add custom fields section
        if (!empty($customFields)) { 
            $allCustomFields = (new CustomFields)->getGlobalFields()['fields'];
            $enabledCustomFields = [];
            
            // Filter custom fields based on pref_custom settings
            foreach ($allCustomFields as $field) {
                if (in_array($field['slug'], $customFields)) {
                    $enabledCustomFields[] = $field;
                }
            }
            
            if (!empty($enabledCustomFields)) {
                $formFields[] = [
                    'type' => 'raw_html',
                    'html' => '<p class="fc_custom_fields_heading"></p>'
                ];
                
                // Group fields by their group attribute
                $groupedFields = [];
                $ungroupedFields = [];
                
                foreach ($enabledCustomFields as $field) {
                    if (!empty($field['group'])) {
                        $group = $field['group'];
                        if (!isset($groupedFields[$group])) {
                            $groupedFields[$group] = [];
                        }
                        $groupedFields[$group][] = $field;
                    } else {
                        $ungroupedFields[] = $field;
                    }
                }

                // Add ungrouped fields first
                if (!empty($ungroupedFields)) {
                    $ungroupedContainer = [
                        'type'            => 'container',
                        'container_class' => 'fc_custom_fields fc_2_col',
                        'fields'          => []
                    ];

                    foreach ($ungroupedFields as $field) {
                        $fieldType = $field['type'];
                        $fieldKey = $field['slug'];
                        $fieldConfig = $this->getCustomFieldConfig($field, $subscriber);
                        $ungroupedContainer['fields'][$fieldKey] = $fieldConfig;
                    }

                    $formFields['custom_fields_ungrouped'] = $ungroupedContainer;
                }

                // Create containers for each group
                foreach ($groupedFields as $groupName => $fields) {
                    $customFieldsContainer = [
                        'type'            => 'container',
                        'container_class' => 'fc_custom_fields fc_2_col fc_custom_field_group_box',
                        'fields'          => []
                    ];

                    $customFieldsContainer['fields']['group_heading'] = [
                        'type' => 'raw_html',
                        'html' => '<h5 class="fc_custom_field_group_heading">' . esc_html($groupName) . '</h5>'
                    ];

                    foreach ($fields as $field) {
                        $fieldType = $field['type'];
                        $fieldKey = $field['slug'];
                        $fieldConfig = $this->getCustomFieldConfig($field, $subscriber);
                        $customFieldsContainer['fields'][$fieldKey] = $fieldConfig;
                    }

                    $formFields['custom_fields_' . sanitize_title($groupName)] = $customFieldsContainer;
                }
            }
        }


        if (!$inputOnly) {
            return $formFields;
        }

        return $this->parseInputs($formFields);
    }

    private function parseInputs($fields)
    {
        $inputFields = [];

        $inputTypes = ['hidden', 'input', 'checkboxes', 'select', 'radio', 'date', 'textarea', 'select-multi', 'custom_date', 'custom_date_time'];

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

    private function getCustomFieldConfig($field, $subscriber)
    {
        $fieldType = $field['type'];
        $fieldKey = $field['slug'];

        $fieldConfig = [
            'type'     => 'input',
            'name'     => $fieldKey,
            'id'       => 'fc_' . $fieldKey,
            'label'    => $field['label'],
            'required' => !empty($field['required']),
            'value'    => $subscriber->getMeta($field['slug'], 'custom_field')
        ];

        // Add field-specific configurations
        switch ($fieldType) {
            case 'text':
                $fieldConfig['type'] = 'input';
                $fieldConfig['atts'] = [
                    'type'        => 'text',
                    'placeholder' => $field['label'],
                    'class'       => 'fc_input_control'
                ];
                break;

            case 'textarea':
                $fieldConfig['type'] = 'textarea';
                $fieldConfig['atts'] = [
                    'placeholder' => $field['label'],
                    'class'       => 'fc_input_control',
                    'name'        => $fieldKey
                ];
                break;

            case 'number':
                $fieldConfig['type'] = 'input';
                $fieldConfig['atts'] = [
                    'type'        => 'number',
                    'placeholder' => $field['label'],
                    'class'       => 'fc_input_control'
                ];
                break;

            case 'select-one':
                $fieldConfig['type'] = 'select';
                $fieldConfig['options'] = array_combine($field['options'], $field['options']);
                $fieldConfig['placeholder'] = $field['label'];
                $fieldConfig['atts'] = [
                    'class' => 'fc_input_control select-one'
                ];
                break;

            case 'select-multi':
                $fieldConfig['type'] = 'select-multi';
                $fieldConfig['options'] = array_combine($field['options'], $field['options']);
                $fieldConfig['value'] = is_array($fieldConfig['value']) ? $fieldConfig['value'] : [];
                $fieldConfig['name'] = $fieldKey . '[]';
                $fieldConfig['atts'] = [
                    'class' => 'fc_input_control select-multi',
                    'multiple' => 'multiple'
                ];
                break;

            case 'radio':
                $fieldConfig['type'] = 'radio';
                $fieldConfig['options'] = array_combine($field['options'], $field['options']);
                $fieldConfig['atts'] = [
                    'class' => 'fc_input_control'
                ];
                break;

            case 'checkbox':
                $fieldConfig['type'] = 'checkboxes';
                $fieldConfig['options'] = is_array($field['options']) ? $field['options'] : [];
                $fieldConfig['value'] = is_array($fieldConfig['value']) ? $fieldConfig['value'] : [];
                $fieldConfig['atts'] = [
                    'class' => 'fc_input_control'
                ];
                break;

            case 'date':
                $fieldConfig['type'] = 'custom_date';
                $fieldConfig['atts'] = [
                    'type'          => 'date',
                    'class'         => 'fc_date_item fc_input_control',
                    'data-format'   => 'YYYY-MM-DD',
                    'placeholder'   => $field['label'],
                    'data-template' => 'DD - MM - YYYY'
                ];
                break;

            case 'date_time':
                $fieldConfig['type'] = 'custom_date_time';
                $fieldConfig['atts'] = [
                    'type'          => 'text',
                    'class'         => 'fc_date_item fc_input_control',
                    'data-format'   => 'YYYY-MM-DD HH:mm:ss',
                    'placeholder'   => $field['label'],
                    'data-template' => 'DD - MM - YYYY HH:mm'
                ];
                break;
        }

        return $fieldConfig;
    }

}
