<?php

namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\Lists as CrmLists;

class Lists
{
    private $instance = null;

    private $allowedInstanceMethods = [
        'all',
        'get',
        'find',
        'first',
        'paginate'
    ];

    public function __construct(CrmLists $instance)
    {
        $this->instance = $instance;
    }

    public function getInstance()
    {
        return $this->instance;
    }

    public function importBulk($lists)
    {
        $newLists = [];
        foreach ($lists as $list) {
            if (!$list['title']) {
                continue;
            }

            if(empty($list['slug'])) {
                $list['slug'] = sanitize_title($list['title'], 'display');
            } else {
                $list['slug'] = sanitize_title($list['slug'], 'display');
            }

            $list['slug']  = sanitize_text_field($list['slug']);

            $list = \FluentCrm\App\Models\Lists::updateOrCreate(
                [
                    'slug' => $list['slug'],
                    'title' => sanitize_text_field($list['title'])
                ],
                ['slug' => $list['slug']]
            );
            do_action('fluentcrm_list_created', $list->id);

            $newLists[] = $list;
        }

        return $newLists;
    }

    public function __call($method, $params)
    {
        if (in_array($method, $this->allowedInstanceMethods)) {
            return call_user_func_array([$this->instance, $method], $params);
        }

        throw new \Exception("Method {$method} does not exist.");
    }
}
