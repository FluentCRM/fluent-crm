import Dashboard from '@/Modules/Dashboard/Dashboard';
import NoPermission from '@/Modules/Dashboard/NoPermission';

import EmailView from '@/Modules/Email/EmailView';
import Campaigns from '@/Modules/Email/Campaigns/Campaigns';
import Campaign from '@/Modules/Email/Campaigns/Campaign';
import ViewCampaign from '@/Modules/Email/Campaigns/ViewCampaign';
import Templates from '@/Modules/Email/Templates/Templates';
import EditTemplate from '@/Modules/Email/Templates/EditTemplate';
import ImportTemplate from '@/Modules/Email/Templates/Import';
import AllEmails from '@/Modules/Email/AllEmails';

import Contacts from '@/Modules/Contacts/Contacts';
import List from '@/Modules/Lists/List';
import Tag from '@/Modules/Tags/Tag';
import Profile from '@/Modules/Profile/Profile';
import Importer from '@/Modules/Importer/Importer';

import ContactGroups from '@/Modules/Contacts/ContactGroups';
import Lists from '@/Modules/Lists/Lists';
import Tags from '@/Modules/Tags/Tags';
import DynamicSegments from '@/Modules/DynamicSegments/AllSegments';
import SegmentViewer from '@/Modules/DynamicSegments/SegmentViewer';
import CreateCustomSegment from '@/Modules/DynamicSegments/CreateCustomSegment';
import ImportSegment from '@/Modules/DynamicSegments/Import';

import ProfileOverview from '@/Modules/Profile/Parts/ProfileOverview';
import ProfileEmails from '@/Modules/Profile/Parts/ProfileEmails';
import ProfileFormSubmissions from '@/Modules/Profile/Parts/ProfileFormSubmissions';
import ProfileNotes from '@/Modules/Profile/Parts/ProfileNotes';
import ProfilePurchaseHistory from '@/Modules/Profile/Parts/ProfilePurchaseHistory';
import ProfileSupportTickets from '@/Modules/Profile/Parts/ProfileSupportTickets';
import SubscriberFiles from '@/Modules/Profile/Parts/ProfileFiles';
import SubscriberExternalView from '@/Modules/Profile/Parts/SubscriberExternalView';

import Settings from '@/Modules/Settings/Settings';
import EmailSettings from '@/Modules/Settings/parts/EmailSettings';
import BusinessSettings from '@/Modules/Settings/parts/BusinessSettings';
import CustomContactFields from '@/Modules/Settings/parts/CustomContactFields';
import DoubleOptinSettings from '@/Modules/Settings/parts/DoubleOptinSettings';
import WebHookSettings from '@/Modules/Settings/parts/WebHookSettings';
import SettingsTools from '@/Modules/Settings/parts/SettingsTools';
import Managers from '@/Modules/Settings/parts/Managers';
import RestApi from '@/Modules/Settings/parts/RestApi';
import ComplianceSettings from '@/Modules/Settings/parts/ComplianceSettings';
import AbandonCartSettings from '@/Modules/Settings/parts/AbandonCartSettings';

import FunnelRoute from '@/Modules/Funnels/FunnelRoute';
import Funnels from '@/Modules/Funnels/Funnels';
import EditFunnel from '@/Modules/Funnels/FunnelEditor/Edit';
import FunnelSubscribers from '@/Modules/Funnels/FunnelSubscribers';
import ImportFunnel from '@/Modules/Funnels/ImportFunnel';
import FunnelActivities from '@/Modules/Funnels/FunnelActivities';

import SequenceView from '@/Modules/Email/EmailSequences/SequenceView';
import AllSequences from '@/Modules/Email/EmailSequences/AllSequences';
import ViewSequence from '@/Modules/Email/EmailSequences/ViewSequence';
import EditSequenceEmail from '@/Modules/Email/EmailSequences/EditEmail';
import ViewSequenceSubscribers from '@/Modules/Email/EmailSequences/ViewSequenceSubscribers';
import ImportEmailSequence from '@/Modules/Email/EmailSequences/Import.vue';

import Forms from '@/Modules/Forms/Forms';
import SmtpSettings from '@/Modules/Settings/parts/SmtpSettings';
import OtherSettings from '@/Modules/Settings/parts/OtherSettings';
import LicenseManagement from '@/Modules/Settings/parts/LicenseManagement';
import IntegrationSettings from '@/Modules/Settings/parts/IntegrationSettings';
import ExperimentalFeaturesSettings from '@/Modules/Settings/parts/ExperimentalFeaturesSettings';
import SystemLogs from '@/Modules/Settings/parts/SystemLogs.vue';
import ActivityLogs from '@/Modules/Settings/parts/ActivityLogs.vue';

import SmartLinks from '@/Modules/SmartLinks/Links';
import Docs from '@/Modules/Documentation/Docs';

import AddOns from '@/Modules/Settings/AddOns';

import Reports from '@/Modules/Reports/ReportsHome.vue';
import MigrationHome from '@/Modules/Migrator/Home.vue';

import AbandonReports from '@/Modules/Reports/Abandon/AbandonReports.vue';

import CompaniesRoute from '@/Modules/Companies/CompaniesRoute.vue';
import AllCompanies from '@/Modules/Companies/AllCompanies.vue';
import ViewCompany from '@/Modules/Companies/ViewCompany.vue';
import CompanyOverview from '@/Modules/Companies/CompanyContacts';
import CompanyActivities from '@/Modules/Companies/CompanyActivities';
import CompanyExternalView from '@/Modules/Companies/CompanyExternalView';

/*
* Recurring Campaigns
 */
import RecurringCampaignsView from '@/Modules/Email/RecurringCampaigns/RecurringCampaignsView';
import RecurringCampaigns from '@/Modules/Email/RecurringCampaigns/RecurringCampaigns';
import RecurringCampaignCreate from '@/Modules/Email/RecurringCampaigns/CreateFlow';
import ViewSingleCampaign from '@/Modules/Email/RecurringCampaigns/ViewSingleCampaign';
import RecurringEmailConfiguration from '@/Modules/Email/RecurringCampaigns/Campaign/EmailConfiguration';
import RecurringEmailSettings from '@/Modules/Email/RecurringCampaigns/Campaign/Settings';
import RecurringEmailHistory from '@/Modules/Email/RecurringCampaigns/Campaign/EmailHistory';
import RecurringEmailReport from '@/Modules/Email/RecurringCampaigns/Campaign/EmailReport';
import RecurringCampaignImport from '@/Modules/Email/RecurringCampaigns/Import';
import ImportEmailCampaign from '@/Modules/Email/Campaigns/Import';

export default [
    {
        name: 'default',
        path: '*',
        redirect: '/',
        meta: {
            side_path: '/'
        }
    },
    {
        name: 'no_permission',
        path: '/no_permission',
        component: NoPermission
    },
    {
        name: 'dashboard',
        path: '/',
        component: Dashboard,
        props: true,
        meta: {
            active_menu: 'dashboard',
            permission: 'fcrm_view_dashboard',
            side_path: '/'
        }
    },
    {
        name: 'subscribers',
        path: '/subscribers',
        component: Contacts,
        props: true,
        meta: {
            active_menu: 'contacts',
            permission: 'fcrm_read_contacts',
            side_path: '/subscribers'
        }
    },
    {
        path: '/email',
        component: EmailView,
        props: true,
        meta: {
            parent: 'email',
            permission: 'fcrm_read_emails'
        },
        children: [
            {
                name: 'campaigns',
                path: 'campaigns',
                component: Campaigns,
                props: true,
                meta: {
                    parent: 'email',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/campaigns'
                }
            },
            {
                name: 'campaign-view',
                path: 'campaigns/:id/view',
                component: ViewCampaign,
                props: true,
                meta: {
                    parent: 'campaigns',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/campaigns'
                }
            },
            {
                name: 'campaign',
                path: 'campaigns/:id',
                component: Campaign,
                props: true,
                meta: {
                    parent: 'campaigns',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/campaigns'
                }
            },
            {
                name: 'import_email_campaigns',
                path: 'campaigns/import/new',
                component: ImportEmailCampaign,
                props: true,
                meta: {
                    parent: 'campaigns',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/campaigns'
                }
            },
            {
                name: 'templates',
                path: 'templates',
                component: Templates,
                props: true,
                meta: {
                    parent: 'campaigns',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/templates'
                }
            },
            {
                name: 'import_template',
                path: 'templates/import/new',
                component: ImportTemplate,
                props: true,
                meta: {
                    parent: 'templates',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/templates'
                }
            },
            {
                name: 'edit_template',
                path: 'templates/:template_id',
                component: EditTemplate,
                props: true,
                meta: {
                    parent: 'templates',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/templates'
                }
            },
            {
                path: 'sequences',
                component: SequenceView,
                props: true,
                children: [
                    {
                        name: 'email-sequences',
                        path: '/',
                        component: AllSequences,
                        meta: {
                            parent: 'email-sequences',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails',
                            side_path: '/email/sequences'
                        }
                    },
                    {
                        name: 'import_sequence',
                        path: '/import_sequence',
                        component: ImportEmailSequence,
                        meta: {
                            parent: 'email-sequences',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails',
                            side_path: '/email/sequences'
                        }
                    },
                    {
                        name: 'edit-sequence',
                        path: 'edit/:id',
                        component: ViewSequence,
                        props: true,
                        meta: {
                            parent: 'email-sequences',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails',
                            side_path: '/email/sequences'
                        }
                    },
                    {
                        name: 'edit-sequence-email',
                        path: 'edit/:sequence_id/email/:email_id',
                        component: EditSequenceEmail,
                        props: true,
                        meta: {
                            parent: 'email-sequences',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails',
                            side_path: '/email/sequences'
                        }
                    },
                    {
                        name: 'sequence-subscribers',
                        path: 'subscribers/:id/view',
                        component: ViewSequenceSubscribers,
                        props: true,
                        meta: {
                            parent: 'email-sequences',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails',
                            side_path: '/email/sequences'
                        }
                    }
                ]
            },
            {
                path: 'recurring-campaigns',
                component: RecurringCampaignsView,
                props: true,
                children: [
                    {
                        name: 'recurring_campaigns',
                        path: '/',
                        component: RecurringCampaigns,
                        meta: {
                            parent: 'email',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails',
                            side_path: '/email/recurring-campaigns'
                        }
                    },
                    {
                        name: 'create_recurring_campaign',
                        path: 'create-new',
                        component: RecurringCampaignCreate,
                        meta: {
                            parent: 'recurring_campaigns',
                            active_menu: 'campaigns',
                            permission: 'fcrm_read_emails'
                        }
                    },
                    {
                        path: 'emails/:campaign_id',
                        component: ViewSingleCampaign,
                        props: true,
                        children: [
                            {
                                path: 'view',
                                name: 'view_recurring_campaign',
                                component: RecurringEmailConfiguration,
                                props: true,
                                meta: {
                                    parent: 'recurring_campaigns',
                                    active_menu: 'campaigns',
                                    side_path: '/email/recurring-campaigns'
                                }
                            },
                            {
                                path: 'history',
                                name: 'past_recurring_emails',
                                component: RecurringEmailHistory,
                                props: true,
                                meta: {
                                    parent: 'recurring_campaigns',
                                    active_menu: 'campaigns',
                                    side_path: '/email/recurring-campaigns'
                                }
                            },
                            {
                                path: 'settings',
                                name: 'recurring_campaign_settings',
                                component: RecurringEmailSettings,
                                props: true,
                                meta: {
                                    parent: 'recurring_campaigns',
                                    active_menu: 'campaigns',
                                    side_path: '/email/recurring-campaigns'
                                }
                            }
                        ]
                    },
                    {
                        path: 'emails/:campaign_id/history/:email_id',
                        name: 'recurring_email_report',
                        component: RecurringEmailReport,
                        props: true,
                        meta: {
                            parent: 'recurring_campaigns',
                            active_menu: 'campaigns',
                            side_path: '/email/recurring-campaigns'
                        }
                    }
                ]
            },
            {
                name: 'import_recurring_campaigns',
                path: 'recurring-campaigns/import/new',
                component: RecurringCampaignImport,
                props: true,
                meta: {
                    parent: 'recurring_campaigns',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails',
                    side_path: '/email/recurring-campaigns'
                }
            },
            {
                name: 'all_emails',
                path: 'all-emails',
                component: AllEmails,
                props: true,
                meta: {
                    parent: 'email',
                    active_menu: 'campaigns',
                    permission: 'fcrm_read_emails'
                }
            }
        ]
    },
    {
        path: '/contact-groups',
        component: ContactGroups,
        props: true,
        meta: {
            parent: 'contacts',
            permission: 'fcrm_manage_contact_cats'
        },
        children: [
            {
                name: 'lists',
                path: 'lists',
                component: Lists,
                props: true,
                meta: {
                    parent: 'subscribers',
                    active_menu: 'contacts',
                    permission: 'fcrm_manage_contact_cats',
                    side_path: '/subscribers'
                }
            },
            {
                name: 'tags',
                path: 'tags',
                component: Tags,
                props: true,
                meta: {
                    parent: 'subscribers',
                    active_menu: 'contacts',
                    permission: 'fcrm_manage_contact_cats',
                    side_path: '/subscribers'
                }
            },
            {
                name: 'dynamic_segments',
                path: 'dynamic-segments',
                component: DynamicSegments,
                props: true,
                meta: {
                    parent: 'subscribers',
                    active_menu: 'contacts',
                    permission: 'fcrm_manage_contact_cats',
                    side_path: '/subscribers'
                }
            },
            {
                name: 'create_custom_segment',
                path: 'dynamic-segments/create-custom',
                component: CreateCustomSegment,
                meta: {
                    parent: 'subscribers',
                    active_menu: 'contacts',
                    permission: 'fcrm_manage_contact_cats',
                    side_path: '/subscribers'
                }
            },
            {
                name: 'view_segment',
                path: 'dynamic-segments/:slug/view/:id',
                props: true,
                component: SegmentViewer,
                meta: {
                    permission: 'fcrm_read_contacts',
                    parent: 'subscribers',
                    active_menu: 'contacts',
                    side_path: '/subscribers'
                }
            },
            {
                name: 'import_segment',
                path: 'dynamic-segments/import/new',
                component: ImportSegment,
                props: true,
                meta: {
                    parent: 'subscribers',
                    active_menu: 'contacts',
                    permission: 'fcrm_manage_contact_cats',
                    side_path: '/subscribers'
                }
            },
            {
                path: 'companies',
                component: CompaniesRoute,
                props: true,
                meta: {
                    active_menu: 'contacts',
                    parent: 'contacts',
                    permission: 'fcrm_read_contacts'
                },
                children: [
                    {
                        name: 'companies',
                        path: '',
                        component: AllCompanies,
                        meta: {
                            active_menu: 'contacts',
                            parent: 'contacts',
                            permission: 'fcrm_read_contacts'
                        }
                    }
                ]
            }
        ]
    },
    {
        name: 'list',
        path: '/lists/:listId',
        component: List,
        props: true,
        meta: {
            parent: 'subscribers',
            active_menu: 'contacts',
            permission: 'fcrm_manage_contact_cats',
            side_path: '/subscribers'
        }
    },
    {
        name: 'tag',
        path: '/tags/:tagId',
        component: Tag,
        props: true,
        meta: {
            permission: 'fcrm_read_contacts',
            parent: 'subscribers',
            active_menu: 'contacts',
            side_path: '/subscribers'
        }
    },
    {
        name: 'import',
        path: '/import',
        component: Importer,
        props: true,
        meta: {
            permission: 'fcrm_manage_contacts',
            side_path: '/subscribers'
        }
    },
    {
        name: 'forms',
        path: '/forms',
        component: Forms,
        props: true,
        meta: {
            parent: 'forms',
            active_menu: 'forms',
            permission: 'fcrm_manage_forms',
            side_path: '/forms'
        }
    },
    {
        path: '/settings',
        component: Settings,
        meta: {
            active_menu: 'settings',
            permission: 'fcrm_manage_settings',
            side_path: '/settings'
        },
        children: [
            {
                name: 'email_settings',
                path: 'email_settings',
                component: EmailSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'business_settings',
                path: '/',
                component: BusinessSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'custom_contact_fields',
                path: 'custom_contact_fields',
                component: CustomContactFields,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'smart_links',
                path: 'smart_links',
                component: SmartLinks,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'double-optin-settings',
                path: 'double_optin_settings',
                component: DoubleOptinSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'webhook-settings',
                path: 'webhook_settings',
                component: WebHookSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'settings_tools',
                path: 'settings_tools',
                component: SettingsTools,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'managers',
                path: 'managers',
                component: Managers,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'rest-api',
                path: 'reset-api',
                component: RestApi,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'settings_compliance',
                path: 'compliance',
                component: ComplianceSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'smtp_settings',
                path: 'smtp_settings',
                component: SmtpSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'other_settings',
                path: 'other_settings',
                component: OtherSettings,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'license_settings',
                path: 'license_settings',
                component: LicenseManagement,
                meta: {
                    active_menu: 'settings',
                    permission: 'fcrm_manage_settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'integration_settings',
                path: 'integration_settings',
                component: IntegrationSettings,
                meta: {
                    active_menu: 'settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'experimental_features',
                path: 'experimental-features',
                component: ExperimentalFeaturesSettings,
                meta: {
                    active_menu: 'settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'system_logs',
                path: 'system-logs',
                component: SystemLogs,
                meta: {
                    active_menu: 'settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'abandon_cart_settings',
                path: 'abandon-cart-settings',
                component: AbandonCartSettings,
                meta: {
                    active_menu: 'settings',
                    side_path: '/settings'
                }
            },
            {
                name: 'activity_logs',
                path: 'activity-logs',
                component: ActivityLogs,
                meta: {
                    active_menu: 'settings',
                    side_path: '/settings'
                }
            }
        ]
    },
    {
        path: '/funnels',
        component: FunnelRoute,
        meta: {
            active_menu: 'funnels',
            permission: 'fcrm_read_funnels',
            side_path: '/funnels'
        },
        children: [
            {
                name: 'funnels',
                path: '/',
                component: Funnels,
                meta: {
                    active_menu: 'funnels',
                    permission: 'fcrm_read_funnels',
                    side_path: '/funnels'
                }
            },
            {
                name: 'edit_funnel',
                path: '/funnel/:funnel_id/edit',
                component: EditFunnel,
                props: true,
                meta: {
                    active_menu: 'funnels',
                    permission: 'fcrm_read_funnels',
                    side_path: '/funnels'
                }
            },
            {
                name: 'funnel_subscribers',
                path: '/funnel/:funnel_id/subscribers',
                component: FunnelSubscribers,
                props: true,
                meta: {
                    active_menu: 'funnels',
                    permission: 'fcrm_read_funnels',
                    side_path: '/funnels'
                }
            },
            {
                name: 'import_funnel',
                path: '/funnel/import',
                component: ImportFunnel,
                meta: {
                    active_menu: 'funnels',
                    permission: 'fcrm_read_funnels',
                    side_path: '/funnels'
                }
            },
            {
                name: 'funnel_activities',
                path: '/funnel/all-activities',
                component: FunnelActivities,
                meta: {
                    active_menu: 'funnels',
                    permission: 'fcrm_read_funnels',
                    side_path: '/funnels'
                }
            }
        ]
    },
    {
        name: 'docs',
        path: '/documentation',
        component: Docs,
        meta: {
            side_path: '/documentation',
            active_menu: 'documentation'
        }
    },
    {
        name: 'addons',
        path: '/add-ons',
        component: AddOns,
        meta: {
            side_path: '/add-ons',
            active_menu: 'addons'
        }
    },
    {
        name: 'reports',
        path: '/reports',
        component: Reports,
        meta: {
            side_path: '/reports'
        }
    },
    {
        name: 'abandon-carts',
        path: '/abandon-carts',
        component: AbandonReports,
        meta: {
            active_menu: 'abandoned_carts',
            side_path: '/abandon-carts'
        }
    },
    {
        name: 'crm_migrations',
        path: '/crm_migrations',
        component: MigrationHome,
        meta: {
            active_menu: 'contacts',
            permission: 'fcrm_manage_settings',
            side_path: '/subscribers'
        }
    }
];

export var profileRoute = {
    path: '/subscribers/:id',
    component: Profile,
    props: true,
    meta: {
        parent: 'subscribers',
        active_menu: 'contacts',
        permission: 'fcrm_read_contacts',
        side_path: '/subscribers'
    },
    children: [
        {
            name: 'subscriber',
            path: '/',
            component: ProfileOverview,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'subscriber_emails',
            path: 'emails',
            component: ProfileEmails,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'subscriber_form_submissions',
            path: 'form-submissions',
            component: ProfileFormSubmissions,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'subscriber_notes',
            path: 'notes',
            component: ProfileNotes,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'subscriber_purchases',
            path: 'purchases',
            component: ProfilePurchaseHistory,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'subscriber_support_tickets',
            path: 'support-tickets',
            component: ProfileSupportTickets,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'subscriber_files',
            path: 'files',
            component: SubscriberFiles,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        },
        {
            name: 'fluentcrm_profile_extended',
            path: 'profile_section',
            component: SubscriberExternalView,
            meta: {
                parent: 'subscribers',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/subscribers'
            }
        }
    ]
};

export var companyProfileRoute = {
    path: '/companies/:company_id',
    props: true,
    component: ViewCompany,
    meta: {
        active_menu: 'contacts',
        parent: 'contacts',
        permission: 'fcrm_read_contacts'
    },
    children: [
        {
            name: 'view_company',
            path: '/',
            component: CompanyOverview,
            meta: {
                parent: 'view_company',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/companies'
            }
        },
        {
            name: 'company_activities',
            path: 'activities',
            component: CompanyActivities,
            meta: {
                parent: 'view_company',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/companies'
            }
        },
        {
            name: 'fluent_crm_company_section_extended',
            path: 'custom_section',
            component: CompanyExternalView,
            meta: {
                parent: 'view_company',
                active_menu: 'contacts',
                permission: 'fcrm_read_contacts',
                side_path: '/companies'
            }
        }
    ]
};
