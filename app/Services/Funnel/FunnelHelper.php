<?php

namespace FluentCrm\App\Services\Funnel;

use FluentCampaign\App\Models\SequenceTracker;
use FluentCrm\App\Hooks\Handlers\FunnelHandler;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;

class FunnelHelper
{
    public static function changeFunnelSubSequenceStatus($funnelSubId, $sequenceId, $status = 'complete')
    {
        return FunnelSubscriber::where('id', $funnelSubId)
            ->update([
                'last_sequence_status' => $status,
                'last_sequence_id'     => $sequenceId,
                'last_executed_time'   => current_time('mysql')
            ]);
    }

    public static function getUpdateOptions()
    {
        return [
            [
                'id'    => 'update',
                'title' => __('Update if Exist', 'fluent-crm')
            ],
            [
                'id'    => 'skip_all_if_exist',
                'title' => __('Skip this automation if contact already exist', 'fluent-crm')
            ]
        ];
    }

    public static function prepareUserData($user)
    {
        $subscriber = Helper::getWPMapUserInfo($user);
        $subscriber['source'] = 'web';
        $subscriber['ip'] = FluentCrm()->request->getIp();
        return $subscriber;
    }

    public static function getSubscriber($emailOrUserId)
    {
        $column = 'email';
        if (is_int($emailOrUserId)) {
            $column = 'id';
        }

        return Subscriber::where($column, $emailOrUserId)->first();
    }

    public static function createOrUpdateContact($data)
    {

        return FluentCrmApi('contacts')->createOrUpdate($data);
    }

    public static function getUserRoles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }

        $roles = \get_editable_roles();
        $formattedRoles = [];
        foreach ($roles as $roleKey => $role) {
            $formattedRoles[] = [
                'id'    => $roleKey,
                'title' => $role['name']
            ];
        }
        return $formattedRoles;
    }

    public static function ifAlreadyInFunnel($funnelId, $subscriberId)
    {
        return FunnelSubscriber::where('funnel_id', $funnelId)
            ->where('subscriber_id', $subscriberId)
            ->first();
    }

    public static function maybeExplodeFullName($data)
    {
        if (empty($data['first_name']) && empty($data['last_name'])) {
            return $data;
        }

        if (empty($data['first_name']) || !empty($data['last_name'])) {
            return $data;
        }

        $fullNameArray = explode(' ', $data['first_name']);
        $data['first_name'] = array_shift($fullNameArray);
        if ($fullNameArray) {
            $data['last_name'] = implode(' ', $fullNameArray);
        }

        return $data;
    }

    public static function syncTags($subscriber, $tags = [])
    {
        if ($tags) {
            $subscriber->attachTags($tags);
        }
    }

    public static function syncLists($subscriber, $lists = [])
    {
        // Syncing
        if ($lists) {
            $subscriber->attachLists($lists);
        }
    }

    public static function getPrimaryContactFieldMaps()
    {
        return [
            'first_name' => [
                'type'  => 'value_options',
                'label' => __('First Name', 'fluent-crm')
            ],
            'last_name'  => [
                'type'  => 'value_options',
                'label' => __('Last Name', 'fluent-crm')
            ],
            'email'      => [
                'type'  => 'value_options',
                'label' => __('Email', 'fluent-crm')
            ]
        ];
    }

    public static function getSecondaryContactFieldMaps()
    {
        $mainFields = [
            'prefix'         => [
                'type'  => 'value_options',
                'label' => __('Name Prefix', 'fluent-crm')
            ],
            'address_line_1' => [
                'type'  => 'value_options',
                'label' => __('Address Line 1', 'fluent-crm')
            ],
            'address_line_2' => [
                'type'  => 'value_options',
                'label' => __('Address Line 2', 'fluent-crm')
            ],
            'postal_code'    => [
                'type'  => 'value_options',
                'label' => __('Postal Code', 'fluent-crm')
            ],
            'city'           => [
                'type'  => 'value_options',
                'label' => __('City', 'fluent-crm')
            ],
            'state'          => [
                'type'  => 'value_options',
                'label' => __('State', 'fluent-crm')
            ],
            'country'        => [
                'type'  => 'value_options',
                'label' => __('country', 'fluent-crm')
            ],
            'phone'          => [
                'type'  => 'value_options',
                'label' => __('Phone', 'fluent-crm')
            ]
        ];

        $customFields = fluentcrm_get_option('contact_custom_fields', []);
        if ($customFields) {
            foreach ($customFields as $item) {
                $mainFields['custom.' . $item['slug']] = [
                    'type'  => 'value_options',
                    'label' => $item['label']
                ];
            }
        }

        return $mainFields;

    }

    public static function removeSubscribersFromFunnel($funnelId, $subscriberIds)
    {
        FunnelSubscriber::where('funnel_id', $funnelId)
            ->whereIn('subscriber_id', $subscriberIds)
            ->delete();

        FunnelMetric::where('funnel_id', $funnelId)
            ->whereIn('subscriber_id', $subscriberIds)
            ->delete();
        return true;
    }

    public static function saveFunnelSequence($funnelId, $data)
    {
        $funnelSettings = \json_decode(Arr::get($data, 'funnel_settings'), true);

        $funnelConditions = \json_decode(Arr::get($data, 'conditions', []), true);

        $funnel = Funnel::findOrFail($funnelId);
        $funnel->settings = $funnelSettings;
        if ($funnelTitle = Arr::get($data, 'funnel_title')) {
            $funnel->title = sanitize_text_field($funnelTitle);
        }

        $funnel->conditions = $funnelConditions;
        $funnel->status = Arr::get($data, 'status');
        $funnel->save();


        $sequences = \json_decode(Arr::get($data, 'sequences', []), true);

        $sequenceIds = [];
        $cDelay = 0;
        $delay = 0;

        $indexCount = 0;

        foreach ($sequences as $index => $sequence) {
            // it's creatable
            $sequence['funnel_id'] = $funnel->id;
            $sequence['status'] = 'published';
            $sequence['conditions'] = [];
            $sequence['sequence'] = $indexCount + 1;
            $sequence['c_delay'] = $cDelay;
            $sequence['delay'] = $delay;
            $delay = 0;

            $actionName = $sequence['action_name'];

            if ($actionName == 'fluentcrm_wait_times') {
                $delay = self::getDelayInSecond($sequence);
                $cDelay += $delay;
            }

            $sequence = apply_filters('fluentcrm_funnel_sequence_saving_' . $sequence['action_name'], $sequence, $funnel);
            if (Arr::get($sequence, 'type') == 'benchmark') {
                $delay = $sequence['delay'];
            }

            $sequence['id'] = self::createOrUpdateSequence($sequence);
            $sequenceIds[] = $sequence['id'];

            // We have to handle the children if it's conditional block
            if ($sequence['type'] == 'conditional') {
                $childIds = self::saveChildSequences($sequence, $funnel);
                $indexCount += count($childIds);
                $sequenceIds = array_merge($sequenceIds, $childIds);
            }

            $indexCount += 1;
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

        return $funnel;
    }

    private static function createOrUpdateSequence($sequence)
    {
        if (empty($sequence['id'])) {
            $sequence['created_by'] = get_current_user_id();
            $createdSequence = FunnelSequence::create($sequence);
            return $createdSequence->id;
        }

        $sequence['updated_at'] = current_time('mysql');
        $sequence['settings'] = \maybe_serialize($sequence['settings']);
        $sequence['conditions'] = \maybe_serialize($sequence['conditions']);

        $sequenceId = $sequence['id'];
        $data = Arr::only($sequence, (new FunnelSequence())->getFillable());

        FunnelSequence::where('id', $sequenceId)->update($data);

        return $sequenceId;
    }

    public static function getDelayInSecond($sequence)
    {
        $unit = Arr::get($sequence, 'settings.wait_time_unit');
        $converter = 86400; // default day
        if ($unit == 'hours') {
            $converter = 3600; // hour
        } else if ($unit == 'minutes') {
            $converter = 60;
        }
        $time = Arr::get($sequence, 'settings.wait_time_amount');
        return intval($time * $converter);
    }

    private static function saveChildSequences($sequence, $funnel)
    {
        $sequenceIds = [];
        $indexCount = $sequence['sequence'];
        $childCats = Arr::get($sequence, 'children');
        foreach ($childCats as $category => $blocks) {
            $childDelay = 0;
            $childCDelay = 0;
            foreach ($blocks as $childIndex => $childSequence) {
                $childSequence['funnel_id'] = $funnel->id;
                $childSequence['status'] = 'published';
                $childSequence['parent_id'] = $sequence['id'];
                $childSequence['condition_type'] = $category;
                $childSequence['conditions'] = [];
                $childSequence['sequence'] = $indexCount + 1;
                $childSequence['c_delay'] = $sequence['c_delay'] + $childCDelay;
                $childSequence['delay'] = $sequence['delay'] + $childDelay;

                $childDelay = 0;

                /*
                 * For Delay Calculation
                 */
                $actionName = $childSequence['action_name'];
                if ($actionName == 'fluentcrm_wait_times') {
                    $childDelay = self::getDelayInSecond($childSequence);
                    $childCDelay += $childDelay;
                }

                $childSequence = apply_filters('fluentcrm_funnel_sequence_saving_' . $childSequence['action_name'], $childSequence, $funnel);
                $sequenceIds[] = self::createOrUpdateSequence($childSequence);
                $indexCount += 1;
            }
        }
        return $sequenceIds;
    }

    public static function getFunnelSequences($funnel, $isFiltered = false)
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

    public static function getRuntimeSequences($funnel, $funnelSubscriber = false)
    {
        $lastSequence = false;
        if($funnelSubscriber && $funnelSubscriber->last_sequence_id) {
            $lastSequence = FunnelSequence::where('id', $funnelSubscriber->last_sequence_id);
        }

        if($lastSequence && $lastSequence->parent_id) {
            // We just have to find the same child-block sequences
            $sequences = FunnelSequence::where('parent_id', $lastSequence->parent_id)
                ->where('condition_type', $lastSequence->condition_type)
                ->where('sequence', '>', $lastSequence->sequence)
                ->orderBy('sequence', 'ASC')
                ->get();

        }

        $sequencesQuery = FunnelSequence::where('funnel_id', $funnel->id);
        if($lastSequence) {
            $sequencesQuery->where('sequence', '>', $lastSequence->sequence);
        }

        $sequences = $sequencesQuery->orderBy('sequence', 'ASC')->get();

        if($sequences->isEmpty()) {
            return [
                'immediate_sequences' => [],
                'next_sequence' => false
            ];
        }
    }


    public static function extractSequences($sequences)
    {
        if($sequences->isEmpty()) {
            return [
                'immediate_sequences' => [],
                'next_sequence' => false
            ];
        }

        $immediateSequences = [];
        $nextSequence = false;
        $firstSequence = $sequences[0];
        $requiredBenchMark = false;
        $conditionalBlock = false;

        foreach ($sequences as $sequence) {
            if ($requiredBenchMark || $conditionalBlock) {
                continue;
            }

            /*
             * Check if there has a required sequence for this.
             */
            if ($sequence->type == 'benchmark') {
                if ($sequence->settings['type'] == 'required') {
                    $requiredBenchMark = $sequence;
                }
                continue;
            }

            if ($sequence->type == 'conditional') {
                $conditionalBlock = $sequence;
                continue;
            }

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
    }
}
