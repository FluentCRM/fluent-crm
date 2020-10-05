<?php

namespace FluentCrm\App\Services\ExternalIntegrations\FluentForm;

use FluentForm\App\Services\Integrations\IntegrationManager;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Tag;
use FluentCrm\Includes\Helpers\Arr;

class Bootstrap extends IntegrationManager
{
    public $hasGlobalMenu = false;

    public $disableGlobalSettings = 'yes';

    public function __construct(Application $app)
    {
        parent::__construct(
            $app,
            'FluentCRM',
            'fluentcrm',
            '_fluentform_fluentcrm_settings',
            'fluentcrm_feeds',
            10
        );

        $this->logo = FLUENTCRM_PLUGIN_URL . 'assets/images/fluentcrm-logo.png';

        $this->description = 'Connect FluentCRM with WP Fluent Forms and subscribe a contact when a form is submitted.';

        $this->registerAdminHooks();

        add_filter('fluentform_notifying_async_fluentcrm', '__return_false');
    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations[$this->integrationKey] = [
            'title' => $this->title . ' Integration',
            'logo' => $this->logo,
            'is_active' => $this->isConfigured(),
            'configure_title' => 'Configuration required!',
            'global_configure_url' => '#',
            'configure_message' => 'FluentCRM is not configured yet! Please configure your FluentCRM api first',
            'configure_button_text' => 'Set FluentCRM'
        ];
        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        return [
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'full_name' => '',
            'email' => '',
            'other_fields' => [
                [
                    'item_value' => '',
                    'label' => ''
                ]
            ],
            'list_id' => '',
            'tag_ids' => [],
            'skip_if_exists' => false,
            'double_opt_in' => false,
            'conditionals' => [
                'conditions' => [],
                'status' => false,
                'type' => 'all'
            ],
            'enabled' => true
        ];
    }

    public function getSettingsFields($settings, $formId)
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => 'Feed Name',
                    'required' => true,
                    'placeholder' => 'Your Feed Name',
                    'component' => 'text'
                ],
                [
                    'key' => 'list_id',
                    'label' => 'FluentCRM List',
                    'placeholder' => 'Select FluentCRM List',
                    'tips' => 'Select the FluentCRM List you would like to add your contacts to.',
                    'component' => 'select',
                    'required' => true,
                    'options' => $this->getLists(),
                ],
                [
                    'key' => 'CustomFields',
                    'require_list' => false,
                    'label' => 'Primary Fields',
                    'tips' => 'Associate your FluentCRM merge tags to the appropriate Fluent Form fields by selecting the appropriate form field from the list.',
                    'component' => 'map_fields',
                    'field_label_remote' => 'FluentCRM Field',
                    'field_label_local' => 'Form Field',
                    'primary_fileds' => [
                        [
                            'key' => 'email',
                            'label' => 'Email Address',
                            'required' => true,
                            'input_options' => 'emails'
                        ],
                        [
                            'key' => 'first_name',
                            'label' => 'First Name'
                        ],
                        [
                            'key' => 'last_name',
                            'label' => 'Last Name'
                        ],
                        [
                            'key' => 'full_name',
                            'label' => 'Full Name',
                            'help_text' => 'If First Name & Last Name is not available full name will be used to get first name and last name'
                        ]
                    ]
                ],
                [
                    'key' => 'other_fields',
                    'require_list' => false,
                    'label' => 'Other Fields',
                    'tips' => 'Select which Fluent Form fields pair with their<br /> respective FlunentCRM fields.',
                    'component' => 'dropdown_many_fields',
                    'field_label_remote' => 'FluentCRM Field',
                    'field_label_local' => 'Form Field',
                    'options' => [
                        'address_line_1' => 'Address Line 1',
                        'address_line_2' => 'Address Line 2',
                        'city' => 'City',
                        'state' => 'State',
                        'postal_code' => 'ZIP code',
                        'country' => 'Country',
                        'phone' => 'Phone'
                    ]
                ],
                [
                    'key' => 'tag_ids',
                    'require_list' => false,
                    'label' => 'Contact Tags',
                    'component' => 'select',
                    'is_multiple' => true,
                    'options' => $this->getTags()
                ],
                [
                    'key' => 'skip_if_exists',
                    'require_list' => false,
                    'checkbox_label' => 'Skip if contact already exist in FluentCRM',
                    'component' => 'checkbox-single'
                ],
                [
                    'key' => 'double_opt_in',
                    'require_list' => false,
                    'checkbox_label' => 'Enable Double Option for new contacts',
                    'component' => 'checkbox-single'
                ],
                [
                    'require_list' => false,
                    'key' => 'conditionals',
                    'label' => 'Conditional Logics',
                    'tips' => 'Allow FluentCRM integration conditionally based on your submission values',
                    'component' => 'conditional_block'
                ],
                [
                    'require_list' => false,
                    'key' => 'enabled',
                    'label' => 'Status',
                    'component' => 'checkbox-single',
                    'checkbox_label' => 'Enable This feed'
                ]
            ],
            'button_require_list' => false,
            'integration_title' => $this->title
        ];
    }

    public function getMergeFields($list, $listId, $formId)
    {
        return [];
    }

    protected function getLists()
    {
        $lists = Lists::get();
        $formattedLists = [];
        foreach ($lists as $list) {
            $formattedLists[$list->id] = $list->title;
        }
        return $formattedLists;
    }

    protected function getTags()
    {
        $tags = Tag::get();
        $formattedTags = [];
        foreach ($tags as $tag) {
            $formattedTags[$tag->id] = $tag->title;
        }
        return $formattedTags;
    }

    /*
     * Form Submission Hooks Here
     */
    public function notify($feed, $formData, $entry, $form)
    {
        $data = $feed['processedValues'];
        $contact = Arr::only($data, ['first_name', 'last_name', 'email']);

        if (!is_email($contact['email'])) {
            $contact['email'] = ArrayHelper::get($formData, $contact['email']);
        }


        if (!$contact['first_name'] && !$contact['last_name']) {
            $fullName = Arr::get($data, 'full_name');
            if ($fullName) {
                $nameArray = explode(' ', $fullName);
                if (count($nameArray) > 1) {
                    $contact['last_name'] = array_pop($nameArray);
                    $contact['first_name'] = implode(' ', $nameArray);
                } else {
                    $contact['first_name'] = $fullName;
                }
            }
        }

        foreach (Arr::get($data, 'other_fields') as $field) {
            if ($field['item_value']) {
                $contact[$field['label']] = $field['item_value'];
            }
        }

        if ($entry->ip) {
            $contact['ip'] = 'Fluent Forms';
        }

        if (!is_email($contact['email'])) {
            $this->addLog(
                $feed['settings']['name'],
                'failed',
                'FluentCRM API called skipped because no valid email available',
                $form->id,
                $entry->id
            );
            return;
        }

        $subscriber = Subscriber::where('email', $contact['email'])->first();

        if ($subscriber && Arr::isTrue($data, 'skip_if_exists')) {
            $this->addLog(
                $feed['settings']['name'],
                'info',
                'Contact creation has been skipped because contact already exist in the database',
                $form->id,
                $entry->id
            );
        }

        if ($subscriber) {
            if ($subscriber->ip && isset($contact['ip'])) {
                unset($contact['ip']);
            }
        }

        $user = get_user_by('email', $contact['email']);
        if ($user) {
            $contact['user_id'] = $user->ID;
        }

        if (!$subscriber) {
            $contact['source'] = 'FluentForms';

            if (Arr::isTrue($data, 'double_opt_in')) {
                $contact['status'] = 'pending';
            } else {
                $contact['status'] = 'subscribed';
            }

            if ($listId = Arr::get($data, 'list_id')) {
                $contact['lists'] = [$listId];
            }
            if ($tags = Arr::get($data, 'tag_ids')) {
                $contact['tags'] = $tags;
            }

            $subscriber = Subscriber::store($contact);

            if ($subscriber->status == 'pending') {
                $subscriber->sendDoubleOptinEmail();
            }

            $this->addLog(
                $feed['settings']['name'],
                'success',
                'Contact has been created in FluentCRM. Contact ID: ' . $subscriber->id,
                $form->id,
                $entry->id
            );

        } else {
            $subscriber->fill($contact);
            $subscriber->save();

            if ($listId = Arr::get($data, 'list_id')) {
                $lists = [$listId];
                $subscriber->attachLists($lists);
            }

            if ($tags = Arr::get($data, 'tag_ids')) {
                $subscriber->attachTags($tags);
            }

            if (Arr::isTrue($data, 'double_opt_in') && ( $subscriber->status == 'pending' || $subscriber->status == 'unsubscribed' )) {
                $subscriber->sendDoubleOptinEmail();
            }

            $this->addLog(
                $feed['settings']['name'],
                'success',
                'Contact has been updated in FluentCRM. Contact ID: ' . $subscriber->id,
                $form->id,
                $entry->id
            );
        }

    }


    public function isConfigured()
    {
        return true;
    }

    public function isEnabled()
    {
        return true;
    }

    protected function addLog($title, $status, $description, $formId, $entryId)
    {
        do_action('ff_log_data', [
            'title' => $title,
            'status' => $status,
            'description' => $description,
            'parent_source_id' => $formId,
            'source_id' => $entryId,
            'component' => $this->integrationKey,
            'source_type' => 'submission_item'
        ]);
    }
}
