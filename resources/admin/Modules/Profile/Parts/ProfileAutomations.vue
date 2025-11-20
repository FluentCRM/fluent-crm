<template>
    <div class="fc_profile_sub">
        <div class="fc_profile_sub_header d-flex items-center justify-between" style="margin-bottom: 10px;margin-top: 20px;">
            <h3>{{$t('Automations')}}</h3>

            <div class="fc_profile_sequences_bulk_action d-flex items-end">
                <template>
                    <el-popover
                        ref="popover"
                        placement="left"
                        width="350"
                        trigger="click">
                        <el-button size="small" slot="reference">{{$t('Add Automation')}}</el-button>
                        <label>{{ $t('Select Automation') }}</label>
                        <option-selector v-model="selected_sequence"
                                         :field="{ option_key: 'automation_funnels', clearable: true, size: 'mini' }"
                        />

                        <el-button @click="addToAutomation" style="margin-top: 20px;"
                                   :disabled="doing_action || !selected_sequence"
                                   v-loading="doing_action"
                                   size="mini" type="primary"
                                   class="mt-5 ml-5">
                            {{$t('Add To Automation')}}
                        </el-button>
                    </el-popover>

                </template>

            </div>
        </div>

        <el-table
            :empty-text="$t('No Data Available')"
            stripe
            border
            :data="automations"
            style="width: 100%" v-loading="loading">
            <el-table-column :label="$t('Sequence')">
                <template slot-scope="scope">
                    <lazy-individual-progress :funnel="scope.row.funnel" :subscriber_id="subscriber_id"></lazy-individual-progress>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Started At')">
                <template slot-scope="scope">
                    <span :title="scope.row.created_at">
                        {{ scope.row.created_at | nsHumanDiffTime }}
                    </span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Next Step')">
                <template slot-scope="scope">
                    <span v-if="scope.row.next_sequence_item && scope.row.status != 'completed'">{{scope.row.next_sequence_item.title}}</span>
                    <span v-else>
                        <i :title="$t('Completed')" style="font-size: 22px;" class="el-icon el-icon-success"/>
                    </span>
                    <span v-if="scope.row.status == 'active'" :title="scope.row.next_execution_time">
                        - ({{ scope.row.next_execution_time | nsHumanDiffTime }})
                    </span>
                </template>
            </el-table-column>
            <el-table-column width="160" :label="$t('Status')">
                <template slot-scope="scope">
                    {{scope.row.status}}
                </template>
            </el-table-column>
            <el-table-column width="160" :label="$t('Actions')">
                <template slot-scope="scope">
                    <confirm v-loading="deleting" @yes="removeFromFunnel(scope.row.funnel_id, scope.row.subscriber_id)">
                        <el-button
                            size="mini"
                            type="danger"
                            slot="reference"
                            icon="el-icon-delete"
                        />
                    </confirm>
                    <el-button v-loading="updating" @click="changeFunnelSubscriptionStatus(scope.row.funnel_id, scope.row.subscriber_id, 'active')" v-if="scope.row.status == 'cancelled'" type="info" size="mini">
                        {{$t('Resume')}}
                    </el-button>
                    <el-button v-loading="updating" @click="changeFunnelSubscriptionStatus(scope.row.funnel_id, scope.row.subscriber_id, 'cancelled')" v-if="scope.row.status == 'active'" type="info" size="mini">
                        {{$t('Cancel')}}
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
        <pagination :pagination="pagination" @fetch="fetch"/>
    </div>
</template>

<script type="text/babel">
    import Confirm from '@/Pieces/Confirm';
    import Pagination from '@/Pieces/Pagination';
    import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
    import LazyIndividualProgress from '@/Modules/Funnels/parts/_LazyIndividualProgress';

    export default {
        name: 'ProfileAutomations',
        props: ['subscriber_id'],
        components: {
            Confirm,
            Pagination,
            OptionSelector,
            LazyIndividualProgress
        },
        data() {
            return {
                automations: [],
                loading: false,
                deleting: false,
                pagination: {
                    total: 0,
                    per_page: 10,
                    current_page: 1
                },
                updating: false,
                select_job: {
                    action_name: 'add_to_automation',
                    selected_options: []
                },
                selected_sequence: '',
                doing_action: false
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                this.$get(`funnels/subscriber/${this.subscriber_id}/automations`, {
                    per_page: this.pagination.per_page,
                    page: this.pagination.current_page
                })
                    .then(response => {
                        this.automations = response.automations.data;
                        this.pagination.total = response.automations.total;
                    })
                    .catch((errors) => {
                        this.handleError(errors);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            removeFromFunnel(funnelId, subscriberId) {
                this.deleting = true;
                this.$del(`funnels/${funnelId}/subscribers`, {
                    subscriber_ids: [subscriberId]
                })
                    .then(response => {
                        this.$notify.success(response.message);
                        this.fetch();
                    })
                    .catch((errors) => {
                        this.handleError(errors);
                    })
                    .finally(() => {
                        this.deleting = false;
                    });
            },
            changeFunnelSubscriptionStatus(funnelId, subscriberId, status) {
                this.updating = true;
                this.$put(`funnels/${funnelId}/subscribers/${subscriberId}/status`, {
                    status: status
                })
                    .then(response => {
                        this.$notify.success(response.message);
                        this.fetch();
                    })
                    .catch((errors) => {
                        this.handleError(errors);
                    })
                    .finally(() => {
                        this.updating = false;
                    });
            },
            addToAutomation() {
                this.doing_action = true;
                this.$post('subscribers/do-bulk-action', {
                    action_name: 'add_to_automation',
                    new_status: this.selected_sequence,
                    subscriber_ids: [this.subscriber_id]
                })
                    .then(response => {
                        this.visiblePop = false;
                        this.$notify.success(response.message);
                        this.fetch();
                    })
                    .catch((errors) => {
                        this.handleError(errors);
                    })
                    .finally(() => {
                        this.doing_action = false;
                    });
            }
        },
        mounted() {
            this.fetch();
        }
    }
</script>
