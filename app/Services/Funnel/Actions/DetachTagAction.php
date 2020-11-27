<?php

namespace FluentCrm\App\Services\Funnel\Actions;

use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;

class DetachTagAction extends BaseAction
{
    public function __construct()
    {
        $this->actionName = 'detach_contact_from_tag';
        $this->priority = 23;
        parent::__construct();
    }

    public function getBlock()
    {
        return [
            'title'       => 'Remove From Tag',
            'description' => 'Remove this contact from the selected Tags',
            'icon' => fluentCrmMix('images/funnel_icons/tag_remove.svg'),
            'settings'    => [
                'tags' => []
            ]
        ];
    }

    public function getBlockFields()
    {
        return [
            'title'     => 'Remove Contact from the Selected Tags',
            'sub_title' => 'Select Tags that you want to remove from targeted Contact',
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

        $renewedSubscriber = Subscriber::where('id', $subscriber->id)->first();
        $renewedSubscriber->detachTags($tags);
        
        FunnelHelper::changeFunnelSubSequenceStatus($funnelSubscriberId, $sequence->id);
    }
}
