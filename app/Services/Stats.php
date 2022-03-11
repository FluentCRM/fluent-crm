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
                'title' => __('Total Contacts', 'fluent-crm'),
                'count' => Subscriber::count(),
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

        return apply_filters('fluentcrm_dashboard_stats', $data);
    }

    public function getQuickLinks()
    {
        $urlBase = apply_filters(
            'fluentcrm_menu_url_base',
            admin_url('admin.php?page=fluentcrm-admin#/')
        );

        return apply_filters('fluentcrm_quick_links', [
            [
                'title' => __('View Contacts', 'fluent-crm'),
                'url'   => $urlBase . 'subscribers',
                'icon'  => 'el-icon-user'
            ],
            [
                'title' => __('Contact Segments', 'fluent-crm'),
                'url'   => $urlBase . 'contact-groups/lists',
                'icon'  => 'el-icon-folder'
            ],
            [
                'title' => __('Email Campaigns', 'fluent-crm'),
                'url'   => $urlBase . 'email/campaigns',
                'icon'  => 'el-icon-message'
            ],
            [
                'title' => __('Email Sequences', 'fluent-crm'),
                'url'   => $urlBase . 'email/sequences',
                'icon'  => 'el-icon-alarm-clock'
            ],
            [
                'title' => __('Forms', 'fluent-crm'),
                'url'   => $urlBase . 'forms',
                'icon'  => 'el-icon-document-checked'
            ],
            [
                'title' => __('Automations', 'fluent-crm'),
                'url'   => $urlBase . 'funnels',
                'icon'  => 'el-icon-cold-drink'
            ],
            [
                'title' => __('Settings', 'fluent-crm'),
                'url'   => $urlBase . 'settings',
                'icon'  => 'el-icon-setting'
            ],
            [
                'title' => __('Documentations', 'fluent-crm'),
                'url'   =>  $urlBase . 'documentation',
                'icon'  => 'el-icon-document'
            ]
        ]);
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
