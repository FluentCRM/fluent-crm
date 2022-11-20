<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\Tag;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Models\Campaign;
use FluentCrm\Framework\Request\Request;

/**
 *  OptionsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class OptionsController extends Controller
{
    /**
     * Get options based on the requested fields.
     *
     * @return array options.
     * @throws \Exception
     */
    public function index()
    {
        if ($fileds = $this->request->get('fields')) {
            $options = array_unique(explode(',', $fileds));

            $response = [];

            foreach ($options as $method) {
                if (method_exists($this, $method)) {
                    $result = $this->{$method}();
                    $response = array_merge($response, $result);
                }
            }

            return [
                'options' => $response
            ];
        }

        throw new \Exception('Missing requested fields field.', 422);
    }

    /**
     * Include the countries options.
     *
     * @return array
     */
    public function countries()
    {
        $countries = apply_filters('fluentcrm_countries', []);
        $formattedCountries = [];
        foreach ($countries as $country) {
            $country['id'] = $country['code'];
            $country['slug'] = $country['code'];
            $formattedCountries[] = $country;
        }
        return [
            'countries' => $formattedCountries
        ];
    }

    /**
     * Include all the lists.
     *
     * @return array
     */
    public function lists()
    {
        $lists = Lists::select(['id', 'slug', 'title'])->orderBy('title', 'ASC')->get();

        $withCount = (array)$this->request->get('with_count', []);

        if ($withCount && in_array('lists', $withCount)) {
            foreach ($lists as $list) {
                $list->subscribersCount = $list->countByStatus('subscribed');
            }
        }

        return [
            'lists' => $lists
        ];
    }

    /**
     * Include all the tags.
     *
     * @return array
     */
    public function tags()
    {
        $tags = Tag::select(['id', 'slug', 'title'])->orderBy('title', 'ASC')->get();
        foreach ($tags as $tag) {
            $tag->value = strval($tag->id);
            $tag->label = $tag->title;
        }
        return [
            'tags' => $tags
        ];
    }

    /**
     * Include all the Campaigns.
     *
     * @return array
     */
    public function campaigns()
    {
        return [
            'campaigns' => Campaign::select('id', 'title')->orderBy('id', 'DESC')->get()
        ];
    }

    /**
     * Include all the EmailSequences.
     *
     * @return array
     */
    public function email_sequences()
    {
        $sequences = [];

        if (defined('FLUENTCAMPAIGN')) {
            $sequences = \FluentCampaign\App\Models\Sequence::select('id', 'title')->orderBy('id', 'DESC')->get();
        }

        return [
            'email_sequences' => $sequences
        ];
    }

    /**
     * Include all the Automation Funnels.
     *
     * @return array
     */
    public function automation_funnels()
    {
        $funnels = Funnel::select('id', 'title', 'status')
            ->orderBy('id', 'DESC')->get();

        foreach ($funnels as $funnel) {
            $funnel->title .= ' (' . $funnel->status . ')';
        }

        return [
            'automation_funnels' => $funnels
        ];
    }

    /**
     * Include subscriber statuses.
     *
     * @return array
     */
    public function statuses()
    {
        $statuses = fluentcrm_subscriber_statuses();
        $formattedStatues = [];

        $transMaps = [
            'subscribed'   => __('Subscribed', 'fluent-crm'),
            'pending'      => __('Pending', 'fluent-crm'),
            'unsubscribed' => __('Unsubscribed', 'fluent-crm'),
            'bounced'      => __('Bounced', 'fluent-crm'),
            'complained'   => __('Complained', 'fluent-crm')
        ];

        foreach ($statuses as $status) {
            $formattedStatues[] = [
                'id'    => $status,
                'slug'  => $status,
                'title' => isset($transMaps[$status]) ? $transMaps[$status] : ucfirst($status)
            ];
        }

        return [
            'statuses' => $formattedStatues
        ];
    }

    /**
     * Include subscriber editable statuses.
     *
     * @return array
     */
    public function editable_statuses()
    {
        $statuses = fluentcrm_subscriber_statuses();
        $formattedStatues = [];

        $unEditableStatuses = ['bounced', 'complained'];

        $statuses = array_diff($statuses, $unEditableStatuses);

        $transMaps = [
            'subscribed'   => __('Subscribed', 'fluent-crm'),
            'pending'      => __('Pending', 'fluent-crm'),
            'unsubscribed' => __('Unsubscribed', 'fluent-crm'),
            'bounced'      => __('Bounced', 'fluent-crm'),
            'complained'   => __('Complained', 'fluent-crm')
        ];


        foreach ($statuses as $status) {
            $formattedStatues[] = [
                'id'    => $status,
                'slug'  => $status,
                'title' => isset($transMaps[$status]) ? $transMaps[$status] : ucfirst($status)
            ];
        }

        return [
            'editable_statuses' => $formattedStatues
        ];
    }

    /**
     * Include subscriber Contact Types.
     *
     * @return array
     */
    public function contact_types()
    {
        $types = fluentcrm_contact_types();
        $formattedTypes = [];

        foreach ($types as $type => $label) {
            $formattedTypes[] = [
                'id'    => $type,
                'slug'  => $type,
                'title' => $label
            ];
        }

        return [
            'contact_types' => $formattedTypes
        ];
    }

    /**
     * Include the sample csv url.
     *
     * @return array
     */
    public function sampleCsv()
    {
        return [
            'sampleCsv' => $this->app['url.assets'] . 'sample.csv'
        ];
    }

    public function segments()
    {
        $segments = apply_filters('fluentcrm_dynamic_segments', []);

        return [
            'segments' => $segments
        ];
    }

    public function roles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once(ABSPATH . '/wp-admin/includes/user.php');
        }

        return [
            'roles' => \get_editable_roles()
        ];
    }

    public function profile_sections()
    {
        return [
            'profile_sections' => Helper::getProfileSections()
        ];
    }

    public function custom_fields()
    {
        return [
            'custom_fields' => fluentcrm_get_option('contact_custom_fields', [])
        ];
    }

    public function getAjaxOptions(Request $request)
    {
        $optionKey = $request->getSafe('option_key');
        $search = $request->getSafe('search');
        $includedIds = $request->getSafe('values');

        $options = [];

        if ($optionKey == 'woo_categories') {
            // woocommerce categories
            if (defined('WC_PLUGIN_FILE')) {
                $cat_args = array(
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'hide_empty' => false,
                    'search'     => $search,
                    'number'     => 50
                );
                $product_categories = get_terms('product_cat', $cat_args);

                $pushedIds = [];
                foreach ($product_categories as $category) {
                    $options[] = [
                        'id'    => $category->term_id,
                        'title' => $category->name
                    ];
                    $pushedIds[] = $category->term_id;
                }

                if (empty($includedIds)) {
                    $includedIds = $pushedIds;
                }
                $includedIds = array_diff($includedIds, $pushedIds);

                if ($includedIds) {
                    $cat_args = array(
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                        'hide_empty' => false,
                        'include'    => $includedIds
                    );
                    $product_categories = get_terms('product_cat', $cat_args);
                    foreach ($product_categories as $category) {
                        $options[] = [
                            'id'    => $category->term_id,
                            'title' => $category->name
                        ];
                    }
                }
            }
        } else if ($optionKey == 'woo_products' || $optionKey == 'product_selector_woo' || $optionKey == 'product_selector_woo_order') {
            if (defined('WC_PLUGIN_FILE')) {
                $products = wc_get_products([
                    'limit'   => 50,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    's'       => $search
                ]);
                $pushedIds = [];

                foreach ($products as $product) {
                    $productId = $product->get_id();
                    $options[] = [
                        'id'    => $productId,
                        'title' => $product->get_name()
                    ];
                    $pushedIds[] = $productId;
                }

                if (empty($includedIds)) {
                    $includedIds = $pushedIds;
                } else {
                    $includedIds = (array)$includedIds;
                }

                $includedIds = array_diff($includedIds, $pushedIds);

                if ($includedIds) {
                    $products = wc_get_products([
                        'orderby' => 'date',
                        'order'   => 'DESC',
                        'include' => $includedIds
                    ]);
                    foreach ($products as $product) {
                        $productId = $product->get_id();
                        $options[] = [
                            'id'    => $productId,
                            'title' => $product->get_name()
                        ];
                    }
                }
            }
        } else if ($optionKey == 'edd_products' || $optionKey == 'product_selector_edd') {
            if (class_exists('Easy_Digital_Downloads') && defined('FLUENTCAMPAIGN')) {
                $options = \FluentCampaign\App\Services\Integrations\Edd\Helper::getProducts();
            }
        } else if ($optionKey == 'campaigns' || $optionKey == 'funnels' || $optionKey == 'email_sequences') {

            if ($optionKey == 'campaigns') {
                $objectModel = Campaign::select(['id', 'title', 'status'])->where('status', '!=', 'draft');
            } else if ($optionKey == 'funnels') {
                $objectModel = Funnel::select(['id', 'title', 'status']);
            } else if ($optionKey == 'email_sequences') {
                if (!defined('FLUENTCAMPAIGN')) {
                    return [
                        'options' => []
                    ];
                }
                $objectModel = \FluentCampaign\App\Models\Sequence::select(['id', 'title', 'status']);
            } else {
                return [
                    'options' => []
                ];
            }

            $items = $objectModel
                ->when($search, function ($query) use ($search) {
                    return $query->where('title', 'LIKE', "%$search%");
                })
                ->limit(20)
                ->orderBy('id', 'DESC')
                ->get();

            $pushedIds = [];

            foreach ($items as $item) {
                $options[] = [
                    'id'    => $item->id,
                    'title' => $item->title . ' - ' . $item->id
                ];
                $pushedIds[] = $item->id;
            }

            if (!$includedIds) {
                return [
                    'options' => $options
                ];
            }

            $includedIds = (array)$includedIds;

            $includedIds = array_diff($includedIds, $pushedIds);
            if ($includedIds) {

                if ($optionKey == 'campaigns') {
                    $objectModel = Campaign::select(['id', 'title', 'status']);
                } else if ($optionKey == 'funnels') {
                    $objectModel = Funnel::select(['id', 'title', 'status']);
                } else if ($optionKey == 'email_sequences') {
                    $objectModel = \FluentCampaign\App\Models\Sequence::select(['id', 'title', 'status']);
                } else {
                    return [
                        'options' => $options
                    ];
                }

                $items = $objectModel->whereIn('id', $includedIds)->get();
                foreach ($items as $item) {
                    $options[] = [
                        'id'    => $item->id,
                        'title' => $item->title . ' - ' . $item->id
                    ];
                }
            }
        } else {
            $options = apply_filters('fluentcrm_ajax_options_' . $optionKey, [], $search, $includedIds);
        }

        return [
            'options' => $options
        ];
    }

    public function getTaxonomyTerms(Request $request)
    {
        $taxonomy = $request->get('taxonomy');
        $search = $request->get('search');
        $includeIds = (array)$request->get('values', []);

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'number'     => 20
        ];

        if ($search) {
            $args['search'] = $search;
        }

        $terms = get_terms($args);

        $formattedTerms = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $formattedTerms[$term->term_id] = [
                    'id'    => strval($term->term_id),
                    'title' => $term->name
                ];
            }
        }

        if ($includeIds && $formattedTerms) {
            $includeIds = array_diff(array_keys($formattedTerms), $includeIds);
            if ($includeIds) {
                $includedTerms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'include'    => $includeIds
                ]);

                if (!is_wp_error($includedTerms)) {
                    foreach ($includedTerms as $includedTerm) {
                        $formattedTerms[$includedTerm->term_id] = [
                            'id'    => strval($includedTerm->term_id),
                            'title' => $includedTerm->name
                        ];
                    }
                }
            }
        }

        return [
            'options' => array_values($formattedTerms)
        ];

    }
}
