<?php

namespace FluentCrm\App\Http\Requests;

use FluentCrm\Includes\Core\RequestGuard;

/**
 * This is an example of request validation
 */
class StoreListRequest extends RequestGuard
{
    public function rules()
    {
        $id = $this->id;

        return [
            'title' => 'required',
            'slug'  => "required|unique:fc_lists,slug,${id}"
        ];
    }

    public function messages()
    {
        return [];
    }
}
