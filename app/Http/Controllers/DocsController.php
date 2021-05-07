<?php

namespace FluentCrm\App\Http\Controllers;


use FluentCrm\Includes\Helpers\Arr;

class DocsController extends Controller
{
    private $restApi = 'https://fluentcrm.com/wp-json/wp/v2/';

    public function index()
    {
        $request = wp_remote_get($this->restApi.'docs?per_page=100');

        $docs = json_decode(wp_remote_retrieve_body($request), true);

        $formattedDocs = [];

        foreach ($docs as $doc) {
            $primaryCategory = Arr::get($doc, 'taxonomy_info.doc_category.0', ['value' => 'none', 'label' => 'Other']);
            $formattedDocs[] = [
                'title' => $doc['title']['rendered'],
                'content' => $doc['content']['rendered'],
                'link' => $doc['link'],
                'category' => $primaryCategory
            ];
        }

        return [
            'docs' => $formattedDocs
        ];
    }
}
