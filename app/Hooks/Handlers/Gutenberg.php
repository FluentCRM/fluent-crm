<?php

namespace FluentCrm\App\Hooks\Handlers;

class Gutenberg
{
    public function filterBlockTypes($allowedBlocks, $post)
    {
        $postTypes = [
            fluentcrmTemplateCPTSlug(),
            fluentcrmCampaignTemplateCPTSlug()
        ];
        
        if (in_array($post->post_type, $postTypes)) {
            $allowedBlocks = [
                'core/image',
                'core/paragraph',
                'core/heading',
                'core/list',
                
                // 'wp-newsletter-blocks/heading',
                // 'wp-newsletter-blocks/button-block',
                // 'wp-newsletter-blocks/column',
                // 'wp-newsletter-blocks/divider-block',
                // 'wp-newsletter-blocks/image-block',
                // 'wp-newsletter-blocks/menu',
                // 'wp-newsletter-blocks/row-layout',
                // 'wp-newsletter-blocks/social-icon-block',
                // 'wp-newsletter-blocks/spacer-block',
                // 'wp-newsletter-blocks/text'
            ];
        }
     
        return $allowedBlocks;
    }

    public function renderTemplate($content)
    {
        if (!function_exists('\parse_blocks')) {
            return $content;
        }

        $renderedContent = '';
        foreach (\parse_blocks($content) as $block) {
           $renderedContent .= \render_block($block);
        }

        return $renderedContent ? $renderedContent : $content;
    }

    public function rgisterPostType($slug)
    {
        $supports = ['title', 'editor'];

        if ($slug == fluentcrmCampaignTemplateCPTSlug()) {
            unset($supports[0]);
        }

        $args = array(
            'labels'             => [
                'name' => _x('Templates', FLUENTCRM),
                'singular_name'      => _x('Template', FLUENTCRM)
            ],
            // 'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => ['slug' => $slug],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => $supports,
            'show_in_rest'         => true // required to activate gutemberg editor
        );

        register_post_type($slug, $args);
    }

    public function hideAdminNavbarAndTopMenuForCampaignEmailPostType()
    {
        $postTypes = [
            fluentcrmTemplateCPTSlug(),
            fluentcrmCampaignTemplateCPTSlug()
        ];
        
        if (in_array(get_post_type(), $postTypes)) {
            echo '<style type="text/css">
                #adminmenumain, #wpadminbar{display:none}
                #wpcontent, #footer {margin-left:0px} .edit-post-header,.components-notice-list{left:0}
                .components-notice.is-success a.components-button.components-notice__action.is-link{display:none}
                .components-panel .edit-post-post-status{display:none}
                .edit-post-sidebar .components-panel__header ul > li:first-child{display:none}
                .post-publish-panel__postpublish{display:none}
                .editor-post-publish-panel__prepublish .components-panel__body{display:none}
                .edit-post-header .edit-post-header__settings .editor-post-preview{display:none;}
            </style>';
        }
    }

    public static function init()
    {
        $app = FluentCrm();
        
        $instance = new static;

        $instance->rgisterPostType(fluentcrmTemplateCPTSlug());
        
        $instance->rgisterPostType(fluentcrmCampaignTemplateCPTSlug());

        $app->addCustomFilter('gutenberg_content', [$instance, 'renderTemplate']);

        $app->addFilter('allowed_block_types', [$instance, 'filterBlockTypes'], 10, 2);

        $app->addAction('admin_head', [$instance, 'hideAdminNavbarAndTopMenuForCampaignEmailPostType']);
    }
}
