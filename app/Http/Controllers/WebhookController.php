<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\Includes\Request\Request;
use FluentCrm\App\Models\Webhook;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Tag;

class WebhookController extends Controller
{
    public function index(Webhook $webhook)
    {
        $fields = $webhook->getFields();
        
        return [
            'webhooks' => $webhook->latest()->get(),
            'fields' => $fields['fields'],
            'custom_fields' => $fields['custom_fields'],
            'schema' => $webhook->getSchema(),
            'lists' => Lists::get(),
            'tags' => Tag::get()
        ];
    }

    public function create(Request $request, Webhook $webhook)
    {
        $webhook = $webhook->store(
            $this->validate(
                $request->all(),
                ['name' => 'required', 'status' => 'required']
            )
        );

        return [
            'id' => $webhook->id,
            'webhooks' => $webhook->latest()->get()
        ];
    }

    public function update(Request $request, Webhook $webhook, $id)
    {
        $webhook->find($id)->saveChanges($request->all());

        return [
            'webhooks' => $webhook->latest()->get()
        ];
    }


    public function delete(Webhook $webhook, $id)
    {
       $webhook->where('id', $id)->delete();

       return [
            'webhooks' => $webhook->latest()->get()
       ];
    }
}
