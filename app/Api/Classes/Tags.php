<?php
/**
 * Contact Tags Class - PHP APi Wrapper
 *
 * Contacts Tags API Wrapper Class that can be used as fluentCrmApi('tags') to get the class instance
 *
 * @package FluentCrm\App\Api\Classes
 *
 * @version 1.0.0
 */

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\Tag;
use FluentCrm\Framework\Support\Arr;

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

            if (empty($tag['slug'])) {
                $tag['slug'] = sanitize_title($tag['title'], 'display');
            } else {
                $tag['slug'] = sanitize_title($tag['slug'], 'display');
            }

            $tag['slug'] = sanitize_text_field($tag['slug']);

            $tag = \FluentCrm\App\Models\Tag::updateOrCreate(
                array_filter([
                    'slug'        => $tag['slug'],
                    'title'       => sanitize_text_field($tag['title']),
                    'description' => sanitize_textarea_field(Arr::get($tag, 'description'))
                ]),
                ['slug' => $tag['slug']]
            );

            if ($tag->wasRecentlyCreated) {
                do_action('fluentcrm_tag_created', $tag->id);
                do_action('fluent_crm/tag_created', $tag);
            } else {
                do_action('fluentcrm_tag_updated', $tag->id);
                do_action('fluent_crm/tag_updated', $tag);
            }

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
