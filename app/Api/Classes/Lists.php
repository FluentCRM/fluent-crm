<?php
namespace FluentCrm\App\Api\Classes;

use FluentCrm\App\Models\Lists as CrmLists;
use FluentCrm\Framework\Support\Arr;

/**
 * Contacts List Class - PHP APi Wrapper
 *
 * Contacts API Wrapper Class that can be used as <code>FluentCrmApi('lists')</code> to get the class instance.
 * This will contain all the methods of \FluentCrm\App\Models\Lists model.
 *
 * @package FluentCrm\App\Api\Classes
 *
 * @version 1.0.0
 */

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

    /**
     * Add Lists as Bulk
     *
     * Use As: <code>FluentCrmApi('lists')->addBulk($lists)</code>
     *
     * @param array $lists Array of Lists with title, slug etc
     * @return array of List Objects
     */
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
                array_filter([
                    'slug' => $list['slug'],
                    'title' => sanitize_text_field($list['title']),
                    'description' => sanitize_textarea_field(Arr::get($list, 'description'))
                ]),
                ['slug' => $list['slug']]
            );

            if($list->wasRecentlyCreated) {
                do_action('fluentcrm_list_created', $list->id);
                do_action('fluent_crm/list_created', $list);
            } else {
                do_action('fluentcrm_list_updated', $list->id);
                do_action('fluent_crm/list_updated', $list);
            }

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
