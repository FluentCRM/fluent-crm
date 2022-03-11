<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\FunnelHandler;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\CustomEmailCampaign;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelCampaign;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Reporting;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;
use FluentCrm\Framework\Validator\ValidationException;

/**
 *  FunnelController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class FunnelController extends Controller
{
    public function funnels(Request $request)
    {
        $this->maybeMigrateDB();

        $orderBy = $request->get('sort_by', 'id');
        $orderType = $request->get('sort_type', 'DESC');

        $funnelQuery = Funnel::orderBy($orderBy, $orderType);
        if ($search = $request->get('search')) {
            $funnelQuery->where('title', 'LIKE', '%%' . $search . '%%');
        }
        $funnels = $funnelQuery->paginate();
        $with = $this->request->get('with', []);

        foreach ($funnels as $funnel) {
            $funnel->subscribers_count = $funnel->getSubscribersCount();
        }

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
        $funnel = Funnel::findOrFail($funnelId);

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
            $data['composer_context_codes'] = apply_filters('fluent_crm_funnel_context_smart_codes', [], $funnel->trigger_name, $funnel);
        }

        if (in_array('funnel_sequences', $with)) {
            FunnelHelper::maybeMigrateConditions($funnel->id);
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
                'message' => __('Funnel has been created. Please configure now', 'fluent-crm')
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
            'message' => __('Funnel has been deleted', 'fluent-crm')
        ]);
    }

    public function getTriggersRest()
    {
        return [
            'triggers' => $this->getTriggers()
        ];
    }

    public function changeTrigger(Request $request, $funnelId)
    {
        $data = $request->only(['title', 'trigger_name']);

        $this->validate($data, [
            'trigger_name' => 'required',
            'title'        => 'required'
        ]);

        $funnel = Funnel::findOrFail($funnelId);

        if ($funnel->trigger_name == $data['trigger_name']) {
            return $this->sendError([
                'message' => __('Trigger name is same', 'fluent-crm')
            ]);
        }

        $funnel->trigger_name = sanitize_text_field($data['trigger_name']);
        $funnel->title = sanitize_text_field($data['title']);

        $funnel->settings = [];
        $funnel->conditions = [];
        $funnel->save();

        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        return $this->sendSuccess([
            'message' => __('Funnel Trigger has been successfully updated', 'fluent-crm'),
            'funnel'  => $funnel
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
        $sequences = FunnelHelper::getFunnelSequences($funnel, $isFiltered);
        $formattedSequences = [];
        $childs = [];

        foreach ($sequences as $sequence) {
            if ($sequence['type'] == 'conditional') {
                $sequence['children'] = [
                    'yes' => [],
                    'no'  => []
                ];
            }

            if ($parentId = Arr::get($sequence, 'parent_id')) {
                if (!isset($childs[$parentId]['yes'])) {
                    $childs[$parentId]['yes'] = [];
                }
                if (!isset($childs[$parentId]['no'])) {
                    $childs[$parentId]['no'] = [];
                }
                $childs[$parentId][$sequence['condition_type']][] = $sequence;
            } else {
                $formattedSequences[$sequence['id']] = $sequence;
            }
        }

        if ($childs) {
            foreach ($childs as $sequenceId => $children) {
                if (isset($formattedSequences[$sequenceId])) {
                    $formattedSequences[$sequenceId]['children'] = $children;
                }
            }
        }

        return array_values($formattedSequences);
    }

    public function saveSequences(Request $request, $funnelId)
    {
        $data = $request->all();

        $funnel = FunnelHelper::saveFunnelSequence($funnelId, $data);

        return $this->sendSuccess([
            'sequences' => $this->getFunnelSequences($funnel, true),
            'message'   => __('Sequence successfully updated', 'fluent-crm')
        ]);
    }

    public function getSubscribers(Request $request, $funnelId)
    {

        $search = $request->get('search');

        $funnelSubscribersQuery = FunnelSubscriber::with([
            'subscriber',
            'last_sequence',
            'next_sequence_item',
            'metrics' => function ($query) use ($funnelId) {
                $query->where('funnel_id', $funnelId);
            }
        ])
            ->orderBy('id', 'DESC')
            ->where('funnel_id', $funnelId);

        if ($search) {
            $funnelSubscribersQuery->whereHas('subscriber', function ($q) use ($search) {
                $q->searchBy($search);
            });
        }

        $funnelSubscribers = $funnelSubscribersQuery->paginate();

        $data = [
            'funnel_subscribers' => $funnelSubscribers,
            'funnel'             => Funnel::find($funnelId)
        ];

        if (in_array('sequences', $request->get('with', []))) {
            $sequences = FunnelSequence::where('funnel_id', $funnelId)
                ->orderBy('sequence', 'ASC')
                ->get();
            $formattedSequences = [];
            foreach ($sequences as $sequence) {
                $formattedSequences[] = $sequence;
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

    public function cloneFunnel(Request $request, $funnelId)
    {
        $oldFunnel = Funnel::findOrFail($funnelId);

        $newFunnelData = [
            'title'        => '[Copy] ' . $oldFunnel->title,
            'trigger_name' => $oldFunnel->trigger_name,
            'status'       => 'draft',
            'conditions'   => $oldFunnel->conditions,
            'settings'     => $oldFunnel->settings,
            'created_by'   => get_current_user_id()
        ];

        $funnel = Funnel::create($newFunnelData);

        $sequences = FunnelHelper::getFunnelSequences($oldFunnel, true);

        $sequenceIds = [];
        $cDelay = 0;
        $delay = 0;

        $childs = [];
        $oldNewMaps = [];

        foreach ($sequences as $index => $sequence) {
            $oldId = $sequence['id'];
            unset($sequence['id']);
            unset($sequence['created_at']);
            unset($sequence['updated_at']);

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

            $sequence['created_by'] = get_current_user_id();

            $parentId = Arr::get($sequence, 'parent_id');

            if ($parentId) {
                $childs[$parentId][] = $sequence;
            } else {
                $createdSequence = FunnelSequence::create($sequence);
                $sequenceIds[] = $createdSequence->id;
                $oldNewMaps[$oldId] = $createdSequence->id;
            }
        }

        if ($childs) {
            foreach ($childs as $oldParentId => $childBlocks) {
                foreach ($childBlocks as $childBlock) {
                    $newParentId = Arr::get($oldNewMaps, $oldParentId);
                    if ($newParentId) {
                        $childBlock['parent_id'] = $newParentId;
                        $createdSequence = FunnelSequence::create($childBlock);
                        $sequenceIds[] = $createdSequence->id;
                    }
                }
            }
        }

        FunnelHelper::maybeMigrateConditions($funnel->id);
        (new FunnelHandler())->resetFunnelIndexes();

        return [
            'message' => __('Funnel has been successfully cloned', 'fluent-crm'),
            'funnel'  => $funnel
        ];
    }

    public function importFunnel(Request $request)
    {
        $funnelArray = $request->get('funnel');
        $sequences = $request->getJson('sequences');

        $newFunnelData = [
            'title'        => $funnelArray['title'],
            'trigger_name' => $funnelArray['trigger_name'],
            'status'       => 'draft',
            'conditions'   => Arr::get($funnelArray, 'conditions', []),
            'settings'     => $funnelArray['settings'],
            'created_by'   => get_current_user_id()
        ];

        $funnel = Funnel::create($newFunnelData);

        $sequenceIds = [];
        $cDelay = 0;
        $delay = 0;

        $childs = [];
        $oldNewMaps = [];


        foreach ($sequences as $index => $sequence) {
            $oldId = $sequence['id'];
            unset($sequence['id']);
            unset($sequence['created_at']);
            unset($sequence['updated_at']);
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

            $sequence['created_by'] = get_current_user_id();

            $parentId = Arr::get($sequence, 'parent_id');

            if ($parentId) {
                $childs[$parentId][] = $sequence;
            } else {
                $createdSequence = FunnelSequence::create($sequence);
                $sequenceIds[] = $createdSequence->id;
                $oldNewMaps[$oldId] = $createdSequence->id;
            }
        }

        if ($childs) {
            foreach ($childs as $oldParentId => $childBlocks) {
                foreach ($childBlocks as $childBlock) {
                    $newParentId = Arr::get($oldNewMaps, $oldParentId);
                    if ($newParentId) {
                        $childBlock['parent_id'] = $newParentId;
                        $createdSequence = FunnelSequence::create($childBlock);
                        $sequenceIds[] = $createdSequence->id;
                    }
                }
            }
        }

        (new FunnelHandler())->resetFunnelIndexes();
        FunnelHelper::maybeMigrateConditions($funnel->id);

        return [
            'message' => __('Funnel has been successfully imported', 'fluent-crm'),
            'funnel'  => $funnel
        ];

    }

    public function deleteSubscribers(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $ids = $request->get('subscriber_ids');
        if (!$ids) {
            return $this->sendError([
                'message' => __('subscriber_ids parameter is required', 'fluent-crm')
            ]);
        }

        FunnelHelper::removeSubscribersFromFunnel($funnelId, $ids);

        return [
            'message' => __('Subscribed has been removed from this automation funnel', 'fluent-crm')
        ];
    }

    public function subscriberAutomations(Request $request, $subscriberId)
    {
        $automations = FunnelSubscriber::where('subscriber_id', $subscriberId)
            ->with([
                'funnel',
                'last_sequence',
                'next_sequence_item'
            ])
            ->orderBy('id', 'DESC')
            ->paginate();

        return [
            'automations' => $automations
        ];
    }

    public function updateSubscriptionStatus(Request $request, $funnelId, $subscriberId)
    {
        $status = $request->get('status');
        if (!$status) {
            return $this->sendError([
                'message' => __('Subscription status is required', 'fluent-crm')
            ]);
        }

        $funnelSubscriber = FunnelSubscriber::where('funnel_id', $funnelId)
            ->where('subscriber_id', $subscriberId)
            ->first();

        if (!$funnelSubscriber) {
            return $this->sendError([
                'message' => __('No Corresponding report found', 'fluent-crm')
            ]);
        }

        if ($funnelSubscriber->status == 'completed') {
            return $this->sendError([
                'message' => __('The status already completed state', 'fluent-crm')
            ]);
        }

        $funnelSubscriber->status = $status;
        $funnelSubscriber->save();

        return [
            'message' => sprintf(esc_html__('Status has been updated to %s', 'fluent-crm'), $status)
        ];
    }

    private function maybeMigrateDB()
    {
        $sequence = \FluentCrm\App\Models\FunnelSequence::first();
        $isMigrated = false;
        global $wpdb;
        if ($sequence) {
            $attributes = $sequence->getAttributes();
            if (isset($attributes['parent_id'])) {
                $isMigrated = true;
            }
        } else {
            $isMigrated = $wpdb->get_col($wpdb->prepare("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME='parent_id' AND TABLE_NAME=%s", $wpdb->prefix . 'fc_funnel_sequences'));
        }

        if (!$isMigrated) {
            $sequenceTable = $wpdb->prefix . 'fc_funnel_sequences';
            $wpdb->query("ALTER TABLE {$sequenceTable} ADD COLUMN `parent_id` bigint NOT NULL DEFAULT '0', ADD `condition_type` varchar(192) NULL AFTER `parent_id`");
        }
    }

    public function getEmailReports(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $emailSequences = FunnelSequence::where('funnel_id', $funnel->id)
            ->orderBy('sequence', 'ASC')
            ->where('action_name', 'send_custom_email')
            ->get();
        foreach ($emailSequences as $emailSequence) {
            $campaign = FunnelCampaign::where('id', $emailSequence->settings['reference_campaign'])->first();
            $emailSequence->campaign = [
                'subject' => $campaign->email_subject,
                'id'      => $campaign->id,
                'stats'   => $campaign->stats()
            ];
        }

        return [
            'email_sequences' => $emailSequences
        ];
    }
}
