<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\FunnelHandler;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Services\Reporting;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Request\Request;
use FluentValidator\ValidationException;

class FunnelController extends Controller
{
    public function funnels()
    {
        $funnels = Funnel::orderBy('id', 'DESC')->paginate();
        $with = $this->request->get('with', []);
        $data = [
            'funnels' => $funnels
        ];
        if (in_array('triggers', $with)) {
            $data['triggers'] = $this->getTriggers();
        }

        return $this->sendSuccess($data);
    }

    public function getFunnel(Request $request, $funnelId)
    {
        $with = $request->get('with', []);

        $funnel = Funnel::find($funnelId);


        if (!$funnel) {
            return $this->sendError([
                'message' => 'No Funnel found'
            ]);
        }
        $triggers = $this->getTriggers();
        if (isset($triggers[$funnel->trigger_name])) {
            $funnel->trigger = $triggers[$funnel->trigger_name];
        }

        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        $data = [
            'funnel' => $funnel
        ];

        if (in_array('blocks', $with)) {
            $data['blocks'] = $this->getBlocks($funnel);
        }

        if (in_array('block_fields', $with)) {
            $data['block_fields'] = $this->getBlockFields($funnel);
        }

        if (in_array('funnel_sequences', $with)) {
            $data['funnel_sequences'] = $this->getFunnelSequences($funnel, true);
        }

        return $this->sendSuccess($data);
    }

    public function create(Request $request)
    {
        try {
            $funnel = $this->validate($request->get('funnel'), [
                'title'        => 'required',
                'trigger_name' => 'required'
            ]);

            $funnelData = Arr::only($funnel, ['title', 'trigger_name']);
            $funnelData['status'] = 'draft';
            $funnelData['settings'] = [];
            $funnelData['conditions'] = [];
            $funnelData['created_by'] = get_current_user_id();
            $funnel = Funnel::create($funnelData);

            return $this->sendSuccess([
                'funnel'  => $funnel,
                'message' => __('Funnel has been created. Please configure now')
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrors($e);
        }
    }

    public function delete(Request $request, $funnelId)
    {
        Funnel::where('id', $funnelId)->delete();
        FunnelSequence::where('funnel_id', $funnelId)->delete();
        FunnelSubscriber::where('funnel_id', $funnelId)->delete();

        return $this->sendSuccess([
            'message' => __('Funnel has been deleted', 'fluentcampaign')
        ]);
    }

    private function getTriggers()
    {
        return apply_filters('fluentcrm_funnel_triggers', []);
    }

    private function getBlocks($funnel)
    {
        return apply_filters('fluentcrm_funnel_blocks', [], $funnel);
    }

    private function getBlockFields($funnel)
    {
        return apply_filters('fluentcrm_funnel_block_fields', [], $funnel);
    }

    public function getFunnelSequences($funnel, $isFiltered = false)
    {
        $sequences = FunnelSequence::where('funnel_id', $funnel->id)
            ->orderBy('sequence', 'ASC')
            ->get();


        if (!$isFiltered) {
            return $sequences;
        }

        $formattedSequences = [];
        foreach ($sequences as $sequence) {
            $sequenceArray = $sequence->toArray();
            $formattedSequences[] = apply_filters('fluentcrm_funnel_sequence_filtered_' . $sequence->action_name, $sequenceArray, $funnel);
        }

        return $formattedSequences;
    }

    public function saveSequences(Request $request, $funnelId)
    {
        $funnelSettings = \json_decode($request->get('funnel_settings'), true);

        $funnelConditions = \json_decode($request->get('conditions', []), true);

        $funnel = Funnel::findOrFail($funnelId);
        $funnel->settings = $funnelSettings;
        if ($funnelTitle = $request->get('funnel_title')) {
            $funnel->title = sanitize_text_field($funnelTitle);
        }

        $funnel->conditions = $funnelConditions;
        $funnel->status = $request->get('status');
        $funnel->save();

        $sequences = \json_decode($request->get('sequences'), true);

        $sequenceIds = [];
        $cDelay = 0;
        $delay = 0;

        foreach ($sequences as $index => $sequence) {
            // it's creatable
            $sequence['funnel_id'] = $funnel->id;
            $sequence['status'] = 'published';
            $sequence['conditions'] = [];
            $sequence['sequence'] = $index + 1;
            $sequence['c_delay'] = $cDelay;
            $sequence['delay'] = $delay;
            $delay = 0;

            $actionName = $sequence['action_name'];

            if ($actionName == 'fluentcrm_wait_times') {
                $unit = Arr::get($sequence, 'settings.wait_time_unit');
                $converter = 86400; // default day
                if ($unit == 'hours') {
                    $converter = 3600; // hour
                } else if ($unit == 'minutes') {
                    $converter = 60;
                }
                $time = Arr::get($sequence, 'settings.wait_time_amount');
                $delay = intval($time * $converter);
                $cDelay += $delay;
            }

            $sequence = apply_filters('fluentcrm_funnel_sequence_saving_' . $sequence['action_name'], $sequence, $funnel);
            if (Arr::get($sequence, 'type') == 'benchmark') {
                $delay = $sequence['delay'];
            }

            if (empty($sequence['id'])) {
                $sequence['created_by'] = get_current_user_id();
                $createdSequence = FunnelSequence::create($sequence);
                $sequenceIds[] = $createdSequence->id;
            } else {
                $sequenceId = $sequence['id'];
                $sequenceIds[] = $sequenceId;
                $sequence['updated_at'] = current_time('mysql');
                $sequence['settings'] = \maybe_serialize($sequence['settings']);
                $sequence['conditions'] = \maybe_serialize($sequence['conditions']);
                FunnelSequence::where('id', $sequenceId)->update($sequence);
            }
        }

        if ($sequenceIds) {
            $deletingSequences = FunnelSequence::whereNotIn('id', $sequenceIds)
                ->where('funnel_id', $funnel->id)
                ->get();
        } else {
            $deletingSequences = FunnelSequence::where('funnel_id', $funnel->id)->get();
        }

        if ($deletingSequences->count()) {
            foreach ($deletingSequences as $deletingSequence) {
                do_action('fluentcrm_funnel_sequence_deleting_' . $deletingSequence->action_name, $deletingSequence, $funnel);
                $deletingSequence->delete();
            }
        }

        (new FunnelHandler())->resetFunnelIndexes();

        return $this->sendSuccess([
            'sequences' => $this->getFunnelSequences($funnel, true),
            'message'   => __('Sequence successfully updated', 'fluentcampaign')
        ]);
    }

    public function getSubscribers(Request $request, $funnelId)
    {

        $funnelSubscribers = FunnelSubscriber::with([
            'subscriber',
            'metrics' => function ($query) use ($funnelId) {
                $query->where('funnel_id', $funnelId);
            }
        ])
            ->orderBy('id', 'DESC')
            ->where('funnel_id', $funnelId)
            ->paginate();

        $data = [
            'funnel_subscribers' => $funnelSubscribers,
            'funnel'             => Funnel::find($funnelId)
        ];

        if (in_array('sequences', $request->get('with', []))) {
            $sequences = FunnelSequence::where('funnel_id', $funnelId)->get();
            $formattedSequences = [];
            foreach ($sequences as $sequence) {
                $formattedSequences[$sequence->id] = $sequence;
            }
            $data['sequences'] = $formattedSequences;
        }

        return $this->sendSuccess($data);
    }

    public function report(Request $request, Reporting $reporting, $funnelId)
    {
        return [
            'stats' => $reporting->funnelStat($funnelId)
        ];
    }
}
