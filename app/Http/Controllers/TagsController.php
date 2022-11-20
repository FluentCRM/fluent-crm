<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Tag;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  TagsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class TagsController extends Controller
{
    /**
     * Get all of the tags
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response | array
     */
    public function index(Request $request)
    {
        $order = [
            'by'    => $request->getSafe('sort_by', 'id', 'sanitize_sql_orderby'),
            'order' => $request->getSafe('sort_order', 'DESC', 'sanitize_sql_orderby')
        ];

        $tags = Tag::orderBy($order['by'], $order['order'])
            ->searchBy($request->getSafe('search'))
            ->paginate();

        if (!$request->get('exclude_counts')) {
            foreach ($tags as $tag) {
                $tag->subscribersCount = $tag->countByStatus('subscribed');
            }
        }

        $data = [
            'tags' => $tags
        ];

        if($request->get('all_tags')) {
            $allTags = Tag::get();
            $formattedTags = [];
            foreach ($allTags as $tag) {
                $formattedTags[] = [
                    'id' => strval($tag->id),
                    'title' => $tag->title,
                    'slug' => $tag->slug
                ];
            }
            $data['all_tags'] = $formattedTags;
        }

        return $data;
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
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response
     */
    public function create(Request $request)
    {
        $allData = $request->all();

        if (empty($allData['slug'])) {
            $allData['slug'] = sanitize_title($allData['title'], 'display');
        } else {
            $allData['slug'] = sanitize_title($allData['slug'], 'display');
        }

        $allData = $this->validate($allData, [
            'title' => 'required',
            'slug'  => "required|unique:fc_tags,slug"
        ]);

        $tag = Tag::create([
            'title' => sanitize_text_field($allData['title']),
            'slug'  => $allData['slug'],
            'description' => sanitize_textarea_field(Arr::get($allData, 'description'))
        ]);

        do_action('fluentcrm_tag_created', $tag->id);

        return $this->sendSuccess([
            'lists'   => $tag,
            'message' => __('Successfully saved the tag.', 'fluent-crm')
        ]);
    }

    /**
     * Store a tag.
     * @param \FluentCrm\Framework\Request\Request $request
     * @param $id int Tag ID
     * @return \WP_REST_Response
     */
    public function store(Request $request, $id)
    {
        $allData = $this->validate($request->all(), [
            'title' => 'required'
        ]);

        if(!empty($allData['slug'])) {
            $allData['slug'] = sanitize_title($allData['slug'], 'display');
        }

        if ($id == 0 && $request->get('update_by') == 'slug' && !empty($allData['slug'])) {

            $tag = Tag::where('slug', $allData['slug'])->first();
            if (!$tag) {
                return $this->sendError([
                    'message' => 'Tag could not be found'
                ]);
            }
            $id = $tag->id;
        } else {
            $tag = Tag::findOrFail($id);
            if(empty($allData['slug'])) {
                $allData['slug'] = $tag->slug;
            }
        }

        if (Tag::where('slug', $allData['slug'])->where('id', '!=', $id)->first()) {
            return $this->sendError([
                'message' => 'Provided slug already exist in another tag'
            ]);
        }

        $tag = Tag::where('id', $id)->update([
            'title'       => sanitize_text_field($allData['title']),
            'slug'        => $allData['slug'],
            'description' => sanitize_textarea_field(Arr::get($allData, 'description')),
        ]);

        do_action('fluentcrm_tag_updated', $id);

        return $this->sendSuccess([
            'lists'   => $tag,
            'message' => __('Successfully saved the tag.', 'fluent-crm')
        ]);
    }

    /**
     * Store a tag.
     */
    public function storeBulk()
    {
        $tags = $this->request->get('tags', []);

        if(!$tags) {
            $tags = $this->request->get('items', []);
        }

        $createdIds = [];

        foreach ($tags as $tag) {
            if (empty($tag['title'])) {
                continue;
            }

            if(empty($tag['slug'])) {
                $tag['slug'] = sanitize_title($tag['title'], 'display');
            }

            $tag = Tag::updateOrCreate(
                ['slug' => sanitize_title($tag['slug'], 'display')],
                ['title' => sanitize_text_field($tag['title'])]
            );

            $createdIds[] = $tag->id;

            do_action('fluentcrm_tag_created', $tag->id);
        }

        return $this->sendSuccess([
            'message' => __('Successfully saved the tags.', 'fluent-crm'),
            'ids' => $createdIds
        ]);
    }

    /**
     * Delete a tag by id
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @param $tagId
     * @return \WP_REST_Response $object
     */
    public function remove(Request $request, $tagId)
    {
        Tag::find($tagId)->delete();
        do_action('fluentcrm_tag_deleted', $tagId);

        return $this->sendSuccess([
            'message' => __('Successfully removed the tag.', 'fluent-crm')
        ]);
    }


    public function handleBulkAction(Request $request)
    {
        $tagIds = $request->getSafe('tagIds', [], 'intval');

        $tagIds = array_filter($tagIds);

        if($tagIds) {
            foreach ($tagIds as $tagId) {
                Tag::where('id', $tagId)->delete();
                do_action('fluentcrm_tag_deleted', $tagId);
            }
        }

        return $this->sendSuccess([
            'message' => __('Selected Tags has been removed permanently', 'fluent-crm'),
        ]);

    }
}
