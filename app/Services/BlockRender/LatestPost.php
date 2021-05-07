<?php

namespace FluentCrm\App\Services\BlockRender;

class LatestPost
{
    private static $postsCache = [];

    public static function getLatestPostHtml($attributes)
    {
        $postArgs = self::getQueryArgs($attributes);

        $posts = self::getLatestPosts($postArgs);
        if(!$posts) {
            return;
        }

        $block_core_latest_posts_excerpt_length = $attributes['excerptLength'];
        add_filter( 'excerpt_length', 'block_core_latest_posts_get_excerpt_length', 20 );
    }

    private static function getLatestPosts($args)
    {
        $argsSign = maybe_serialize($args);

        if(isset(static::$postsCache[$argsSign])) {
            return static::$postsCache[$argsSign];
        }

        static::$postsCache[$argsSign] = get_posts( $args );

        return static::$postsCache[$argsSign];

    }

    private static function getQueryArgs($attributes)
    {
        $args = array(
            'posts_per_page'   => $attributes['postsToShow'],
            'post_status'      => 'publish',
            'order'            => $attributes['order'],
            'orderby'          => $attributes['orderBy'],
            'suppress_filters' => false,
        );

        if ( isset( $attributes['categories'] ) ) {
            $args['category__in'] = array_column( $attributes['categories'], 'id' );
        }
        if ( isset( $attributes['selectedAuthor'] ) ) {
            $args['author'] = $attributes['selectedAuthor'];
        }

        return $args;
    }
}
