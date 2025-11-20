<template>
    <div class="fluentcrm_settings_wrapper">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'funnels' }">
                        {{ $t('Automation Funnels') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ $t('All Activities') }}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-actions">
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
            </div>
        </div>

        <div v-loading="loading" style="padding-bottom: 30px;" class="fluentcrm_body fluentcrm_automation_funnel_reports">
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
                        <individual-progress :funnel="props.row.funnel" :funnel_subscriber="props.row"
                                             :sequences="props.row.funnel.actions"/>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Contact')"
                                 width="260"
                >
                    <template slot-scope="scope">
                        <contact-card trigger_type="click" display_key="full"
                                      :subscriber="scope.row.subscriber">
                        </contact-card>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Automation')">
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'edit_funnel', params: { funnel_id: scope.row.funnel.id } }">
                            {{scope.row.funnel.title}}
                        </router-link>
                        <span>({{ scope.row.status }})</span>
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
                        <confirm v-loading="deleting" @yes="removeFromFunnel(scope.row)">
                            <el-button
                                size="mini"
                                type="danger"
                                slot="reference"
                                icon="el-icon-delete"
                            />
                        </confirm>
                        <el-button v-loading="updating"
                                   @click="changeFunnelSubscriptionStatus(scope.row, 'active')"
                                   v-if="scope.row.status == 'cancelled'" type="info" size="mini">
                            {{ $t('Resume') }}
                        </el-button>
                        <el-button v-loading="updating"
                                   @click="changeFunnelSubscriptionStatus(scope.row, 'cancelled')"
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
                    <confirm v-if="selectedIds.length" v-loading="deleting" @yes="bulkRemove()">
                        <el-button
                            v-loading="deleting"
                            size="mini"
                            type="danger"
                            slot="reference"
                            icon="el-icon-delete"
                        >{{ $t('Delete Selected Contacts') }} ({{ selectedIds.length }})
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
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import ContactCard from '@/Pieces/CantactCardPop.vue';
import IndividualProgress from './parts/_IndividualProgress';
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'FunnelSubscribers',
    props: [],
    components: {
        Pagination,
        ContactCard,
        IndividualProgress,
        Confirm
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
            selectedIds: [],
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

            this.$get('funnels/all-activities', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                with: withData,
                search: this.search,
                status: this.selected_status,
                sequence_id: this.selected_sequence
            })
                .then((response) => {
                    this.subscribers = response.activities.data;
                    this.pagination.total = response.activities.total;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                    this.loading_first = false;
                });
        },
        rowStatusClass({row}) {
            return 'fc_table_row_' + row.status;
        },
        removeFromFunnel(row) {
            this.deleting = true;
            this.$del(`funnels/${row.funnel_id}/subscribers`, {
                subscriber_ids: [row.subscriber_id]
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
            if (!this.selectedIds.length) {
                this.$notify.error(this.$t('Please select subscribers first'));
                return false;
            }
            this.deleting = true;
            this.$post('funnels/remove-bulk-subscribers', {
                funnel_subscriber_ids: this.selectedIds
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.selectedIds = [];
                    this.fetchSubscribers();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        changeFunnelSubscriptionStatus(row, status) {
            this.updating = true;
            this.$put(`funnels/${row.funnel_id}/subscribers/${row.subscriber_id}/status`, {
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
                selected.push(item.id);
            });
            this.selectedIds = selected;
        }
    },
    mounted() {
        this.fetchSubscribers();
        this.changeTitle(this.$t('All Automation Activities'));
    }
}
</script>
