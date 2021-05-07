<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Tag;
use FluentCrm\Includes\Helpers\Arr;

class Integrations
{
    public function register()
    {
        if (defined('FLUENTFORM')) {
            (new \FluentCrm\App\Services\ExternalIntegrations\FluentForm\FluentFormInit())->init();
        }

        /*
         * Oxygen Edito Integration
         */
        if (defined('CT_VERSION')) {
            require_once FLUENTCRM_PLUGIN_PATH.'app/Services/ExternalIntegrations/Oxygen/oxy_init.php';
        }

        $this->registerBlockEditorBlocks();
    }

    public function registerBlockEditorBlocks()
    {
        wp_register_script(
            'fluentcrm-blocks-block-editor',
            fluentCrmMix('admin/js/conditional_block.js'),
            ['wp-blocks', 'wp-element', 'wp-polyfill'],
            FLUENTCRM_PLUGIN_VERSION
        );

        wp_localize_script('fluentcrm-blocks-block-editor', 'fluenctm_block_vars', [
            'available_tags' => Tag::get()
        ]);

        wp_register_style(
            'fluentcrm-blocks-block-editor',
            fluentCrmMix('admin/css/conditional_block.css'),
            array(),
            FLUENTCRM_PLUGIN_VERSION
        );

        register_block_type(
            'fluent-crm/conditional-content',
            array(
                'editor_script'   => 'fluentcrm-blocks-block-editor',
                'editor_style'    => 'fluentcrm-blocks-block-editor',
                'attributes'      => array(
                    'condition_type' => [
                        'type'    => 'string',
                        'default' => 'show_if_tag_exist'
                    ],
                    'tag_ids'        => [
                        'type'    => 'array',
                        'default' => []
                    ]
                ),
                'render_callback' => function ($data, $content) {
                    $checkType = Arr::get($data, 'condition_type', 'show_if_tag_exist');
                    if ($checkType == 'show_if_logged_in') {
                        if (get_current_user_id()) {
                            return $content;
                        }
                        return '';
                    }

                    if ($checkType == 'show_if_public_users') {
                        if (!get_current_user_id()) {
                            return $content;
                        }
                        return '';
                    }

                    $tagIds = Arr::get($data, 'tag_ids', []);
                    if (!$tagIds) {
                        return '';
                    }

                    $subscriber = fluentcrm_get_current_contact();
                    if ($checkType == 'show_if_tag_not_exist') {
                        if (!$subscriber) {
                            return $content;
                        }
                        $tagMatched = $subscriber->hasAnyTagId($tagIds);
                        if ($tagMatched) {
                            return '';
                        };

                        return $content;
                    }

                    if (!$subscriber) {
                        return '';
                    }

                    $tagMatched = $subscriber->hasAnyTagId($tagIds);

                    if ($checkType == 'show_if_tag_exist') {
                        if ($tagMatched) {
                            return $content;
                        };
                        return '';
                    }
                }
            )
        );
    }
}
