<template>
    <div class="fluentcrm_settings_wrapper">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'funnels' }">
                        {{ $t('Automation Funnels') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item :to="{ name: 'edit_funnel', params: { funnel_id: funnel_id } }">
                        <i class="el-icon-edit"></i>
                        {{ funnel.title }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ $t('Subscribers') }}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-actions">
                <el-tooltip class="item" :openDelay="500" effect="dark" :content="$t('Funnel_Step_Alert')" placement="left">
                    <el-button @click="syncSteps = true" type="default" size="small">
                        {{ $t('Re-apply New Steps') }}
                    </el-button>
                </el-tooltip>
            </div>
        </div>

        <div v-if="stats.metrics.length" style="padding: 20px;" class="fluentcrm_body fc_chart_box">
            <div class="text-align-center fluentcrm_pad_b_30">
                <el-radio-group size="small" v-model="visualization_type">
                    <el-radio-button label="chart">{{ $t('Chart Report') }}</el-radio-button>
                    <el-radio-button label="text">{{ $t('Step Report') }}</el-radio-button>
                    <el-radio-button label="emails">{{ $t('Emails Analytics') }}</el-radio-button>
                </el-radio-group>
            </div>
            <funnel-chart v-if="visualization_type == 'chart'" :stats="stats" :funnel_id="funnel_id"/>
            <funnel-text-report v-else-if="visualization_type == 'text'" :stats="stats" :funnel="funnel"/>
            <funnel-emails v-else-if="visualization_type == 'emails'" :funnel_id="funnel_id"/>

            <h3 class="text-align-center" v-if="stats.total_revenue">
                {{ $t('Fun_Total_Rftf') }}: {{ stats.revenue_currency }} {{ stats.total_revenue_formatted }}</h3>
        </div>

        <div v-if="loading" class="fluentcrm_body fc_chart_box" style="position: relative;">
            <div class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" :rows="8"></el-skeleton>
        </div>

        <div v-loading="loading" class="fluentcrm_body fluentcrm_automation_funnel_reports">
            <div class="fluentcrm_title_cards">
                <div class="fluentcrm_inner_header">
                    <h3 class="fluentcrm_inner_title">{{ $t('Individual Reporting') }}</h3>
                    <div class="fluentcrm_inner_actions d-flex">
                        <div class="fluentcrm_inner_action" style="margin-right: 10px;">
                            {{ $t('Sequence') }}
                            <el-select clearable @change="fetchSubscribers()" :title="$t('Sequence')"
                                       :placeholder="$t('All Sequences')"
                                       style="max-width: 120px;" size="mini" v-model="selected_sequence">
                                <el-option v-for="sequence in sequences" :key="sequence.id" :value="sequence.id"
                                           :label="sequence.title"/>
                            </el-select>
                        </div>
                        <div class="fluentcrm_inner_action" style="margin-right: 10px;">
                            {{ $t('Status') }}
                            <el-select @change="fetchSubscribers()" :title="$t('Status')" :placeholder="$t('Status')"
                                       style="max-width: 120px;" size="mini" v-model="selected_status">
                                <el-option value="" label="All"></el-option>
                                <el-option v-for="(status, statusName) in funnel_statuses" :key="statusName"
                                           :value="statusName"
                                           :label="status"/>
                            </el-select>
                        </div>
                        <div class="fluentcrm_inner_action">
                            <el-input @keyup.enter.native="fetchSubscribers" style="width: 200px" size="mini"
                                      :placeholder="$t('Search')" v-model="search" class="input-with-select">
                                <el-button @click="fetchSubscribers()" slot="append" icon="el-icon-search"></el-button>
                            </el-input>
                        </div>
                    </div>
                </div>
                <el-table
                    :empty-text="$t('No Data Available')"
                    border
                    stripe
                    @selection-change="onSelection"
                    :data="subscribers"
                    :row-class-name="rowStatusClass"
                >
                    <el-table-column type="selection"></el-table-column>
                    <el-table-column type="expand">
                        <template slot-scope="props">
                            <individual-progress :funnel="funnel" :funnel_subscriber="props.row"
                                                 :sequences="sequences"/>
                        </template>
                    </el-table-column>
                    <el-table-column label=""
                                     width="64"
                    >
                        <template slot-scope="scope">
                            <contact-card trigger_type="click" display_key="photo"
                                          :subscriber="scope.row.subscriber">
                            </contact-card>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Contact')">
                        <template slot-scope="scope">
                            <span v-if="scope.row.subscriber">{{
                                    scope.row.subscriber.full_name || scope.row.subscriber.email
                                }}</span>
                            <span v-else>{{ $t('No Subscriber Found') }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Status')">
                        <template slot-scope="scope">
                            <span>{{ scope.row.status }}</span>
                            <el-popover
                                placement="top-start"
                                min-width="200"
                                trigger="hover"
                                :content="$t('Current status of the subscriber in the funnel')"
                            >
                                <i slot="reference" class="el-icon el-icon-info"></i>
                            </el-popover>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Latest Action')">
                        <template slot-scope="scope">
                            <span v-if="scope.row.last_sequence">{{ scope.row.last_sequence.title }}</span>
                            <span
                                v-else-if="scope.row.status == 'pending'">
                                {{ $t('Fun_Waiting_fdoc') }}
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column :label="$t('Next Step')">
                        <template slot-scope="scope">
                            <template v-if="scope.row.status != 'completed'">
                                <span
                                    v-if="scope.row.next_sequence_item">{{ scope.row.next_sequence_item.title }}</span>
                                <span v-if="scope.row.status == 'active'" :title="scope.row.next_execution_time">
                                    - ({{ scope.row.next_execution_time | nsHumanDiffTime }})
                                </span>
                            </template>
                            <span v-else>
                                <i title="Completed" style="font-size: 22px;" class="el-icon el-icon-success"/>
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column width="150" :label="$t('Last Executed At')">
                        <template slot-scope="scope">
                            <span :title="scope.row.last_executed_time">
                                {{ scope.row.last_executed_time | nsHumanDiffTime }}
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column width="150" :label="$t('Created At')">
                        <template slot-scope="scope">
                            <span :title="scope.row.created_at">
                                {{ scope.row.created_at | nsHumanDiffTime }}
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column width="150" :label="$t('Actions')">
                        <template slot-scope="scope">
                            <confirm v-loading="deleting" @yes="removeFromFunnel(scope.row.subscriber_id)">
                                <el-button
                                    size="mini"
                                    type="danger"
                                    slot="reference"
                                    icon="el-icon-delete"
                                />
                            </confirm>
                            <el-button v-loading="updating"
                                       @click="changeFunnelSubscriptionStatus(scope.row.subscriber_id, 'active')"
                                       v-if="scope.row.status == 'cancelled'" type="info" size="mini">
                                {{ $t('Resume') }}
                            </el-button>
                            <el-button v-loading="updating"
                                       @click="changeFunnelSubscriptionStatus(scope.row.subscriber_id, 'cancelled')"
                                       v-if="scope.row.status == 'active'" type="info" size="mini">
                                {{ $t('Cancel') }}
                            </el-button>
                            <el-tooltip v-if="scope.row.source_trigger_name == 'fcrm_manual_attach'" class="item"
                                        effect="dark"
                                        :content="$t('ProfileAutomations.Contact_Added_manually_to_Automation')"
                                        placement="top-start">
                                <i class="el-icon el-icon-info"></i>
                            </el-tooltip>
                        </template>
                    </el-table-column>
                </el-table>
                <el-row :guter="20">
                    <el-col style="padding-top: 10px;" :xs="24" :md="12">
                        <confirm v-if="selectedSubscribers.length" v-loading="deleting" @yes="bulkRemove()">
                            <el-button
                                v-loading="deleting"
                                size="mini"
                                type="danger"
                                slot="reference"
                                icon="el-icon-delete"
                            >{{ $t('Delete Selected Contacts') }} ({{ selectedSubscribers.length }})
                            </el-button>
                        </confirm>
                        <div v-else>&nbsp;</div>
                    </el-col>
                    <el-col :xs="24" :md="12">
                        <pagination :pagination="pagination" @fetch="fetchSubscribers"/>
                    </el-col>
                </el-row>
            </div>
        </div>

        <el-drawer
            class="fc_company_info_drawer"
            :append-to-body="true"
            :size="globalDrawerSize"
            :title="$t('Sync_New_Steps')"
            :visible.sync="syncSteps">
            <div style="padding: 10px 20px;">
                <sync-new-steps @reload="fetchReport(); fetchSubscribers(); syncSteps = false;" :funnel_id="funnel_id" v-if="syncSteps" />
            </div>
        </el-drawer>

    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import ContactCard from '@/Pieces/CantactCardPop.vue';
import IndividualProgress from './parts/_IndividualProgress';
import FunnelChart from './charts/FunnelChart'
import FunnelTextReport from './parts/_FunnelTextReport'
import Confirm from '@/Pieces/Confirm';
import FunnelEmails from './FunnelEmails';
import SyncNewSteps from './parts/_SyncNewSteps';

export default {
    name: 'FunnelSubscribers',
    props: ['funnel_id'],
    components: {
        Pagination,
        ContactCard,
        IndividualProgress,
        FunnelChart,
        FunnelTextReport,
        Confirm,
        FunnelEmails,
        SyncNewSteps
    },
    data() {
        return {
            funnel: {},
            subscribers: [],
            loading: false,
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            sequences: [],
            stats: {
                metrics: [],
                total_revenue: 0,
                revenue_currency: 'USD'
            },
            visualization_type: 'chart',
            search: '',
            deleting: false,
            updating: false,
            selectedSubscribers: [],
            selected_status: '',
            selected_sequence: '',
            funnel_statuses: {
                active: this.$t('Active'),
                completed: this.$t('Completed'),
                cancelled: this.$t('Cancelled'),
                pending: this.$t('Pending')
            },
            loading_first: true,
            syncSteps: false
        }
    },
    methods: {
        fetchSubscribers() {
            this.loading = true;
            let withData = ['funnel', 'sequences'];

            if (!this.loading_first) {
                withData = [];
            }

            this.$get(`funnels/${this.funnel_id}/subscribers`, {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                with: withData,
                search: this.search,
                status: this.selected_status,
                sequence_id: this.selected_sequence
            })
                .then((response) => {
                    this.subscribers = response.funnel_subscribers.data;
                    this.pagination.total = response.funnel_subscribers.total;
                    if (this.loading_first) {
                        this.funnel = response.funnel;
                        this.sequences = response.sequences;
                        this.loading_first = false;
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchReport() {
            this.$get(`funnels/${this.funnel_id}/report`)
                .then(response => {
                    this.stats = response.stats;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                });
        },
        rowStatusClass({row}) {
            return 'fc_table_row_' + row.status;
        },
        removeFromFunnel(subscriberId) {
            this.deleting = true;
            this.$del(`funnels/${this.funnel_id}/subscribers`, {
                subscriber_ids: [subscriberId]
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchSubscribers();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        bulkRemove() {
            if (!this.selectedSubscribers.length) {
                this.$notify.error(this.$t('Please select subscribers first'));
                return false;
            }
            this.deleting = true;
            this.$del(`funnels/${this.funnel_id}/subscribers`, {
                subscriber_ids: this.selectedSubscribers
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.selectedSubscribers = [];
                    this.fetchSubscribers();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        changeFunnelSubscriptionStatus(subscriberId, status) {
            this.updating = true;
            this.$put(`funnels/${this.funnel_id}/subscribers/${subscriberId}/status`, {
                status: status
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchSubscribers();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        onSelection(subscribers) {
            const selected = [];
            this.each(subscribers, (item) => {
                selected.push(item.subscriber_id);
            });
            this.selectedSubscribers = selected;
        }
    },
    mounted() {
        this.fetchSubscribers();
        this.fetchReport();
        this.changeTitle(this.$t('Funnel Report'));
    }
}
</script>

<style lang="scss">
.el-table {
    .fc_table_row_completed {
        background: #f0f9eb;

        td {
            background: #f0f9eb !important;
        }
    }

    .fc_table_row_pending {
        background: oldlace;

        td {
            background: oldlace !important;
        }
    }
}
</style>
