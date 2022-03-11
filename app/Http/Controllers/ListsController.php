<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  ListsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class ListsController extends Controller
{
    /**
     * Get all of the lists
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response
     */
    public function index(Request $request)
    {
        $with = $request->get('with', []);

        $order = [
            'by'    => $request->get('sort_by', 'id'),
            'order' => $request->get('sort_order', 'DESC')
        ];
        $lists = Lists::orderBy($order['by'], $order['order'])
            ->searchBy($request->get('search'))
            ->get();

        if (!$request->get('exclude_counts')) {
            foreach ($lists as $list) {
                $list->totalCount = $list->totalCount();
                $list->subscribersCount = $list->countByStatus('subscribed');
            }
        }

        return $this->send([
            'lists' => $lists
        ]);
    }

    /**
     * Find a list.
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @param int $id
     * @return \WP_REST_Response
     */
    public function find(Request $request, $id)
    {
        return $this->send(Lists::find($id));
    }


    /**
     * Store a list.
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response
     */
    public function create(Request $request)
    {
        $allData = $request->all();

        if (empty($data['slug'])) {
            if ($allData['title']) {
                $data['slug'] = $allData['title'];
            }
        }

        $data = $this->validate($allData, [
            'title' => 'required',
            'slug'  => "required|unique:fc_lists,slug"
        ]);

        $list = Lists::create([
            'title'       => $allData['title'],
            'slug'        => sanitize_title($data['slug'], 'display'),
            'description' => sanitize_text_field(Arr::get($allData, 'description'))
        ]);

        do_action('fluentcrm_list_created', $list->id);

        return $this->send([
            'lists'   => $list,
            'message' => __('Successfully saved the list.', 'fluent-crm')
        ]);
    }


    /**
     * Store a list.
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @param $id int
     * @return \WP_REST_Response
     */
    public function update(Request $request, $id)
    {
        $allData = $this->validate($request->all(), [
            'title' => 'required'
        ]);

        $list = Lists::where('id', $id)->update([
            'title'       => $allData['title'],
            'description' => sanitize_text_field($allData['description']),
        ]);

        do_action('fluentcrm_list_updated', $id);

        return $this->send([
            'lists'   => $list,
            'message' => __('Successfully saved the list.', 'fluent-crm'),
        ]);
    }

    /**
     * Bulk store lists.
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response
     */
    public function storeBulk(Request $request)
    {
        $lists = $request->get('lists', []);
        if (empty($lists)) {
            $lists = $this->request->get('items', []);
        }

        $createdIds = [];
        foreach ($lists as $list) {
            if (empty($list['title'])) {
                continue;
            }

            if (empty($list['slug'])) {
                $list['slug'] = $list['title'];
            }

            $list = Lists::updateOrCreate(
                ['slug' => sanitize_title($list['slug'], 'display')],
                ['title' => sanitize_text_field($list['title'])]
            );

            $createdIds[] = $list->id;

            do_action('fluentcrm_list_created', $list->id);
        }

        return $this->sendSuccess([
            'message' => __('Provided Lists have been successfully created', 'fluent-crm'),
            'ids'     => $createdIds
        ]);
    }

    /**
     * Delete a list
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @param int $id
     * @return \WP_REST_Response
     */
    public function remove(Request $request, $id)
    {
        Lists::where('id', $id)->delete();
        do_action('fc_list_deleted', $id);
        return $this->send([
            'message' => __('Successfully removed the list.', 'fluent-crm')
        ]);
    }
}
