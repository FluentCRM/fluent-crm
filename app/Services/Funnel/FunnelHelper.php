<?php

namespace FluentCrm\App\Services\Funnel;

use FluentCrm\App\Hooks\Handlers\FunnelHandler;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\FunnelMetric;
use FluentCrm\App\Models\FunnelSequence;
use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;

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
        return $subscriber;
    }

    public static function getSubscriber($emailOrUserId)
    {
        $column = 'email';
        if (is_int($emailOrUserId)) {
            $column = 'user_id';
        }

        return Subscriber::where($column, $emailOrUserId)->first();
    }

    public static function createOrUpdateContact($data)
    {
        return FluentCrmApi('contacts')->createOrUpdate($data);
    }

    public static function getUserRoles($keyed = false)
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }

        $roles = \get_editable_roles();
        $formattedRoles = [];
        foreach ($roles as $roleKey => $role) {
            if ($keyed) {
                $formattedRoles[$roleKey] = $role['name'];
            } else {
                $formattedRoles[] = [
                    'id'    => $roleKey,
                    'title' => $role['name']
                ];
            }

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

        if ($funnelDescription = Arr::get($data, 'funnel_description')) {
            $funnel->updateMeta('description', $funnelDescription);
        } else {
            $funnel->deleteMeta('description');
        }

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
                $delay = self::getDelayInSecond($sequence['settings']);
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
                $sequenceIds = array_unique(array_merge($sequenceIds, $childIds));
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
            do_action('fluent_crm/sequence_created_' . $createdSequence->action_name, $createdSequence);
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

    public static function getDelayInSecond($settings)
    {
        $waitType = Arr::get($settings, 'wait_type');

        if (!$waitType && Arr::get($settings, 'is_timestamp_wait') == 'yes') {
            return 1;
        }

        if ($waitType == 'timestamp_wait' || $waitType == 'to_day') {
            return 1;
        }

        $unit = Arr::get($settings, 'wait_time_unit');
        $converter = 86400; // default day
        if ($unit == 'hours') {
            $converter = 3600; // hour
        } else if ($unit == 'minutes') {
            $converter = 60;
        }

        $time = Arr::get($settings, 'wait_time_amount');
        $delay = (int)$time * $converter;

        if (!$delay || $delay < 1) {
            $delay = 1;
        }

        return $delay;
    }

    public static function getCurrentDelayInSeconds($settings, $sequence = null, $funnerSubId = null)
    {
        $waitType = Arr::get($settings, 'wait_type');

        /*
         * For Specific Date and Time
         */
        if ((!$waitType && Arr::get($settings, 'is_timestamp_wait') == 'yes') || $waitType == 'timestamp_wait') {
            $timeStamp = current_time('timestamp');
            $waitTimes = strtotime(Arr::get($settings, 'wait_date_time'), $timeStamp) - $timeStamp;
            if ($waitTimes < 1) {
                $waitTimes = 0;
            }
            return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', $waitTimes, $settings, $sequence, $funnerSubId);
        }

        if ($waitType && $waitType == 'to_day') {
            $nextDays = Arr::get($settings, 'to_day', []);
            $timeStampNow = current_time('timestamp');

            $nextDays = array_map(function ($dayName) {
                return substr($dayName, 0, 3);
            }, $nextDays);

            if (empty($nextDays)) { // if no day is selected
                $nextDays = [date('D', $timeStampNow), date('D', strtotime('+1 day', $timeStampNow))];
            }

            $nextTime = Arr::get($settings, 'to_day_time');
            if (!$nextTime) {
                $nextTime = date('H:i', $timeStampNow);
            }

            $date = self::getEarliestDay($nextDays, $nextTime);

            $seconds = strtotime($date) - current_time('timestamp');
            $waitTimes = ($seconds < 1) ? 0 : $seconds;
            return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', $waitTimes, $settings, $sequence, $funnerSubId);
        }


        if ($waitType == 'by_custom_field') {
            if (!$funnerSubId) {
                return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', 60, $settings, $sequence, $funnerSubId);
            }

            $funnelSub = FunnelSubscriber::where('id', $funnerSubId)->first();

            if (!$funnelSub || !$funnelSub->subscriber) {
                return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', 60, $settings, $sequence, $funnerSubId);
            }

            $customFieldKey = Arr::get($settings, 'by_custom_field', '');

            if (!$customFieldKey) {
                return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', 60, $settings, $sequence, $funnerSubId);
            }

            $dateTime = null;

            if ($customFieldKey == '__date_of_birth__') {
                $dateTime = $funnelSub->subscriber->date_of_birth;

                if ($dateTime) {
                    // should be this current year's date
                    $dateTime = date('Y') . '-' . date('m-d', strtotime($dateTime));

                    // if the date is passed, then next year
                    if (strtotime($dateTime) < current_time('timestamp')) {
                        $dateTime = (date('Y') + 1) . '-' . date('m-d', strtotime($dateTime));
                    }
                }
            } else {
                $meta = $funnelSub->subscriber->custom_field_meta()->where('key', $customFieldKey)->first();
                if ($meta) {
                    $dateTime = $meta->value;
                }
            }

            if (!$dateTime) {
                return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', 60, $settings, $sequence, $funnerSubId);
            }

            $timeStamp = strtotime($dateTime);

            $waitTimes = $timeStamp - current_time('timestamp');

            if ($waitTimes < 1) {
                $waitTimes = 60;
            }

            return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', $waitTimes, $settings, $sequence, $funnerSubId);
        }

        $unit = Arr::get($settings, 'wait_time_unit');
        $converter = 86400; // default day
        if ($unit == 'hours') {
            $converter = 3600; // hour
        } else if ($unit == 'minutes') {
            $converter = 60;
        }

        $time = Arr::get($settings, 'wait_time_amount');
        $waitTimes = (int)$time * $converter;

        if (!$waitTimes || $waitTimes < 1) {
            $waitTimes = 1;
        }

        return apply_filters('fluent_crm/funnel_seq_delay_in_seconds', $waitTimes, $settings, $sequence, $funnerSubId);
    }

    /*
     * Get the next earliest day provided to $days array as ['Mon', Wed, 'Fri']
     * @param array $days
     * @return string $earliest
     */
    private static function getEarliestDay($days, $time = '')
    {
        $timestamp = current_time('timestamp');
        $timeStampsArray = [];
        for ($i = 0; $i < 8; $i++) {
            $timeStampsArray[] = $timestamp + ($i * 86400);
        }

        $earliest = date('Y-m-d ' . $time . ':s', $timestamp);

        foreach ($timeStampsArray as $timeStampVal) {
            if (in_array(date('D', $timeStampVal), $days)) {
                $earliest = date('Y-m-d ' . $time . ':s', $timeStampVal);
                if (strtotime($earliest) - $timestamp > -60) {
                    return $earliest;
                }
            }
        }

        return $earliest;
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
                    $childDelay = self::getDelayInSecond($childSequence['settings']);
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

    public static function extractSequences($sequences)
    {
        if ($sequences->isEmpty()) {
            return [
                'immediate_sequences' => [],
                'next_sequence'       => false
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

    public static function maybeMigrateConditions($funnelId)
    {
        $conditionSequences = FunnelSequence::where('funnel_id', $funnelId)
            ->where('type', 'conditional')
            ->where('action_name', '!=', 'funnel_condition')
            ->get();

        foreach ($conditionSequences as $conditionSequence) {
            self::migrateConditionSequence($conditionSequence);
        }

        return !$conditionSequences->isEmpty();
    }

    public static function migrateConditionSequence($sequence, $dryRun = false)
    {
        $conditionalBlocks = [
            'funnel_condition',
            'funnel_ab_testing'
        ];

        if ($sequence->type != 'conditional' || in_array($sequence->action_name, $conditionalBlocks)) {
            return $sequence;
        }

        $simpleMaps = [
            'fcrm_has_contact_tag'         => [
                'source'        => ['segment', 'tags'],
                'operator'      => 'in',
                'value_access'  => 'tags',
                'default_value' => [],
            ],
            'fcrm_has_contact_list'        => [
                'source'        => ['segment', 'lists'],
                'operator'      => 'in',
                'value_access'  => 'lists',
                'default_value' => [],
            ],
            'fcrm_has_user_role'           => [
                'source'        => ['segment', 'user_role'],
                'operator'      => 'in',
                'value_access'  => 'roles',
                'default_value' => [],
            ],
            'fcrm_woo_is_purchased'        => [
                'source'        => ['woo', 'purchased_items'],
                'operator'      => 'in',
                'value_access'  => 'product_ids',
                'default_value' => [],
            ],
            'fcrm_wishlist_is_in_level'    => [
                'source'        => ['wishlist', 'in_membership'],
                'operator'      => 'in',
                'value_access'  => 'level_ids',
                'default_value' => [],
            ],
            'fcrm_tutor_is_in_course'      => [
                'source'        => ['tutorlms', 'is_in_course'],
                'operator'      => 'in',
                'value_access'  => 'course_ids',
                'default_value' => [],
            ],
            'fcrm_pmpro_is_in_membership'  => [
                'source'        => ['pmpro', 'in_membership'],
                'operator'      => 'in',
                'value_access'  => 'level_ids',
                'default_value' => [],
            ],
            'fcrm_lifter_is_in_course'     => [
                'source'        => ['lifterlms', 'purchased_items'],
                'operator'      => 'in',
                'value_access'  => 'course_ids',
                'default_value' => [],
            ],
            'fcrm_lifter_is_in_membership' => [
                'source'        => ['lifterlms', 'purchased_groups'],
                'operator'      => 'in',
                'value_access'  => 'course_ids',
                'default_value' => [],
            ],
            'fcrm_learndhash_is_in_course' => [
                'source'        => ['learndash', 'purchased_items'],
                'operator'      => 'in',
                'value_access'  => 'course_ids',
                'default_value' => [],
            ],
            'fcrm_learndhash_is_in_group'  => [
                'source'        => ['learndash', 'purchased_groups'],
                'operator'      => 'in',
                'value_access'  => 'group_ids',
                'default_value' => [],
            ],
            'fcrm_edd_is_purchased'        => [
                'source'        => ['edd', 'purchased_items'],
                'operator'      => 'in',
                'value_access'  => 'product_ids',
                'default_value' => [],
            ],
            'fcrm_rcp_is_in_membership'    => [
                'source'        => ['rcp', 'in_membership'],
                'operator'      => 'in',
                'value_access'  => 'level_ids',
                'default_value' => [],
            ]
        ];

        $operatorMaps = [
            'match_all'     => 'in_all',
            'match_none_of' => 'not_in_all',
            'contains'      => 'contains',
            'doNotContains' => 'not_contains',
            'startsWith'    => 'startsWith',
            'endsWith'      => 'endsWith'
        ];

        $conditionName = $sequence->action_name;

        $oldSettings = $sequence->settings;

        if (isset($simpleMaps[$conditionName])) {
            $map = $simpleMaps[$conditionName];
            $sequence->action_name = 'funnel_condition';
            $sequence->settings = [
                'conditions' => [
                    [
                        [
                            'source'   => $map['source'],
                            'operator' => $map['operator'],
                            'value'    => Arr::get($sequence->settings, $map['value_access'], $map['default_value'])
                        ]
                    ]
                ]
            ];
            if (!$dryRun) {
                $sequence->save();
            }
        } else if ($conditionName == 'fcrm_check_user_prop') {
            $sequence->action_name = 'funnel_condition';
            $conditionGroups = $oldSettings['condition_groups'];

            $formattedConditions = [[]];

            if (isset($conditionGroups[0])) {
                $conditions = $conditionGroups[0]['conditions'];
                $conditionType = $conditionGroups[0]['match_type'];

                if ($conditionType != 'match_all') {
                    $formattedConditions = [];
                }

                foreach ($conditions as $condition) {
                    $dataKey = $condition['data_key'];
                    $operator = $condition['operator'];
                    $dataValue = $condition['data_value'];
                    if (!$dataKey || !$operator) {
                        continue;
                    }

                    if (isset($operatorMaps[$operator])) {
                        $operator = $operatorMaps[$operator];
                    }

                    if ($dataKey == 'contact_type' || $dataKey == 'country') {
                        if ($operator == '=') {
                            $operator = 'in';
                        } else {
                            $operator = 'not_in';
                        }
                        if ($dataKey == 'country') {
                            $dataValue = (array)$dataValue;
                        }

                    }

                    $provider = 'subscriber';

                    if (strpos($dataKey, 'custom.') === 0) {
                        $provider = 'custom_fields';
                        $dataKey = str_replace('custom.', '', $dataKey);
                    }

                    $item = [
                        'source'   => [
                            $provider,
                            $dataKey
                        ],
                        'operator' => $operator,
                        'value'    => $dataValue
                    ];

                    if ($conditionType == 'match_all') {
                        $formattedConditions[0][] = $item;
                    } else {
                        $formattedConditions[] = [$item];
                    }
                }
            }
            $sequence->settings = [
                'conditions' => $formattedConditions
            ];
            if (!$dryRun) {
                $sequence->save();
            }
        } else if ($conditionName == 'fcrm_woo_conditions') {
            $sequence->action_name = 'funnel_condition';
            $conditionGroups = $sequence->settings['conditional_groups'];

            $formattedConditions = [[]];

            if (isset($conditionGroups[0])) {
                $conditions = $conditionGroups[0]['conditions'];

                $keyMaps = [
                    'order_total_value'     => [
                        'woo_order',
                        'total_value'
                    ],
                    'order_product_ids'     => [
                        'woo_order',
                        'product_ids'
                    ],
                    'order_cat_purchased'   => [
                        'woo_order',
                        'cat_purchased'
                    ],
                    'order_billing_country' => [
                        'woo_order',
                        'billing_country'
                    ],
                    'order_shipping_method' => [
                        'woo_order',
                        'shipping_method'
                    ],
                    'order_payment_gateway' => [
                        'woo_order',
                        'payment_gateway'
                    ],

                    'customer_total_spend'        => [
                        'woo',
                        'total_order_value'
                    ],
                    'customer_order_count'        => [
                        'woo',
                        'total_spend'
                    ],
                    'customer_guest_user'         => [
                        'woo',
                        'guest_user'
                    ],
                    'customer_billing_country'    => [
                        'woo',
                        'billing_country'
                    ],
                    'customer_cat_purchased'      => [
                        'woo',
                        'purchased_categories'
                    ],
                    'customer_purchased_products' => [
                        'woo',
                        'purchased_items'
                    ],
                ];

                foreach ($conditions as $condition) {
                    $dataKey = $condition['data_key'];
                    $operator = $condition['operator'];
                    $dataValue = $condition['data_value'];

                    if (!$dataKey || !$operator || !isset($keyMaps[$dataKey])) {
                        continue;
                    }

                    if (isset($operatorMaps[$operator])) {
                        $operator = $operatorMaps[$operator];
                    }

                    if ($dataKey == 'order_billing_country' || $dataKey == 'customer_billing_country') {
                        if ($operator != '=') {
                            $operator = 'not_in';
                        } else {
                            $operator = 'in';
                        }

                        $dataValue = (array)$dataValue;
                    }

                    $item = [
                        'source'   => $keyMaps[$dataKey],
                        'operator' => $operator,
                        'value'    => $dataValue
                    ];
                    $formattedConditions[0][] = $item;
                }
            }

            $sequence->settings = [
                'conditions' => $formattedConditions
            ];

            if (!$dryRun) {
                $sequence->save();
            }
        }

        return $sequence;
    }


    public static function createWpUserFromSubscriber($subscriber, $sendWelcomeEmail = false, $password = '', $role = '', $metaData = [])
    {
        if ($userId = $subscriber->getWpUserId()) {
            return $userId;
        }

        if (!$password) {
            $password = wp_generate_password(8);
        }

        $userId = wp_create_user(sanitize_user($subscriber->email), $password, $subscriber->email);
        if (is_wp_error($userId)) {
            return $userId;
        }

        if (!$role) {
            // get default user role of WordPress
            $role = get_option('default_role');
            if (!$role) {
                $role = 'subscriber';
            }
        }


        $user = new \WP_User($userId);
        $user->set_role($role);

        $metaData['first_name'] = $subscriber->first_name;
        $metaData['last_name'] = $subscriber->last_name;

        $userMetas = array_filter($metaData);

        foreach ($userMetas as $metaKey => $metaValue) {
            update_user_meta($userId, $metaKey, $metaValue);
        }

        if ($sendWelcomeEmail) {
            wp_send_new_user_notifications($userId, 'user');
        }

        $subscriber->user_id = $userId;
        $subscriber->save();
        return $userId;
    }


    public static function getCountryShortName($countryName)
    {
        if (!function_exists('getFluentFormCountryList')) {
            return null;
        }

        $countries = getFluentFormCountryList();
        if (isset($countries[strtoupper($countryName)])) {
            return $countryName;
        }

        $countries = array_flip($countries);
        if (isset($countries[$countryName])) {
            return $countries[$countryName];
        }
        return null;
    }

    public static function getFunnelSubscriberStatus($defaultStatus, $funnel, $subscriber)
    {
        if ($defaultStatus == 'active' || $defaultStatus == 'waiting') {
            return $defaultStatus;
        }

        $processableStatuses = ['subscribed', 'transactional'];
        if (in_array($subscriber->status, $processableStatuses, true)) {
            return 'active';
        }

        if (Arr::get($funnel->settings, '__force_run_actions') == 'yes') {
            return 'active';
        }

        return $defaultStatus;
    }
}
