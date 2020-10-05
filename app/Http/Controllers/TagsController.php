<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Tag;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Request\Request;

class TagsController extends Controller
{
    /**
     * Get all of the tags
     * @param \FluentCrm\Includes\Request\Request $request
     * @return \WP_REST_Response
     */
    public function index(Request $request)
    {
        $order = [
            'by'    => $request->get('sort_by', 'id'),
            'order' => $request->get('sort_order', 'DESC')
        ];

        $tags = Tag::with('subscribers')->orderBy(
            $order['by'], $order['order']
        )->get()->each(function ($tag) {
            $tag->totalCount = $tag->subscribers->count();
            $tag->subscribersCount = $tag->subscribers->where('status', 'subscribed')->count();
            unset($tag->subscribers);
        });

        return $this->send([
            'tags' => $tags
        ]);
    }

    /**
     * Find a tag.
     */
    public function find($id)
    {
        return $this->send([
            'tag' => Tag::find($id)
        ]);
    }

    /**
     * Store a tag.
     * @param \FluentCrm\Includes\Request\Request $request
     * @return \WP_REST_Response
     */
    public function create(Request $request)
    {
        $allData = $request->all();

        if (empty($data['slug'])) {
            $data['slug'] = sanitize_title($allData['title'], 'display');
        } else {
            $data['slug'] = sanitize_title($data['slug'], 'display');
        }

        $this->validate($request->except('action'), [
            'title' => 'required',
            'slug'  => "required|unique:fc_tags,slug"
        ]);

        $tag = Tag::create([
            'title' => $allData['title'],
            'slug'  => $data['slug'],
            'description' => sanitize_text_field(Arr::get($allData, 'description'))
        ]);

        do_action('fluentcrm_tag_created', $tag->id);

        return $this->sendSuccess([
            'lists'   => $tag,
            'message' => 'Successfully saved the tag.'
        ]);
    }

    /**
     * Store a tag.
     * @param \FluentCrm\Includes\Request\Request $request
     * @param $id int Tag ID
     * @return \WP_REST_Response
     */
    public function store(Request $request, $id)
    {
        $allData = $request->all();
        $this->validate($allData, [
            'title' => 'required'
        ]);

        $tag = Tag::where('id', $id)->update([
            'title' => $allData['title'],
            'description' => sanitize_text_field(Arr::get($allData, 'description'))
        ]);

        do_action('fluentcrm_tag_updated', $tag->id);

        return $this->sendSuccess([
            'lists'   => $tag,
            'message' => 'Successfully saved the tag.'
        ]);
    }

    /**
     * Store a tag.
     */
    public function storeBulk()
    {
        $tags = $this->request->get('tags', []);

        foreach ($tags as $tag) {
            if (!$tag['title'] || !$tag['slug']) {
                continue;
            }
            $tag = Tag::updateOrCreate(
                ['slug' => sanitize_title($tag['slug'], 'display')],
                ['title' => $tag['title']]
            );
            do_action('fluentcrm_tag_created', $tag->id);
        }

        return $this->sendSuccess([
            'message' => 'Successfully saved the tags.'
        ]);
    }

    /**
     * Delete a tag by id
     *
     * @param \FluentCrm\Includes\Request\Request $request
     * @param $tagId
     * @return \WP_REST_Response $object
     */
    public function remove(Request $request, $tagId)
    {
        Tag::find($tagId)->delete();
        do_action('fluentcrm_tag_deleted', $tagId);

        return $this->sendSuccess([
            'message' => __('Successfully removed the tag.', 'fluentcrm')
        ]);
    }
}
