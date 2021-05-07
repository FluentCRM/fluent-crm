<?php

namespace FluentCrm\App\Services\ExternalIntegrations\Oxygen;

use FluentCrm\App\Models\Tag;

class ConditionBuilder
{
    public function init()
    {
        $tags = Tag::get();
        $formattedTags = [];
        foreach ($tags as $tag) {
            $formattedTags[] = $tag->slug;
        }

        oxygen_vsb_register_condition(
            'FluentCRM - Contact Tag',
            array(
                'options' => $formattedTags,
                'custom'  => false
            ),
            array('exist', 'not exist'),
            'fcrmOxyCheckTagCondition',
            'FluentCRM'
        );

        oxygen_vsb_register_condition(
            'FluentCRM - Contact Status',
            array(
                'options' => ['subscribed', 'pending', 'unsubscribed'],
                'custom'  => false
            ),
            array('=', '!='),
            'fcrmOxyCheckStatusCondition',
            'FluentCRM'
        );

        oxygen_vsb_register_condition(
            'FluentCRM - Contact Exist',
            array(
                'options' => ['yes', 'no'],
                'custom'  => false
            ),
            array('='),
            'fcrmOxyCheckContactExistCondition',
            'FluentCRM'
        );

    }
}
