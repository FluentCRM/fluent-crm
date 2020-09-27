<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;

use FluentCrm\App\Services\Funnel\Benchmarks\RemoveFromListBenchmark;
use FluentCrm\App\Services\Funnel\Benchmarks\RemoveFromTagBenchmark;
use FluentCrm\App\Services\Funnel\Benchmarks\TagAppliedBenchmark;
use FluentCrm\App\Services\Funnel\FunnelProcessor;

use FluentCrm\App\Services\Funnel\Triggers\FluentFormSubmissionTrigger;
use FluentCrm\App\Services\Funnel\Triggers\UserRegistrationTrigger;

use FluentCrm\App\Services\Funnel\Actions\ApplyListAction;
use FluentCrm\App\Services\Funnel\Actions\ApplyTagAction;
use FluentCrm\App\Services\Funnel\Actions\DetachListAction;
use FluentCrm\App\Services\Funnel\Actions\DetachTagAction;
use FluentCrm\App\Services\Funnel\Actions\SendEmailAction;
use FluentCrm\App\Services\Funnel\Actions\WaitTimeAction;

use FluentCrm\App\Services\Funnel\Benchmarks\ListAppliedBenchmark;

class FunnelHandler
{
    private $settingsKey = 'fluentcrm_funnel_settings';

    public function handle()
    {
        $this->initBlockActions();
        $this->initBenchMarkBlocks();
        $this->initTriggers();
        $triggers = get_option($this->settingsKey, []);

        if (!$triggers) {
            return;
        }

        foreach ($triggers as $triggerName) {
            $argNum = apply_filters('fluentcrm_funnel_arg_num_' . $triggerName, 1);
            add_action($triggerName, function () use ($triggerName, $argNum) {
                $this->mapTriggers($triggerName, func_get_args(), $argNum);
            }, 10, $argNum);
        }

        add_action('fluentcrm_process_scheduled_tasks_init', function () {
            (new FunnelProcessor())->followUpSequenceActions();
        });
    }

    private function mapTriggers($triggerName, $originalArgs, $argNumber)
    {
        $funnels = Funnel::where('status', 'published')
            ->where('trigger_name', $triggerName)
            ->get();

        foreach ($funnels as $funnel) {
            ob_start();
            do_action('fluentcrm_funnel_start_' . $triggerName, $funnel, $originalArgs);
            $maybeErrors = ob_get_clean();
        }

        $benchMarks = FunnelSequence::where('type', 'benchmark')
            ->whereHas('funnel', function ($q) {
                $q->where('status', 'published');
            })
            ->orderBy('id', 'asc')
            ->get();

        foreach ($benchMarks as $benchMark) {
            ob_start();
            do_action('fluentcrm_funnel_benchmark_start_' . $triggerName, $benchMark, $originalArgs);
            $maybeErrors = ob_get_clean();
        }

    }

    public function resetFunnelIndexes()
    {
        $funnels = Funnel::select('trigger_name')
            ->where('status', 'published')
            ->groupBy('trigger_name')
            ->get();

        $funnelArrays = [];
        foreach ($funnels as $funnel) {
            $funnelArrays[] = $funnel->trigger_name;
        }

        $sequenceMetrics = FunnelSequence::select('action_name')
            ->where('status', 'published')
            ->where('type', 'benchmark')
            ->groupBy('action_name')
            ->get();

        foreach ($sequenceMetrics as $sequenceMetric) {
            $funnelArrays[] = $sequenceMetric->action_name;
        }

        update_option($this->settingsKey, array_unique($funnelArrays), 'yes');
    }

    private function initTriggers()
    {
        new UserRegistrationTrigger();
        new FluentFormSubmissionTrigger();
    }

    private function initBlockActions()
    {
        new ApplyListAction();
        new ApplyTagAction();
        new DetachListAction();
        new DetachTagAction();
        new WaitTimeAction();
        new SendEmailAction();
    }

    private function initBenchMarkBlocks()
    {
        new ListAppliedBenchmark();
        new TagAppliedBenchmark();
        new RemoveFromListBenchmark();
        new RemoveFromTagBenchmark();
    }

    public function resumeSubscriberFunnels($subscriber, $oldStatus)
    {
        $funnelSubscribers = FunnelSubscriber::where('status', 'pending')
            ->where('subscriber_id', $subscriber->id)
            ->whereHas('funnel', function ($query) {
                $query->where('status', 'published');
            })
            ->get();

        $funnelProcessorClass = new FunnelProcessor();

        foreach ($funnelSubscribers as $funnelSubscriber) {

            // check the last sequence ID here
            $lastSequence = false;
            if($funnelSubscriber->last_sequence_id) {
                $lastSequence = FunnelSequence::find($funnelSubscriber->last_sequence_id);
            }

            $sequences = FunnelSequence::where('funnel_id', $funnelSubscriber->funnel_id)
                ->whereHas('funnel', function ($query) {
                    $query->where('status', 'published');
                })
                ->orderBy('sequence', 'ASC');

            if($lastSequence) {
                // If sequence already stated then we want to resume here
                $sequences = $sequences->where('sequence', '>', $lastSequence->sequence);
            }

            $sequences = $sequences->get();

            $funnelProcessorClass->processSequences($sequences, $subscriber, $funnelSubscriber);
        }
    }
}
