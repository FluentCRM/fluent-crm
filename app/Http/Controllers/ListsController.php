<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Request\Request;

class ListsController extends Controller
{
    /**
     * Get all of the lists
     *
     * @param \FluentCrm\Includes\Request\Request $request
     * @return \WP_REST_Response
     */
    public function index(Request $request)
    {
        $with = $request->get('with', []);

        $order = [
            'by'    => $request->get('sort_by', 'id'),
            'order' => $request->get('sort_order', 'DESC')
        ];
        $lists = Lists::orderBy($order['by'], $order['order'])->get()->each(function ($list) {
            $list->totalCount = $list->totalCount();
            $list->subscribersCount = $list->countByStatus('subscribed');
        });

        return $this->send([
            'lists' => $lists
        ]);
    }

    /**
     * Find a list.
     *
     * @param \FluentCrm\Includes\Request\Request $request
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
     * @param \FluentCrm\Includes\Request\Request $request
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
            'title' => $allData['title'],
            'slug'  => sanitize_title($data['slug'], 'display'),
            'description' => sanitize_text_field(Arr::get($allData, 'description'))
        ]);

        do_action('fluentcrm_list_created', $list->id);

        return $this->send([
            'lists'   => $list,
            'message' => 'Successfully saved the list.'
        ]);
    }


    /**
     * Store a list.
     *
     * @param \FluentCrm\Includes\Request\Request $request
     * @param $id int
     * @return \WP_REST_Response
     */
    public function update(Request $request, $id)
    {
        $allData = $this->validate($request->all(), [
            'title' => 'required'
        ]);

        $list = Lists::where('id', $id)->update([
            'title' => $allData['title'],
            'description' => sanitize_text_field($allData['description']),
        ]);

        do_action('fluentcrm_list_updated', $id);

        return $this->send([
            'lists'   => $list,
            'message' => 'Successfully saved the list.'
        ]);
    }

    /**
     * Bulk store lists.
     *
     * @param \FluentCrm\Includes\Request\Request $request
     * @return \WP_REST_Response
     */
    public function storeBulk(Request $request)
    {
        foreach ($request->get('lists') as $list) {
            if (!$list['title'] || !$list['slug']) {
                continue;
            }

            $list = Lists::updateOrCreate(
                ['slug' => sanitize_title($list['title'], 'display')],
                ['title' => sanitize_text_field($list['title'])]
            );
            do_action('fluentcrm_list_created', $list->id);
        }

        return $this->sendSuccess([
            'message' => 'Provided Lists have been successfully created'
        ]);
    }

    /**
     * Delete a list
     *
     * @param \FluentCrm\Includes\Request\Request $request
     * @param int $id
     * @return \WP_REST_Response
     */
    public function remove(Request $request, $id)
    {
        Lists::where('id', $id)->delete();
        do_action('fc_list_deleted', $id);
        return $this->send([
            'message' => 'Successfully removed the list.'
        ]);
    }
}
