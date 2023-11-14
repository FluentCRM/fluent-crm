<?php

namespace FluentCrm\App\Services\Funnel;

use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\Framework\Support\Arr;

class SequencePoints
{
    private $nextSequence = null;

    private $immediateSequences = [];

    private $lastSequence = null;

    private $requiredBenchMark = null;

    private $funnel;

    private $hasNext = null;

    private $funnelSubscriber;

    private $nextSequenceExecutionTime = false;

    public $hasEndSequence = false;

    public function __construct($funnel, $funnelSubscriber = false)
    {
        $this->funnel = $funnel;
        $this->funnelSubscriber = $funnelSubscriber;
        $this->setupData();
    }

    private function setupData()
    {
        if ($this->funnelSubscriber && $this->funnelSubscriber->last_sequence_id) {
            $this->lastSequence = FunnelSequence::where('id', $this->funnelSubscriber->last_sequence_id)->first();
        }

        $isInChild = false;

        if ($this->lastSequence) {
            $query = FunnelSequence::orderBy('sequence', 'ASC')
                ->where('funnel_id', $this->funnel->id)
                ->where('sequence', '>', $this->lastSequence->sequence);

            if ($this->lastSequence->parent_id) {
                $isInChild = true;
                // We just have to find the same child-block sequences
                $query->where('parent_id', $this->lastSequence->parent_id)
                    ->where('condition_type', $this->lastSequence->condition_type);
            }

            $sequences = $query->get();

            if ($isInChild && $sequences->isEmpty()) {
                // No sequences found with the conditions so we may have to
                // move to parent again
                $nextSequenceNumber = $this->funnelSubscriber->next_sequence;
                if ($this->funnelSubscriber->next_sequence_item) {
                    $nextSequenceNumber = $this->funnelSubscriber->next_sequence_item->sequence;
                }

                $sequences = FunnelSequence::orderBy('sequence', 'ASC')
                    ->where('funnel_id', $this->funnel->id)
                    ->where('sequence', '>=', $nextSequenceNumber)
                    ->get();
            }
        } else {
            $sequences = FunnelSequence::orderBy('sequence', 'ASC')
                ->where('funnel_id', $this->funnel->id)
                ->get();
        }

        if (!$sequences || $sequences->isEmpty()) {
            return;
        }

        $immediateSequences = [];
        $firstSequence = $sequences[0];
        $conditionalBlock = false;
        $inWaitTimes = false;

        $hasEndSequence = false;

        foreach ($sequences as $sequence) {
            if ($this->requiredBenchMark || $conditionalBlock || $hasEndSequence) {
                continue;
            }

            if (!$isInChild && $sequence->parent_id) {
                /*
                 * Something is wrong here. Maybe admin added new condition after initiating the items
                 * So we are just skipping these items
                 */
                continue;
            }

            if ($sequence->action_name == 'fluentcrm_wait_times' && !$inWaitTimes) {
                $inWaitTimes = true;
                $seconds = FunnelHelper::getCurrentDelayInSeconds($sequence->settings);
                $this->nextSequenceExecutionTime = date('Y-m-d H:i:s', current_time('timestamp') + $seconds);
            }

            /*
             * Check if there has a required sequence for this.
             */
            if ($sequence->type == 'benchmark') {
                if ($sequence->settings['type'] == 'required') {
                    $this->requiredBenchMark = $sequence;
                }
                continue;
            }

            if ($sequence->type == 'conditional') {
                $conditionalBlock = $sequence;
            }

            if ($sequence->c_delay == $firstSequence->c_delay) {
                $immediateSequences[] = $sequence;

                if($sequence->action_name == 'end_this_funnel') {
                    $hasEndSequence = true;
                }

            } else {
                if (!$this->nextSequence) {
                    $this->hasNext = true;
                    $this->nextSequence = $sequence;
                }
                if ($sequence->c_delay < $this->nextSequence->c_delay) {
                    $this->nextSequence = $sequence;
                }
            }
        }

        if ($conditionalBlock) {
            $this->hasNext = true;
        }

        $this->immediateSequences = $immediateSequences;

        if (!$this->nextSequence && $isInChild && !$conditionalBlock) {
            // let's find the parent sequence
            $parentSequence = FunnelSequence::where('id', $this->lastSequence->id)->first();
            if ($parentSequence) {
                $sequences = FunnelSequence::where('funnel_id', $this->funnel->id)
                    ->where('sequence', '>', $parentSequence->sequence)
                    ->where(function ($q) {
                        $q->whereNull('parent_id')
                            ->orWhere('parent_id', '0');
                        return $q;
                    })
                    ->orderBy('sequence', 'ASC')
                    ->get();

                if ($sequences->isEmpty()) {
                    return;
                }

                if ($inWaitTimes) {
                    $this->hasNext = true;
                    $this->nextSequence = $sequences[0];
                    if($this->nextSequenceExecutionTime) {
                        $this->nextSequence->execution_date_time = $this->nextSequenceExecutionTime;
                    }
                    return;
                }

                $firstSequence = $sequences[0];
                $conditionalBlock = false;

                foreach ($sequences as $sequence) {
                    if ($this->requiredBenchMark || $conditionalBlock || $hasEndSequence) {
                        continue;
                    }

                    /*
                     * Check if there has a required sequence for this.
                     */
                    if ($sequence->type == 'benchmark') {
                        if ($sequence->settings['type'] == 'required') {
                            $this->requiredBenchMark = $sequence;
                        }
                        continue;
                    }

                    if ($sequence->type == 'conditional') {
                        $conditionalBlock = $sequence;
                    }

                    if ($sequence->c_delay == $firstSequence->c_delay) {
                        $this->immediateSequences[] = $sequence;
                        if($sequence->action_name == 'end_this_funnel') {
                            $hasEndSequence = true;
                        }
                    } else {
                        if (!$this->nextSequence) {
                            $this->hasNext = true;
                            $this->nextSequence = $sequence;
                        }
                        if ($sequence->c_delay < $this->nextSequence->c_delay) {
                            $this->nextSequence = $sequence;
                        }
                    }
                }
            }
        }

        if ($this->nextSequence && $this->nextSequenceExecutionTime) {
            $this->nextSequence->execution_date_time = $this->nextSequenceExecutionTime;
        }

        $this->hasEndSequence = $hasEndSequence;
    }

    public function getCurrentSequences()
    {
        return $this->immediateSequences;
    }

    public function getNextSequence()
    {
        return $this->nextSequence;
    }

    public function hasNext()
    {
        return $this->hasNext || !!$this->nextSequence;
    }

    public function getRequiredBenchmark()
    {
        return $this->requiredBenchMark;
    }

    public function hasSequences()
    {
        return !!$this->requiredBenchMark || !!$this->immediateSequences;
    }
}
