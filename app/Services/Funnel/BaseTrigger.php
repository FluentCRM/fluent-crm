<?php

namespace FluentCrm\App\Services\Funnel;

abstract class BaseTrigger
{

    protected $triggerName;

    protected $actionArgNum = 1;

    protected $priority = 10;

    public function __construct()
    {
        $this->register();
    }

    public function register()
    {
        add_filter('fluentcrm_funnel_triggers', array($this, 'addTrigger'), $this->priority, 1);

        add_filter('fluentcrm_funnel_editor_details_' . $this->triggerName, array($this, 'prepareEditorDetails'), 10, 1);

        add_action('fluentcrm_funnel_start_' . $this->triggerName, array($this, 'handle'), 10, 2);

        add_filter('fluentcrm_funnel_arg_num_' . $this->triggerName, function ($num) {
            if ($num >= $this->actionArgNum) {
                return $num;
            }
            return $this->actionArgNum;
        });
    }

    public function addTrigger($triggers)
    {
        $trigger = $this->getTrigger();

        if (!$trigger) {
            return $triggers;
        }

        $triggers[$this->triggerName] = $trigger;
        return $triggers;
    }

    public function prepareEditorDetails($funnel)
    {
        $funnel->settings = wp_parse_args($funnel->settings, $this->getFunnelSettingsDefaults());

        $settingsFields = $this->getSettingsFields($funnel);

        $settingsFields['fields']['__force_run_actions'] = [
            'label'       => '',
            'type'        => 'yes_no_check',
            'check_label' => __('Run the automation actions even contact status is not in subscribed status', 'fluent-crm'),
            'true_label'  => 'yes',
            'false_label' => 'no'
        ];

        $settingsFields['fields']['__force_run_actions_info'] = [
            'type'       => 'html',
            'info'       => '<em>' . __('The actions will run even the contact\'s status is not in subscribed status.', 'fluent-crm') . '</em>',
            'dependency' => [
                'depends_on' => '__force_run_actions',
                'operator'   => '=',
                'value'      => 'yes'
            ]
        ];

        $funnel->settingsFields = $settingsFields;

        $funnel->conditions = wp_parse_args($funnel->conditions, $this->getFunnelConditionDefaults($funnel));

        $conditionFields = $this->getConditionFields($funnel);

        $funnel->conditionFields = $conditionFields;
        return $funnel;
    }

    public function getFunnelConditionDefaults($funnel)
    {
        return [];
    }

    public function getConditionFields($funnel)
    {
        return [];
    }

    abstract public function getTrigger();

    abstract public function getFunnelSettingsDefaults();

    abstract public function getSettingsFields($funnel);

    abstract public function handle($funnel, $originalArgs);
}
