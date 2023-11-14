<?php

namespace FluentCrm\App\Models;

use FluentCrm\Framework\Support\Arr;

/**
 *  CustomContactField Model - DB Model for Custom Contact Fields
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
class CustomContactField
{
    protected $globalMetaName = 'contact_custom_fields';

    public function getGlobalFields($with = [])
    {
        $data['fields'] = fluentcrm_get_option($this->globalMetaName, []);

        if (in_array('field_types', $with)) {
            $data['field_types'] = $this->getFieldTypes();
        }

        if (in_array('field_groups', $with)) {
            $data['field_groups'] = $this->getFieldGroups();
        }

        return $data;
    }

    public function getFieldTypes()
    {
        return apply_filters('fluent_crm/global_field_types', [
            'text'          => [
                'type'       => 'text',
                'label'      => __('Single Line Text', 'fluent-crm'),
                'value_type' => 'string'
            ],
            'textarea'      => [
                'type'       => 'textarea',
                'label'      => __('Multi Line Text', 'fluent-crm'),
                'value_type' => 'string'
            ],
            'number'        => [
                'type'       => 'number',
                'label'      => __('Numeric Field', 'fluent-crm'),
                'value_type' => 'numeric'
            ],
            'single-select' => [
                'type'       => 'select-one',
                'label'      => __('Select choice', 'fluent-crm'),
                'value_type' => 'string'
            ],
            'multi-select'  => [
                'type'       => 'select-multi',
                'label'      => __('Multiple Select choice', 'fluent-crm'),
                'value_type' => 'array'
            ],
            'radio'         => [
                'type'       => 'radio',
                'label'      => __('Radio Choice', 'fluent-crm'),
                'value_type' => 'string'
            ],
            'checkbox'      => [
                'type'       => 'checkbox',
                'label'      => __('Checkboxes', 'fluent-crm'),
                'value_type' => 'array'
            ],
            'date'          => [
                'type'       => 'date',
                'label'      => __('Date', 'fluent-crm'),
                'value_type' => 'date'
            ],
            'date_time'     => [
                'type'       => 'date_time',
                'label'      => __('Date and Time', 'fluent-crm'),
                'value_type' => 'datetime'
            ]
        ]);
    }

    public function saveGlobalFields($fields)
    {
        $slugs = [];

        foreach ($fields as $field) {
            if (isset($field['slug'])) {
                $slugs[] = $field['slug'];
            }
        }

        $formattedFields = [];

        $keys = [];
        foreach ($fields as $field) {

            if (empty($field['slug'])) {
                $field['slug'] = $this->generateSlug($field, $slugs);
            }

            if (in_array($field['slug'], $keys)) {
                continue;
            }

            $keys[] = $field['slug'];

            $formattedFields[] = $field;
        }


        fluentcrm_update_option($this->globalMetaName, $formattedFields);

        return $formattedFields;
    }

    protected function generateSlug($field, $slugs)
    {
        $label = str_replace(' ', '_', $field['label']);
        $label = sanitize_title($label, 'custom_field', 'view');
        $label = substr($label, 0, 25);
        $originalLabel = $label;

        if (is_numeric($label)) {
            $label = 'cf_' . $label;
        }

        $mainColumns = array_merge(
            (new Subscriber)->getFillable(),
            ['id', 'updated_at']
        );

        if (in_array($label, $mainColumns)) {
            $label = 'cf_' . $label;
        }

        $index = 1;

        while (in_array($label, $slugs)) {
            $label = $originalLabel . '_' . $index;
            $index++;
        }

        return $label;
    }

    public function formatCustomFieldValues($values, $fields = [])
    {
        if (!$values) {
            return $values;
        }
        if (!$fields) {
            $rawFields = fluentcrm_get_option($this->globalMetaName, []);
            foreach ($rawFields as $field) {
                $fields[$field['slug']] = $field;
            }
        }

        foreach ($values as $valueKey => $value) {

            $isArrayType = Arr::get($fields, $valueKey . '.type') == 'checkbox' || Arr::get($fields, $valueKey . '.type') == 'select-multi';

            if (!is_array($value) && $isArrayType) {
                $itemValues = explode(',', $value);
                $trimmedvalues = [];
                foreach ($itemValues as $itemValue) {
                    $trimmedvalues[] = trim($itemValue);
                }
                if ($itemValue) {
                    $values[$valueKey] = $trimmedvalues;
                }
            }
        }

        return array_filter($values, function ($item) {
            return $item != null;
        });
    }

    public function getFieldGroups()
    {
        $fieldGroups = fluentcrm_get_option('contact_field_groups');

        if (!$fieldGroups) {
            $fieldGroups = [
                [
                    'slug'  => 'default',
                    'title' => __('Custom Profile Data', 'fluent-crm')
                ]
            ];
        }
        return $fieldGroups;
    }
}
