<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Subscriber;

class CustomContactFieldsController extends Controller
{
    protected $globalMetaName = 'contact_custom_fields';

    public function getGlobalFields()
    {
        $data['fields'] = fluentcrm_get_option($this->globalMetaName, []);
        $with = $this->request->get('with', []);

        if (in_array('field_types', $with)) {
            $data['field_types'] = $this->getFieldTypes();
        }

        return $this->sendSuccess($data);
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

    public function saveGlobalFields()
    {
        $fields = \json_decode($this->request->get('fields'), true);
        $slugs = [];
        foreach ($fields as $field) {
            if (isset($field['slug'])) {
                $slugs[] = $field['slug'];
            }
        }

        $formattedFields = [];
        foreach ($fields as $field) {
            if (empty($field['slug'])) {
                $field['slug'] = $this->generateSlug($field, $slugs);
            }
            $formattedFields[] = $field;
        }

        fluentcrm_update_option($this->globalMetaName, $formattedFields);

        return $this->sendSuccess([
            'fields'  => $formattedFields,
            'message' => 'Fields successfully saved'
        ]);
    }

    private function generateSlug($field, $slugs)
    {
        $label = str_replace(' ', '_', $field['label']);
        $label = sanitize_title($label, 'custom_field', 'view');
        $label = substr($label, 0, 16);
        $originalLabel = $label;

        if (is_numeric($label)) {
            $label = 'cf_' . $label;
        }

        $mainColumns = (new Subscriber)->getFillable();
        $mainColumns[] = 'id';
        $mainColumns[] = 'updated_at';

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
}
