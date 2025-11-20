<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('Advanced Features Settings') }}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    type="success"
                    size="medium"
                    @click="save"
                    :loading="btnFromLoading || loading"
                >{{ $t('Update Settings') }}
                </el-button>
            </div>
        </div>
        <div v-loading="loading" class="fluentcrm_pad_around">
            <p>{{ $t('Features_Enable_Or_Disable') }}</p>
            <el-form :model="settings" label-position="top">

                <table class="form-table">
                    <tr valign="top">
                        <th>
                            {{ $t('Quick Contact Navigation') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <h3>{{$t('Quick Contact Navigation')}}</h3>
                                    <p>
                                        {{ $t('Quick_Nav_Enable_Help') }}
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.quick_contact_navigation" true-label="yes"
                                         false-label="no">
                                {{ $t('Enable Quick Contact Navigation') }}
                            </el-checkbox>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>
                            {{ $t('Campaign Archives') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <h3>{{$t('Campaign Archives on Frontend')}}</h3>
                                    <p>
                                        {{ $t('Email_Camp_Enable_Help') }}
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.campaign_archive" true-label="yes" false-label="no">
                                {{ $t('Enable Campaign Archive Frontend Feature') }}
                            </el-checkbox>
                            <div v-if="settings.campaign_archive == 'yes'" class="settings-section fluentcrm_databox">
                                <h3 style="margin: 0 0 10px;">{{ $t('Campaign Archive Settings') }}</h3>
                                <hr />
                                <el-form-item :label="$t('List_Campaigns_Label')">
                                    <el-input placeholder="Campaign Search Keyword" type="text"
                                              v-model="settings.campaign_search"></el-input>
                                    <p>{{ $t('List_Campaigns_Help') }}</p>
                                </el-form-item>
                                <el-form-item :label="$t('Select Campaigns')">
                                    <el-select v-loading="campaignLoading"
                                               v-model="settings.campaign_ids"
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
                                    <p class="help_text">{{ $t('Leave it blank to display all campaigns') }}</p>
                                </el-form-item>
                                <el-form-item :label="$t('Filter by status')">
                                    <el-select
                                        v-model="settings.campaign_status"
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
                                              v-model="settings.campaign_max_number"></el-input>
                                </el-form-item>
                                <el-form-item>
                                    <p>{{ $t('Campaign_Shortcode_Help') }}</p>
                                    <template v-if="has_campaign_pro">
                                        <textarea :readonly="true"
                                              style="width: 100%; background: #ecf5ff;height: 40px;">[fluent_crm_campaign_archives]</textarea>
                                        <p>You can use multiple email campaign archives with the following shortcode: <b>[fluent_crm_campaign_archives ids=1101,5,1100,1072 status=all search=Summer limit=50]</b>. If the parameters <b>ids, status, search,</b> or <b>limit</b> are missing, the values from the global campaign archive settings will be used.</p>
                                    </template>
                                    <div v-else>
                                        <h2 class="text-align-center">{{ $t('Campaign_Feature_Note') }}</h2>
                                        <generic-promo/>
                                    </div>
                                </el-form-item>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>
                            {{ $t('Date & Time Format') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <p>
                                        {{ $t('Date_And_Time_Format_Label') }}
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-radio-group v-model="settings.classic_date_time">
                                <el-radio label="no">{{ $t('Date Time difference (EG: 2 hours ago)') }}</el-radio>
                                <el-radio label="yes">{{ $t('WordPress Default') }}</el-radio>
                            </el-radio-group>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>
                            {{ $t('Navigation') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <p>
                                        {{ $t('Fluent_CRM_Nav_Hide') }}
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.full_navigation" true-label="yes" false-label="no">
                                {{ $t('Fluent_CRM_Exp') }}
                            </el-checkbox>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>
                            {{ $t('Company Module') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <p>
                                        {{ $t('Company_Module_Help_1') }}
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.company_module" true-label="yes" false-label="no">
                                {{ $t('Enable Company Module for Contacts') }}
                            </el-checkbox>

                            <div v-if="settings.company_module == 'yes'" class="settings-section fluentcrm_databox">
                                <el-checkbox v-model="settings.company_auto_logo" true-label="yes" false-label="no">
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
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>
                            {{ $t('Disable AI?') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <p>
                                        Enable or Disable AI integration on Visual Email Builder
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.disable_visual_ai" true-label="yes" false-label="no">
                                {{ $t('Disable AI Integration on Visual Email Builder') }}
                            </el-checkbox>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th>
                            {{ $t('Multi-Threading Email Sending?') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <p>
                                        Enable or Disable Multi-Thread Email Sending. If you enable this, FluentCRM try to send emails in parallel.
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.multi_threading_emails" true-label="yes" false-label="no">
                                {{ $t('Enable Multi-Threading Email Sending') }}
                            </el-checkbox>
                            <div v-if="settings.multi_threading_emails == 'yes'" class="settings-section fluentcrm_databox">
                                <p style="margin-bottom: 10px;"><b>Please make sure your server met the following requirements for optimal performance.</b></p>
                                <ul class="fc_list">
                                    <li>Multiple CPU on the server.</li>
                                    <li>At least 4GB Server Memory (RAM).</li>
                                    <li>Rate limit of your SMTP/Email Sending Service is within the max email sending rate set in email settings.</li>
                                    <li>Max Execution Time of PHP value should within within 50-60 seconds.</li>
                                    <li>If you already have a good sending speed. Do not enable this feature.</li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th>
                            {{ $t('System Log') }}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <p>
                                        Enable to log FluentCRM system events. Useful for debugging purpose.
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </th>
                        <td>
                            <el-checkbox v-model="settings.system_logs" true-label="yes" false-label="no">
                                {{ $t('__ENABLE_SYSTEM_LOG') }}
                            </el-checkbox>
                        </td>
                    </tr>

<!--                    <tr valign="top">-->
<!--                        <th>-->
<!--                            {{ $t('Activity Log') }}-->
<!--                            <el-tooltip class="item" placement="bottom-start" effect="light">-->
<!--                                <div slot="content">-->
<!--                                    <p>-->
<!--                                        {{ $t('activity_log_info') }}-->
<!--                                    </p>-->
<!--                                </div>-->
<!--                                <i class="el-icon-info text-info"></i>-->
<!--                            </el-tooltip>-->
<!--                        </th>-->
<!--                        <td>-->
<!--                            <el-checkbox v-model="settings.activity_log" true-label="yes" false-label="no">-->
<!--                                {{ $t('enable activity log') }}-->
<!--                            </el-checkbox>-->
<!--                        </td>-->
<!--                    </tr>-->
                </table>

                <el-form-item>
                    <el-button
                        type="success"
                        size="medium"
                        @click="save"
                        :loading="btnFromLoading || loading"
                    >{{ $t('Update Settings') }}
                    </el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script type="text/babel">
import GenericPromo from '../../Promos/GenericPromo';

export default {
    name: 'ExperimentalFeaturesSettings',
    components: {
        GenericPromo
    },
    data() {
        return {
            btnFromLoading: false,
            loading: false,
            settings: {},
            settings_loaded: false,
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
        };
    },
    methods: {
        save() {
            this.btnFromLoading = true;
            this.$post('setting/experiments', {
                ...this.settings
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
                    this.btnFromLoading = false;
                });
        },
        fetchSettings() {
            this.loading = true;
            this.$get('setting/experiments')
                .then(response => {
                    this.settings = response.settings;
                })
                .catch((error) => {
                    console.log(error);
                })
                .finally(() => {
                    this.loading = false;
                    this.settings_loaded = true;
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
        this.fetchSettings();
        this.fetchCampaign('');
        this.changeTitle(this.$t('Experimental Settings'));
    }
};
</script>
