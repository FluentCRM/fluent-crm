<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\FunnelHandler;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelCampaign;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\Label;
use FluentCrm\App\Models\Meta;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\TermRelation;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\ProFunnelItems;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Libs\Parser\Parser;
use FluentCrm\App\Services\Reporting;
use FluentCrm\App\Services\Sanitize;
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

        $orderBy = $request->getSafe('sort_by', 'id', 'sanitize_sql_orderby');
        $orderType = $request->getSafe('sort_type', 'DESC', 'sanitize_sql_orderby');

        $labels = $request->getSafe('labels', [], 'intval');

        $funnelQuery = Funnel::orderBy($orderBy, $orderType);
        if ($search = $request->getSafe('search')) {
            $funnelQuery->where('title', 'LIKE', '%%' . $search . '%%');
        }

        if (!empty($labels)) {
            $funnelQuery->whereHas('labelsTerm', function ($query) use ($labels) {
                $query->whereIn('term_id', $labels);
            });
        }
        $funnels = $funnelQuery->paginate();
        $with = $this->request->get('with', []);
        foreach ($funnels as $funnel) {
            $funnel->subscribers_count = $funnel->getSubscribersCount();
            $funnel->description = $funnel->getMeta('description');
            $funnel->labels = $funnel->getFormattedLabels();
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

        if (defined('MEPR_PLUGIN_NAME')) {
            // Maybe trigger name changed
            $migrationMaps = [
                'recurring-transaction-expired' => 'mepr-event-transaction-expired'
            ];

            if (isset($migrationMaps[$funnel->trigger_name])) {
                $funnel->trigger_name = $migrationMaps[$funnel->trigger_name];
                $funnel->save();
            }
        }

        $triggers = $this->getTriggers();
        if (isset($triggers[$funnel->trigger_name])) {
            $funnel->trigger = $triggers[$funnel->trigger_name];
        }

        /**
         * Determine the funnel editor details based on the funnel trigger name.
         *
         * The dynamic portion of the hook name, `$funnel->trigger_name`, refers to the trigger name of the funnel.
         *
         * @since 1.0.0
         *
         * @param object $funnel The funnel object containing the editor details.
         */
        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        $funnel->description = $funnel->getMeta('description');

        if (!$funnel->settings) {
            $funnel->settings = (object)[];
        }

        $data = [
            'funnel' => $funnel
        ];

        if (in_array('blocks', $with)) {
            $data['blocks'] = $this->getBlocks($funnel);
        }

        if (in_array('block_fields', $with)) {
            $data['block_fields'] = $this->getBlockFields($funnel);
            /**
             * Determine the smart codes for a funnel based on the context.
             *
             * This filter allows modification of the context smart codes used in a funnel based on the funnel's trigger name.
             *
             * @since 2.5.7
             *
             * @param array  An array of context smart codes.
             * @param string $funnel->trigger_name   The name of the funnel trigger.
             * @param object $funnel                 The funnel object.
             */
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
                'trigger_name' => 'required'
            ]);

            $description = sanitize_textarea_field(Arr::get($funnel, 'description'));

            $funnelData = Arr::only($funnel, ['title', 'trigger_name']);


            if (empty($funnelData['title'])) {
                $allTriggers = $this->getTriggers();
                $label = Arr::get($allTriggers, $funnelData['trigger_name'] . '.label', 'Unknown Automation');
                $funnelData['title'] = $label . ' (Created at ' . date('Y-m-d') . ')';
            }

            $funnelData['status'] = 'draft';
            $funnelData['settings'] = [];
            $funnelData['conditions'] = [];
            $funnelData['created_by'] = get_current_user_id();

            $funnelData = Sanitize::funnel($funnelData);
            $funnel = Funnel::create($funnelData);

            if ($description) {
                $funnel->updateMeta('description', $description);
            }

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
        $funnel = Funnel::findOrFail($funnelId);
        $funnel->deleteMeta('description');
        $funnel->deleteMeta('funnel_label');

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

        /**
         * Determine the funnel editor details based on the funnel's trigger name in FluentCRM.
         *
         * The dynamic portion of the hook name, `$funnel->trigger_name`, refers to the trigger name of the funnel.
         *
         * @since 2.3.1
         *
         * @param object $funnel The funnel object containing the editor details.
         */
        $funnel = apply_filters('fluentcrm_funnel_editor_details_' . $funnel->trigger_name, $funnel);

        return $this->sendSuccess([
            'message' => __('Funnel Trigger has been successfully updated', 'fluent-crm'),
            'funnel'  => $funnel
        ]);

    }

    private function getTriggers()
    {
        /**
         * Determine the list of funnel triggers in FluentCRM.
         *
         * This filter allows you to modify the array of funnel triggers.
         *
         * @since 1.0.0
         *
         * @param array An array of funnel triggers.
         */
        return apply_filters('fluentcrm_funnel_triggers', []);
    }

    private function getBlocks($funnel)
    {
        /**
         * Determine the funnel blocks.
         *
         * This filter allows modification of the funnel blocks.
         *
         * @since 1.0.0
         *
         * @param array An array of funnel blocks.
         * @param mixed $funnel The funnel object or data.
         */
        return apply_filters('fluentcrm_funnel_blocks', [], $funnel);
    }

    private function getBlockFields($funnel)
    {
        /**
         * Determine the funnel block fields.
         *
         * This filter allows modification of the funnel block fields.
         *
         * @since 1.0.0
         *
         * @param array  The current funnel block fields.
         * @param object $funnel The funnel object.
         */
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
            } else if ($sequence['type'] == 'benchmark') {
                //  @todo: we may delete this mid 2023
                if (empty($sequence['settings']['can_enter'])) {
                    $sequence['settings']['can_enter'] = 'yes';
                }
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

    public function saveSequencesFallback(Request $request)
    {
        $funnelId = $request->get('funnel_id');
        return $this->saveSequences($request, $funnelId);
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

        $funnel = Funnel::findOrFail($funnelId);

        $search = $request->getSafe('search');
        $status = $request->getSafe('status', '');

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

        $sequenceId = (int)$request->get('sequence_id');
        if ($sequenceId) {
            $funnelSubscribersQuery->whereHas('metrics', function ($q) use ($sequenceId) {
                $q->where('sequence_id', $sequenceId);
            });
        }

        if ($status) {
            $funnelSubscribersQuery->where('status', $status);
        }

        $funnelSubscribers = $funnelSubscribersQuery->paginate();

        $data = [
            'funnel_subscribers' => $funnelSubscribers
        ];

        $with = $request->get('with', []);

        if (in_array('funnel', $with)) {
            $data['funnel'] = $funnel;
        }

        if (in_array('sequences', $with)) {
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

    public function getSubscriberReporting(Request $request, $funnelId, $contactId)
    {
        Funnel::findOrFail($funnelId);
        Subscriber::findOrFail($contactId);

        $funnelSubscriber = FunnelSubscriber::with([
            'last_sequence',
            'next_sequence_item',
            'metrics' => function ($query) use ($funnelId) {
                $query->where('funnel_id', $funnelId);
            }
        ])
            ->where('funnel_id', $funnelId)
            ->where('subscriber_id', $contactId)
            ->first();

        $sequences = FunnelSequence::where('funnel_id', $funnelId)
            ->orderBy('sequence', 'ASC')
            ->get();

        $formattedSequences = [];
        foreach ($sequences as $sequence) {
            $formattedSequences[] = $sequence;
        }

        return [
            'funnel_subscriber' => $funnelSubscriber,
            'sequences'         => $formattedSequences
        ];
    }

    public function getAllActivities(Request $request)
    {
        $search = $request->getSafe('search');
        $status = $request->getSafe('status', '');

        $funnelSubscribersQuery = FunnelSubscriber::with([
            'subscriber',
            'last_sequence',
            'next_sequence_item',
            'funnel.actions' => function ($query) {
                $query->orderBy('sequence', 'ASC');
            }
        ])
            ->orderBy('id', 'DESC');

        if ($search) {
            $funnelSubscribersQuery->whereHas('subscriber', function ($q) use ($search) {
                $q->searchBy($search);
            });
        }

        if ($status) {
            $funnelSubscribersQuery->where('status', $status);
        }

        $funnelSubscribers = $funnelSubscribersQuery->paginate();

        foreach ($funnelSubscribers as $funnelSubscriber) {
            $funnelSubscriber->metrics = FunnelMetric::where('funnel_id', $funnelSubscriber->funnel_id)
                ->where('subscriber_id', $funnelSubscriber->subscriber_id)
                ->get();
        }

        return [
            'activities' => $funnelSubscribers
        ];
    }

    public function removeBulkSubscribers(Request $request)
    {
        $funnel_subscriber_ids = $request->get('funnel_subscriber_ids', []);

        $funnel_subscriber_ids = array_map('intval', $funnel_subscriber_ids);

        if (!$funnel_subscriber_ids) {
            return $this->sendError([
                'message' => __('Please provide funnel subscriber IDs', 'fluent-crm')
            ]);
        }

        $items = FunnelSubscriber::whereIn('id', $funnel_subscriber_ids)->get();

        foreach ($items as $item) {
            FunnelMetric::where('funnel_id', $item->funnel_id)
                ->where('subscriber_id', $item->subscriber_id)
                ->delete();
        }

        FunnelSubscriber::whereIn('id', $funnel_subscriber_ids)->delete();

        return [
            'message' => __('Selected subscribers has been removed from this automation funnels', 'fluent-crm')
        ];
    }

    public function report(Request $request, Reporting $reporting, $funnelId)
    {
        return [
            'stats' => $reporting->funnelStat($funnelId)
        ];
    }

    public function updateFunnelProperty(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $newStatus = $request->getSafe('status');

        if ($funnel->status == $newStatus) {
            return $this->sendError([
                'message' => __('Funnel already have the same status', 'fluent-crm')
            ]);
        }

        $funnel->status = $newStatus;
        $funnel->save();

        return $this->sendSuccess([
            'message' => __(sprintf('Status has been updated to %s', $newStatus), 'fluent-crm')
        ]);
    }

    public function handleBulkAction(Request $request)
    {
        $actionName = $request->getSafe('action_name', '');

        $funnelIds = $request->getSafe('funnel_ids', [], 'intval');

        $funnelIds = array_unique(array_filter($funnelIds));

        if (!$funnelIds) {
            return $this->sendError([
                'message' => __('Please provide funnel IDs', 'fluent-crm')
            ]);
        }

        if ($actionName == 'change_funnel_status') {
            $newStatus = sanitize_text_field($request->get('status', ''));
            if (!$newStatus) {
                return $this->sendError([
                    'message' => __('Please select status', 'fluent-crm')
                ]);
            }

            $funnels = Funnel::whereIn('id', $funnelIds)->get();

            foreach ($funnels as $funnel) {
                $oldStatus = $funnel->status;
                if ($oldStatus != $newStatus) {
                    $funnel->status = $newStatus;
                    $funnel->save();
                }
            }

            (new FunnelHandler())->resetFunnelIndexes();

            return $this->sendSuccess([
                'message' => __('Status has been changed for the selected funnels', 'fluent-crm')
            ]);
        }

        if ($actionName == 'delete_funnels') {

            $funnels = Funnel::whereIn('id', $funnelIds)->get();

            foreach ($funnels as $funnel) {
                $sequences = FunnelSequence::whereIn('funnel_id', $funnelIds)->get();

                $labelIds = TermRelation::where('object_id', $funnel->id)
                    ->where('object_type', Funnel::class)
                    ->pluck('term_id')
                    ->toArray();
                if (!empty($labelIds)) {
                    $funnel->detachLabels($labelIds);
                }

                foreach ($sequences as $deletingSequence) {
                    do_action('fluentcrm_funnel_sequence_deleting_' . $deletingSequence->action_name, $deletingSequence, $funnel);
                    $deletingSequence->delete();
                }
                FunnelSubscriber::whereIn('funnel_id', $funnelIds)->delete();

                $funnel->deleteMeta('funnel_label');
                $funnel->delete();
            }

            (new FunnelHandler())->resetFunnelIndexes();

            return $this->sendSuccess([
                'message' => __('Selected Funnels has been deleted permanently', 'fluent-crm'),
            ]);

        }

        if ($actionName == 'apply_labels') {

            $newLabels = $request->getSafe('labels', '');

            if (!$newLabels) {
                return $this->sendError([
                    'message' => __('Please provide labels', 'fluent-crm')
                ]);
            }

            $funnels = Funnel::whereIn('id', $funnelIds)->get();

            foreach ($funnels as $funnel) {
                $funnel->attachLabels($newLabels);
            }

            return $this->sendSuccess([
                'message' => __('Labels has been applied successfully', 'fluent-crm'),
            ]);
        }

        return $this->sendError([
            'message' => __('invalid bulk action', 'fluent-crm')
        ]);
    }

    public function cloneFunnel(Request $request, $funnelId)
    {
        $oldFunnel = Funnel::findOrFail($funnelId);

        $newFunnelData = [
            'title'        => __('[Copy] ', 'fluent-crm') . $oldFunnel->title,
            'trigger_name' => $oldFunnel->trigger_name,
            'status'       => 'draft',
            'conditions'   => $oldFunnel->conditions,
            'settings'     => $oldFunnel->settings,
            'created_by'   => get_current_user_id()
        ];
        $labelIds = $oldFunnel->getFormattedLabels()->pluck('id')->toArray();

        $funnel = Funnel::create($newFunnelData);
        $funnel->attachLabels($labelIds);

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
                $delay = FunnelHelper::getDelayInSecond($sequence['settings']);
                $cDelay += $delay;
            }

            /**
             * Determine the funnel sequence before saving.
             *
             * This filter allows modification of the funnel sequence before it is saved.
             *
             * @since 1.1.4
             * 
             * @param array $sequence The sequence data to be saved.
             * @param array $funnel The funnel data associated with the sequence.
             *
             * @return array The modified sequence data.
             */
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
                do_action('fluent_crm/sequence_created_' . $createdSequence->action_name, $createdSequence);
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

        $funnel = $this->createFunnelFromData($funnelArray, $sequences);

        return [
            'message' => __('Funnel has been successfully imported', 'fluent-crm'),
            'funnel'  => $funnel
        ];

    }

    public function deleteSubscribers(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $ids = $request->getSafe('subscriber_ids', [], 'intval');
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
        $status = $request->getSafe('status');
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

    public function saveEmailActionFallback(Request $request)
    {
        $funnelId = $request->get('funnel_id');
        return $this->saveEmailAction($request, $funnelId);
    }

    public function saveEmailAction(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);

        $settings = $request->getJson('action_data');
        $settings['action_name'] = 'send_custom_email';

        $funnelCampaign = Arr::get($settings, 'campaign', []);

        $funnelCampaignId = Arr::get($funnelCampaign, 'id');

        $data = Arr::only($funnelCampaign, array_keys(FunnelCampaign::getMock()));
        $data['settings']['mailer_settings'] = Arr::get($settings, 'mailer_settings', []);

        $type = 'created';

        if ($funnelCampaignId && $funnel->id == Arr::get($data, 'parent_id')) {
            // We have this campaign
            $data['settings'] = \maybe_serialize($data['settings']);
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' (' . $funnel->id . ')';
            FunnelCampaign::where('id', $funnelCampaignId)->update($data);
            $type = 'updated';
        } else {
            $data['parent_id'] = $funnel->id;
            $data['type'] = 'funnel_email_campaign';
            $data['title'] = $funnel->title . ' (' . $funnel->id . ')';
            $campaign = FunnelCampaign::create($data);
            $funnelCampaignId = $campaign->id;
        }

        if (Arr::get($funnelCampaign, 'design_template') == 'visual_builder') {
            $design = Arr::get($funnelCampaign, '_visual_builder_design', []);
            fluentcrm_update_campaign_meta($funnelCampaignId, '_visual_builder_design', $design);
        } else {
            fluentcrm_delete_campaign_meta($funnelCampaignId, '_visual_builder_design');
        }

        $refCampaign = FunnelCampaign::find($funnelCampaignId);

        return [
            'type'               => $type,
            'reference_campaign' => $funnelCampaignId,
            'campaign'           => Arr::only($refCampaign->toArray(), array_keys(FunnelCampaign::getMock()))
        ];
    }

    public function getSyncableContactCounts(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $latestAction = \FluentCrm\App\Models\FunnelSequence::where('funnel_id', $funnelId)
            ->orderBy('sequence', 'DESC')
            ->first();

        if (!$latestAction) {
            return [
                'syncable_count' => 0
            ];
        }

        $count = \FluentCrm\App\Models\FunnelSubscriber::where('funnel_id', $funnel->id)
            ->with(['subscriber'])
            ->where('status', 'completed')
            ->whereHas('subscriber', function ($q) {
                $q->where('status', 'subscribed');
            })
            ->whereHas('last_sequence', function ($q) use ($latestAction) {
                $q->where('action_name', '!=', 'end_this_funnel')
                    ->where('id', '!=', $latestAction->id)
                    ->where('sequence', '<', $latestAction->sequence);
            })->count();

        return [
            'syncable_count' => $count
        ];
    }

    public function syncNewSteps(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);

        if ($funnel->status != 'published') {
            return $this->sendError([
                'message' => __('Funnel status need to be published', 'fluent-crm')
            ]);
        }

        if (!defined('FLUENTCAMPAIGN_DIR_FILE')) {
            return $this->sendError([
                'message' => __('This feature require latest version of FluentCRM Pro version', 'fluent-crm')
            ]);
        }

        $cleanup = new \FluentCampaign\App\Hooks\Handlers\Cleanup();

        if (!method_exists($cleanup, 'syncAutomationSteps')) {
            return $this->sendError([
                'message' => __('This feature require latest version of FluentCRM Pro version', 'fluent-crm')
            ]);
        }

        $result = $cleanup->syncAutomationSteps($funnel);

        if (is_wp_error($result)) {
            return $this->sendError($result->get_error_messages());
        }

        return [
            'message' => __('Synced successfully', 'fluent-crm')
        ];
    }

    public function getTemplates()
    {
        $templates = fluentCrmPersistentCache('funnel_remote_templates', function () {
            return $this->getDynamicTemplates();
        }, 60 * 60 * 24); // 24 hours

        $allowedTemplates = $this->filterTemplates($templates);

        return [
            'templates' => $allowedTemplates,
            'all' => $templates,
            'cats'  => $this->allowedCategories()
        ];
    }

    public function filterTemplates($templates)
    {
        $allowedCategories = $this->allowedCategories();
        $filteredTemplates = [];

        foreach ($templates as $template) {
            if(empty($template['dependencies'])) {
                $filteredTemplates[] = $template;
                continue;
            }
            $diff = array_diff($template['dependencies'], $allowedCategories);
            if(!$diff) {
                $filteredTemplates[] = $template;
            }
        }

        return $filteredTemplates;
    }

    public function allowedCategories()
    {
        $categories = [];

        if(defined( 'FLUENTFORM')) {
            $categories[] = 'fluentforms';
        }

        if(defined('MEPR_PLUGIN_NAME')) {
            $categories[] = 'memberpress';
        }

        if(defined('FLUENT_BOARDS')) {
            $categories[] = 'fluent-boards';
        }

        if(defined('FLUENT_SUPPORT')) {
            $categories[] = 'fluent-support';
        }

        if(defined('FLUENT_BOOKING_VERSION')) {
            $categories[] = 'fluent-booking';
        }

        if (defined('WC_PLUGIN_FILE')) {
            $categories[] = 'woocommerce';
        }

        if (defined('WCS_INIT_TIMESTAMP')) {
            $categories[] = 'wcs';
        }

        if (defined('EDD_PLUGIN_FILE')) {
            $categories[] = 'edd';
        }

        if (defined('LIFTERLMS_VERSION')) {
            $categories[] = 'lifterlms';
        }

        if (defined('TUTOR_VERSION')) {
            $categories[] = 'tutor';
        }

        if (defined('LEARNDASH_VERSION')) {
            $categories[] = 'learndash';
        }

        if (defined('SURECART_PLUGIN_FILE')) {
            $categories[] = 'surecart';
        }

        if(Helper::isExperimentalEnabled('abandoned_cart')) {
            $categories[] = 'woo_abandon_carts';
        }

        if(defined('FLUENTCAMPAIGN_DIR_FILE')) {
            $categories[] = 'fluentcrm_pro';
        }

        return $categories;

    }

    public function createFromTemplate(Request $request)
    {
        $template = $request->get('template');

        $templateData = $this->getFunnelData($template['content']);
        $funnelArray = $templateData;
        $sequences = $templateData['sequences'];

        $funnel = $this->createFunnelFromData($funnelArray, $sequences);

        return [
            'funnel'  => $funnel,
            'message' => __('Funnel has been created from template', 'fluent-crm')
        ];

    }

    private function createFunnelFromData($funnelArray, $sequences)
    {
        $funnelArray = Sanitize::funnel($funnelArray);

        $newFunnelData = [
            'title'        => Arr::get($funnelArray, 'title'),
            'trigger_name' => Arr::get($funnelArray, 'trigger_name'),
            'status'       => 'draft',
            'conditions'   => Arr::get($funnelArray, 'conditions', []),
            'settings'     => Arr::get($funnelArray, 'settings'),
            'created_by'   => get_current_user_id()
        ];

        $funnel = Funnel::create($newFunnelData);

        $funnelLabels = Arr::get($funnelArray, 'labels', []);
        if (isset($funnelLabels) && !empty($funnelLabels)) {
            $newLabels = [];
            foreach ($funnelLabels as $key => $funnelLabel) {
                // Validate required fields
                if (!isset($funnelLabel['slug'], $funnelLabel['title'])) {
                    continue; // Skip invalid labels
                }

                $existLabel = Label::where('taxonomy_name', 'global_label')->where('slug', $funnelLabel['slug'])->first();
                if (!$existLabel) {
                    $labelData = [
                        'slug'  => sanitize_text_field($funnelLabel['slug']),
                        'title' => sanitize_text_field($funnelLabel['title']),
                    ];
                    $color = sanitize_hex_color($funnelLabel['color']);

                    $labelData['settings'] = [
                        'color' => $color
                    ];

                    $existLabel = Label::create($labelData);
                }

                $newLabels[] = $existLabel->id;
            }
            $funnel->attachLabels($newLabels);
        }

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
                $delay = FunnelHelper::getDelayInSecond($sequence['settings']);
                $cDelay += $delay;
            }
            /**
             * Determine the funnel sequence before saving.
             *
             * This filter allows modification of the funnel sequence before it is saved.
             *
             * @since 2.9.20
             * 
             * @param array $sequence The sequence data to be saved.
             * @param array $funnel The funnel data associated with the sequence.
             *
             * @return array The modified sequence data.
             */
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
                do_action('fluent_crm/sequence_created_' . $createdSequence->action_name, $createdSequence);

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

        return $funnel;
    }

    public function temporaryStaticTemplates()
    {
        return \FluentCrm\App\Services\Funnel\StaticTemplates::get();
    }

    public function getDynamicTemplates()
    {
        $restBase =  defined('FC_TEMPLATE_API_DOMAIN') ? FC_TEMPLATE_API_DOMAIN : 'https://fluentcrm.com';
        $restApi =  $restBase.'/wp-json/wp/v2/automation-templates';

        $response = wp_remote_get($restApi, [
            'sslverify' => false,
        ]);

        if (is_wp_error($response)) {
            // Handle error
            error_log($response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $templateLists = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle JSON error
            error_log('JSON Decode Error: ' . json_last_error_msg());
            return [];
        }

        $formattedTemplates = [];
        foreach ($templateLists as $template) {

            if (!$template['template_json']) {
                // Skip if no template json
                continue;
            }
            $formattedTemplates[] = [
                'id'           => $template['id'],
                'title'        => $template['title']['rendered'],
//                'description'  => $template['excerpt']['rendered'],
                'short_description'    => $template['short_description'],
                'type'         => $template['template_type'],
                'dependencies' => $template['plugin_dependencies'],
                'content'      => $template['template_json'],
                'link'         => $template['link'],
                'midea'        => $template['_links']['wp:attachment'][0]['href'],
                'status'       => $template['status'],
            ];
        }

        return $formattedTemplates;
    }

    public function getFunnelData($jsonUrl)
    {
        $request = wp_remote_get($jsonUrl, [
            'sslverify' => false,
        ]);

        if (is_wp_error($request)) {
            return [];
        }
        return json_decode(wp_remote_retrieve_body($request), true);
    }

    public function sendTestWebhook(Request $request)
    {
        $bodyDataType   = $request->getSafe('data.body_data_type');
        $bodyDataValues = $request->getSafe('data.body_data_values', []);
        $headerType     = $request->getSafe('data.header_type');
        $headerData     = $request->getSafe('data.header_data', []);
        $sendingMethod  = $request->getSafe('data.sending_method');
        $requestFormat  = $request->getSafe('data.request_format');
        $remoteUrl      = $request->getSafe('data.remote_url');

        $this->validate($request->all(), [
            'data.remote_url' => 'required|url',
        ],[
            'data.remote_url.required' => __('Remote URL is required', 'fluent-crm')
        ]);


        $user  = get_user_by('ID', get_current_user_id());
        $email = $user->user_email;

        $subscriber = Subscriber::where('email', $email)
            ->orWhere('status', 'subscribed')
            ->first();

        if (!$subscriber) {
            return $this->sendError([
                'message' => __('No subscriber found to send test webhook. Please add at least one contact with subscribed status.', 'fluent-crm')
            ]);
        }

        $headers = $this->prepareHeaders($headerType, $headerData, $subscriber);
        $body = $this->prepareBody($bodyDataType, $bodyDataValues, $subscriber);

        $isJson = 'no';
        if ($requestFormat == 'json' && $sendingMethod == 'POST') {
            $isJson = 'yes';
            $headers['Content-Type'] = 'application/json; charset=utf-8';
        }

        if ($sendingMethod == 'GET') {
            $remoteUrl = add_query_arg($body, $remoteUrl);
        }

        $data = [
            'payload'       => [
                'body'      => ($sendingMethod == 'POST') ? $body : null,
                'method'    => $sendingMethod,
                'headers'   => $headers,
                /**
                 * Determine whether to verify SSL for FluentCRM webhook requests.
                 *
                 * This filter allows you to control whether SSL verification should be performed
                 * when making webhook requests in FluentCRM.
                 *
                 * @since 2.9.25
                 *
                 * @param bool Whether to verify SSL. Default false.
                 */
                'sslverify' => apply_filters('fluent_crm/webhook_ssl_verify', false)
            ],
            'remote_url'    => $remoteUrl,
            'is_json'       => $isJson
        ];

        if ($data['is_json'] == 'yes') {
            $data['payload']['body'] = json_encode($data['payload']['body']);
        }

        $response = wp_remote_request($data['remote_url'], $data['payload']);

        if (is_wp_error($response)) {
            $code = Arr::get($response, 'response.code');
            $response->get_error_message() . ', with response code: ' . $code . ' - ' . (int)$response->get_error_code();
            return $this->senderror([
                'message' => __('Test Webhook failed to send', 'fluent-crm')
            ]);
        }

        return [
            'message'  => __('Test Webhook has been sent successfully', 'fluent-crm')
        ];
    }

    private function prepareHeaders($headerType, $headerData, $subscriber)
    {
        $headers = [];
        if ($headerType === 'with_headers') {
            foreach ($headerData as $item) {
                if (empty($item['data_key']) || empty($item['data_value'])) {
                    continue;
                }
                $item['data_key'] = str_replace(' ', '-', $item['data_key']);
                $headers[$item['data_key']] = Parser::parse($item['data_value'], $subscriber);
            }
        }
        return $headers;
    }

    private function prepareBody($bodyDataType, $bodyDataValues, $subscriber)
    {
        $body = [];
        if ($bodyDataType === 'subscriber_data') {
            $body = $subscriber->toArray();
            $body['custom_field'] = $subscriber->custom_fields();
        } else {
            foreach ($bodyDataValues as $item) {
                if (empty($item['data_key']) || empty($item['data_value'])) {
                    continue;
                }
                $body[$item['data_key']] = Parser::parse($item['data_value'], $subscriber);
            }
        }
        return $body;
    }
  
    public function updateFunnelTitle(Request $request, $funnelId)
    {
        $funnel = Funnel::findOrFail($funnelId);
        $newTitle = $request->getSafe('title');

        if ($funnel->title == $newTitle) {
            return $this->sendError([
                'message' => __('Funnel already have the same title', 'fluent-crm')
            ]);
        }

        $funnel->title = $newTitle;
        $funnel->save();

        return $this->sendSuccess([
            'message' => __(sprintf('Title has been updated to %s', $newTitle), 'fluent-crm')
        ]);
    }

    public function updateLabels(Request $request, $funnel_id)
    {
        $funnel = Funnel::findOrFail($funnel_id);
        $action = $request->getSafe('action');
        $labelIds = $request->getSafe('label_ids');

        if (!is_array($labelIds)) {
            $labelIds = [$labelIds];
        }

        if ($action == 'attach') {
            $funnel->attachLabels($labelIds);
        } else {
            $funnel->detachLabels($labelIds);
        }

        return $this->sendSuccess([
            'message' => __('Labels has been updated', 'fluent-crm')
        ]);

    }



}
