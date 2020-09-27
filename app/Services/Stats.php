<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Campaign;
use FluentCrm\App\Models\Template;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\CampaignEmail;

Class Stats
{
    public function getCounts()
    {
        $data = [
            'lists' => [
                'title' => __('Lists', 'fluentcrm'),
                'count' => Lists::count(),
                'route' => [
                    'name' => 'lists'
                ]
            ],
            'total_subscribers' => [
                'title' => __('Total Contacts', 'fluentcrm'),
                'count' => Subscriber::count(),
                'route' => [
                    'name' => 'subscribers'
                ]
            ],
            'total_campaigns' => [
                'title' => __('Campaigns', 'fluentcrm'),
                'count' => Campaign::count(),
                'route' => [
                    'name' => 'campaigns'
                ]
            ],
            'total_templates' => [
                'title' => __('Email Templates', 'fluentcrm'),
                'count' => Template::where('post_type', fluentcrmTemplateCPTSlug())->count(),
                'route' => [
                    'name' => 'templates'
                ]
            ],
            'email_sent' => [
                'title' => __('Emails Sent', 'fluentcrm'),
                'count' => CampaignEmail::where('status','sent')->count(),
                'route' => [
                    'name' => 'campaigns'
                ]
            ]
        ];

        $pendingEmails = CampaignEmail::whereIn('status', ['pending', 'scheduled'])->count();

        if ($pendingEmails) {
            $data['email_pending'] = [
                'title' => __('Emails Pending', 'fluentcrm'),
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
                'title' => 'View Contacts',
                'url' => $urlBase.'subscribers',
                'icon' => 'el-icon-user'
            ],
            [
                'title' => 'Contact Segments',
                'url' => $urlBase.'contact-groups/lists',
                'icon' => 'el-icon-folder'
            ],
            [
                'title' => 'Email Campaigns',
                'url' => $urlBase.'email/campaigns',
                'icon' => 'el-icon-message'
            ],
            [
                'title' => 'Email Sequences',
                'url' => $urlBase.'email/sequences',
                'icon' => 'el-icon-alarm-clock'
            ],
            [
                'title' => 'Forms',
                'url' => $urlBase.'forms',
                'icon' => 'el-icon-document-checked'
            ],
            [
                'title' => 'Funnels',
                'url' => $urlBase.'funnels',
                'icon' => 'el-icon-cold-drink'
            ],
            [
                'title' => 'Settings',
                'url' => $urlBase.'settings',
                'icon' => 'el-icon-setting'
            ]
        ]);
    }
}
