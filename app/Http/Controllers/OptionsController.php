<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Tag;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\Subscriber;

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
        $countries = $this->app->applyFilters('fluentcrm-countries', []);
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
        $lists = Lists::select(['id', 'slug', 'title'])->get();

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
        $tags = Tag::select(['id', 'slug', 'title'])->get();
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
     * Include subscriber statuses.
     *
     * @return array
     */
    public function statuses()
    {
        $statuses = fluentcrm_subscriber_statuses();
        $formattedStatues = [];

        $transMaps = [
            'subscribed' => __('Subscribed', 'fluent-crm'),
            'pending' => __('Pending', 'fluent-crm'),
            'unsubscribed' => __('Unsubscribed', 'fluent-crm'),
            'bounced' => __('Bounced', 'fluent-crm'),
            'complained' => __('Complained', 'fluent-crm')
        ];

        foreach ($statuses as $status) {
            $formattedStatues[] = [
                'id'    => $status,
                'slug'  => $status,
                'title' => isset($transMaps[$status]) ? $transMaps[$status] :  ucfirst($status)
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
            'subscribed' => __('Subscribed', 'fluent-crm'),
            'pending' => __('Pending', 'fluent-crm'),
            'unsubscribed' => __('Unsubscribed', 'fluent-crm'),
            'bounced' => __('Bounced', 'fluent-crm'),
            'complained' => __('Complained', 'fluent-crm')
        ];


        foreach ($statuses as $status) {
            $formattedStatues[] = [
                'id'    => $status,
                'slug'  => $status,
                'title' => isset($transMaps[$status]) ? $transMaps[$status] :  ucfirst($status)
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
}
