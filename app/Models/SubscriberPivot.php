<?php

namespace FluentCrm\App\Models;


/**
 *  SubscriberPivot Model - DB Model for Contact's relationships
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class SubscriberPivot extends Model
{
    protected $table = 'fc_subscriber_pivot';

    protected $guarded = ['id'];

    public function scopeFilter($query, $constraints)
    {
        foreach ($constraints as $filed => $value) {
            $query->where($filed, $value);
        }

        return $query;
    }

    /**
     * Save an entry to the subscriber pivot table.
     *
     * @param  array $attributes
     * @return int
     */
    public static function store($attributes)
    {
        $attributes += [
            'created_at' => $now = date('Y-m-d h-i-s'),
            'updated_at' => $now
        ];

        return static::insert($attributes);
    }

    /**
     * Attach tags/lists to the subscriber.
     *
     * @param array  $items
     * @param int    $subscriber
     * @param string $type
     */
    public static function attach($items, $subscriber, $type)
    {
        $objectIds = [];

        foreach ($items as $objectId) {
            $objectIds = array_merge($objectIds, [$objectId]);
            static::firstOrCreate([
                'subscriber_id' => $subscriber,
                'object_id'     => $objectId,
                'object_type'   => $type
            ]);
        }

        if ($objectIds) {
            $function = static::getFunctionName($type, __FUNCTION__);
            $function($objectIds, Subscriber::find($subscriber));
        }
    }

    /**
     * Detach tags/lists from the subscriber.
     *
     * @param array  $items
     * @param int    $subscriber
     * @param string $type
     */
    public static function detach($items, $subscriber, $type)
    {
        if ($items) {
            static::where('subscriber_id', $subscriber)
                  ->where('object_type', $type)
                  ->whereIn('object_id', $items)
                  ->delete();

            $function = static::getFunctionName($type, __FUNCTION__);
            $function($items, Subscriber::find($subscriber));
        }
    }

    private static function getFunctionName($type, $prefix)
    {
        $parts = explode('\\', $type);
        $typeOfObject = end($parts);
        $function = $typeOfObject == 'Tag' ? 'tags' : 'lists';

        if ($prefix == 'attach') {
            return "fluentcrm_contact_added_to_$function";
        } else if ($prefix == 'detach') {
            return "fluentcrm_contact_removed_from_$function";
        }
    }
}
