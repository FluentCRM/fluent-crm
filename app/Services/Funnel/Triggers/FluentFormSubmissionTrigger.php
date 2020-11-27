<?php

namespace FluentCrm\App\Services\Funnel\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Includes\Helpers\Arr;
use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\App\Services\FormBuilder\ShortCodeParser;

class FluentFormSubmissionTrigger extends BaseTrigger
{
    public function __construct()
    {
        if (!defined('FLUENTFORM')) {
            return;
        }

        $this->actionArgNum = 3;
        $this->triggerName = 'fluentform_submission_inserted';
        $this->priority = 25;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => 'Forms',
            'label'       => 'New Form Submission (Fluent Forms)',
            'description' => 'This Funnel will be initiated when a new form submission will happen'
        ];
    }

    public function getFunnelSettingsDefaults()
    {
        return [
            'form_id'             => '',
            'primary_fields'      => [
                'first_name' => '',
                'last_name'  => '',
                'email'      => ''
            ],
            'other_fields'        => [
                [
                    'field_key'   => '',
                    'field_value' => ''
                ]
            ],
            'subscription_status' => 'subscribed'
        ];
    }

    public function getSettingsFields($funnel)
    {
        $valueOptions = $this->getValueOptions($funnel);

        $formId = Arr::get($funnel->settings, 'form_id');

        $subtitle = 'This Funnel will be initiated when a new form submission will happen.';

        if ($formId) {
            $subtitle .= ' Use shortcode <b> [fluentform id="' . $formId . '"] </b> to show the form in your WordPress page/posts. <a target="_blank" href="' . admin_url('admin.php?page=fluent_forms&route=editor&form_id=' . $formId) . '">Edit The Form</a>';
        }

        return [
            'title'     => 'New Fluent Forms Submission Funnel',
            'sub_title' => $subtitle,
            'fields'    => [
                'form_id'                  => [
                    'type'    => 'reload_field_selection',
                    'label'   => 'Select your form',
                    'options' => $this->getForms($funnel)
                ],
                'primary_fields'           => [
                    'label'         => 'Map Primary Data',
                    'type'          => 'form-group-mapper',
                    'value_options' => $valueOptions,
                    'local_label'   => 'Contact Field (CRM)',
                    'remote_label'  => 'Form Field',
                    'fields'        => FunnelHelper::getPrimaryContactFieldMaps()
                ],
                'other_fields'             => [
                    'label'         => 'Map Other Data',
                    'type'          => 'form-many-drop-down-mapper',
                    'value_options' => $valueOptions,
                    'local_label'   => 'Select Contact Property',
                    'remote_label'  => 'Select Form Field',
                    'fields'        => apply_filters(
                        'fluentcrm_fluentform_other_map_fields',
                        FunnelHelper::getSecondaryContactFieldMaps()
                    )
                ],
                'subscription_status'      => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => 'Subscription Status',
                    'placeholder' => 'Select Status'
                ],
                'subscription_status_info' => [
                    'type'       => 'html',
                    'info'       => '<b>An Automated double-optin email will be sent if the contact is new or not subscribed already</b>',
                    'dependency' => [
                        'depends_on' => 'subscription_status',
                        'operator'   => '=',
                        'value'      => 'pending'
                    ]
                ]
            ]
        ];
    }

    protected function getForms($funnel)
    {
        return wpFluent()->table('fluentform_forms')
            ->select('id', 'title')
            ->orderBy('id', 'DESC')
            ->get();
    }

    protected function getValueOptions($funnel)
    {
        $formId = Arr::get($funnel->settings, 'form_id');
        if (!$formId) {
            return [];
        }

        $form = wpFluent()->table('fluentform_forms')
            ->find($formId);
        $formFields = FormFieldsParser::getShortCodeInputs(
            $form, [
            'admin_label'
        ]);

        $formattedInputs = [];

        foreach ($formFields as $inputKey => $input) {
            $formattedInputs[] = [
                'id'    => '{inputs.' . $inputKey . '}',
                'title' => $input['admin_label']
            ];
        }

        return $formattedInputs;
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [
            'run_only_one' => 'yes'
        ];
    }

    public function getConditionFields($funnel)
    {
        return [
            'run_only_one' => [
                'type'        => 'yes_no_check',
                'label'       => '',
                'check_label' => 'Run this automation only once per contact. If unchecked then it will over-write existing flow',
                'help'        => 'If you enable this then this will run only once per customer otherwise, It will delete the existing automation flow and start new',
                'options'     => FunnelHelper::getUpdateOptions()
            ],
        ];
    }

    public function handle($funnel, $originalArgs)
    {
        $insertId = $originalArgs[0];
        $formData = $originalArgs[1];
        $form = $originalArgs[2];

        $processedValues = $funnel->settings;

        if (Arr::get($processedValues, 'form_id') != $form->id) {
            return; // not our form
        }

        $processedValues['primary_fields']['ip'] = '{submission.ip}';

        $processedValues = ShortCodeParser::parse($processedValues, $insertId, $formData);

        if (!is_email(Arr::get($processedValues, 'primary_fields.email'))) {
            return;
        }

        $subscriberData = Arr::get($processedValues, 'primary_fields', []);

        $subscriberData['custom_values'] = [];

        foreach (Arr::get($processedValues, 'other_fields', []) as $otherField) {
            if (!empty($otherField['field_key']) && !empty($otherField['field_value'])) {
                $key = $otherField['field_key'];
                if (strpos($key, '.')) {
                    $subscriberData['custom_values'][str_replace('custom.', '', $key)] = $otherField['field_value'];
                } else {
                    $subscriberData[$key] = $otherField['field_value'];
                }
            }
        }

        $willProcess = $this->isProcessable($funnel, $subscriberData);

        $willProcess = apply_filters('fluentcrm_funnel_will_process_' . $this->triggerName, $willProcess, $funnel, $subscriberData, $originalArgs);
        if (!$willProcess) {
            return;
        }

        $subscriberData['status'] = $processedValues['subscription_status'];

        if (!empty($subscriberData['country'])) {
            $countries = getFluentFormCountryList();
            $countries = array_flip($countries);
            if (isset($countries[$subscriberData['country']])) {
                $subscriberData['country'] = $countries[$subscriberData['country']];
            } else {
                unset($subscriberData['country']);
            }
        }

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id'       => $insertId,
        ]);

    }

    private function isProcessable($funnel, $subscriberData)
    {
        $conditions = $funnel->conditions;
        // check update_type
        $isOnlyOne = Arr::get($conditions, 'run_only_one') != 'no';

        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);

        // check run_only_one
        if ($isOnlyOne && $subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
        }

        return true;
    }
}
