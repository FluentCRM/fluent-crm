<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\Tag;

class Tags
{
    private $instance = null;

    private $allowedInstanceMethods = [
        'all',
        'get',
        'find',
        'first',
        'paginate'
    ];


    public function importBulk($tags)
    {
        $newTags = [];
        foreach ($tags as $tag) {
            if (!$tag['title']) {
                continue;
            }

            if(empty($tag['slug'])) {
                $tag['slug'] = sanitize_title($tag['title'], 'display');
            } else {
                $tag['slug'] = sanitize_title($tag['slug'], 'display');
            }

            $tag['slug']  = sanitize_text_field($tag['slug']);

            $tag = \FluentCrm\App\Models\Tag::updateOrCreate(
                [
                    'slug' => $tag['slug'],
                    'title' => sanitize_text_field($tag['title'])
                ],
                ['slug' => $tag['slug']]
            );
            do_action('fluentcrm_list_created', $tag->id);

            $newTags[] = $tag;
        }

        return $newTags;
    }


    public function __construct(Tag $instance)
    {
        $this->instance = $instance;
    }

    public function getInstance()
    {
        return $this->instance;
    }

    public function __call($method, $params)
    {
        if (in_array($method, $this->allowedInstanceMethods)) {
            return call_user_func_array([$this->instance, $method], $params);
        }

        throw new \Exception("Method {$method} does not exist.");
    }
}
