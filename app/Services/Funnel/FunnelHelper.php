<?php

namespace FluentCrm\App\Services\Funnel;

use FluentCrm\App\Models\FunnelSubscriber;
use FluentCrm\App\Models\Subscriber;

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
                'title' => 'Update if Exist'
            ],
            [
                'id'    => 'skip_all_if_exist',
                'title' => 'Skip this automation if contact already exist'
            ]
        ];
    }

    public static function prepareUserData($user)
    {
        if(is_numeric($user)) {
            $user = get_user_by('ID', $user);
        }

        return array_filter([
            'user_id'    => $user->ID,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->user_email,
            'source'     => 'web',
            'ip'         => FluentCrm()->request->getIp()
        ]);
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
        return (new Subscriber)->updateOrCreate($data, false, false);
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
            $tags = array_combine($tags, array_fill(
                0, count($tags), ['object_type' => 'FluentCrm\App\Models\Tag']
            ));
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
                'label' => 'First Name'
            ],
            'last_name'  => [
                'type'  => 'value_options',
                'label' => 'Last Name'
            ],
            'email'      => [
                'type'  => 'value_options',
                'label' => 'Email'
            ]
        ];
    }

    public static function getSecondaryContactFieldMaps()
    {
        $mainFields = [
            'prefix' => [
                'type' => 'value_options',
                'label' => 'Name Prefix'
            ],
            'address_line_1' => [
                'type'  => 'value_options',
                'label' => 'Address Line 1'
            ],
            'address_line_2'  => [
                'type'  => 'value_options',
                'label' => 'Address Line 2'
            ],
            'postal_code'      => [
                'type'  => 'value_options',
                'label' => 'Postal Code'
            ],
            'city'      => [
                'type'  => 'value_options',
                'label' => 'City'
            ],
            'state'      => [
                'type'  => 'value_options',
                'label' => 'State'
            ],
            'country'      => [
                'type'  => 'value_options',
                'label' => 'country'
            ],
            'phone'      => [
                'type'  => 'value_options',
                'label' => 'Phone'
            ]
        ];

        $customFields = fluentcrm_get_option('contact_custom_fields', []);
        if ($customFields) {
            foreach ($customFields as $item) {
                $mainFields['custom.' . $item['slug']] = [
                    'type' => 'value_options',
                    'label' => $item['label']
                ];
            }
        }

        return $mainFields;

    }

}
