<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <email-header-pop-nav :head_title="$t('Recurring Email Campaigns')" />
                    <span v-show="pagination.total" class="ff_small">({{pagination.total | formatMoney }})</span>
                </h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div class="fc-search-box">
                    <el-input
                        clearable
                        size="mini"
                        v-model="search"
                        @clear="fetch"
                        @keyup.enter.native="fetch"
                        :placeholder="$t('Type and Enter...')"
                    >
                        <el-button @click="fetch" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>

                <el-select
                    size="mini"
                    multiple
                    clearable
                    :placeholder="$t('Filter by Labels')"
                    filterable
                    v-model="labelFilter"
                    class="ml-5"
                    style="max-width: 194px;"
                >
                    <el-option
                        v-for="label in options.labels"
                        :key="label.id"
                        :value="label.id"
                        :label="label.title"
                    >
                        <span
                            :style="'background:'+label.settings.color+';padding: 2px 5px 4px 5px;border-radius: 4px;'"
                        >
                            {{ label.title }}
                        </span>
                    </el-option>
                </el-select>

                <template v-if="hasPermission('fcrm_manage_emails')">
                    <el-button class="ml-5" icon="el-icon-plus" size="small"
                               type="primary"
                               @click="$router.push({name: 'create_recurring_campaign'})">
                        {{ $t('Create New Recurring Campaign') }}
                    </el-button>
                </template>

                <el-button @click="$router.push({ name: 'import_recurring_campaigns' })" size="small" type="info"
                           icon="el-icon-upload">
                    {{ $t('Import') }}
                </el-button>

                <el-dropdown trigger="click">
                    <span class="el-dropdown-link">
                        <i style="cursor: pointer;" class="el-icon-more icon-90degree el-icon--right"></i>
                    </span>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item class="fc_dropdown_action">
                            <span class="el-popover__reference" @click="showLabelDialog">
                                {{ $t('Manage Labels') }}
                            </span>
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>

            </div>
        </div>

        <div v-if="selection" class="fluentcrm-header-secondary">
            <bulk-campaign-actions
                :selectedCampaigns="selectedCampaigns"
                :options="options"
                @refetch="fetch"
            />

        </div>

        <div class="fluentcrm_body fluentcrm_pad_b_20" style="position: relative;">
            <div v-if="loading" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading && !app_loaded" :rows="7"></el-skeleton>
            <div v-if="pagination.total" class="fluentcrm_sequences_table">
                <el-table stripe
                          border
                          :data="campaigns"
                          @sort-change="handleSortable"
                          @selection-change="onSelection">
                    <el-table-column type="selection" width="45"/>

                    <el-table-column sortable property="title" width="250" :label="$t('Title')" prop="title">
                        <template slot-scope="scope">
                            <router-link
                                :to="{ name: 'view_recurring_campaign', params: { campaign_id: scope.row.id }, query: { t: (new Date()).getTime() }}">
                                {{ scope.row.title }}
                                <el-tag size="mini" :type="scope.row.status == 'active' ? 'success' : 'info'">
                                    {{ scope.row.status }}
                                </el-tag>
                            </router-link>
                            <el-button @click="$router.push({ name: 'past_recurring_emails', params: { campaign_id: scope.row.id } })" size="mini" type="info" v-if="scope.row.has_draft">View Draft Email</el-button>
                        </template>
                    </el-table-column>

                    <el-table-column width="250" :label="$t('Labels')">
                        <template slot-scope="scope">
                            <div v-if="scope.row.labels" class="fc_funnel_labels">
                                <el-tag v-for="(label, index) in scope.row.labels" :key="index" size="mini" :style="'background:' + label.color">
                                    {{  label.title }}
                                    <Confirm @yes="applyLabels(scope.row, label.id, 'detach')" :message="$t('Remove_Label_From_campaign_Message')">
                                        <i slot="reference" class="el-tag__close el-icon-close"></i>
                                    </Confirm>
                                </el-tag>
                            </div>
                        </template>
                    </el-table-column>

                    <el-table-column width="220" :label="$t('Description')">
                        <template slot-scope="scope">
                            {{ getDescription(scope.row.settings) }}
                            <span v-if="scope.row.status == 'active' && !scope.row.has_draft" class="fc_small" :title="nsHumanDiffTime(scope.row.scheduled_at)"> <br />Next Schedule: {{ scope.row.scheduled_at }}</span>
                        </template>
                    </el-table-column>

                    <el-table-column width="100" :label="$t('Broadcasts')">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'past_recurring_emails', params: { campaign_id: scope.row.id } }">
                                {{ scope.row.emails_count }}
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column width="180" sortable property="created_at" :label="$t('Created at')"
                                     prop="created_at">
                        <template slot-scope="scope">
                            <span>{{ scope.row.created_at | nsHumanDiffTime }}</span>
                        </template>
                    </el-table-column>

                    <el-table-column fixed="right" :label="$t('Actions')" min-width="130">
                        <template slot-scope="scope">
                            <el-tooltip effect="dark" :content="$t('Edit Emails')" placement="top">
                                <el-button
                                    type="info"
                                    size="mini"
                                    icon="el-icon-edit"
                                    @click="$router.push({name: 'view_recurring_campaign', params: { campaign_id: scope.row.id }, query: { t: (new Date()).getTime() }})"
                                >{{ $t('edit') }}
                                </el-button>
                            </el-tooltip>

                            <el-popover :visible="isOpenCampaignAction" placement="bottom" popper-class="fc-funnel-actions-popover" :width="200">
                                <div ref="fcFunnelActions">
                                    <div v-if="!showActionButtons" class="fc_funnel_action_header" @click="handleBackAction"><i class="el-icon-back"></i> {{ $t('Back') }}</div>
                                    <div v-if="showActionButtons" class="fc_funnel_acton_field">
                                        <el-button
                                            class="fc_funnel_action_btn"
                                            link
                                            icon="el-icon-copy-document"
                                            @click="duplicateCampaign(scope.row)">
                                            {{ $t('Duplicate') }}
                                        </el-button>
                                        <el-button
                                            class="fc_funnel_action_btn"
                                            link
                                            icon="el-icon-price-tag"
                                            @click="applyLabelSetting(scope.row)">
                                            {{ $t('Apply Labels') }}
                                        </el-button>
                                        <el-button
                                            v-if="hasPermission('fcrm_manage_emails')"
                                            class="fc_funnel_action_btn"
                                            link
                                            icon="el-icon-download"
                                            @click="exportRecurringCampaign(scope.row)">
                                            {{ $t('Export Campaign') }}
                                        </el-button>
                                        <confirm placement="top-start" :message="$t('Rec_Camp_Delete_Alert')"
                                                 @yes="deleteCampaigns([scope.row.id])">
                                            <span slot="reference">
                                                <el-button class="fc_funnel_action_btn" icon="el-icon-delete">
                                                    {{ $t('Delete Automation') }}
                                                </el-button>
                                            </span>
                                        </confirm>
                                    </div>
                                    <el-form class="fc_label_form" v-if="showApplyLabelSetting">
                                        <el-form-item :label="$t('Labels')">
                                            <el-select v-model="selectedLabels" multiple placeholder="Select Labels">
                                                <el-option
                                                    v-for="label in options.labels"
                                                    :key="label.id"
                                                    :label="label.title"
                                                    :value="label.id">
                                                </el-option>
                                            </el-select>
                                        </el-form-item>
                                        <el-form-item>
                                            <el-button type="primary" size="small" class="fc_primary_button" @click.native="applyLabels(scope.row, [], 'attach')">
                                                {{ $t('Apply') }}
                                            </el-button>
                                        </el-form-item>
                                    </el-form>
                                </div>

                                <template #reference>
                                    <span ref="buttonRefMoreFiled" class="fbs_show_board_show_section_actions"
                                          @click="toggleComponent">
                                        <i style="font-weight: bold; cursor: pointer;" class="el-icon-more icon-90degree el-icon--right"></i>
                                    </span>
                                </template>
                            </el-popover>

                        </template>
                    </el-table-column>
                </el-table>

                <el-row>
                    <el-col :md="12" :sm="24">
                        &nbsp;
                    </el-col>
                    <el-col :md="12" :sm="24">
                        <pagination :pagination="pagination" @fetch="fetch"/>
                    </el-col>
                </el-row>
            </div>
            <div v-else>
                <div v-if="!loading" class="fluentcrm_hero_box">
                    <h2>{{ $t('Rec_Camp_Empty_Alert') }}</h2>
                    <el-button icon="el-icon-plus" size="small" type="success"
                               @click="$router.push({name: 'create_recurring_campaign'})">
                        {{ $t('Create New Recurring Campaign') }}
                    </el-button>
                </div>
            </div>
        </div>

        <labels v-if="showingLabelsConfig" :open="showingLabelsConfig" @close="closeDrawer" @callFetchLabels="fetchLabels"/>
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import Pagination from '@/Pieces/Pagination';
import EmailHeaderPopNav from '@/Pieces/EmailHeaderPopNav.vue';
import Labels from '@/Modules/Labels/Labels';
import BulkCampaignActions from './_BulkCampaignActions.vue';

export default {
    name: 'RecurringCampaigns',
    components: {
        Confirm,
        Pagination,
        EmailHeaderPopNav,
        Labels,
        BulkCampaignActions
    },
    data() {
        return {
            loading: false,
            deleting: false,
            search: '',
            dialogVisible: false,
            app_loaded: false,
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            order: 'desc',
            orderBy: 'id',
            selection: false,
            campaigns: [],
            selectedCampaigns: [],
            duplicating: false,
            showingLabelsConfig: false,
            options: {
                labels: []
            },
            labelFilter: [],
            selectedLabels: [],
            isOpenCampaignAction: false,
            showApplyLabelSetting: false,
            showActionButtons: false
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            const query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                labels: this.labelFilter,
                with: ['stats'],
                order: this.order,
                orderBy: this.orderBy,
                search: this.search
            };

            this.$get('recurring-campaigns', query).then(response => {
                this.campaigns = response.campaigns.data;
                this.pagination.total = response.campaigns.total;
            })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                    this.app_loaded = true;
                });
        },
        deleteSelected() {
            const ids = [];
            this.each(this.selectedCampaigns, (campaign) => {
                ids.push(campaign.id);
            });
            this.deleteCampaigns(ids, () => {
                this.selectedCampaigns = [];
                this.selection = false;
            });
        },
        onSelection(campaigns) {
            this.selection = !!campaigns.length;
            this.selectedCampaigns = campaigns;
        },
        deleteCampaigns(campaignIds, callback) {
            this.$post('recurring-campaigns/delete-bulk', {
                campaign_ids: campaignIds
            })
                .then(response => {
                    if (callback) {
                        callback(response)
                    }
                    this.fetch();
                    this.$notify.success(response.message);
                })
                .catch(errors => {
                    this.handleError(errors);
                });
        },
        duplicateCampaign(campaign) {
            this.duplicating = true;
            this.$post(`recurring-campaigns/${campaign.id}/duplicate`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'view_recurring_campaign',
                        params: {
                            campaign_id: response.campaign_id
                        }
                    })
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.duplicating = false;
                });
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.orderBy = sorting.prop;
                this.order = 'desc';
            } else {
                this.orderBy = sorting.prop;
                this.order = 'asc';
            }
            this.fetch();
        },
        getDescription(settings) {
            const schedule = settings.scheduling_settings;

            const autoString = (schedule.send_automatically == 'yes') ? '(automatically)' : '(manually)';

            if (schedule.type == 'daily') {
                return `Broadcasts daily at ${schedule.time} ${autoString}`;
            }

            if (schedule.type == 'weekly') {
                return `Broadcasts every ${this.getFullDayName(schedule.day)} at ${schedule.time} ${autoString}`
            }

            if (schedule.type == 'monthly') {
                return `Broadcasts every ${this.getDayName(schedule.day)} of a month at ${schedule.time} ${autoString}`
            }

            return '--';
        },
        getFullDayName(day) {
            // Use a switch statement to determine the full day name of the given day
            switch (day) {
                case 'sun':
                    return this.$t('Sunday');
                case 'mon':
                    return this.$t('Monday');
                case 'tue':
                    return this.$t('Tuesday');
                case 'wed':
                    return this.$t('Wednesday');
                case 'thu':
                    return this.$t('Thursday');
                case 'fri':
                    return this.$t('Friday');
                case 'sat':
                    return this.$t('Saturday');
                default:
                    return '--';
            }
        },
        getDayName(day) {
            // Check if the day is a valid day of the month (1-31)
            if (day < 1 || day > 31) {
                return '--';
            }

            // Use a switch statement to determine the suffix for the day
            switch (day) {
                case 1:
                case 21:
                case 31:
                    return `${day}st`;
                case 2:
                case 22:
                    return `${day}nd`;
                case 3:
                case 23:
                    return `${day}rd`;
                default:
                    return `${day}th`;
            }
        },
        exportRecurringCampaign(campaign) {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Recurring_Campaign_Export_Alert'));
                return;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_recurring_campaign',
                campaign_id: campaign.id
            });
        },
        fetchLabels() {
            this.$get('labels')
            .then((response) => {
                this.options.labels = response.labels;
            }).catch((errors) => {
                this.handleError(errors);
            }).finally(() => {

            });
        },
        showLabelDialog() {
            this.showingLabelsConfig = true;
        },
        closeDrawer() {
            this.showingLabelsConfig = false;
        },
        handleBackAction() {
            this.showActionButtons = true;
            this.showApplyLabelSetting = false;
        },
        toggleComponent() {
            this.isOpenCampaignAction = !this.isOpenCampaignAction;
            if (this.isOpenCampaignAction) {
                this.showActionButtons = true;
                setTimeout(() => {
                    document.addEventListener('click', this.handleClickOutside);
                }, 0);
            } else {
                document.removeEventListener('click', this.handleClickOutside);
            }
        },
        handleClickOutside(event) {
            if (!this.$refs.fcFunnelActions?.contains(event.target)) {
                this.closePopOver();
            }
        },
        closePopOver() {
            this.isOpenCampaignAction = false;
            this.showApplyLabelSetting = false;
            this.showActionButtons = true;
        },
        applyLabelSetting(campaign) {
            this.showApplyLabelSetting = true;
            this.showActionButtons = false;
            this.selectedLabels = campaign.labels.map(label => label.id);
        },
        applyLabels(campaign, labelId, action = 'attach') {
            this.$put('recurring-campaigns/' + campaign.id + '/update-labels', {
                action: action,
                label_ids: action == 'attach' ? this.selectedLabels : labelId
            })
            .then((response) => {
                this.$notify.success(response.message);
                this.fetch();
            })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {

            });
        }
    },
    watch: {
        labelFilter() {
            this.fetch();
        }
    },
    mounted() {
        this.fetch();
        this.fetchLabels();
        this.changeTitle(this.$t('Recurring Email Campaigns'));
    }
}
</script>
