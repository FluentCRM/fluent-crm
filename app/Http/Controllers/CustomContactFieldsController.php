<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\CustomContactField;

/**
 *  CustomContactFieldsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class CustomContactFieldsController extends Controller
{
    public function getGlobalFields(CustomContactField $model)
    {
        return $this->sendSuccess(
            $model->getGlobalFields(
                $this->request->get('with', [])
            )
        );
    }

    public function saveGlobalFields(CustomContactField $model)
    {
        $fields = $model->saveGlobalFields(
            $this->request->getJson('fields')
        );

        return $this->sendSuccess([
            'fields'  => $fields,
            'message' => __('Fields saved successfully!', 'fluent-crm')
        ]);
    }

    public function updateGroupName(CustomContactField $model)
    {
        $oldName = sanitize_text_field($this->request->get('old_name'));
        $newName = sanitize_text_field($this->request->get('new_name'));
        $updatedCustomFields = $model->updateGroupName($oldName, $newName);

        return $this->sendSuccess([
            'fields'   => $updatedCustomFields,
            'message' => __('Group name updated successfully!', 'fluent-crm')
        ]);
    }
}
