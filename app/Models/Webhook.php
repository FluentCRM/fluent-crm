<?php

namespace FluentCrm\App\Models;

use FluentCrm\Framework\Support\Arr;

/**
 *  Webhook Model - DB Model for Webhooks
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */

class Webhook extends Meta
{
    protected $fillable = [
        'id',
        'key',
        'value',
        'object_type'
    ];

    public static function boot()
    {
        static::addGlobalScope('type', function ($builder) {
            $builder->where('object_type', '=', 'webhook');
        });
    }

    public function getFields()
    {
        $contactFields = [
            'fields' => [],
            'custom_fields' => []
        ];

        foreach (Subscriber::mappables() as $key => $column) {
            $contactFields['fields'][] = ['key' => $key, 'field' => $column];
        }

        foreach ((new CustomContactField)->getGlobalFields()['fields'] as $field) {
            $contactFields['custom_fields'][] = ['key' => $field['slug'], 'field' => $field['label']];
        }

        return $contactFields;
    }

    public function getSchema()
    {
        return [
            'name' => '',
            'lists' => [],
            'tags' => [],
            'url' => '',
            'status' => ''
        ];
    }

    public function store($data)
    {
        return static::create([
            'object_type' => 'webhook',
            'key' => $key = wp_generate_uuid4(),
            'value' => array_merge($data, [
                'url' => admin_url("?fluentcrm=1&route=contact&hash={$key}")
            ]),
        ]);
    }

    public function saveChanges($data)
    {
        $data['tags'] = Arr::get($data, 'tags', []);
        $data['lists'] = Arr::get($data, 'lists', []);

        $this->value = array_merge(
            $this->value,
            array_diff_key($data, [
                'id' => '', 'url' => ''
            ])
        );

        $this->save();

        return $this;
    }
}
