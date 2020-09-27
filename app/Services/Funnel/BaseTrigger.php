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

        add_filter('fluentcrm_funnel_editor_details_'.$this->triggerName, array($this, 'prepareEditorDetails'), 10, 1);

        add_action('fluentcrm_funnel_start_' . $this->triggerName, array($this, 'handle'), 10, 2);

        add_filter('fluentcrm_funnel_arg_num_' . $this->triggerName, function ($num) {
            if($num >= $this->actionArgNum) {
                return $num;
            }
            return $this->actionArgNum;
        });
    }

    public function addTrigger($triggers)
    {
        $triggers[$this->triggerName] = $this->getTrigger();
        return $triggers;
    }

    public function prepareEditorDetails($funnel)
    {
        $funnel->settings = wp_parse_args($funnel->settings, $this->getFunnelSettingsDefaults());
        $funnel->settingsFields = $this->getSettingsFields($funnel);
        $funnel->conditions = wp_parse_args($funnel->conditions, $this->getFunnelConditionDefaults($funnel));
        $funnel->conditionFields = $this->getConditionFields($funnel);
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
