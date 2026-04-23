<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Models\Model;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\Framework\Support\Arr;

class Company extends Model
{
    protected $table = 'fc_companies';

    protected $guarded = ['id'];

    protected $fillable = [
        'hash',
        'name',
        'owner_id',
        'industry',
        'type',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'city',
        'state',
        'country',
        'timezone',
        'employees_number',
        'description',
        'logo',
        'linkedin_url',
        'facebook_url',
        'twitter_url',
        'meta',
        'website',
        'date_of_start',
        'created_at',
        'updated_at'
    ];

    /**
     * Get subscriber mappable fields.
     *
     * @return array
     */
    public static function mappables()
    {
        return [
            'name'             => __('Company Name *', 'fluent-crm'),
            'owner_email'      => __('Owner Email', 'fluent-crm'),
            'owner_name'       => __('Owner Name', 'fluent-crm'),
            'industry'         => __('Industry', 'fluent-crm'),
            'description'      => __('Company Description', 'fluent-crm'),
            'logo'             => __('Company Logo URL', 'fluent-crm'),
            'type'             => __('Type', 'fluent-crm'),
            'email'            => __('Company Email', 'fluent-crm'),
            'phone'            => __('Company Phone', 'fluent-crm'),
            'address_line_1'   => __('Address Line 1', 'fluent-crm'),
            'address_line_2'   => __('Address Line 2', 'fluent-crm'),
            'postal_code'      => __('Postal Code', 'fluent-crm'),
            'city'             => __('City', 'fluent-crm'),
            'state'            => __('State', 'fluent-crm'),
            'country'          => __('Country', 'fluent-crm'),
            'employees_number' => __('Employees Number', 'fluent-crm'),
            'linkedin_url'     => __('LinkedIn URL', 'fluent-crm'),
            'facebook_url'     => __('Facebook URL', 'fluent-crm'),
            'twitter_url'      => __('Twitter URL', 'fluent-crm'),
            'website'          => __('Website URL', 'fluent-crm')
        ];
    }

    protected $searchable = [
        'name',
        'phone',
        'description',
        'email'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->hash = md5(wp_generate_uuid4() . '_' . time() . '_' . mt_rand(1000, 9999));
        });
    }

    /**
     * Local scope to filter companies by search/query string
     */
    public function scopeSearchBy($query, $search)
    {
        if ($search) {
            $fields = $this->searchable;
            $query->where(function ($query) use ($fields, $search) {
                $query->where(array_shift($fields), 'LIKE', "%$search%");
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', "%$search%");
                }
            });
        }

        return $query;
    }

    public function scopeOfType($query, $status)
    {
        return $query->where('type', $status);
    }

    public function scopeOfIndustry($query, $status)
    {
        return $query->where('industry', $status);
    }

    /**
     * Get all of the subscribers that belongs to the company.
     *
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsToMany
     */
    public function subscribers()
    {
        return $this->belongsToMany(
            __NAMESPACE__ . '\Subscriber', 'fc_subscriber_pivot', 'object_id', 'subscriber_id'
        )->where('object_type', __CLASS__);
    }

    public function owner()
    {
        return $this->belongsTo(Subscriber::class, 'owner_id', 'id');
    }

    public function getContactsCount()
    {
        return $this->subscribers()->count();
    }

    /**
     * A Company has many notes and activities.
     *
     * @return \FluentCrm\Framework\Database\Orm\Relations\HasMany
     */
    public function notes()
    {
        return $this->hasMany(CompanyNote::class, 'subscriber_id', 'id');
    }

    public function setMetaAttribute($meta)
    {
        $this->attributes['meta'] = \maybe_serialize($meta);
    }

    public function getMetaAttribute($meta)
    {
        $metaData = \maybe_unserialize($meta);

        if (!$metaData) {
            return [
                'custom_values' => []
            ];
        }

        $metaDefaults = [
            'custom_values' => []
        ];

        return array_merge($metaDefaults, $metaData);
    }

    public function getCustomValues()
    {
        return Arr::get($this->meta, 'custom_values', []);
    }

}
