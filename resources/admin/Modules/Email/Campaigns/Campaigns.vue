<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <email-header-pop-nav :head_title="$t('Email Campaigns')" />
                    <span v-show="pagination.total" class="ff_small">({{ pagination.total | formatMoney }})</span>
                </h3>
            </div>
            <div v-if="hasPermission('fcrm_manage_emails')" class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-input
                    v-if="!selection"
                    size="mini"
                    @keyup.enter.native="fetch()"
                    v-model="searchBy"
                    :placeholder="$t('Search by title...')"
                    class="mr-5"
                >
                    <el-button @click="fetch()" slot="append" icon="el-icon-search"></el-button>
                </el-input>

                <el-select
                    size="mini"
                    multiple
                    clearable
                    :placeholder="$t('Filter by status')"
                    v-model="filterByStatuses"
                    class="mr-5"
                    @change="fetch"
                >
                    <el-option
                        v-for="status in statuses"
                        :key="status.key"
                        :value="status.key"
                        :label="status.label"
                    />
                </el-select>

                <el-select
                    size="mini"
                    multiple
                    clearable
                    :placeholder="$t('Filter by Labels')"
                    filterable
                    v-model="labelFilter"
                    class="mr-5"
                    @change="fetch"
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

                <el-button icon="el-icon-plus" size="small" type="primary" @click="create">
                    {{ $t('Create New Campaign') }}
                </el-button>
                <el-button @click="$router.push({ name: 'import_email_campaigns' })" size="small" type="info"
                           icon="el-icon-upload">
                    {{ $t('Import') }}
                </el-button>
                <inline-doc :doc_id="389" />

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

        <div v-if="loading" class="fc_loading_bar" style="position: relative;">
            <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
        </div>

        <div v-if="selection" class="fluentcrm-header-secondary" style="position: relative;">
            <el-row :gutter="24">
                <el-col :md="12" :sm="24">
                    <bulk-campaign-actions
                        :selectedCampaigns="selectedCampaigns"
                        :options="options"
                        @refetch="fetch"/>
                    &nbsp;
                </el-col>
            </el-row>
        </div>

        <div class="fluentcrm_body">
            <el-skeleton style="padding: 20px;" v-if="loading && !pagination.total" :rows="10"></el-skeleton>
            <div v-if="pagination.total" class="campaigns-table" style="padding-bottom: 10px;">
                <el-table
                    border
                    @sort-change="handleSortable"
                    stripe
                    v-loading="loading"
                    :data="campaigns"
                    @selection-change="onSelection">
                    <el-table-column type="selection" fixed :width="45"/>

                    <el-table-column type="expand">
                        <template slot-scope="scope">
                            <div class="fluentcrm_title_cards">
                                <h3 style="margin:0;">{{ $t('Quick Stats') }}</h3>
                                <ul v-if="scope.row.stats" class="fluentcrm_inline_stats">
                                    <li>
                                        <span class="fluentcrm_digit">{{ scope.row.stats.sent || '--' }}</span>
                                        <p>{{ $t('Sent') }}</p>
                                    </li>
                                    <li :title="scope.row.stats.views">
                                        <span class="fluentcrm_digit">
                                            {{ getPercent(scope.row.stats.views, scope.row.stats.sent) }}
                                        </span>
                                        <p>{{ $t('Opened') }}</p>
                                    </li>
                                    <li :title="scope.row.stats.clicks">
                                        <span class="fluentcrm_digit">{{
                                                getPercent(scope.row.stats.clicks, scope.row.stats.sent)
                                            }}</span>
                                        <p>{{ $t('Clicked') }}</p>
                                    </li>
                                    <li :title="scope.row.stats.unsubscribers">
                                            <span
                                                class="fluentcrm_digit">{{
                                                    getPercent(scope.row.stats.unsubscribers, scope.row.stats.sent)
                                                }}</span>
                                        <p>{{ $t('Unsubscribed') }}</p>
                                    </li>
                                    <li v-if="scope.row.stats.revenue">
                                            <span class="fluentcrm_digit">
                                                {{ scope.row.stats.revenue.total }}
                                            </span>
                                        <p>{{ scope.row.stats.revenue.label }}</p>
                                    </li>
                                </ul>
                            </div>
                            <div class="fluentcrm_card_actions">
                                <el-button
                                    v-show="scope.row.status !== 'draft'"
                                    type="text"
                                    @click="routeCampaign(scope.row)"
                                    size="mini"
                                    icon="el-icon-edit"
                                    class="fluentcrm-report-text-btn"
                                >
                                    {{ $t('Reports') }}
                                </el-button>
                                <template class="fluentcrm-delete-duplicate-campaign"
                                          v-if="hasPermission('fcrm_manage_emails')">
                                    <el-button
                                        class="fluentcrm-duplicate-campaign"
                                        type="text"
                                        @click="exportCampaign(scope.row)"
                                        size="mini"
                                        icon="el-icon-download"
                                    >
                                        {{ $t('Export') }}
                                    </el-button>
                                    <el-button
                                        class="fluentcrm-duplicate-campaign"
                                        type="text"
                                        @click="cloneCampaign(scope.row)"
                                        size="mini"
                                        icon="el-icon-document-copy"
                                    >
                                        {{ $t('Duplicate') }}
                                    </el-button>
                                    <confirm v-show="scope.row.status !== 'working'" placement="top-start"
                                             @yes="remove(scope.row)">
                                        <el-button
                                            class="fluentcrm-delete-campaign"
                                            size="mini"
                                            type="text"
                                            slot="reference"
                                            icon="el-icon-delete"
                                        >
                                            {{ $t('Delete') }}
                                        </el-button>
                                    </confirm>
                                </template>
                            </div>
                        </template>
                    </el-table-column>

                    <el-table-column sortable property="title" :min-width="250" :label="$t('Title')" prop="title">
                        <template slot-scope="scope">
                            <a :href="generateUrl(scope.row)">{{ scope.row.title }}</a>
                            <span class="fluentcrm_cart_cta" v-if="scope.row.status == 'draft'">
                                <el-button @click="routeCampaign(scope.row)" size="mini" type="danger">
                                    {{ $t('Setup') }}
                                </el-button>
                            </span>
                            <span v-else>
                                <el-button @click="showPreview(scope.row)" icon="el-icon-view" size="mini" type="text">

                                </el-button>
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column sortable :width="120" property="status" :label="$t('Status')">
                        <template slot-scope="scope">
                            {{ scope.row.status | ucFirst }}
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
                    <el-table-column sortable property="created_at" :width="180" :label="$t('Created at')">
                        <template slot-scope="scope">
                            <span :title="scope.row.created_at">{{ scope.row.created_at | nsHumanDiffTime }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column sortable property="scheduled_at" :width="180" :label="$t('Broadcast')">
                        <template slot-scope="scope">
                            <span v-if="scope.row.status == 'processing'">soon...</span>
                            <span v-else-if="scope.row.scheduled_at && scope.row.status != 'draft'"
                                  :title="scope.row.scheduled_at">{{ scope.row.scheduled_at | nsHumanDiffTime }}</span>
                            <span v-else>{{ $t('n/a') }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column sortable property="recipients_count" :width="130" :label="$t('Recipients')">
                        <template slot-scope="scope">
                            <span v-if="scope.row.recipients_count">{{ formatMoney(scope.row.recipients_count) }} <span
                                v-if="scope.row.status == 'processing'">++</span> </span>
                            <span v-else>{{ $t('n/a') }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column sortable :sort-method="customOpenRateSort" :width="110" :label="$t('Open Rate')">
                        <template slot="header">
                            {{ $t('Open Rate') }}
                            <el-tooltip class="item" effect="dark"
                                        :content="$t('open_rate_info')"
                                        placement="top-start">
                                <i class="el-icon el-icon-info"></i>
                            </el-tooltip>
                        </template>
                        <template slot-scope="scope">
                            <span :title="$t('Open Rate')" v-if="scope.row.stats.views && scope.row.stats.sent"
                                  class="fluentcrm_digit">
                                    {{ getPercent(scope.row.stats.views, scope.row.stats.sent) }}
                                </span>
                            <span v-else>--</span>
                        </template>
                    </el-table-column>
                    <el-table-column sortable :sort-method="customClickRateSort" :width="120" :label="$t('Click Rate')">
                        <template slot="header">
                            {{ $t('Click Rate') }}
                            <el-tooltip class="item" effect="dark"
                                        :content="$t('click_rate_info')"
                                        placement="top-start">
                                <i class="el-icon el-icon-info"></i>
                            </el-tooltip>
                        </template>
                        <template slot-scope="scope">
                            <span :title="$t('Click Rate')" v-if="scope.row.stats.clicks && scope.row.stats.sent"
                                  class="fluentcrm_digit">
                                 {{ getPercent(scope.row.stats.clicks, scope.row.stats.sent) }}
                                </span>
                            <span v-else>--</span>
                        </template>
                    </el-table-column>
                    <el-table-column :width="130" :label="$t('Revenue')">
                        <template slot-scope="scope">
                            <span :title="$t('Revenue')" v-if="scope.row.stats.revenue">
                                {{ scope.row.stats.revenue.total }} <span
                                style="font-size: 70%">{{ scope.row.stats.revenue.currency }}</span>
                            </span>
                            <span v-else>--</span>
                        </template>
                    </el-table-column>
                </el-table>

                <el-row>
                    <el-col :md="12">
                        &nbsp;
                    </el-col>
                    <el-col :md="12">
                        <pagination :pagination="pagination" @fetch="fetch"/>
                    </el-col>
                </el-row>
            </div>
            <div v-else>
                <div v-if="!loading" class="fluentcrm_hero_box">
                    <h2>{{ $t('Cam_Looks_lydnbaecy') }}</h2>
                    <el-button icon="el-icon-plus" size="small" type="primary" @click="create">
                        {{ $t('Cam_Create_YFEC') }}
                    </el-button>
                </div>
            </div>
        </div>

        <UpdateOrCreate
            @saved="saved"
            @toggleDialog="toggleDialog"
            :dialogVisible="dialogVisible"
            :dialogTitle="dialogTitle"
        />

        <labels v-if="showingLabelsConfig" :open="showingLabelsConfig" @close="closeDrawer" @callFetchLabels="fetchLabels" />
        
        <div v-if="previewingCampaign">
            <email-preview @modalClosed="() => { previewingCampaign = null; }" :auto_load="true" :by_campaign_id="true" :campaign="previewingCampaign"  />
        </div>
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import Pagination from '@/Pieces/Pagination';
import UpdateOrCreate from './UpdateOrCreate';
import InlineDoc from '@/Modules/Documentation/InlineDoc';
import EmailHeaderPopNav from '@/Pieces/EmailHeaderPopNav.vue';
import Labels from '@/Modules/Labels/Labels';
import BulkCampaignActions from './_BulkCampaignActions.vue';
import EmailPreview from '@/Pieces/EmailElements/EmailPreview';

export default {
    name: 'Campaigns',
    components: {
        BulkCampaignActions,
        Labels,
        Confirm,
        Pagination,
        UpdateOrCreate,
        InlineDoc,
        EmailHeaderPopNav,
        EmailPreview
    },
    data() {
        return {
            campaigns: [],
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            loading: true,
            dialogVisible: false,
            dialogTitle: this.$t('Add Campaign Title'),
            searchBy: '',
            filterByStatuses: [],
            statuses: [
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
            ],
            sort_type: 'DESC',
            sort_by: 'id',
            deleting: false,
            selection: false,
            selectedCampaigns: [],
            showingLabelsConfig: false,
            options: {
                labels: []
            },
            labelFilter: [],
            previewingCampaign: null
        };
    },
    methods: {
        create() {
            this.toggleDialog(true);
        },
        toggleDialog(isVisible) {
            this.dialogVisible = isVisible;
        },
        isNotEditable(campaign) {
            return [
                'archived', 'working'//, 'incomplete', 'purged'
            ].indexOf(campaign.status) >= 0;
        },
        scheduledAt(date) {
            if (date === null) {
                return this.$t('Not Scheduled');
            }

            return this.nsDateFormat(date, 'MMMM Do, YYYY [at] h:mm A');
        },
        saved(campaign) {
            this.fetch();
            this.$router.push({
                name: 'campaign',
                params: {id: campaign.id}
            });
        },
        setup() {
            let queryParams = this.$route.query;

            if (window.fcrm_camp_sub_params) {
                queryParams = window.fcrm_camp_sub_params;
            }

            this.sort_by = queryParams.sort_by;
            this.sort_type = queryParams.sort_type;
            this.searchBy = queryParams.searchBy;
            this.filterByStatuses = queryParams.statuses;
            this.with = queryParams.with;

            if (queryParams.page) {
                this.pagination.current_page = parseInt(queryParams.page);
            }
            if (queryParams.per_page) {
                this.pagination.per_page = parseInt(queryParams.per_page);
            }

            return false;
        },
        fetch() {
            this.loading = true;
            const query = {
                sort_by: this.sort_by,
                sort_type: this.sort_type,
                searchBy: this.searchBy,
                statuses: this.filterByStatuses,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                labels: this.labelFilter,
                with: ['stats']
            };

            const params = {};

            Object.keys(query).forEach(key => {
                if (query[key] !== undefined) {
                    params[key] = query[key];
                }
            });

            window.fcrm_camp_sub_params = params;
            params.t = Date.now();

            this.$router.replace({
                name: 'campaigns', query: params
            });

            this.$get('campaigns', query).then(response => {
                this.loading = false;
                this.campaigns = response.campaigns.data;
                this.pagination.total = response.campaigns.total;
                this.registerHeartBeat();
                this.$bus.$emit('refresh-stats');
            });
        },
        searchCampaigns() {
            this.fetch();
        },
        remove(campaign) {
            this.$del(`campaigns/${campaign.id}`)
                .then(response => {
                    this.fetch();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: this.$t('Campaign deleted.'),
                        offset: 19
                    });
                }).catch(r => {
                this.$message(r.message, this.$t('Oops!'), {
                    center: true,
                    type: 'warning',
                    confirmButtonText: this.$t('Close'),
                    dangerouslyUseHTMLString: true,
                    callback: action => {
                        this.$router.push({
                            name: 'campaigns',
                            query: {t: (new Date()).getTime()}
                        });
                    }
                });
            });
        },
        deleteSelected() {
            const campaignIds = [];
            this.each(this.selectedCampaigns, (campaign) => {
                campaignIds.push(campaign.id);
            });

            this.doing_action = true;
            this.$post('campaigns/do-bulk-action', {
                campaign_ids: campaignIds
            })
                .then(res => {
                    this.$notify.success(res.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.doing_action = false;
                });
        },
        onSelection(campaigns) {
            this.selection = !!campaigns.length;

            this.selectedCampaigns = campaigns;
        },
        sortCampaigns(sort) {
            this.sort_type = sort.order;
            this.sort_by = sort.prop;
            this.fetch();
        },
        registerHeartBeat() {
            jQuery(document).off('heartbeat-send').on('heartbeat-send', (event, data) => {
                data.fluentcrm_campaign_ids = this.campaigns.map(c => c.id);
            });

            jQuery(document).off('heartbeat-tick').on('heartbeat-tick', (event, data) => {
                if (data.fluentcrm_campaigns) {
                    for (const id in data.fluentcrm_campaigns) {
                        const status = data.fluentcrm_campaigns[id];
                        this.campaigns.forEach(c => {
                            if (c.id === id && c.status !== status) {
                                c.status = status;
                            }
                        });
                    }
                }
            });
        },
        routeCampaign(campaign) {
            let routeName = 'campaign-view';
            if (campaign.status === 'draft') {
                routeName = 'campaign';
            }
            this.$router.push({
                    name: routeName,
                    params: {
                        id: campaign.id
                    },
                    query: {
                        t: (new Date()).getTime(),
                        step: (campaign.next_step && parseInt(campaign.next_step) <= 3) ? campaign.next_step : 0
                    }
                }
            );
        },
        generateUrl(campaign) {
            let routeName = 'campaign-view';
            if (campaign.status === 'draft') {
                routeName = 'campaign';
            }
            return this.$router.resolve({
                name: routeName,
                params: {id: campaign.id},
                query: {
                    t: new Date().getTime(),
                    step: campaign.next_step && parseInt(campaign.next_step) <= 3 ? campaign.next_step : 0
                }
            }).href;
        },
        customOpenRateSort(a, b) {
            const aRate = (a.stats && a.stats.sent && a.stats.views) ? (a.stats.views / a.stats.sent) : 0;
            const bRate = (b.stats && b.stats.sent && b.stats.views) ? (b.stats.views / b.stats.sent) : 0;

            return aRate - bRate;
        },
        customClickRateSort(a, b) {
            const aRate = (a.stats && a.stats.sent && a.stats.clicks) ? (a.stats.clicks / a.stats.sent) : 0;
            const bRate = (b.stats && b.stats.sent && b.stats.clicks) ? (b.stats.clicks / b.stats.sent) : 0;

            return aRate - bRate;
        },
        getPercent(number, total) {
            if (!total || !number) {
                return '--';
            }
            return parseFloat(number / total * 100).toFixed(2) + '%';
        },
        cloneCampaign(campaign) {
            this.loading = true;
            this.$post(`campaigns/${campaign.id}/duplicate`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.routeCampaign(response.campaign);
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.sort_by = sorting.prop;
                this.sort_type = 'DESC';
            } else {
                this.sort_by = sorting.prop;
                this.sort_type = 'ASC';
            }
            this.fetch();
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
        applyLabels(campaign, labelId, action = 'attach') {
            this.$put('campaigns/' + campaign.id + '/update-labels', {
                action: action,
                label_ids: labelId
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
        },
        exportCampaign(campaign) {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Campaign_Export_Alert'));
                return;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_email_campaign',
                campaign_id: campaign.id
            });
        },
        showPreview(campaign) {
            this.previewingCampaign = campaign;
        }
    },
    watch: {
        filterByStatuses() {
            this.fetch();
        }
    },
    mounted() {
        this.setup();
        this.fetch();
        this.fetchLabels();

        this.changeTitle(this.$t('Email Campaigns'));
    }
};
</script>

<style lang="scss">
.fluentcrm-campaigns {
    .action-buttons {
        margin: 0 0 15px 0;
        text-align: right;

        .el-input__inner {
            background: #fff !important;
        }
    }

    .status {
        display: inline-block;
        font-size: 10px;
        width: 80px;

        &-draft {
            color: #909399;
            border: solid 1px #909399;
        }

        &-pending {
            color: #409eff;
            border: solid 1px #409eff;
        }

        &-archived {
            color: #67c23a;
            border: solid 1px #67c23a;
        }

        &-failed,
        &-incomplete {
            color: #f56c6c;
            border: solid 1px #f56c6c;
        }

        &-working {
            color: #a7cc90;
            border: solid 1px #a7cc90;
            opacity: 1;
            position: relative;
            transition: opacity linear 0.1s;

            &:before {
                animation: 2s linear infinite working;
                border: solid 3px #eee;
                border-bottom-color: #a7cc90;
                border-radius: 50%;
                content: "";
                height: 10px;
                left: 10px;
                opacity: inherit;
                position: absolute;
                top: 50%;
                transform: translate3d(-50%, -50%, 0);
                transform-origin: center;
                width: 10px;
                will-change: transform;
            }
        }

        &-purged {
            color: #e6a23d;
            border: solid 1px #e6a23d;
        }

        &-purged,
        &-failed,
        &-working,
        &-draft,
        &-incomplete,
        &-archived,
        &-pending {
            padding: 0px 4px;
            display: inline-block;
            border-radius: 4px;

        }
    }
}

@keyframes working {
    0% {
        transform: translate3d(-50%, -50%, 0) rotate(0deg);
    }
    100% {
        transform: translate3d(-50%, -50%, 0) rotate(360deg);
    }
}
</style>
