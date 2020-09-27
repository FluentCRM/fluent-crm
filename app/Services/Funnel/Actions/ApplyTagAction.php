<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class ApplyTagAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'add_contact_to_tag';
        $this->priority = 21;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => 'Apply Tag',
            'description' => 'Add this contact to the selected Tags',
            'icon' => fluentCrmMix('images/funnel_icons/apply_tag.svg'),
            'settings'    => [
                'tags' => []
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => 'Apply Tag to the contact',
            'sub_title' => 'Select which tag will be added to the contact',
            'fields'    => [
                'tags' => [
                    'type'        => 'option_selectors',
                    'option_key' => 'tags',
                    'is_multiple' => true,
                    'label'       => 'Select Tags',
                    'placeholder' => 'Select Tag'
                ]
            ]
        ];
    }

    public function handle($subscriber, $sequence, $funnelSubscriberId, $funnelMetric)
    {
        if (empty($sequence->settings['tags']) || !is_array($sequence->settings['tags'])) {
            FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id, 'skipped');
            return;
        }

        $tags = $sequence->settings['tags'];
        $tags = array_combine($tags, array_fill(
            0, count($tags), ['object_type' => 'FluentCrm\App\Models\Tag']
        ));

        $subscriber->tags()->sync($tags, false);
        FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id);
    }
}
