<?php

namespace FluentCrm\App\Services\Funnel\Triggers;

use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
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
            'category'    => __('CRM', 'fluent-crm'),
            'label'       => __('New Form Submission (Fluent Forms)', 'fluent-crm'),
            'description' => __('This Funnel will be initiated when a new form submission has been submitted', 'fluent-crm'),
            'icon'        => 'fc-icon-fluentforms',
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

        $subtitle = __('This Funnel will be initiated when a new form submission has been submitted.', 'fluent-crm');

        if ($formId) {
            $subtitle .= ' Use shortcode <b> [fluentform id="' . $formId . '"] </b> to show the form in your WordPress page/posts. <a target="_blank" href="' . admin_url('admin.php?page=fluent_forms&route=editor&form_id=' . $formId) . '">Edit The Form</a>';
        }

        $secondaryFields = apply_filters('fluentcrm_fluentform_other_map_fields',
            FunnelHelper::getSecondaryContactFieldMaps()
        );

        return [
            'title'     => __('New Fluent Forms Submission Funnel', 'fluent-crm'),
            'sub_title' => $subtitle,
            'fields'    => [
                'form_id'                  => [
                    'type'    => 'reload_field_selection',
                    'label'   => __('Select your form', 'fluent-crm'),
                    'options' => $this->getForms($funnel)
                ],
                'primary_fields'           => [
                    'label'         => __('Map Primary Data', 'fluent-crm'),
                    'type'          => 'form-group-mapper',
                    'value_options' => $valueOptions,
                    'local_label'   => __('Contact Field (CRM)', 'fluent-crm'),
                    'remote_label'  => __('Form Field', 'fluent-crm'),
                    'fields'        => FunnelHelper::getPrimaryContactFieldMaps()
                ],
                'other_fields'             => [
                    'label'              => __('Map Other Data', 'fluent-crm'),
                    'type'               => 'form-many-drop-down-mapper',
                    'value_options'      => $valueOptions,
                    'local_label'        => __('Select Contact Property', 'fluent-crm'),
                    'remote_label'       => __('Select Form Field', 'fluent-crm'),
                    'local_placeholder'  => __('Select Contact Property', 'fluent-crm'),
                    'remote_placeholder' => __('Select Form Property', 'fluent-crm'),
                    'fields'             => $secondaryFields
                ],
                'subscription_status'      => [
                    'type'        => 'option_selectors',
                    'option_key'  => 'editable_statuses',
                    'is_multiple' => false,
                    'label'       => __('Subscription Status', 'fluent-crm'),
                    'placeholder' => __('Select Status', 'fluent-crm')
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
        return fluentCrmDb()->table('fluentform_forms')
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

        $form = fluentCrmDb()->table('fluentform_forms')
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
                'check_label' => __('Run this automation only once per contact. If unchecked then it will over-write existing flow', 'fluent-crm'),
                'help'        => __('If you enable this then this will run only once per customer otherwise, It will delete the existing automation flow and start new', 'fluent-crm'),
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

        $entry = fluentFormApi('submissions')->find($insertId);

        if ($entry && $entry->status == 'confirmed') {
            $subscriberData['status'] = 'subscribed';
        }

        if (!empty($subscriberData['country'])) {
            $country = FunnelHelper::getCountryShortName($subscriberData['country']);
            if ($country) {
                $subscriberData['country'] = $country;
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
        $subscriber = FunnelHelper::getSubscriber($subscriberData['email']);
        if ($subscriber && FunnelHelper::ifAlreadyInFunnel($funnel->id, $subscriber->id)) {
            $conditions = $funnel->conditions;
            // check update_type
            $runMultiple = Arr::get($conditions, 'run_only_one') == 'no';

            // if run multiple then delete
            if ($runMultiple) {
                FunnelHelper::removeSubscribersFromFunnel($funnel->id, [$subscriber->id]);
            }

            return $runMultiple;
        }

        return true;
    }
}
