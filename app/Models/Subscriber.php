<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Mailer\Handler;
use WPManageNinja\WPOrm\ModelCollection;
use function Clue\StreamFilter\fun;

class Subscriber extends Model
{
    protected $table = 'fc_subscribers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hash',
        'prefix',
        'first_name',
        'last_name',
        'user_id',
        'email',
        'status', // pending / subscribed / bounced / unsubscribed; Default: subscriber
        'contact_type', // lead / customer
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country',
        'phone',
        'timezone',
        'date_of_birth',
        'source',
        'life_time_value',
        'last_activity',
        'total_points',
        'latitude',
        'longitude',
        'ip'
    ];

    public static function boot()
    {
        static::saving(function ($model) {
            $model->hash = md5($model->email);
        });

        static::updating(function ($model) {
            if ($model->user_id && Helper::isUserSyncEnabled()) {
                $user = get_user_by('email', $model->email);
                if ($user) {
                    if ($model->first_name) {
                        update_user_meta($user->ID, 'first_name', $model->first_name);
                    }
                    if ($model->last_name) {
                        update_user_meta($user->ID, 'last_name', $model->last_name);
                    }
                }
                $model->user_id = $user->ID; // in case user id mismatch
            }
        });
    }

    /**
     * $searchable Columns in table to search
     * @var array
     */
    protected $searchable = [
        'email',
        'first_name',
        'last_name',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country',
        'phone',
        'status'
    ];

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param string $search
     * @return ModelQueryBuilder
     */
    public function scopeSearchBy($query, $search)
    {
        if ($search) {
            $fields = $this->searchable;
            $query->where(function ($query) use ($fields, $search) {
                $query->where(array_shift($fields), 'LIKE', "%$search%");

                $nameArray = explode(' ', $search);
                if (count($nameArray) >= 2) {
                    $query->orWhere(function ($q) use ($nameArray) {
                        $fname = array_shift($nameArray);
                        $lastName = implode(' ', $nameArray);
                        $q->where('first_name', 'LIKE', "$fname%");
                        $q->where('last_name', 'LIKE', "$lastName%");
                    });
                }

                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', "$search%");
                }

            });
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param ModelQueryBuilder $query
     * @param array $statuses
     * @return ModelQueryBuilder
     */
    public function scopeFilterByStatues($query, $statuses)
    {
        if ($statuses) {
            $query->whereIn('status', $statuses);
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by contact type
     * @param ModelQueryBuilder $query
     * @param array $statuses
     * @return ModelQueryBuilder
     */
    public function scopeFilterByContactType($query, $type)
    {
        if ($type) {
            $query->where('contact_type', $type);
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by tags
     * @param ModelQueryBuilder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return ModelQueryBuilder
     */
    public function scopeFilterByTags($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';
        $subQuery = $query->getQuery()
            ->table($prefix . 'tags')
            ->innerJoin(
                $prefix . 'subscriber_pivot',
                $prefix . 'subscriber_pivot.object_id',
                '=',
                $prefix . 'tags.id'
            )
            ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Tag')
            ->whereIn($prefix . 'tags.' . $filterBy, $keys)
            ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
            ->select($prefix . 'subscriber_pivot.subscriber_id');

        return $query->whereIn('id', $query->subQuery($subQuery));
    }

    /**
     * Local scope to filter subscribers by not in tags
     * @param ModelQueryBuilder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return ModelQueryBuilder
     */
    public function scopeFilterByNotInTags($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';
        $subQuery = $query->getQuery()
            ->table($prefix . 'tags')
            ->innerJoin(
                $prefix . 'subscriber_pivot',
                $prefix . 'subscriber_pivot.object_id',
                '=',
                $prefix . 'tags.id'
            )
            ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Tag')
            ->whereIn($prefix . 'tags.' . $filterBy, $keys)
            ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
            ->select($prefix . 'subscriber_pivot.subscriber_id');

        return $query->whereNotIn('id', $query->subQuery($subQuery));
    }

    /**
     * Local scope to filter subscribers by lists
     * @param ModelQueryBuilder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return ModelQueryBuilder
     */
    public function scopeFilterByLists($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';
        $subQuery = $query->getQuery()
            ->table($prefix . 'lists')
            ->innerJoin(
                $prefix . 'subscriber_pivot',
                $prefix . 'subscriber_pivot.object_id',
                '=',
                $prefix . 'lists.id'
            )
            ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Lists')
            ->whereIn($prefix . 'lists.' . $filterBy, $keys)
            ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
            ->select($prefix . 'subscriber_pivot.subscriber_id');

        return $query->whereIn('id', $query->subQuery($subQuery));
    }

    /**
     * Local scope to filter subscribers by not in lists
     * @param ModelQueryBuilder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return ModelQueryBuilder
     */
    public function scopeFilterByNotInLists($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';
        $subQuery = $query->getQuery()
            ->table($prefix . 'lists')
            ->innerJoin(
                $prefix . 'subscriber_pivot',
                $prefix . 'subscriber_pivot.object_id',
                '=',
                $prefix . 'lists.id'
            )
            ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Lists')
            ->whereIn($prefix . 'lists.' . $filterBy, $keys)
            ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
            ->select($prefix . 'subscriber_pivot.subscriber_id');

        return $query->whereNotIn('id', $query->subQuery($subQuery));
    }

    /**
     * Many2Many: Subscriber belongs to many tags
     * @return Model Collection
     */
    public function tags()
    {
        $class = __NAMESPACE__ . '\Tag';

        return $this->belongsToMany(
            $class, 'fc_subscriber_pivot', 'subscriber_id', 'object_id'
        )
            ->wherePivot('object_type', $class)
            ->withPivot('object_type')
            ->withTimestamps();
    }

    /**
     * Many2Many: Subscriber belongs to many lists
     * @return Model Collection
     */
    public function lists()
    {
        $class = __NAMESPACE__ . '\Lists';

        return $this->belongsToMany(
            $class, 'fc_subscriber_pivot', 'subscriber_id', 'object_id'
        )
            ->wherePivot('object_type', $class)
            ->withPivot('object_type')
            ->withTimestamps();
    }


    /**
     * One2Many: Subscriber has to many SubscriberMeta
     * @return Model Collection
     */
    public function meta()
    {
        $class = __NAMESPACE__ . '\SubscriberMeta';
        return $this->hasMany(
            $class, 'subscriber_id', 'id'
        );
    }

    /**
     * One2Many: Subscriber has to many SubscriberMeta
     * @return Model Collection
     */
    public function custom_field_meta()
    {
        $class = __NAMESPACE__ . '\SubscriberMeta';
        return $this->hasMany(
            $class, 'subscriber_id', 'id'
        )->where('object_type', '=', 'custom_field');
    }

    /**
     * One2Many: Subscriber has to many Click Metrics
     * @return Model Collection
     */
    public function urlMetrics()
    {
        $class = __NAMESPACE__ . '\CampaignUrlMetric';

        return $this->hasMany(
            $class, 'subscriber_id', 'id'
        );
    }

    /**
     * One2Many: Subscriber has to many custom fields value
     * @return array
     */
    public function custom_fields()
    {
        $customFields = fluentcrm_get_custom_contact_fields();

        if (!$customFields || !is_array($customFields)) {
            return [];
        }

        $keys = array_map(function ($item) {
            return $item['slug'];
        }, $customFields);


        if (!$keys) {
            return [];
        }

        $items = $this->custom_field_meta()->whereIn('key', $keys)->get();
        $formattedValues = [];
        foreach ($items as $item) {
            $formattedValues[$item->key] = $item->value;
        }
        return $formattedValues;
    }

    /**
     * Update Custom Field Values
     * @param $values array of custom values
     * @param bool $deleteOtherValues
     * @return Model
     */
    public function syncCustomFieldValues($values, $deleteOtherValues = true)
    {
        $emptyValues = array_filter($values, function ($value) {
            return empty($value);
        });

        if ($deleteOtherValues) {
            $deleteMetaKeys = array_map(function ($key) {
                return $key;
            }, array_keys($emptyValues));

            if ($deleteMetaKeys) {
                $this->custom_field_meta()->whereIn('key', $deleteMetaKeys)->delete();
            }
        }

        $newValues = array_filter($values);
        foreach ($newValues as $key => $value) {
            $exist = $this->meta()->where('key', $key)->first();
            if ($exist) {
                $exist->fill(['value' => $value])->save();
            } else {
                $meta = new SubscriberMeta();
                $meta->fill([
                    'subscriber_id' => $this->id,
                    'object_type'   => 'custom_field',
                    'key'           => $key,
                    'value'         => $value,
                    'created_by'    => get_current_user_id()
                ]);
                $meta->save();
            }
        }

        do_action('fluentcrm_contact_custom_data_updated', $newValues, $this);

        return $this;
    }

    public function stats()
    {
        return [
            'emails' => CampaignEmail::where('subscriber_id', $this->id)
                ->where('status', 'sent')
                ->count(),
            'opens'  => CampaignEmail::where('subscriber_id', $this->id)
                ->where('is_open', '>', 0)
                ->where('status', 'sent')
                ->count(),
            'clicks' => CampaignEmail::where('subscriber_id', $this->id)
                ->whereNotNull('click_counter')
                ->where('status', 'sent')
                ->count()
        ];
    }


    /**
     * Save the subscriber.
     *
     * @param array $data
     */
    public static function store($data = [])
    {
        $model = static::create($data);

        $tagIds = Arr::get($data, 'tags', []);
        if ($tagIds) {
            $model->attachTags($tagIds);
        }

        $listIds = Arr::get($data, 'lists', []);
        if ($listIds) {
            $model->attachLists($listIds);
        }

        if ($customValues = Arr::get($data, 'custom_values')) {
            $model->syncCustomFieldValues($customValues);
        }

        return $model;
    }

    /**
     * Get subscriber mappable fields.
     *
     * @return array
     */
    public static function mappables()
    {
        return [
            'prefix'         => 'Name Prefix',
            'first_name'     => 'First Name',
            'last_name'      => 'Last Name',
            'full_name'      => 'Full Name',
            'email'          => 'Email',
            'timezone'       => 'Timezone',
            'address_line_1' => 'Address Line 1',
            'address_line_2' => 'Address Line 2',
            'city'           => 'City',
            'state'          => 'State',
            'postal_code'    => 'Postal Code',
            'country'        => 'Country',
            'ip'             => 'IP Address',
            'phone'          => 'Phone',
            'source'         => 'Source',
            'date_of_birth'  => 'Date of Birth (Y-m-d Format only)'
        ];
    }

    /**
     * Accessor to get dynamic photo attribute
     * @return string
     */
    public function getPhotoAttribute()
    {
        if (isset($this->attributes['avatar'])) {
            return $this->attributes['avatar'];
        }
        return fluentcrmGravatar($this->attributes['email']);
    }

    /**
     * Accessor to get dynamic full_name attribute
     * @return string
     */
    public function getFullNameAttribute()
    {
        $fname = $this->attributes['first_name'];
        $lname = $this->attributes['last_name'];
        return trim("{$fname} {$lname}");
    }

    /**
     * Import csv/wpusers into subscribers
     * @param array $data
     * @param array $tags
     * @param array $lists
     * @param mixed $update string true/false or boolean true/false
     * @param string $newStatus status for the new subscribers
     * @param boolean $doubleOptin Send Double Optin Emails for new pending contacts
     * @return array affected records/collection
     */
    public static function import($data, $tags, $lists, $update, $newStatus = '', $doubleOptin = false)
    {
        if(!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            define('FLUENTCRM_DOING_BULK_IMPORT', true);
        }

        ob_start();
        $insertables = [];
        $updateables = [];
        $insertedModels = new ModelCollection;
        $updatedModels = new ModelCollection;
        $shouldUpdate = $update === 'true' || $update === true;

        $records = [];

        foreach ($data as $index => $record) {
            $record = self::explodeFullName($record);
            $data[$index] = $record;
            $records[] = $record['email'];
        }

        $existingSubscribers = [];
        $oldSubscribers = static::whereIn('email', $records)->get();

        foreach ($oldSubscribers as $model) {
            $existingSubscribers[$model->email] = $model;
        }

        $strictStatuses = fluentcrm_strict_statues();

        $newContactCustomFields = [];
        $newRecords = [];
        $skips = [];
        foreach ($data as $item) {
            $item['hash'] = md5($item['email']);
            if (isset($existingSubscribers[$item['email']])) {
                if ($newStatus && !in_array($newStatus, $strictStatuses)) {
                    $item['status'] = $existingSubscribers[$item['email']]->status;
                } else if ($newStatus) {
                    $item['status'] = $newStatus;
                }

                unset($item['source']);

                if ($customValues = Arr::get($item, 'custom_values')) {
                    $existingSubscribers[$item['email']]->syncCustomFieldValues($customValues, false);
                }
                unset($item['custom_values']);
                $updateables[] = $item;
            } else {
                if (isset($newRecords[$item['email']])) {
                    $skips[] = $item;
                    continue;
                }
                $extraValues = [
                    'created_at' => fluentCrmTimestamp()
                ];
                if ($newStatus) {
                    $extraValues['status'] = $newStatus;
                }

                if ($customValues = Arr::get($item, 'custom_values')) {
                    $newContactCustomFields[$item['email']] = $customValues;
                }

                $newRecords[$item['email']] = 1;

                unset($item['custom_values']);
                $insertables[] = array_merge($item, $extraValues);
            }
        }


        if ($insertables) {
            $insertIds = static::insert($insertables);

            $insertIds = array_filter($insertIds);
            $insertedModels = static::whereIn('id', $insertIds)->get();

            /*
             * Add custom Fields if available
             */
            if ($newContactCustomFields) {
                foreach ($insertedModels as $insertedModel) {
                    if (isset($newContactCustomFields[$insertedModel->email])) {
                        $insertedModel->syncCustomFieldValues(
                            $newContactCustomFields[$insertedModel->email], false
                        );
                    }
                }
            }
        }

        if ($shouldUpdate) {
            foreach ($updateables as $updateable) {
                $existingModel = $existingSubscribers[$updateable['email']];
                $existingModel->fill($updateable)->save();
                $updatedModels->push($existingModel);
            }
        }

        // Syncing Tags & Lists
        if ($tags || $lists || $doubleOptin) {
            if ($shouldUpdate) {
                foreach ($oldSubscribers->merge($insertedModels) as $model) {
                    $tags && $model->attachTags($tags);
                    $lists && $model->attachLists($lists);
                }
            } else {
                foreach ($insertedModels as $model) {
                    $tags && $model->attachTags($tags);
                    $lists && $model->attachLists($lists);

                    if($doubleOptin && $model->status == 'pending') {
                        $model->sendDoubleOptinEmail();
                    }
                }
            }
        }

        do_action('fluentcrm_contacts_imported_bulk', $insertedModels);
        do_action('fluentcrm_contacts_updated_bulk', $updatedModels);

        $errors = ob_get_clean();

        return [
            'inserted' => $insertedModels,
            'updated'  => $updatedModels,
            'skips'    => $skips,
            'errors' => $errors
        ];
    }

    public function updateOrCreate($data, $forceUpdate = false, $deleteOtherValues = false, $sync = false)
    {
        $subscriberData = static::explodeFullName($data);
        $subscriberData = array_filter(Arr::only($subscriberData, $this->getFillable()));
        $tags = Arr::get($data, 'tags', []);
        $lists = Arr::get($data, 'lists', []);

        $exist = static::where('email', $subscriberData['email'])->first();

        if (empty($subscriberData['user_id'])) {
            $user = get_user_by('user_email', $subscriberData['email']);
            if ($user) {
                $subscriberData['user_id'] = $user->ID;
            }
        }

        $isNew = true;
        $oldStatus = '';
        if ($exist) {
            $isNew = false;
            $oldStatus = $exist->status;
        }

        if (!empty($data['status'])) {
            $status = $data['status'];
            if ($forceUpdate) {
                $subscriberData['status'] = $status;
            } else if ($exist && $exist->status == 'subscribed') {
                unset($subscriberData['status']);
            } else if ($exist && in_array($exist->status, ['bounced', 'complained'])) {
                unset($subscriberData['status']);
            } else {
                $subscriberData['status'] = $status;
            }
        }

        $isSubscribed = false;
        if (($exist && $exist->status != 'subscribed') && (!empty($subscriberData['status']) && $subscriberData['status']) == 'subscribed') {
            $isSubscribed = true;
        } else if (!$exist && (!empty($subscriberData['status']) && $subscriberData['status']) == 'subscribed') {
            $isSubscribed = true;
        }

        if ($exist) {
            $exist->fill($subscriberData)->save();
        } else {
            $exist = static::create($subscriberData);
        }

        // Syncing Tags
        $tags && $exist->attachTags($tags);

        // Syncing Lists
        $lists && $exist->attachLists($lists);

        if ($customValues = Arr::get($data, 'custom_values')) {
            $exist->syncCustomFieldValues($customValues, $deleteOtherValues);
        }

        if ($isNew) {
            do_action('fluentcrm_contact_created', $exist);
        } else {
            do_action('fluentcrm_contact_updated', $exist);
        }

        if ($isSubscribed && $exist->status == 'subscribed') {
            do_action('fluentcrm_subscriber_status_to_subscribed', $exist, $oldStatus);
        }

        return $exist;
    }

    public function sendDoubleOptinEmail()
    {
        $lastDoubleOptin = fluentcrm_get_subscriber_meta($this->id, '_last_double_optin_timestamp');
        if ($lastDoubleOptin && (time() - $lastDoubleOptin < 150)) {
            return false;
        } else {
            fluentcrm_update_subscriber_meta($this->id, '_last_double_optin_timestamp', time());
        }

        return (new Handler())->sendDoubleOptInEmail($this);
    }

    public static function explodeFullName($record)
    {
        if (!empty($record['first_name']) || !empty($record['last_name'])) {
            return $record;
        }
        if (!empty($record['full_name'])) {
            $fullNameArray = explode(' ', $record['full_name']);
            $record['first_name'] = array_shift($fullNameArray);
            if ($fullNameArray) {
                $record['last_name'] = implode(' ', $fullNameArray);
            }
            unset($record['full_name']);
        }

        return $record;
    }

    public function attachLists($listIds)
    {
        if (!$listIds) {
            return $this;
        }

        $existingLists = $this->lists;
        $existingListIds = [];
        foreach ($existingLists as $list) {
            $existingListIds[] = $list->id;
        }
        $newListIds = array_diff($listIds, $existingListIds);

        $lists = array_combine($newListIds, array_fill(
            0, count($newListIds), ['object_type' => 'FluentCrm\App\Models\Lists']
        ));

        if ($lists) {
            $this->lists()->attach($lists);
            $this->load('lists');
            fluentcrm_contact_added_to_lists($newListIds, $this);
        }

        return $this;
    }

    public function attachTags($tagIds)
    {
        if (!$tagIds) {
            return $this;
        }
        $existingTags = $this->tags;
        $existingTagIds = [];
        foreach ($existingTags as $tag) {
            $existingTagIds[] = $tag->id;
        }
        $newTagIds = array_diff($tagIds, $existingTagIds);

        $tags = array_combine($newTagIds, array_fill(
            0, count($newTagIds), ['object_type' => 'FluentCrm\App\Models\Tag']
        ));

        if ($tags) {
            $this->tags()->attach($tags);
            $this->load('tags');
            fluentcrm_contact_added_to_tags($newTagIds, $this);
        }

        return $this;
    }

    public function detachLists($listIds)
    {
        if (!$listIds) {
            return $this;
        }
        $existingLists = $this->lists;
        $existingListIds = [];
        foreach ($existingLists as $list) {
            $existingListIds[] = $list->id;
        }

        $validListIds = array_intersect($listIds, $existingListIds);

        if ($validListIds) {
            $this->lists()->detach($validListIds);
            $this->load('lists');
            fluentcrm_contact_removed_from_lists($validListIds, $this);
        }

        return $this;
    }

    public function detachTags($tagsIds)
    {
        if (!$tagsIds) {
            return $this;
        }

        $existingTags = $this->tags;
        $existingTagIds = [];
        foreach ($existingTags as $tag) {
            $existingTagIds[] = $tag->id;
        }

        $validTagIds = array_intersect($tagsIds, $existingTagIds);

        if ($validTagIds) {
            $this->tags()->detach($validTagIds);
            $this->load('tags');
            fluentcrm_contact_removed_from_tags($validTagIds, $this);
        }

        return $this;
    }

    public function unsubscribeReason($metaKey = 'unsubscribe_reason')
    {
        return fluentcrm_get_subscriber_meta($this->id, $metaKey, '');
    }

    public function hasAnyTagId($tagIds)
    {
        if (!$tagIds || !is_array($tagIds)) {
            return false;
        }
        foreach ($this->tags as $tag) {
            if (in_array($tag->id, $tagIds)) {
                return true;
            }
        }
        return false;
    }

    public function hasAnyListId($listIds)
    {
        if (!$listIds || !is_array($listIds)) {
            return false;
        }
        foreach ($this->lists as $list) {
            if (in_array($list->id, $listIds)) {
                return true;
            }
        }
        return false;
    }
}
