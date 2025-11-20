<?php
namespace FluentCrm\App\Services\Funnel\Triggers;


use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\App\Services\FormBuilder\ShortCodeParser;

class FluentFormSubscriptionPaymentReceivedTrigger extends BaseTrigger
{
    public function __construct()
    {
        if (!defined('FLUENTFORM')) {
            return;
        }

        $this->actionArgNum = 2;
        $this->triggerName = 'fluentform/subscription_payment_active';
        $this->priority = 25;
        parent::__construct();
    }

    public function getTrigger()
    {
        return [
            'category'    => __('Fluent Forms', 'fluent-crm'),
            'label'       => __('Subscription Payment Received (Fluent Forms)', 'fluent-crm'),
            'description' => __('This Funnel will be initiated when a subscription payment is successfully received through Fluent Forms.', 'fluent-crm'),
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
        $secondaryFields = apply_filters('fluent_crm/fluentform_other_map_fields',
            FunnelHelper::getSecondaryContactFieldMaps()
        );

        return [
            'title'     => __('Subscription Payment Received', 'fluent-crm'),
            'sub_title' => __('This Funnel will be initiated when a subscription payment is successfully received through Fluent Forms.', 'fluent-crm'),
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
                    'type'        => 'select',
                    'is_multiple' => false,
                    'options' => [
                        [
                            'id' => 'subscribed',
                            'title' => 'Subscribed'
                        ],
                        [
                            'id' => 'pending',
                            'title' => 'Pending'
                        ]
                    ],
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

    protected function getValueOptions($funnel)
    {
        $formId = Arr::get($funnel->settings, 'form_id');
        if (!$formId) {
            return [];
        }

        $form = fluentCrmDb()->table('fluentform_forms')
            ->find($formId);

        if(!$form) {
            return [];
        }

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

    protected function getForms($funnel)
    {
        return fluentCrmDb()->table('fluentform_forms')
            ->select('id', 'title')
            ->orderBy('id', 'DESC')
            ->get();
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
        [$submission, $formResponse] = $originalArgs;
        $formData = $formResponse->response;

        $processedValues = $funnel->settings;
        $insertId = $submission->submission_id;

        if (Arr::get($processedValues, 'form_id') != $formResponse->form_id) {
            return; // not our form
        }

        $processedValues = ShortCodeParser::parse($processedValues, $insertId, $formData);

        $subscriberData = Arr::get($processedValues, 'primary_fields', []);
        $subscriberData['custom_values'] = $this->processOtherFields(Arr::get($processedValues, 'other_fields', []));

        $willProcess = $this->isProcessable($funnel, $subscriberData);

        $willProcess = apply_filters('fluentcrm_funnel_will_process_' . $this->triggerName, $willProcess, $funnel, $subscriberData, $originalArgs);
        if (!$willProcess) {
            return;
        }
        $subscriberData['status'] = $processedValues['subscription_status'];

        (new FunnelProcessor())->startFunnelSequence($funnel, $subscriberData, [
            'source_trigger_name' => $this->triggerName,
            'source_ref_id'       => $insertId,
        ]);
    }

    private function processOtherFields(array $otherFields)
    {
        $customValues = [];
        foreach ($otherFields as $otherField) {
            if (!empty($otherField['field_key']) && !empty($otherField['field_value'])) {
                $key = $otherField['field_key'];
                if (strpos($key, '.')) {
                    $customValues[str_replace('custom.', '', $key)] = $otherField['field_value'];
                } else {
                    $customValues[$key] = $otherField['field_value'];
                }
            }
        }
        return $customValues;
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