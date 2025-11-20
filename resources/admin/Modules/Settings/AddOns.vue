<template>
    <div style="max-width: 1000px; margin: 30px auto; padding: 0px 20px;" class="fc_docs">
        <div class="addons_wrap">
            <el-skeleton v-if="loading" style="width: 100%;" animated>
                <template slot="template">
                    <el-row :gutter="30">
                        <el-col :span="24">
                            <el-skeleton style="background: white; padding: 15px;" :rows="5"/>
                        </el-col>
                        <el-col :span="24">
                            <el-skeleton style="background: white; padding: 15px;" :rows="5"/>
                        </el-col>
                        <el-col :span="24">
                            <el-skeleton style="background: white; padding: 15px;" :rows="5"/>
                        </el-col>
                    </el-row>
                </template>
            </el-skeleton>
            <template v-else>
                <div class="fc-advanced-modules-features-wrap">
                    <div class="fc-advanced-module-features-header">
                        <h3>{{ $t('Advanced Modules') }}</h3>
                        <p>{{ $t('Advanced_Feature_And_Integrations_Desc') }}</p>
                    </div>

                    <div class="fc-advanced-modules-wrap">
                        <div class="fc-advanced-module-box">
                            <div class="fc-advanced-module-header">
                                <div class="icon">
                                    <CustomIcon type="company" />
                                </div>
                                <div class="right">
                                    <h5>
                                    <span class="text">
                                        {{ $t('Company Module') }}
                                        <el-tooltip class="item" effect="dark"
                                                    placement="top-start">
                                            <div slot="content" style="max-width: 500px;">
                                                {{ $t('Company_Module_Help') }}
                                            </div>
                                            <i class="el-icon el-icon-info"></i>
                                        </el-tooltip>
                                    </span>
                                        <el-switch
                                            v-model="experimental_features.company_module"
                                            @change="saveExperimentalSettings()"
                                            active-value="yes"
                                            inactive-value="no"
                                        />
                                    </h5>
                                    <p>{{ $t('Business Contact Management') }}</p>
                                </div>
                            </div>
                            <div class="fc-advanced-module-footer">
                                <div class="fc-advanced-module-footer-top">
                                <span
                                    class="fc_addon_installed"
                                    :class="{disabled: experimental_features.company_module == 'no'}"
                                >
                                    {{ experimental_features.company_module == 'yes' ? $t('Enabled') : $t('Disabled') }}
                                </span>

                                    <el-popover
                                        style="margin-left: auto;"
                                        placement="top"
                                        width="500"
                                        trigger="click"
                                    >
                                        <div>
                                            <h3>{{ $t('Company Module Settings') }}</h3>
                                            <el-checkbox v-model="experimental_features.company_module" true-label="yes" false-label="no">
                                                {{ $t('Enable Company Module for Contacts') }}
                                            </el-checkbox>
                                            <div style="margin-top: 15px;" v-if="experimental_features.company_module == 'yes'">
                                                <el-checkbox v-model="experimental_features.company_auto_logo" true-label="yes" false-label="no">
                                                    {{ $t('Company_Logo_Auto_Download_Help') }}
                                                    <el-tooltip class="item" placement="bottom-start" effect="light">
                                                        <div slot="content">
                                                            <p>
                                                                {{ $t('Company_Logo_Auto_Download_Note') }}
                                                            </p>
                                                        </div>
                                                        <i class="el-icon-info text-info"></i>
                                                    </el-tooltip>
                                                </el-checkbox>
                                            </div>
                                            <div  style="margin-top: 15px;" >
                                                <el-button @click="saveExperimentalSettings()" :disabled="saving" v-loading="saving" type="success" size="small">{{ $t('Save Settings') }}</el-button>
                                            </div>
                                        </div>
                                        <el-button size="small" slot="reference" class="fc-learn-more-btn">{{ $t('Settings') }}</el-button>
                                    </el-popover>
                                </div>
                            </div>
                        </div>
                        <div class="fc-advanced-module-box">
                            <div class="fc-advanced-module-header">
                                <div class="icon">
                                    <CustomIcon type="campaign" />
                                </div>
                                <div class="right">
                                    <h5>
                                    <span class="text">
                                        {{ $t('Campaign Archives') }}
                                        <el-tooltip class="item" effect="dark"
                                                    placement="top-start">
                                            <div slot="content" style="max-width: 500px;">
                                                {{ $t('Email_Camp_Enable_Help') }}
                                            </div>
                                            <i class="el-icon el-icon-info"></i>
                                        </el-tooltip>
                                    </span>
                                        <el-switch
                                            v-model="experimental_features.campaign_archive"
                                            @change="saveExperimentalSettings()"
                                            active-value="yes"
                                            inactive-value="no"
                                        />
                                    </h5>
                                    <p>{{ $t('Frontend Showcase with Shortcode') }}</p>
                                </div>
                            </div>
                            <div class="fc-advanced-module-footer">
                                <div class="fc-advanced-module-footer-top">
                                    <span
                                        class="fc_addon_installed"
                                        :class="{disabled: experimental_features.campaign_archive == 'no'}"
                                    >
                                        {{ experimental_features.campaign_archive == 'yes' ? $t('Enabled') : $t('Disabled') }}
                                    </span>

                                    <el-popover
                                        style="margin-left: auto;"
                                        placement="left"
                                        width="500"
                                        trigger="click"
                                        popper-class="fc_addons_campaign_popover"
                                    >
                                        <div>
                                            <el-checkbox v-model="experimental_features.campaign_archive" true-label="yes" false-label="no">
                                                {{ $t('Enable Campaign Archive Frontend Feature') }}
                                            </el-checkbox>
                                            <el-form v-if="experimental_features.campaign_archive == 'yes'" label-position="top" :model="experimental_features">
                                                <h4 style="margin: 10px 0 10px;">{{ $t('Campaign Archive Settings') }}</h4>
                                                <hr />
                                                <el-form-item :label="$t('List_Campaigns_Label')">
                                                    <el-input :placeholder="$t('Campaign Search Keyword')" type="text"
                                                              v-model="experimental_features.campaign_search"></el-input>
                                                    <p style="margin: 0">{{ $t('List_Campaigns_Help') }}</p>
                                                </el-form-item>
                                                <el-form-item :label="$t('Select Campaigns')">
                                                    <el-select v-loading="campaignLoading"
                                                               v-model="experimental_features.campaign_ids"
                                                               multiple
                                                               :placeholder="$t('Select Campaigns')"
                                                               filterable
                                                               popper-class="fc_select_campaigns_popover"
                                                               :remote="true"
                                                               :clearable="true"
                                                               :remote-method="fetchCampaign"
                                                    >
                                                        <el-option
                                                            v-for="campaign in campaigns"
                                                            :key="campaign.id"
                                                            :label="campaign.title"
                                                            :value="campaign.id">
                                                            {{ campaign.title }}
                                                            <span :class="'badge ' + campaign.status">{{ campaign.status }}</span>
                                                        </el-option>
                                                    </el-select>
                                                    <p style="margin: 0" class="help_text">{{ $t('Leave it blank to display all campaigns') }}</p>
                                                </el-form-item>
                                                <el-form-item :label="$t('Filter by status')">
                                                    <el-select
                                                        v-model="experimental_features.campaign_status"
                                                        :placeholder="$t('Filter by status')"
                                                    >
                                                        <el-option
                                                            v-for="status in statuses"
                                                            :key="status.key"
                                                            :value="status.key"
                                                            :label="status.label"
                                                        />
                                                    </el-select>
                                                </el-form-item>
                                                <el-form-item :label="$t('Max Campaigns to list (max 50)')">
                                                    <el-input :placeholder="$t('Campaign Search Keyword')" :min="1" :max="50" type="number"
                                                              v-model="experimental_features.campaign_max_number"></el-input>
                                                </el-form-item>
                                                <el-form-item>
                                                    <p style="margin: 0">{{ $t('Campaign_Shortcode_Help') }}</p>
                                                    <textarea v-if="has_campaign_pro" :readonly="true"
                                                              style="width: 100%; background: #ecf5ff;height: 40px;">[fluent_crm_campaign_archives]</textarea>
                                                    <div v-else>
                                                        <h2 class="text-align-center">{{ $t('Campaign_Feature_Note') }}</h2>
                                                    </div>
                                                </el-form-item>
                                            </el-form>

                                            <div  style="margin-top: 15px;" >
                                                <el-button @click="saveExperimentalSettings()" :disabled="saving || !has_campaign_pro" v-loading="saving" type="success" size="small">{{ $t('Save Settings') }}</el-button>
                                            </div>
                                        </div>
                                        <el-button size="small" slot="reference" class="fc-learn-more-btn">{{ $t('Settings') }}</el-button>
                                    </el-popover>
                                </div>
                            </div>
                        </div>
                        <div class="fc-advanced-module-box">
                            <div class="fc-advanced-module-header">
                                <div class="icon">
                                    <CustomIcon type="event-tracking" />
                                </div>
                                <div class="right">
                                    <h5>
                                    <span class="text">
                                        {{ $t('Event Tracking Module') }}
                                        <el-tooltip class="item" effect="dark"
                                                    placement="top-start">
                                            <div slot="content" style="max-width: 500px;">
                                                {{ $t('Event_Tracking_Module_Help') }}
                                            </div>
                                            <i class="el-icon el-icon-info"></i>
                                        </el-tooltip>
                                    </span>
                                        <el-switch
                                            v-model="experimental_features.event_tracking"
                                            @change="saveExperimentalSettings()"
                                            active-value="yes"
                                            inactive-value="no"
                                        />
                                    </h5>
                                    <p>{{ $t('Flexible Contact Behavior Analytic') }}</p>
                                </div>
                            </div>
                            <div class="fc-advanced-module-footer">
                                <div class="fc-advanced-module-footer-top">
                                <span
                                    class="fc_addon_installed"
                                    :class="{disabled: experimental_features.event_tracking == 'no'}"
                                >
                                    {{ experimental_features.event_tracking == 'yes' ? $t('Enabled') : $t('Disabled') }}
                                </span>

                                    <a href="https://fluentcrm.com/docs/event-tracking-automation/" target="_blank" class="fc-learn-more-btn">{{ $t('Learn More') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fc-advanced-modules-features-wrap fc-recommended-plugins-wrap">
                    <div class="fc-advanced-module-features-header">
                        <h3>{{ $t('Recommended Plugins and Addons') }}</h3>
                        <p>{{ $t('Plugins that will extend your FluentCrm Functionalities') }}</p>
                    </div>

                    <div class="fc-advanced-modules-wrap">
                        <div v-if="!has_campaign_pro" class="fc-advanced-module-box">
                            <div class="fc-advanced-module-header">
                                <div class="right">
                                    <h5>
                                        <span class="text">
                                            {{ $t('Addons.fluentcrm_pro.title') }}
                                        </span>
                                    </h5>
                                    <p>{{ $t('with_fluentcrm_pro_integrate_with_other_plugins') }}</p>
                                </div>
                            </div>
                            <div class="fc-advanced-module-footer">
                                <a target="_blank" class="fc-pro-btn el-button el-button--danger" href="https://fluentcrm.com?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp">{{$t('Get FluentCRM Pro Now')}}</a>
                            </div>
                        </div>
                        <div v-for="(addon, addOnKey) in addOns" :key="addOnKey" class="fc-advanced-module-box">
                            <div class="fc-advanced-module-header">
                                <div class="icon">
                                    <img :src="addon.logo" :alt="addon.title" />
                                </div>
                                <div class="right">
                                    <h5>
                                        <a :href="addon.learn_more_url" target="_blank" class="text">
                                            {{ addon.title }} <i class="dashicons dashicons-external"></i>
                                        </a>
                                    </h5>
                                    <p>{{ addon.description }}</p>
                                </div>
                            </div>
                            <div class="fc-advanced-module-footer">
                                <el-button
                                    v-if="!addon.is_installed"
                                    class="btn-full-width"
                                    v-loading="saving"
                                    :disabled="saving"
                                    @click="installPlugin(addOnKey)"
                                    type="primary">{{
                                        addon.action_text
                                    }}
                                </el-button>
                                <div v-else class="fc-advanced-module-footer-top">
                                <span class="fc_addon_installed">
                                    {{ $t('Installed') }}
                                </span>

                                    <a :href="addon.settings_url" class="el-button el-button--default fc-learn-more-btn">{{$t('View Settings')}}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script type="text/babel">
import CustomIcon from '@/Pieces/CustomIcon.vue';
export default {
    name: 'Addons',
    components: {
        CustomIcon
    },
    data() {
        return {
            addOns: [],
            loading: true,
            installing: false,
            experimental_features: {},
            saving: false,
            campaigns: [],
            campaignLoading: false,
            searchQuery: '',
            statuses: [
                {
                    key: 'all', label: this.$t('All')
                },
                {
                    key: 'draft', label: this.$t('Draft')
                },
                {
                    key: 'pending', label: this.$t('Pending')
                },
                {
                    key: 'archived', label: this.$t('Archived')
                },
                {
                    key: 'incomplete', label: this.$t('Incomplete')
                },
                {
                    key: 'purged', label: this.$t('Purged')
                },
                {
                    key: 'processing', label: this.$t('Processing')
                },
                {
                    key: 'pending-scheduled', label: this.$t('Scheduled (pending)')
                },
                {
                    key: 'scheduled', label: this.$t('Scheduled')
                }
            ]
        }
    },
    methods: {
        fetchAddons() {
            this.loading = true;
            this.$get('docs/addons', { with: ['experimental_features'] })
                .then(response => {
                    this.addOns = response.addons;
                    this.experimental_features = response.experimental_features;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        installPlugin(slug) {
            this.installing = true;
            this.$post('setting/install-' + slug)
                .then(response => {
                    if (response.is_installed) {
                        this.$notify.success(response.message);
                    } else {
                        this.$notify.error(this.$t('Sorry, the selected plugins could not be installed'));
                    }
                    this.fetchAddons();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.installing = false;
                });
        },
        saveExperimentalSettings() {
            this.saving = true;
            this.$post('setting/experiments', {
                ...this.experimental_features
            })
                .then(r => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: r.message,
                        offset: 19
                    });
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        fetchCampaign(query) {
            this.campaignLoading = true;
            this.searchQuery = query;

            this.$get('campaigns', {
                searchBy: this.searchQuery
            })
                .then(response => {
                    this.campaignLoading = false;
                    this.campaigns = response.campaigns.data;
                })
                .catch((error) => {
                    console.log(error);
                });
        }
    },
    mounted() {
        this.fetchAddons();
        this.fetchCampaign('');
        this.changeTitle(this.$t('Addons'));
    }
}
</script>
