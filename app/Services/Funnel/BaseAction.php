<?php

namespace FluentCrm\App\Services\Funnel;

abstract class BaseAction
{
    protected $actionName;

    protected $funnel;
    protected $priority = 10;

    public function __construct()
    {
        $this->register();
    }

    public function register()
    {
        add_filter('fluentcrm_funnel_blocks', array($this, 'pushBlock'), $this->priority, 2);
        add_filter('fluentcrm_funnel_block_fields', array($this, 'pushBlockFields'), $this->priority, 2);
        add_action('fluentcrm_funnel_sequence_handle_' . $this->actionName, array($this, 'handle'), 10, 4);
    }

    public function pushBlock($blocks, $funnel)
    {
        $this->funnel = $funnel;

        $block = $this->getBlock();
        if($block) {
            $block['type'] = 'action';
            $blocks[$this->actionName] = $block;
        }

        return $blocks;
    }

    public function pushBlockFields($fields, $funnel)
    {
        $this->funnel = $funnel;

        $fields[$this->actionName] = $this->getBlockFields();
        return $fields;
    }

    abstract public function getBlock();

    abstract public function getBlockFields();

    abstract public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric);

}
