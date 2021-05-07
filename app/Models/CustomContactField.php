<?php

namespace FluentCrm\App\Models;

use FluentCrm\Includes\Helpers\Arr;

class CustomContactField
{
    protected $globalMetaName = 'contact_custom_fields';

    public function getGlobalFields($with = [])
    {
        $data['fields'] = fluentcrm_get_option($this->globalMetaName, []);

        if (in_array('field_types', $with)) {
            $data['field_types'] = $this->getFieldTypes();
        }

        return $data;
    }

    public function getFieldTypes()
    {
        return apply_filters('fluentcrm_global_field_types', [
            'text'          => [
                'type'       => 'text',
                'label'      => 'Single Line Text',
                'value_type' => 'string'
            ],
            'number'        => [
                'type'       => 'number',
                'label'      => 'Numeric Field',
                'value_type' => 'numeric'
            ],
            'single-select' => [
                'type'       => 'select-one',
                'label'      => 'Select choice',
                'value_type' => 'string'
            ],
            'multi-select'  => [
                'type'       => 'select-multi',
                'label'      => 'Multiple Select choice',
                'value_type' => 'array'
            ],
            'radio'         => [
                'type'       => 'radio',
                'label'      => 'Radio Choice',
                'value_type' => 'string'
            ],
            'checkbox'      => [
                'type'       => 'checkbox',
                'label'      => 'Checkboxes',
                'value_type' => 'array'
            ],
            'date'          => [
                'type'       => 'date',
                'label'      => 'Date',
                'value_type' => 'date'
            ],
            'date_time'     => [
                'type'       => 'date_time',
                'label'      => 'Date and Time',
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

            if(in_array($field['slug'], $keys)) {
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
        if(!$values) {
            return $values;
        }
        if(!$fields) {
            $rawFields = fluentcrm_get_option($this->globalMetaName, []);
            foreach ($rawFields as $field) {
                $fields[$field['slug']] = $field;
            }
        }

        foreach ($values as $valueKey => $value) {
            if(!is_array($value) && Arr::get($fields, $valueKey.'.type') == 'checkbox') {
                $itemValues = explode(',', $value);
                $trimmedvalues = [];
                foreach ($itemValues as $itemValue) {
                    $trimmedvalues[] = trim($itemValue);
                }
                if($itemValue) {
                    $values[$valueKey] = $trimmedvalues;
                }
            }
        }

        return array_filter($values);
    }
}
