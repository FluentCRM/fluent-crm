<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Services\Funnel\Actions\ApplyListAction;
use FluentCrm\App\Services\Funnel\Actions\ApplyTagAction;
use FluentCrm\App\Services\Funnel\Actions\DetachListAction;
use FluentCrm\App\Services\Funnel\Actions\DetachTagAction;
use FluentCrm\App\Services\Funnel\Actions\SendEmailAction;
use FluentCrm\App\Services\Funnel\Actions\WaitTimeAction;
use FluentCrm\App\Services\Funnel\Benchmarks\ListAppliedBenchmark;
use FluentCrm\App\Services\Funnel\Benchmarks\RemoveFromListBenchmark;
use FluentCrm\App\Services\Funnel\Benchmarks\RemoveFromTagBenchmark;
use FluentCrm\App\Services\Funnel\Benchmarks\TagAppliedBenchmark;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\App\Services\Funnel\SequencePoints;
use FluentCrm\App\Services\Funnel\Triggers\FluentFormSubmissionTrigger;
use FluentCrm\App\Services\Funnel\Triggers\UserRegistrationTrigger;
use FluentCrm\App\Services\PermissionManager;
use FluentCrm\Framework\Support\Arr;

/**
 *  FunnelHandler Class - Automation Funnel Handler
 *
 * Automation Funnel Handler Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class FunnelHandler
{
    private $settingsKey = 'fluentcrm_funnel_settings';

    public function handle()
    {
        $this->initBlockActions();
        $this->initBenchMarkBlocks();
        $this->initTriggers();

        if (!defined('FLUENTCAMPAIGN_DIR_FILE')) {
            new \FluentCrm\App\Services\Funnel\ProFunnelItems();
        }

        $triggers = get_option($this->settingsKey, []);

        $triggers = array_unique($triggers);

        if ($triggers) {
            foreach ($triggers as $triggerName) {
                $argNum = apply_filters('fluentcrm_funnel_arg_num_' . $triggerName, 1);
                add_action($triggerName, function () use ($triggerName, $argNum) {
                    $this->mapTriggers($triggerName, func_get_args(), $argNum);
                }, 10, $argNum);
            }

            if (in_array('edd_update_payment_status', $triggers)) {
                add_action('edd_complete_purchase', function ($paymentId) {
                    $this->mapTriggers('edd_update_payment_status', [$paymentId, 'publish', 'pending'], 3);
                });
            }

        }

        add_action('fluentcrm_process_scheduled_tasks_init', function () {
            (new FunnelProcessor())->followUpSequenceActions();
        });
    }

    private function mapTriggers($triggerName, $originalArgs, $argNumber)
    {
        $triggerNameBase = $triggerName;

        $funnels = Funnel::where('status', 'published')
            ->where('trigger_name', $triggerNameBase)
            ->get();

        foreach ($funnels as $funnel) {
            ob_start();
            /**
             * Automation Funnel Start Trigger from specific action
             * @param Funnel $funnel
             * @param array $originalArgs Original Arguments from the trigger
             */
            do_action("fluentcrm_funnel_start_{$triggerName}", $funnel, $originalArgs);
            $maybeErrors = ob_get_clean();
        }

        $benchMarks = FunnelSequence::where('type', 'benchmark')
            ->where('action_name', $triggerNameBase)
            ->whereHas('funnel', function ($q) {
                return $q->where('status', 'published');
            })
            ->orderBy('id', 'ASC')
            ->get();

        foreach ($benchMarks as $benchMark) {
            ob_start();
            /**
             * Automation Funnel's Benchmark Start Trigger from specific action trigger
             * @param Funnel $funnel
             * @param array $originalArgs Original Arguments from the trigger
             */
            do_action("fluentcrm_funnel_benchmark_start_{$triggerName}", $benchMark, $originalArgs);
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
            ->whereHas('funnel', function ($q) {
                return $q->where('status', 'published');
            })
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
            ->with(['funnel'])
            ->where('subscriber_id', $subscriber->id)
            ->whereHas('funnel', function ($query) {
                return $query->where('status', 'published');
            })
            ->get();

        $funnelProcessorClass = new FunnelProcessor();

        foreach ($funnelSubscribers as $funnelSubscriber) {
            $funnel = $funnelSubscriber->funnel;

            if (!$funnel || $funnel->status != 'published') {
                continue;
            }

            $sequencePoints = new SequencePoints($funnel, $funnelSubscriber);
            $funnelProcessorClass->processSequencePoints($sequencePoints, $subscriber, $funnelSubscriber);
        }
    }

    public function saveSequences()
    {
        $hasPermission = PermissionManager::currentUserCan('fcrm_write_funnels');

        if (!$hasPermission) {
            wp_send_json([
                'message' => __('Sorry, You do not have permission to do this action', 'fluent-crm')
            ], 423);
        }

        $request = FluentCrm('request');
        $data = $request->all();

        $data['sequences'] = wp_unslash(Arr::get($data, 'sequences'));

        $funnel = FunnelHelper::saveFunnelSequence($data['funnel_id'], $data);

        wp_send_json([
            'sequences' => FunnelHelper::getFunnelSequences($funnel, true),
            'message'   => __('Sequence successfully updated', 'fluent-crm')
        ]);
    }

    public function exportFunnel()
    {
        $permission = 'manage_options';
        if (!current_user_can($permission)) {
            die('You do not have permission');
        }

        $funnelId = intval($_REQUEST['funnel_id']);
        $funnel = Funnel::findOrFail($funnelId);
        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        $funnel->sequences = FunnelHelper::getFunnelSequences($funnel, true);

        $funnel->site_hash = md5(site_url());
        $funnel->export_date = date('Y-m-d H:i:s');

        header('Content-disposition: attachment; filename=' . sanitize_title($funnel->title, 'funnel', 'display') . '-' . $funnelId . '.json');
        header('Content-type: application/json');
        echo json_encode($funnel);
        exit();
    }
}
