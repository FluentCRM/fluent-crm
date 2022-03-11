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
}
