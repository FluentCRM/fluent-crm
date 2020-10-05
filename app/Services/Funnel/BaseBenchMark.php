<?php

namespace FluentCrm\App\Services\Funnel;

abstract class BaseBenchMark
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
        add_filter('fluentcrm_funnel_blocks', array($this, 'addBenchmark'), $this->priority, 1);

        add_filter('fluentcrm_funnel_block_fields', array($this, 'pushBlockFields'), $this->priority, 2);

        add_action('fluentcrm_funnel_benchmark_start_' . $this->triggerName, array($this, 'handle'), $this->priority, 2);

        add_filter('fluentcrm_funnel_arg_num_' . $this->triggerName, function ($num) {
            if($num >= $this->actionArgNum) {
                return $num;
            }
            return $this->actionArgNum;
        });

        apply_filters('fluentcrm_funnel_sequence_saving_'.$this->triggerName, function ($sequence) {
            $sequence['type'] = 'benchmark';
            return $sequence;
        });
    }

    public function addBenchmark($benchMarks)
    {
        $benchMark = $this->getBlock();
        if($benchMark) {
            $benchMark['type'] = 'benchmark';
            $benchMarks[$this->triggerName] = $benchMark;
        }

        return $benchMarks;
    }

    public function pushBlockFields($fields, $funnel)
    {
        $fields[$this->triggerName] = $this->getBlockFields($funnel);
        return $fields;
    }

    public function getConditionDefaults($benchMark)
    {
        return [];
    }

    public function getConditionFields($benchMark)
    {
        return [];
    }

    public function benchmarkTypeField()
    {
        return [
            'label'       => 'Benchmark type',
            'type'        => 'radio',
            'options'     => [
                [
                    'id'    => 'optional',
                    'title' => '[Optional Point] This is an optional trigger point'
                ],
                [
                    'id'    => 'required',
                    'title' => '[Essential Point] Select IF this step is required for processing further actions'
                ]
            ],
            'inline_help' => 'If you select [Optional Point] it will work as an Optional Trigger otherwise, it will wait for full-fill this action'
        ];
    }

    abstract public function getBlock();

    abstract public function getBlockFields($funnel);

    abstract public function handle($benchMark, $originalArgs);
}
