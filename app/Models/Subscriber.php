<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\Libs\Mailer\Handler;
use FluentCrm\Framework\Database\Orm\Collection;

/**
 *  Subscriber Model - DB Model for Contacts
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class Subscriber extends Model
{
    protected $table = 'fc_subscribers';

    protected $guarded = ['id'];

    protected $appends = ['full_name', 'photo'];

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
        'ip',
        'created_at',
        'updated_at',
        'avatar'
    ];

    public static function boot()
    {
        static::saving(function ($model) {
            $model->hash = md5($model->email);
        });

        static::updating(function ($model) {
            if ($model->user_id && Helper::isUserSyncEnabled()) {
                $user = get_user_by('email', $model->email);

                $email_mismatch = false;

                if (!$user) {
                    $email_mismatch = true;
                    $user = get_user_by('ID', $model->user_id);
                }

                if ($user) {
                    if ($model->first_name) {
                        update_user_meta($user->ID, 'first_name', $model->first_name);
                    }
                    if ($model->last_name) {
                        update_user_meta($user->ID, 'last_name', $model->last_name);
                    }

                    if ($email_mismatch && apply_filters('fluentcrm_update_wp_user_email_on_change', false)) {
                        $user->user_email = $model->email;
                        wp_update_user($user);
                    }

                    $model->user_id = $user->ID; // in case user id mismatch
                }
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
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param string $search
     * @param boolean $custom_fields
     * @return \FluentCrm\Framework\Database\Query\Builder $query
     */
    public function scopeSearchBy($query, $search, $custom_fields = false)
    {
        if ($search) {
            $fields = $this->searchable;
            $query->where(function ($query) use ($fields, $search, $custom_fields) {
                $query->where(array_shift($fields), 'LIKE', "%$search%");

                $nameArray = explode(' ', $search);
                if (count($nameArray) >= 2) {
                    $query->orWhere(function ($q) use ($nameArray) {
                        $fname = array_shift($nameArray);
                        $lastName = implode(' ', $nameArray);
                        $q->where('first_name', 'LIKE', "%$fname%");
                        $q->orWhere('last_name', 'LIKE', "%$lastName%");
                    });
                }

                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', "%$search%");
                }
            });

            /**
             * If contact list has custom field
             * Then this block is responsible for searching by custom filed
             */
            if ($custom_fields) {
                $query->orWhere(function ($q) use ($search, $custom_fields) {
                    $q->whereHas('custom_field_meta', function ($q) use ($search) {
                        $q->where('value', 'LIKE', "%$search%");
                    });
                });
            }
        }

        return $query;
    }

    /**
     * Local scope to filter subscribers by search/query string
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param array $statuses
     * @return \FluentCrm\Framework\Database\Query\Builder $query
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
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param array $statuses
     * @return \FluentCrm\Framework\Database\Query\Builder $query
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
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return \FluentCrm\Framework\Database\Query\Builder $query
     */
    public function scopeFilterByTags($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';

        return $query->whereIn('id', function ($q) use ($prefix, $keys, $filterBy) {
            $q->from($prefix . 'tags')
                ->join(
                    $prefix . 'subscriber_pivot',
                    $prefix . 'subscriber_pivot.object_id',
                    '=',
                    $prefix . 'tags.id'
                )
                ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Tag')
                ->whereIn($prefix . 'tags.' . $filterBy, $keys)
                ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
                ->select($prefix . 'subscriber_pivot.subscriber_id');
        });
    }

    /**
     * Local scope to filter subscribers by not in tags
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return \FluentCrm\Framework\Database\Query\Builder $query
     */
    public function scopeFilterByNotInTags($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';

        return $query->whereNotIn('id', function ($q) use ($prefix, $keys, $filterBy) {
            $q->from($prefix . 'tags')
                ->join(
                    $prefix . 'subscriber_pivot',
                    $prefix . 'subscriber_pivot.object_id',
                    '=',
                    $prefix . 'tags.id'
                )
                ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Tag')
                ->whereIn($prefix . 'tags.' . $filterBy, $keys)
                ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
                ->select($prefix . 'subscriber_pivot.subscriber_id');
        });
    }

    /**
     * Local scope to filter subscribers by lists
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return \FluentCrm\Framework\Database\Query\Builder $query
     */
    public function scopeFilterByLists($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';

        return $query->whereIn('id', function ($q) use ($prefix, $keys, $filterBy) {
            $q->from($prefix . 'lists')
                ->join(
                    $prefix . 'subscriber_pivot',
                    $prefix . 'subscriber_pivot.object_id',
                    '=',
                    $prefix . 'lists.id'
                )
                ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Lists')
                ->whereIn($prefix . 'lists.' . $filterBy, $keys)
                ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
                ->select($prefix . 'subscriber_pivot.subscriber_id');
        });
    }

    /**
     * Local scope to filter subscribers by not in lists
     * @param \FluentCrm\Framework\Database\Query\Builder $query
     * @param array $keys
     * @param string $filterBy id/slug
     * @return \FluentCrm\Framework\Database\Query\Builder $query
     */
    public function scopeFilterByNotInLists($query, $keys, $filterBy = 'id')
    {
        $prefix = 'fc_';

        return $query->whereNotIn('id', function ($q) use ($prefix, $keys, $filterBy) {
            $q->from($prefix . 'lists')
                ->join(
                    $prefix . 'subscriber_pivot',
                    $prefix . 'subscriber_pivot.object_id',
                    '=',
                    $prefix . 'lists.id'
                )
                ->where($prefix . 'subscriber_pivot.object_type', 'FluentCrm\App\Models\Lists')
                ->whereIn($prefix . 'tags.' . $filterBy, $keys)
                ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
                ->select($prefix . 'subscriber_pivot.subscriber_id');
        });
    }

    /**
     * Many2Many: Subscriber belongs to many tags
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsToMany
     */
    public function tags()
    {
        $class = __NAMESPACE__ . '\Tag';

        return $this->belongsToMany(
            $class,
            'fc_subscriber_pivot',
            'subscriber_id',
            'object_id'
        )
            ->wherePivot('object_type', $class)
            ->withPivot('object_type')
            ->withTimestamps();
    }

    /**
     * Many2Many: Subscriber has many email sequences
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsToMany
     */
    public function sequences()
    {
        $class = '\FluentCampaign\App\Models\Sequence';

        return $this->belongsToMany(
            $class,
            'fc_sequence_tracker',
            'subscriber_id',
            'campaign_id'
        )
            ->withoutGlobalScopes()
            ->withTimestamps();
    }

    /**
     * Many2Many: Subscriber has many sequence trackers
     * @return \FluentCrm\Framework\Database\Orm\Relations\HasMany
     */
    public function sequence_trackers()
    {
        $class = '\FluentCampaign\App\Models\SequenceTracker';

        return $this->hasMany(
            $class,
            'subscriber_id',
            'id'
        );
    }

    /**
     * Many2Many: Subscriber has many funnels
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsToMany
     */
    public function funnels()
    {
        $class = __NAMESPACE__ . '\Funnel';

        return $this->belongsToMany(
            $class,
            'fc_funnel_subscribers',
            'subscriber_id',
            'funnel_id'
        )
            ->withoutGlobalScopes()
            ->withTimestamps();
    }

    /**
     * Many2Many: Subscriber has many funnel subscribers
     * @return \FluentCrm\Framework\Database\Orm\Relations\hasMany
     */
    public function funnel_subscribers()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\FunnelSubscriber',
            'subscriber_id',
            'id'
        );
    }

    /**
     * hasMany: Subscriber has many commerce items
     * @return \FluentCrm\Framework\Database\Orm\Relations\HasMany
     */
    public function contact_commerce()
    {
        $class = '\FluentCampaign\App\Services\Commerce\ContactRelationModel';
        return $this->hasMany($class, 'subscriber_id', 'id');
    }

    /**
     * hasOne: Subscriber has a commerce for a specific provider
     * @return \FluentCrm\Framework\Database\Orm\Relations\hasOne
     */
    public function commerce_by_provider()
    {
        $class = '\FluentCampaign\App\Services\Commerce\ContactRelationModel';
        return $this->hasOne($class, 'subscriber_id', 'id');
    }


    /**
     * hasOne: Subscriber has many commerce items
     * @return \FluentCrm\Framework\Database\Orm\Relations\HasMany
     */
    public function contact_commerce_items()
    {
        $class = '\FluentCampaign\App\Services\Commerce\ContactRelationItemsModel';
        return $this->hasMany($class, 'subscriber_id', 'id');
    }

    public function scopeCommerceItemsItemIds($query, $provider, $method, $column, $values)
    {
        $query->whereHas('contact_commerce_items', $provider)
            ->{$method}($column, $values);
    }

    public function affiliate_wp()
    {
        $class = '\FluentCampaign\App\Services\Integrations\AffiliateWP\AffiliateWPModel';
        return $this->hasOne($class, 'user_id', 'user_id');
    }

    /**
     * Many2Many: Subscriber belongs to many lists
     * @return Model Collection
     */
    public function lists()
    {
        $class = __NAMESPACE__ . '\Lists';

        return $this->belongsToMany(
            $class,
            'fc_subscriber_pivot',
            'subscriber_id',
            'object_id'
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
            $class,
            'subscriber_id',
            'id'
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
            $class,
            'subscriber_id',
            'id'
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
            $class,
            'subscriber_id',
            'id'
        );
    }

    /**
     * A subscriber has many campaign emails.
     *
     * @return \FluentCrm\Framework\Database\Orm\Relations\HasMany
     */
    public function campaignEmails()
    {
        return $this->hasMany(CampaignEmail::class, 'subscriber_id', 'id');
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
            return $value === '';
        });

        if ($deleteOtherValues) {
            $deleteMetaKeys = array_map(function ($key) {
                return $key;
            }, array_keys($emptyValues));

            if ($deleteMetaKeys) {
                $this->custom_field_meta()->whereIn('key', $deleteMetaKeys)->delete();
            }
        }

        $newValues = array_filter($values, function ($value) {
            return $value !== '';
        });

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

        if ($customValues = Arr::get($data, 'custom_values')) {
            $model->syncCustomFieldValues($customValues);
        }

        $tagIds = Arr::get($data, 'tags', []);
        if ($tagIds) {
            $model->attachTags($tagIds);
        }

        $listIds = Arr::get($data, 'lists', []);
        if ($listIds) {
            $model->attachLists($listIds);
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
            'prefix'         => __('Name Prefix', 'fluent-crm'),
            'first_name'     => __('First Name', 'fluent-crm'),
            'last_name'      => __('Last Name', 'fluent-crm'),
            'full_name'      => __('Full Name', 'fluent-crm'),
            'email'          => __('Email', 'fluent-crm'),
            'timezone'       => __('Timezone', 'fluent-crm'),
            'address_line_1' => __('Address Line 1', 'fluent-crm'),
            'address_line_2' => __('Address Line 2', 'fluent-crm'),
            'city'           => __('City', 'fluent-crm'),
            'state'          => __('State', 'fluent-crm'),
            'postal_code'    => __('Postal Code', 'fluent-crm'),
            'country'        => __('Country', 'fluent-crm'),
            'ip'             => __('IP Address', 'fluent-crm'),
            'phone'          => __('Phone', 'fluent-crm'),
            'source'         => __('Source', 'fluent-crm'),
            'date_of_birth'  => __('Date of Birth (Y-m-d Format only)', 'fluent-crm')
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

        $fallBack = '';
        if (isset($this->attributes['first_name'])) {
            $fallBack = $this->attributes['first_name'];
        }

        if (isset($this->attributes['last_name'])) {
            $fallBack .= '+' . $this->attributes['last_name'];
        }

        return fluentcrmGravatar($this->attributes['email'], $fallBack);
    }

    /**
     * Accessor to get dynamic full_name attribute
     * @return string
     */
    public function getFullNameAttribute()
    {
        $fname = isset($this->attributes['first_name']) ? $this->attributes['first_name'] : '';
        $lname = isset($this->attributes['last_name']) ? $this->attributes['last_name'] : '';
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
     * @param string $source Fallback Source for New Contacts
     * @return array affected records/collection
     */
    public static function import($data, $tags, $lists, $update, $newStatus = '', $doubleOptin = false, $forceStatusChange = false, $source = '')
    {
        if (!defined('FLUENTCRM_DOING_BULK_IMPORT')) {
            define('FLUENTCRM_DOING_BULK_IMPORT', true);
        }

        ob_start();
        $insertables = [];
        $updateables = [];
        $updatedModels = new Collection;
        $shouldUpdate = $update === 'true' || $update === true;

        $records = [];

        $uniqueEmails = [];

        foreach ($data as $index => $record) {
            $email = $record['email'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || in_array(strtolower($email), $uniqueEmails)) {
                unset($data[$index]);
                continue;
            }

            $uniqueEmails[] = strtolower($email);

            $record = self::explodeFullName($record);
            $data[$index] = $record;

            $records[] = $email;
        }

        $existingSubscribers = [];
        $oldSubscribers = static::whereIn('email', $records)->get();

        foreach ($oldSubscribers as $model) {
            $existingSubscribers[strtolower($model->email)] = $model;
        }

        $strictStatuses = fluentcrm_strict_statues();

        $newContactCustomFields = [];
        $newRecords = [];
        $skips = [];
        foreach ($data as $item) {
            $item['hash'] = md5($item['email']);
            $lowEmail = strtolower($item['email']);
            if (isset($existingSubscribers[$lowEmail])) {
                if (!$forceStatusChange && $newStatus && !in_array($newStatus, $strictStatuses)) {
                    $item['status'] = $existingSubscribers[$lowEmail]->status;
                } else if ($newStatus) {
                    $item['status'] = $newStatus;
                }

                unset($item['source']);

                $customValues = Arr::get($item, 'custom_values');
                if ($shouldUpdate && $customValues) {
                    $existingSubscribers[$lowEmail]->syncCustomFieldValues($customValues, false);
                }
                unset($item['custom_values']);
                unset($item['id']);
                unset($item['created_at']);
                $item['updated_at'] = fluentCrmTimestamp();
                $updateables[] = array_filter($item);
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

                $itemEmail = $item['email'];

                unset($item['custom_values']);
                unset($item['id']);

                if (empty($item['source']) && $source) {
                    $item['source'] = $source;
                }

                $newRecords[$itemEmail] = 1;
                $insertables[] = array_filter(array_merge($item, $extraValues));
            }
        }

        $insertedModels = [];
        if ($insertables) {
            foreach ($insertables as $insertable) {
                $insertedModel = self::create($insertable);
                if ($newContactCustomFields) {
                    if (isset($newContactCustomFields[$insertedModel->email])) {
                        $insertedModel->syncCustomFieldValues(
                            $newContactCustomFields[$insertedModel->email],
                            false
                        );
                    }
                }

                if ($tags || $lists || $doubleOptin) {
                    $tags && $insertedModel->attachTags($tags);
                    $lists && $insertedModel->attachLists($lists);

                    if ($doubleOptin && $insertedModel->status == 'pending') {
                        $insertedModel->sendDoubleOptinEmail();
                    }
                }

                do_action('fluentcrm_contact_created', $insertedModel);
                $insertedModels[] = $insertedModel;
            }
        }

        if ($shouldUpdate) {
            foreach ($updateables as $updateable) {
                $existingModel = $existingSubscribers[strtolower($updateable['email'])];
                $oldStatus = $existingModel->status;
                $existingModel->fill($updateable)->save();

                if (!empty($updateable['status']) && $updateable['status'] != $oldStatus) {
                    $newStatus = $updateable['status'];
                    do_action('fluentcrm_subscriber_status_to_' . $newStatus, $existingModel, $oldStatus);
                }

                do_action('fluentcrm_contact_updated', $existingModel);
                $updatedModels->push($existingModel);
            }
        }

        // Syncing Tags & Lists
        if ($tags || $lists || $doubleOptin) {
            if ($shouldUpdate) {
                foreach ($oldSubscribers as $model) {
                    $tags && $model->attachTags($tags);
                    $lists && $model->attachLists($lists);
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
            'errors'   => $errors
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
            $user = get_user_by('email', $subscriberData['email']);
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
            $oldEmail = $exist->email;
            $exist->fill($subscriberData);
            $dirtyFields = $exist->getDirty();
            $exist->save();

            if (isset($dirtyFields['email'])) {
                do_action('fluentcrm_contact_email_changed', $exist, $oldEmail);
            }
        } else {
            if (!isset($subscriberData['created_at'])) {
                $subscriberData['created_at'] = current_time('mysql');
            }
            $exist = static::create($subscriberData);
            $exist = $this->find($exist->id);
            $exist->wasRecentlyCreated = true;
        }

        if ($customValues = Arr::get($data, 'custom_values', [])) {
            $exist->syncCustomFieldValues($customValues, $deleteOtherValues);
        }

        // Syncing Tags
        $tags && $exist->attachTags($tags);

        // Syncing Lists
        $lists && $exist->attachLists($lists);

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

        $newListIds = array_map(function ($listId) {
            return (int) $listId;
        }, $newListIds);

        $newListIds = array_filter($newListIds);

        if (!$newListIds) {
            return $this;
        }

        $lists = array_combine($newListIds, array_fill(
            0,
            count($newListIds),
            ['object_type' => 'FluentCrm\App\Models\Lists']
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
            $existingTagIds[] = (int) $tag->id;
        }

        $newTagIds = array_diff($tagIds, $existingTagIds);

        $newTagIds = array_map(function ($tagId) {
            return (int) $tagId;
        }, $newTagIds);

        $newTagIds = array_filter($newTagIds);

        if (!$newTagIds) {
            return $this;
        }

        $tags = array_combine($newTagIds, array_fill(
            0,
            count($newTagIds),
            ['object_type' => 'FluentCrm\App\Models\Tag']
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

        $validListIds = array_map(function ($listId) {
            return (int) $listId;
        }, $validListIds);

        $validListIds = array_filter($validListIds);

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


        $validTagIds = array_map(function ($tagId) {
            return (int) $tagId;
        }, $validTagIds);

        $validTagIds = array_filter($validTagIds);

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

    public function unsubscribeReasonDate($metaKey = 'unsubscribe_reason')
    {
        $item = SubscriberMeta::where('key', $metaKey)
            ->where('subscriber_id', $this->id)
            ->first();

        if ($item) {
            return (string)$item->updated_at;
        }
        return '';
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

    public function updateMeta($metaKey, $metaValue, $objectType)
    {
        $exist = $this->meta()
            ->where('key', $metaKey)
            ->where('object_type', $objectType)
            ->first();

        if ($exist) {
            $exist->value = $metaValue;
            $exist->save();
            return true;
        }
        $this->meta()->create([
            'key'         => $metaKey,
            'object_type' => $objectType,
            'value'       => $metaValue
        ]);

        return true;
    }

    public function getMeta($metaKey, $objectType)
    {
        $exist = $this->meta()
            ->where('key', $metaKey)
            ->where('object_type', $objectType)
            ->first();
        if ($exist) {
            return $exist->value;
        }

        return false;
    }

    /**
     * Parse filter to set proper operator and value for the filter query for date operators
     *
     * @param array $filter
     * @return array
     */
    public static function filterParser($filter)
    {

        switch ($filter['operator']) {
            case 'before':
                $filter['operator'] = '<';
                if(strlen($filter['value']) < 11) {
                    $filter['value'] = $filter['value'] . ' 23:59:59';
                }
                break;

            case 'after':
                $filter['operator'] = '>';
                if(strlen($filter['value']) < 11) {
                    $filter['value'] = $filter['value'] . ' 23:59:59';
                }
                break;

            case 'date_equal':
            case 'contains':
                $filter['operator'] = 'LIKE';
                $filter['value'] = '%' . $filter['value'] . '%';
                break;
            case 'not_contains':
                $filter['operator'] = 'NOT LIKE';
                $filter['value'] = '%' . $filter['value'] . '%';
                break;

            case 'days_before':
                $daysToSeconds = intval($filter['value']) * 24 * 60 * 60;
                $filter['operator'] = '<';
                $filter['value'] = date('Y-m-d', current_time('timestamp') - $daysToSeconds);
                break;

            case 'days_within':
                $daysToSeconds = intval($filter['value']) * 24 * 60 * 60;
                $filter['operator'] = 'BETWEEN';
                $filter['value'] = [
                    date('Y-m-d 00:00:01', current_time('timestamp') - $daysToSeconds),
                    date('Y-m-d') . ' 23:59:59'
                ];
                break;
        }

        return $filter;
    }

    public static function applyGeneralFilterQuery($query, $filter, $referenceColumn = 'value')
    {
        $exactOperators = ['=', '!=', '>', '<'];

        $operator = self::parseCustomFieldsFilterOperator($filter);

        if (in_array($operator, $exactOperators)) {
            if ($operator == '>' || $operator == '<') {
                $filter['value'] = (int)$filter['value'];
            } else {
                $filter['value'] = sanitize_text_field($filter['value']);
            }
            $query->where($referenceColumn, $operator, $filter['value']);
        } else {
            $filter = self::filterParser($filter);

            if ($filter['operator'] != $operator) {
                $newOperator = $filter['operator'];
                if ($newOperator == 'BETWEEN') {
                    $query->whereBetween($referenceColumn, $filter['value']);
                } elseif ($newOperator == 'LIKE' || $newOperator == 'NOT LIKE') {
                    $query->where($referenceColumn, $newOperator, '%' . $filter['value'] . '%');
                } else {
                    // This can be date
                    $dateOperators = ['before', 'after', 'date_equal', 'days_before', 'days_within'];

                    if (in_array($operator, $dateOperators)) {
                        $query->whereTimestamp($referenceColumn, $newOperator, $filter['value']);
                    } else {
                        $query->where($referenceColumn, $newOperator, $filter['value']);
                    }
                }
            } else {
                $filter['value'] = sanitize_text_field($filter['value']);
                $query->where($referenceColumn, $operator, '%' . $filter['value'] . '%');
            }
        }

        return $query;
    }

    /**
     * Dynamically build relation filter query for the Subscriber
     * model. It handles purchase, lists, tags relations.
     *
     * @param string $relation
     * @param \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder $query
     * @param string $method
     * @param string $subMethod
     * @param string $subField
     * @param array $filter
     * @param string $provider
     * @return \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder
     */
    public static function buildRelationFilterQuery($relation, $query, $method, $subMethod, $subField, $filter, $provider = false)
    {
        if (in_array($filter['operator'], ['in_all', 'not_in_all'])) {
            foreach ($filter['value'] as $item) {
                $query = static::buildRelationFilterQuery($relation, $query, $method, $subMethod, $subField, ['value' => $item, 'operator' => ''], $provider);
            }
        } else {
            $query = $query->{$method}($relation, function ($relationQuery) use ($subMethod, $subField, $filter, $provider) {
                $relationQuery = $relationQuery->{$subMethod}($subField, $filter['value']);

                if ($provider) {
                    $relationQuery = $relationQuery->where('provider', $provider);
                }

                return $relationQuery;
            });
        }

        return $query;
    }

    /**
     * Builds purchase provider related filter query. It handles woo, edd filter now.
     *
     * @param \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder $query
     * @param array $filters
     * @param string $provider
     * @return \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder
     */
    public static function providerQueryBuilder($query, $filters, $provider = 'woo')
    {
        $filters = array_reduce($filters, function ($carry, $filter) {
            if ($filter['property'] == 'purchased_items') {
                $carry['contactRelationsItems'][] = $filter;
            } elseif (in_array($filter['property'], ['purchased_categories', 'purchased_tags', 'commerce_coupons'])) {
                if ($filter['property'] != 'commerce_coupons') {
                    $filter['property'] = 'commerce_taxonomies';
                }
                $carry['contactCommerceIn'][] = $filter;
            } elseif ($filter['property'] == 'commerce_exist') {
                $filter['method'] = 'whereHas';
                if ($filter['operator'] == 'not_exist') {
                    $filter['method'] = 'whereDoesntHave';
                }
                $carry['contactCommerceCheck'][] = $filter;
            } else {
                $carry['contactRelations'][] = $filter;
            }

            return $carry;
        }, []);

        if (array_key_exists('contactRelations', $filters)) {
            $query->whereHas('contact_commerce', function ($contactCommerceQuery) use ($filters, $provider) {
                foreach ($filters['contactRelations'] as $filter) {
                    $filter = static::filterParser($filter);
                    if ($filter['operator'] == 'BETWEEN') {
                        $contactCommerceQuery->whereBetween($filter['property'], $filter['value']);
                    } else {
                        $contactCommerceQuery->where($filter['property'], $filter['operator'], $filter['value']);
                    }
                }

                return $contactCommerceQuery->where('provider', $provider);
            });
        }

        if (array_key_exists('contactRelationsItems', $filters)) {
            foreach ($filters['contactRelationsItems'] as $filter) {
                list($method, $subMethod) = static::parseRelationalFilterQueryMethods($filter);

                $query = static::buildRelationFilterQuery('contact_commerce_items', $query, $method, $subMethod, 'item_id', $filter, $provider);
            }
        }

        if (array_key_exists('contactCommerceIn', $filters)) {
            foreach ($filters['contactCommerceIn'] as $filter) {
                $filter['value'] = (array)$filter['value'];

                $method = in_array($filter['operator'], ['in', 'in_all']) ? 'whereHas' : 'whereDoesntHave';

                if (in_array($filter['operator'], ['in', 'not_in'])) {
                    $query->{$method}('contact_commerce', function ($contactCommerceQuery) use ($filter, $provider) {
                        $contactCommerceQuery
                            ->where('provider', $provider)
                            ->where(function ($query) use ($filter) {
                                $firstVal = array_shift($filter['value']);
                                $operator = 'LIKE';

                                $query->where($filter['property'], $operator, '%' . $firstVal . '%');
                                foreach ($filter['value'] as $value) {
                                    $query->orWhere($filter['property'], $operator, '%' . $value . '%');
                                }
                            });

                    });
                } else {
                    foreach ($filter['value'] as $value) {
                        $query->{$method}('contact_commerce', function ($contactCommerceQuery) use ($filter, $value, $provider) {
                            $contactCommerceQuery
                                ->where('provider', $provider)
                                ->where($filter['property'], 'LIKE', '%' . $value . '%');
                        });
                    }
                }
            }
        }

        if (array_key_exists('contactCommerceCheck', $filters)) {
            foreach ($filters['contactCommerceCheck'] as $filter) {
                $method = $filter['method'];
                $query->{$method}('contact_commerce', function ($q) use ($provider) {
                    $q->where('provider', $provider);
                });
            }
        }

        return $query;
    }

    public function buildSearchableQuery($query, $search, $operator = 'LIKE')
    {
        $fields = $this->searchable;

        $query->where(function ($query) use ($fields, $search, $operator) {
            $query->where(array_shift($fields), $operator, $search);

            $nameArray = explode(' ', $search);

            if (count($nameArray) >= 2) {
                $query->orWhere(function ($q) use ($nameArray, $operator) {
                    $firstName = array_shift($nameArray);
                    $lastName = implode(' ', $nameArray);

                    $q->where('first_name', $operator, $firstName);
                    $q->where('last_name', $operator, $lastName);
                });
            }

            foreach ($fields as $field) {
                $query->orWhere($field, $operator, $search);
            }
        });

        return $query;
    }

    /**
     * @param \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder $query
     * @param array $filters
     * @return \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder
     */
    public function buildGeneralPropertiesFilterQuery($query, $filters)
    {
        foreach ($filters as $filter) {

            $operator = $filter['operator'];
            $searchTerm = $filter['value'];

            if ($filter['operator'] == 'contains') {
                if (is_array($filter['value'])) {
                    continue;
                }
                $operator = 'LIKE';
                $searchTerm = '%' . $filter['value'] . '%';
            } elseif ($filter['operator'] == 'not_contains') {
                if (is_array($filter['value'])) {
                    continue;
                }
                $operator = 'NOT LIKE';
                $searchTerm = '%' . $filter['value'] . '%';
            }

            $dateFields = ['created_at', 'last_activity', 'date_of_birth'];

            if (in_array($filter['property'], $dateFields)) {

                if (empty($filter['value'])) {
                    continue;
                }

                $query = self::applyGeneralFilterQuery($query, $filter, $filter['property']);
            } else if ($filter['property'] == 'search') {
                $query = $this->buildSearchableQuery($query, $searchTerm, $operator);
            } else if ($operator == 'in') {
                if (!is_array($searchTerm)) {
                    $searchTerm = (array)$searchTerm;
                }
                if ($searchTerm) {
                    $query = $query->whereIn($filter['property'], $searchTerm);
                }
            } else if ($operator == 'not_in') {
                if (!is_array($searchTerm)) {
                    $searchTerm = (array)$searchTerm;
                }
                if ($searchTerm) {
                    $query = $query->whereNotIn($filter['property'], $searchTerm);
                }
            } else if ($operator == 'is_null') {
                $query = $query->where(function ($q) use ($filter) {
                    return $q->whereNull($filter['property'])
                        ->orWhere($filter['property'], '=', '');
                });
            } else if ($operator == 'not_null') {
                $query = $query->where(function ($q) use ($filter) {
                    return $q->whereNotNull($filter['property'])
                        ->orWhere($filter['property'], '!=', '');
                });
            } else {
                $query = $query->where($filter['property'], $operator, $searchTerm);
            }
        }
        return $query;
    }

    /**
     * @param array $filter
     * @return string[]
     */
    public static function parseRelationalFilterQueryMethods($filter)
    {
        // default operator = in
        $method = 'whereHas';
        $subMethod = 'whereIn';

        switch ($filter['operator']) {
            case 'not_in':
                $method = 'whereDoesntHave';
                $subMethod = 'whereIn';

                break;
            case 'in_all':
                $method = 'whereHas';
                $subMethod = 'where';

                break;
            case 'not_in_all':
                $method = 'whereDoesntHave';
                $subMethod = 'where';

                break;
        }

        return [$method, $subMethod];
    }

    /**
     * @param \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder $query
     * @param array $filters
     * @return \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder
     */
    public function buildSegmentFilterQuery($query, $filters)
    {
        foreach ($filters as $filter) {
            if (empty($filter['value'])) {
                continue;
            }

            if (in_array($filter['property'], ['tags', 'lists'])) {
                list($method, $subMethod) = static::parseRelationalFilterQueryMethods($filter);
                $query = static::buildRelationFilterQuery($filter['property'], $query, $method, $subMethod, 'object_id', $filter);
            } else {
                $operator = $filter['operator'];
                $method = ($operator == 'in' || $operator == 'contains') ? 'whereIn' : 'whereNotIn';

                $query = $query->{$method}($filter['property'], (array)$filter['value']);
            }
        }

        return $query;
    }

    /**
     * @param \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder $query
     * @param array $filters
     * @return \FluentCrm\Framework\Database\Query\Builder
     */
    public function buildCustomFieldsFilterQuery($query, $filters)
    {

        $filters = array_reduce($filters, function ($carry, $filter) {
            if ($filter['operator'] == 'not_in') {
                $carry['notIn'][] = $filter;
            } else if ($filter['operator'] == '!=' || $filter['operator'] == 'not_contains') {
                $carry['notEqualNorExist'][] = $filter;
            } else {
                $carry['regular'][] = $filter;
            }
            return $carry;
        }, []);


        if (array_key_exists('regular', $filters)) {
            foreach ($filters['regular'] as $filter) {
                $query->whereHas('custom_field_meta', function ($customFieldQuery) use ($filter) {
                    $customFieldQuery->where('key', $filter['property']);
                    $operator = self::parseCustomFieldsFilterOperator($filter);
                    if (is_array($filter['value'])) {
                        $customFieldQuery->where(function ($valueQuery) use ($operator, $filter) {
                            $firstVal = array_shift($filter['value']);

                            $valueQuery->where('value', $operator, '%' . $firstVal . '%');

                            foreach ($filter['value'] as $value) {
                                $valueQuery->orWhere('value', $operator, '%' . $value . '%');
                            }
                        });
                    } else {
                        $customFieldQuery = self::applyGeneralFilterQuery($customFieldQuery, $filter, 'value');
                    }
                    return $customFieldQuery;
                });
            }
        }

        if (array_key_exists('notIn', $filters)) {
            foreach ($filters['notIn'] as $filter) {
                $filter['value'] = (array)$filter['value'];

                foreach ($filter['value'] as $value) {
                    $query->whereDoesntHave('custom_field_meta', function ($customFieldQuery) use ($value, $filter) {
                        $customFieldQuery->where('key', $filter['property'])
                            ->where('value', 'LIKE', '%' . $value . '%');
                    });
                }
            }
        }

        if (array_key_exists('notEqualNorExist', $filters)) {
            foreach ($filters['notEqualNorExist'] as $filter) {
                $value = (string) trim($filter['value']);
                $operator = $filter['operator'];

                if ($operator == 'not_contains') {
                    $operator = 'LIKE';
                    $value = '%' . $value . '%';
                } else {
                    $operator = '=';
                }

                $query->whereDoesntHave('custom_field_meta', function ($customFieldQuery) use ($value, $filter, $operator) {
                    $customFieldQuery->where('key', $filter['property'])
                        ->where('value', $operator, $value);
                });

            }
        }

        return $query;
    }

    public static function parseCustomFieldsFilterOperator($filter)
    {
        $operator = $filter['operator'];

        switch ($filter['operator']) {
            case 'contains':
            case 'in':
                $operator = 'LIKE';
                break;
            case 'not_contains':
            case 'not_in':
                $operator = 'NOT LIKE';
                break;
        }

        return $operator;
    }

    /**
     * @param \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder $query
     * @param array $filters
     * @return \FluentCrm\Framework\Database\Orm\Builder|\FluentCrm\Framework\Database\Query\Builder
     */
    public function buildActivitiesFilterQuery($query, $filters)
    {
        foreach ($filters as $filter) {
            if (empty($filter['value'])) {
                continue;
            }


            $originalValue = $filter['value'];

            $relation = 'campaignEmails';

            $filter['where'] = [
                'prop'  => 'status',
                'value' => 'sent',
                'field' => 'scheduled_at'
            ];

            $filterProp = $filter['property'];

            if ($filterProp == 'campaign_email_activity') {
                $campaignId = (int)$filter['value'];
                $operator = $filter['operator'];

                if ($operator == 'not_in') {
                    $query->whereDoesntHave('campaignEmails', function ($q) use ($campaignId) {
                        $q->where('campaign_id', $campaignId);
                    });
                } else {
                    $query->whereHas('campaignEmails', function ($q) use ($campaignId, $operator) {
                        $q->where('campaign_id', $campaignId);
                        if ($operator == 'clicked') {
                            $q->whereNull('click_counter');
                        } else if ($operator == 'not_clicked') {
                            $q->whereNotNull('click_counter');
                        } else if ($operator == 'open') {
                            $q->where('is_open', 1);
                        } else if ($operator == 'no_open') {
                            $q->where('is_open', '0');
                        }
                    });
                }
                continue;
            } else if ($filterProp == 'automation_activity') {

                $funnelId = (int)$filter['value'];
                $operator = $filter['operator'];

                if ($operator == 'not_in') {
                    $query->whereDoesntHave('funnel_subscribers', function ($q) use ($funnelId) {
                        $q->where('funnel_id', $funnelId);
                    });
                } else {
                    $query->whereHas('funnel_subscribers', function ($q) use ($funnelId, $operator) {
                        $q->where('funnel_id', $funnelId);
                        $statusItems = ['completed', 'active', 'cancelled', 'waiting'];
                        if (in_array($operator, $statusItems)) {
                            $q->where('status', $operator);
                        }
                    });
                }

                continue;
            } else if ($filterProp == 'email_sequence_activity') {

                $sequenceId = (int)$filter['value'];
                $operator = $filter['operator'];

                if ($operator == 'not_in') {
                    $query->whereDoesntHave('sequence_trackers', function ($q) use ($sequenceId) {
                        $q->where('campaign_id', $sequenceId);
                    });
                } else {
                    $query->whereHas('sequence_trackers', function ($q) use ($sequenceId, $operator) {
                        $q->where('campaign_id', $sequenceId);
                        $statusItems = ['completed', 'active', 'cancelled'];
                        if (in_array($operator, $statusItems)) {
                            $q->where('status', $operator);
                        }
                    });
                }

                continue;
            } else if ($filterProp != 'email_sent') {
                $relation = 'urlMetrics';

                $filter['where'] = [
                    'prop'  => 'type',
                    'value' => $filter['property'] == 'email_opened' ? 'open' : 'click',
                    'field' => 'updated_at'
                ];
            }

            $filter = static::filterParser($filter);

            $query->whereHas($relation, function ($campaignEmailQuery) use ($filter, $relation) {
                $campaignEmailQuery->where($filter['where']['prop'], $filter['where']['value']);
                if ($filter['operator'] == 'BETWEEN') {
                    $campaignEmailQuery->whereBetween($filter['where']['field'], $filter['value']);
                } else {
                    $campaignEmailQuery->where($filter['where']['field'], $filter['operator'], $filter['value']);
                }
            });

            $operator = $filter['operator'];
            if ($operator == '<' || $operator == 'LIKE') {
                if ($operator == 'LIKE') {
                    $compareValue = $originalValue . ' 23:59:59';
                } else {
                    $compareValue = $filter['value'] . ' 23:59:59';
                }

                $query->whereDoesntHave($relation, function ($campaignEmailQuery) use ($filter, $compareValue) {
                    $campaignEmailQuery->where($filter['where']['prop'], $filter['where']['value']);
                    $campaignEmailQuery->where($filter['where']['field'], '>', $compareValue);
                });
            }
        }

        return $query;
    }

    public function lastActivityDate($activityName)
    {
        $validNames = ['email_sent', 'email_link_clicked', 'email_opened'];
        if (!in_array($activityName, $validNames)) {
            return false;
        }

        if ($activityName == 'email_sent') {
            $lastEmail = CampaignEmail::where('subscriber_id', $this->id)
                ->where('status', 'sent')
                ->orderBy('scheduled_at', 'DESC')
                ->first();
            if ($lastEmail) {
                return $lastEmail->scheduled_at;
            }
            return false;
        }

        $lastActivity = CampaignUrlMetric::where('subscriber_id', $this->id)
            ->where('type', ($activityName == 'email_link_clicked') ? 'click' : 'open')
            ->orderBy('updated_at', 'DESC')
            ->first();

        if ($lastActivity) {
            return $lastActivity->updated_at;
        }

        return false;
    }

    public function getWpUser()
    {
        if ($this->user_id) {
            return get_user_by('ID', $this->user_id);
        }

        $user = get_user_by('email', $this->email);

        if ($user) {
            $this->user_id = $user->ID;
            $this->save();
        }

        return $user;
    }

    public function getWpUserId()
    {
        if ($this->user_id) {
            return $this->user_id;
        }

        $user = $this->getWpUser();

        if ($user) {
            return $user->ID;
        }

        return false;
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->fillable)) {
                continue;
            }

            if (!array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                !$this->originalIsNumericallyEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function getSecureHash()
    {
        $hash = $this->getMeta('_secure_hash', 'internal');
        if ($hash) {
            return $hash;
        }

        $hash = md5(mt_rand(100, 10000) . '_' . $this->id . '_' . $this->email . '_' . time());

        $hash = str_replace('e', 'd', $hash);

        $this->updateMeta('_secure_hash', $hash, 'internal');

        return $hash;
    }
}
