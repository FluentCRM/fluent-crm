<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\Tag;
use FluentCrm\App\Models\Template;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\CampaignEmail;

class Stats
{
    public function getCounts()
    {
        $data = [
            'total_subscribers' => [
                'title' => __('Active Contacts', 'fluent-crm'),
                'count' => Subscriber::where('status', 'subscribed')->count(),
                'route' => [
                    'name' => 'subscribers'
                ]
            ],
            'total_campaigns'   => [
                'title' => __('Campaigns', 'fluent-crm'),
                'count' => Campaign::count(),
                'route' => [
                    'name' => 'campaigns'
                ]
            ],
            'email_sent'        => [
                'title' => __('Emails Sent', 'fluent-crm'),
                'count' => CampaignEmail::where('status', 'sent')->count(),
                'route' => [
                    'name' => 'all_emails'
                ]
            ],
            'tags'              => [
                'title' => __('Tags', 'fluent-crm'),
                'count' => Tag::count(),
                'route' => [
                    'name' => 'tags'
                ]
            ],
            'total_templates'   => [
                'title' => __('Email Templates', 'fluent-crm'),
                'count' => Template::where('post_type', fluentcrmTemplateCPTSlug())->count(),
                'route' => [
                    'name' => 'templates'
                ]
            ],
            'total_automations'   => [
                'title' => __('Active Automations', 'fluent-crm'),
                'count' => Funnel::where('status', 'published')->count(),
                'route' => [
                    'name' => 'funnels'
                ]
            ]
        ];

        $pendingEmails = CampaignEmail::whereIn('status', ['pending', 'scheduled', 'processing'])->count();

        if ($pendingEmails) {
            $data['email_pending'] = [
                'title' => __('Pending Emails', 'fluent-crm'),
                'count' => $pendingEmails,
                'route' => [
                    'name' => 'campaigns'
                ]
            ];
        }

        /**
         * Filter the dashboard statistics data.
         *
         * This filter allows modification of the dashboard statistics data before it is returned.
         *
         * @since 2.7.0
         *
         * @param array $data The dashboard statistics data.
         */
        return apply_filters('fluent_crm/dashboard_stats', $data);
    }

    public function getQuickLinks()
    {
        $urlBase = fluentcrm_menu_url_base();

        $quickLinks = [
            [
                'title' => __('Contact Segments', 'fluent-crm'),
                'url'   => $urlBase . 'contact-groups/lists',
                'icon'  => 'el-icon-folder'
            ],
            [
                'title' => __('Recurring Campaigns', 'fluent-crm'),
                'url'   => $urlBase . 'email/recurring-campaigns',
                'icon'  => 'el-icon-message'
            ],
            [
                'title' => __('Email Sequences', 'fluent-crm'),
                'url'   => $urlBase . 'email/sequences',
                'icon'  => 'el-icon-alarm-clock'
            ],
            [
                'title' => __('Documentations', 'fluent-crm'),
                'url'   =>  $urlBase . 'documentation',
                'icon'  => 'el-icon-document'
            ],
            [
                'title' => __('Video Tutorials (Free)', 'fluent-crm'),
                'url' => 'https://www.youtube.com/playlist?list=PLXpD0vT4thWG-ZPeM6cco7BS5cJY9bTjL',
                'icon' => 'el-icon-video-camera',
                'is_external' => true
            ]
        ];

        /**
         * Filter the quick links in FluentCRM.
         *
         * This filter allows modification of the quick links array in FluentCRM.
         *
         * @since 2.7.1
         *
         * @param array $quickLinks An array of quick links.
         */
        return apply_filters('fluent_crm/quick_links', $quickLinks);
    }

    public function getOnboardingStat()
    {
        if (fluentcrm_get_option('onboarding_status') == 'yes') {
            return null;
        }

        $formCreated = false;
        if(defined('FLUENTFORM')) {
            $firstFeed = fluentCrmDb()->table('fluentform_form_meta')
                ->where('meta_key', 'fluentcrm_feeds')
                ->first();
            $formCreated = !!$firstFeed;
            if(!$formCreated) {
                $formCreated = !!Funnel::where('trigger_name', 'fluentform_submission_inserted')->first();
            }
        }

        $boardingSteps = [
            [
                'label'     => __('Create a Tag', 'fluent-crm'),
                'completed' => !!Tag::first(),
                'route'     => [
                    'name' => 'tags'
                ]
            ],
            [
                'label'     => __('Import Contacts', 'fluent-crm'),
                'completed' => !!Subscriber::first(),
                'route'     => [
                    'name' => 'subscribers'
                ]
            ],
            [
                'label'     => __('Create a Campaign', 'fluent-crm'),
                'completed' => !!Campaign::first(),
                'route'     => [
                    'name' => 'campaigns'
                ]
            ],
            [
                'label'     => __('Create an Automation', 'fluent-crm'),
                'completed' => !!Funnel::first(),
                'route'     => [
                    'name' => 'funnels'
                ]
            ],
            [
                'label'     => __('Create a Form', 'fluent-crm'),
                'completed' => $formCreated,
                'route'     => [
                    'name' => 'forms'
                ]
            ]
        ];

        $completed = 0;
        $total = count($boardingSteps);

        foreach ($boardingSteps as $step) {
            if($step['completed']) {
                $completed++;
            }
        }

        if($completed == $total) {
            fluentcrm_update_option('onboarding_status', 'yes');
            return null;
        }

        return [
            'total'     => $total,
            'completed' => $completed,
            'steps'     => $boardingSteps
        ];
    }
}
