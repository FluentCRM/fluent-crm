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
            'by'    => $request->getSafe('sort_by', 'id', 'sanitize_sql_orderby'),
            'order' => $request->getSafe('sort_order', 'DESC', 'sanitize_sql_orderby')
        ];
        $lists = Lists::orderBy($order['by'], $order['order'])
            ->searchBy($request->getSafe('search'))
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

        if (empty($allData['slug'])) {
            if ($allData['title']) {
                $data['slug'] = sanitize_text_field($allData['title']);
            }
        }

        $data = $this->validate($allData, [
            'title' => 'required',
            'slug'  => "required|unique:fc_lists,slug"
        ]);

        $list = Lists::create([
            'title'       => sanitize_text_field($allData['title']),
            'slug'        => sanitize_title($data['slug'], 'display'),
            'description' => sanitize_textarea_field(Arr::get($allData, 'description'))
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

        if(!empty($allData['slug'])) {
            $allData['slug'] = sanitize_title($allData['slug'], 'display');
        }

        if ($id == 0 && $request->get('update_by') == 'slug' && !empty($allData['slug'])) {

            $list = Lists::where('slug', $allData['slug'])->first();
            if (!$list) {
                return $this->sendError([
                    'message' => 'List could not be found'
                ]);
            }

            $id = $list->id;
        } else {
            $list = Lists::findOrFail($id);
            if(empty($allData['slug'])) {
                $allData['slug'] = $list->slug;
            }
        }

        if (Lists::where('slug', $allData['slug'])->where('id', '!=', $id)->first()) {
            return $this->sendError([
                'message' => 'Provided slug already exist in another list'
            ]);
        }

        $list = Lists::where('id', $id)->update([
            'title'       => sanitize_text_field($allData['title']),
            'slug'        => $allData['slug'],
            'description' => sanitize_textarea_field(Arr::get($allData, 'description')),
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
                $list['slug'] = sanitize_text_field($list['title']);
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

    public function handleBulkAction(Request $request)
    {
        $listIds = $request->getSafe('listIds', [], 'intval');
        $listIds = array_filter($listIds);

        foreach ($listIds as $listId) {
            Lists::where('id', $listId)->delete();
            do_action('fc_list_deleted', $listId);
        }

        return $this->sendSuccess([
            'message' => __('Selected Lists has been removed permanently', 'fluent-crm'),
        ]);

    }
}
