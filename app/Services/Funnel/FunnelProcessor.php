<?php

namespace FluentCrm\App\Services\Funnel;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

class FunnelProcessor
{
    private $subscribersCache = [];

    private $sequenceFunnelCache = [];

    private $funnelCache = [];

    public function getSubscriber($id)
    {
        if (isset($this->subscribersCache[$id])) {
            return $this->subscribersCache[$id];
        }
        $subscriber = Subscriber::find($id);
        $this->subscribersCache[$id] = $subscriber;
        return $this->subscribersCache[$id];
    }

    public function setSubscriber($id)
    {
        $subscriber = Subscriber::find($id);
        $this->subscribersCache[$id] = $subscriber;
        return $this->subscribersCache[$id];
    }

    public function getSequence($id)
    {
        if (isset($this->sequenceFunnelCache[$id])) {
            return $this->sequenceFunnelCache[$id];
        }

        $funnelAction = FunnelSequence::find($id);
        $this->sequenceFunnelCache[$id] = $funnelAction;
        return $this->sequenceFunnelCache[$id];
    }

    public function getFunnel($id)
    {
        if (isset($this->funnelCache[$id])) {
            return $this->funnelCache[$id];
        }
        $funnel = Funnel::find($id);
        $this->funnelCache[$id] = $funnel;
        return $this->funnelCache[$id];
    }

    public function setFunnel($id)
    {
        $funnel = Funnel::find($id);
        $this->funnelCache[$id] = $funnel;
        return $this->funnelCache[$id];
    }

    public function startFunnelSequence($funnel, $subscriberData, $funnelSubArgs = [], $subscriber = false)
    {
        if (isset($subscriberData['email'])) {
            $subscriber = Subscriber::where('email', $subscriberData['email'])->first();
        }

        if (!$subscriber) {
            // it's new so let's create new subscriber
            $subscriber = FunnelHelper::createOrUpdateContact($subscriberData);

            if (!$subscriber) {
                return false;
            }
        }

        if ($subscriber->status == 'pending') {
            $subscriber->sendDoubleOptinEmail();
        }

        $args = [
            'status' => ($subscriber->status == 'pending' || $subscriber->status == 'unsubscribed') ? 'pending' : 'draft'
        ];

        if ($funnelSubArgs) {
            $args = wp_parse_args($args, $funnelSubArgs);
        }

        (new FunnelProcessor())->startSequences($subscriber, $funnel, $args);
    }

    public function startSequences($subscriber, $funnel, $funnelSubArgs = [])
    {
        $data = [
            'funnel_id'     => $funnel->id,
            'subscriber_id' => $subscriber->id,
            'status'        => 'draft'
        ];

        if ($funnelSubArgs) {
            $data = wp_parse_args($funnelSubArgs, $data);
        }

        $data['status'] = FunnelHelper::getFunnelSubscriberStatus($data['status'], $funnel, $subscriber);

        // let's create an empty sequence_subscriber
        $funnelSubscriber = FunnelSubscriber::create($data);

        $sequencePoints = (new SequencePoints($funnel, $funnelSubscriber));

        if (!$sequencePoints->hasSequences()) {
            FunnelSubscriber::where('id', $funnelSubscriber->id)->delete();
            return;
        }

        do_action('fluent_crm/automation_funnel_start', $funnel, $subscriber);

        if ($funnelSubscriber->status != 'pending') {
            $this->processSequencePoints($sequencePoints, $subscriber, $funnelSubscriber);
        }
    }

    public function processSequencePoints(SequencePoints $sequencePoints, $subscriber, $funnelSubscriber)
    {
        if (!$sequencePoints->hasSequences()) {
            $this->completeFunnelSequence($funnelSubscriber);
            return;
        }

        $hasEnd = $sequencePoints->hasEndSequence;

        if ($hasEnd) {
            foreach ($sequencePoints->getCurrentSequences() as $sequence) {
                $this->processSequence($subscriber, $sequence, $funnelSubscriber->id);
                if ($sequence->action_name == 'end_this_funnel') {
                    $this->setFunnel($funnelSubscriber->funnel_id);
                    return;
                }
            }

            $this->completeFunnelSequence($funnelSubscriber);

            return;
        }

        $nextSequence = $sequencePoints->getNextSequence();

        $requiredBenchMark = $sequencePoints->getRequiredBenchmark();

        if ($nextSequence && $requiredBenchMark) {
            if ($nextSequence->sequence < $requiredBenchMark->sequence) {
                $requiredBenchMark = false;
            }
        }

        if ($requiredBenchMark) {
            // if we have required benchmark then wait for that
            FunnelSubscriber::where('id', $funnelSubscriber->id)
                ->update([
                    'next_sequence'       => $requiredBenchMark->sequence,
                    'next_sequence_id'    => $requiredBenchMark->id,
                    'next_execution_time' => NULL,
                    'status'              => 'waiting'
                ]);
        } else if (!$sequencePoints->hasNext()) {
            $this->completeFunnelSequence($funnelSubscriber);
        } else if ($nextSequence) {
            $nextDateTime = date('Y-m-d H:i:s', current_time('timestamp') + $nextSequence->delay);

            if ($nextSequence->execution_date_time) {
                $nextDateTime = $nextSequence->execution_date_time;
            }

            FunnelSubscriber::where('id', $funnelSubscriber->id)
                ->update([
                    'next_sequence'       => $nextSequence->sequence,
                    'next_sequence_id'    => $nextSequence->id,
                    'next_execution_time' => $nextDateTime,
                    'status'              => 'active'
                ]);
        }

        foreach ($sequencePoints->getCurrentSequences() as $sequence) {
            $this->processSequence($subscriber, $sequence, $funnelSubscriber->id);
            if ($sequence->action_name == 'end_this_funnel') {
                $this->completeFunnelSequence($funnelSubscriber);
                return;
            }
        }
    }

    /**
     * @param $sequences
     * @param $subscriber
     * @param $funnelSubscriber
     * @deprecated 1.2.0 Use processSequencePoints method
     */
    public function processSequences($sequences, $subscriber, $funnelSubscriber)
    {
        _deprecated_function(__METHOD__, '1.2.0', "(new FunnelProcessor)->processSequencePoints()");

        $funnel = $this->getFunnel($funnelSubscriber->funnel_id);
        $funnelPoints = new SequencePoints($funnel, $funnelSubscriber);
        $this->processSequencePoints($funnelPoints, $subscriber, $funnelSubscriber);
    }

    public function processSequence($subscriber, $sequence, $funnelSubscriberId)
    {
        $funnelMetric = $this->recordFunnelMetric($subscriber, $sequence);
        FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'complete');

        if ($sequence->type == 'conditional' && $sequence->action_name != 'funnel_condition') {
            $sequence = FunnelHelper::migrateConditionSequence($sequence);
        }

        do_action('fluentcrm_funnel_sequence_handle_' . $sequence->action_name, $subscriber, $sequence, $funnelSubscriberId, $funnelMetric);
    }

    public function completeFunnelSequence($funnelSubscriber)
    {
        FunnelSubscriber::where('id', $funnelSubscriber->id)
            ->update([
                'status' => 'completed'
            ]);

        $this->setFunnel($funnelSubscriber->funnel_id);

        do_action('fluent_crm/automation_funnel_completed', $funnelSubscriber->funnel, $funnelSubscriber->subscriber);
    }

    public function followUpSequenceActions()
    {
        update_option('_fc_last_funnel_processor_ran', time(), 'no');

        /**
         * Apply a filter to retrieve the subscriber statuses for the funnel.
         *
         * This filter allows customization of the subscriber statuses used in the funnel processing.
         * By default, it includes only the 'active' status.
         *
         * @since 1.0.0
         *
         * @param array $statuses The default subscriber statuses, which is ['active'].
         * @return array Filtered subscriber statuses.
         */
        $statuses = apply_filters('fluent_crm/funnel_subscriber_statuses', ['active']);

        $jobs = FunnelSubscriber::whereIn('status', $statuses)
            ->whereHas('funnel', function ($q) {
                return $q->where('status', 'published');
            })
            ->where('next_execution_time', '<=', current_time('mysql'))
            ->whereNotNull('next_execution_time')
            ->orderBy('next_execution_time', 'ASC')
            ->limit(200)// we want to process 200 records each time
            ->get();

        $startingAt = time();

        $completed = 0;

        foreach ($jobs as $job) {
            if ((time() - $startingAt) > 55) {
                // We are running this for 55 seconds. We have to stop now
                break;
            }

            $completed++;

            $this->processFunnelAction($job);
        }

        Helper::debugLog('Automation followUpSequenceActions', 'Completed Jobs Count: ' . $completed);
    }

    public function processFunnelAction($funnelSubscriber)
    {
        $subscriber = $this->getSubscriber($funnelSubscriber->subscriber_id);
        $funnel = $this->getFunnel($funnelSubscriber->funnel_id);

        if (!$subscriber) {
            FunnelSubscriber::where('id', $funnelSubscriber->id)->update([
                'status' => 'skipped'
            ]);
            return false;
        }

        $sequencePoints = new SequencePoints($funnel, $funnelSubscriber);

        $this->processSequencePoints($sequencePoints, $subscriber, $funnelSubscriber);
    }

    public function startFunnelFromSequencePoint($startSequence, $subscriber, $args = [], $metricArgs = [])
    {
        if (!$subscriber) {
            return false;
        }

        $funnelSubscriber = FunnelHelper::ifAlreadyInFunnel($startSequence->funnel_id, $subscriber->id);

        if (!$funnelSubscriber && $startSequence->type == 'benchmark') {
            // it's new starting point for a goal type sequence
            // so if the can start is set to no then we will skip this
            if (Arr::get($startSequence->settings, 'can_enter') == 'no') {
                return false;
            }

            if ($funnelSubscriber->status == 'completed' || $funnelSubscriber->status == 'cancelled') {
                return false; // It's already completed or cancelled. We don't need to start again
            }

        }

        $this->recordFunnelMetric($subscriber, $startSequence, $metricArgs);

        if (!$funnelSubscriber) {

            $processableStatuses = ['subscribed', 'transactional'];

            // we have to create a funnel subscriber
            $data = [
                'funnel_id'            => $startSequence->funnel_id,
                'subscriber_id'        => $subscriber->id,
                'status'               => (in_array($subscriber->status, $processableStatuses, true)) ? 'active' : 'pending',
                'starting_sequence_id' => $startSequence->id,
                'last_sequence_status' => 'completed',
                'next_sequence'        => $startSequence->sequence + 1,
                'last_sequence_id'     => $startSequence->id,
                'last_executed_time'   => current_time('mysql'),
                'source_trigger_name'  => $startSequence->action_name
            ];

            if ($args) {
                $data = wp_parse_args($args, $data);
            }

            if ($data['status'] != 'active') {
                $data['status'] = FunnelHelper::getFunnelSubscriberStatus($data['status'], $this->getFunnel($startSequence->funnel_id), $subscriber);
            }

            // let's create an empty sequence_subscriber
            $funnelSubscriber = FunnelSubscriber::create($data);
        } else {
            // We already have funnel subscriber. Now we have to update that
            $lastSequence = $funnelSubscriber->last_sequence;

            if (!$lastSequence || ($lastSequence->sequence <= $startSequence->sequence)) {
                $nextSequence = FunnelSequence::where('sequence', '>', $startSequence->sequence)
                    ->orderBy('sequence', 'ASC')
                    ->first();

                if (!$nextSequence) {
                    $this->completeFunnelSequence($funnelSubscriber);
                    return;
                }

                $funnelSubscriber->last_sequence_id = $startSequence->id;
                $funnelSubscriber->next_sequence_id = $nextSequence->id;
                $funnelSubscriber->next_sequence = $nextSequence->sequence;
                if ($funnelSubscriber->status == 'waiting') {
                    $funnelSubscriber->status = 'active';
                }
                $funnelSubscriber->next_execution_time = current_time('mysql');  // we are auto advancing the funnel
                $funnelSubscriber->save();
            } else {
                // this already advanced than our target
                // We have to check if we have to fire immediately
                if ($funnelSubscriber->next_sequence - 1 == $startSequence->sequence) {
                    // we are just make the time with current time if that had a timer for the target sequence
                    $funnelSubscriber->next_execution_time = current_time('mysql');
                    $funnelSubscriber->save();
                } else {
                    return; // It will work as it is; This funnel don't need any help
                }
            }
        }

        if ($funnelSubscriber->status == 'pending') {
            return; // We need double-optin from this user.
        }

        $funnel = $this->getFunnel($startSequence->funnel_id);

        $sequencePoints = new SequencePoints($funnel, $funnelSubscriber);

        $this->processSequencePoints($sequencePoints, $subscriber, $funnelSubscriber);
    }

    public function recordFunnelMetric($subscriber, $sequence, $metricArgs = [])
    {
        $data = [
            'funnel_id'     => $sequence->funnel_id,
            'sequence_id'   => $sequence->id,
            'subscriber_id' => $subscriber->id
        ];

        if ($metricArgs) {
            $data = wp_parse_args($data, $metricArgs);
        }

        return FunnelMetric::updateOrCreate($data, [
            'funnel_id'     => $sequence->funnel_id,
            'sequence_id'   => $sequence->id,
            'subscriber_id' => $subscriber->id
        ]);
    }

    public function initChildSequences($parent, $isMatched, $subscriber, $funnelSubscriberId, $funnelMetric)
    {
        $conditionType = 'no';
        if ($isMatched) {
            $conditionType = 'yes';
        }
        // find the corresponding sequence
        $sequences = FunnelSequence::where('funnel_id', $parent->funnel_id)
            ->where('parent_id', $parent->id)
            ->orderBy('sequence', 'ASC')
            ->where('condition_type', $conditionType)
            ->get();

        $waitTimes = 0;
        if (!$sequences->isEmpty()) {
            $immediateSequences = [];
            $firstSequence = $sequences[0];
            $nextSequence = false;

            foreach ($sequences as $sequence) {
                if ($sequence->c_delay == $firstSequence->c_delay) {
                    $immediateSequences[] = $sequence;
                } else {
                    if (!$nextSequence) {
                        $nextSequence = $sequence;
                    }
                    if ($sequence->c_delay < $nextSequence->c_delay) {
                        $nextSequence = $sequence;
                    }
                }
            }

            foreach ($immediateSequences as $immediateSequence) {
                $this->processSequence($subscriber, $immediateSequence, $funnelSubscriberId);
                if ($immediateSequence->action_name == 'end_this_funnel') {
                    $this->setFunnel($immediateSequence->funnel_id);
                    return;
                }

                if ($immediateSequence->action_name == 'fluentcrm_wait_times') {
                    $waitTimes = FunnelHelper::getCurrentDelayInSeconds($immediateSequence->settings, $sequence, $funnelSubscriberId);
                }
            }

            if ($nextSequence) {

                $waitDateTimes = date('Y-m-d H:i:s', current_time('timestamp') + $nextSequence->delay);

                if ($waitTimes) {
                    $waitDateTimes = date('Y-m-d H:i:s', current_time('timestamp') + $waitTimes);
                }

                return FunnelSubscriber::where('id', $funnelSubscriberId)
                    ->update([
                        'next_sequence'       => $nextSequence->sequence,
                        'next_sequence_id'    => $nextSequence->id,
                        'next_execution_time' => $waitDateTimes,
                        'status'              => 'active'
                    ]);
            }
        }

        $funnelSubscriber = FunnelSubscriber::where('id', $funnelSubscriberId)->first();
        if (!$funnelSubscriber) {
            return false;
        }

        $funnelSubscriber->last_sequence_id = $parent->id;
        $funnel = $this->getFunnel($parent->funnel_id);

        // we don't have next sequence so we have to loop back to the parent
        $sequencePoints = new SequencePoints($funnel, $funnelSubscriber);

        if ($waitTimes && $currentNextSequences = $sequencePoints->getCurrentSequences()) {
            $nextSequence = $currentNextSequences[0];
            return FunnelSubscriber::where('id', $funnelSubscriberId)
                ->update([
                    'next_sequence'       => $nextSequence->sequence,
                    'next_sequence_id'    => $nextSequence->id,
                    'next_execution_time' => date('Y-m-d H:i:s', strtotime(current_time('mysql')) + $waitTimes),
                    'status'              => 'active'
                ]);
        }

        $this->processSequencePoints($sequencePoints, $subscriber, $funnelSubscriber);
    }
}
