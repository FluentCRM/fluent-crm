<template>
    <div class="fc_profile_sub">
        <div class="fc_profile_sub_header d-flex items-center justify-between" style="margin-bottom: 10px;margin-top: 10px;">
            <h3>{{ $t('Email Sequences') }}</h3>

            <div class="fc_profile_sequences_bulk_action d-flex items-end">
                <template>
                    <el-popover
                        ref="popover"
                        placement="left"
                        width="350"
                        trigger="click">
                        <el-button size="small" slot="reference">{{$t('Add Sequence')}}</el-button>
                        <label>{{ $t('Select Sequence') }}</label>
                        <option-selector v-model="selected_sequence"
                                         :field="{ option_key: 'email_sequences', clearable: true, size: 'mini' }"
                        />

                        <el-button @click="addToSequence" style="margin-top: 20px;"
                                   :disabled="doing_action || !selected_sequence"
                                   v-loading="doing_action"
                                   size="mini" type="primary"
                                   class="mt-5 ml-5">
                            {{$t('Add To Sequence')}}
                        </el-button>
                    </el-popover>

                </template>
            </div>
        </div>

        <el-table
            :empty-text="$t('No Data Available')"
            stripe
            border
            :data="sequences"
            style="width: 100%" v-loading="loading">
            <el-table-column :label="$t('Sequence')">
                <template slot-scope="scope">
                    <span v-if="scope.row.sequence">{{ scope.row.sequence.title }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Started At')">
                <template slot-scope="scope">
                    <span :title="scope.row.created_at">
                        {{ scope.row.created_at | nsHumanDiffTime }}
                    </span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Next Email')">
                <template slot-scope="scope">
                    <span v-if="scope.row.next_sequence">{{ scope.row.next_sequence.title }}</span>
                    <span v-if="scope.row.status == 'active'" :title="scope.row.next_execution_time">
                        - ({{ scope.row.next_execution_time | nsHumanDiffTime }})
                    </span>
                    <span v-else>
                        --
                    </span>
                </template>
            </el-table-column>
            <el-table-column width="160" :label="$t('Status')">
                <template slot-scope="scope">
                    {{ scope.row.status }}
                </template>
            </el-table-column>
            <el-table-column width="160" :label="$t('Action')">
                <template slot-scope="scope">
                    <confirm v-loading="removing" @yes="removeFromSequence(scope.row.campaign_id, scope.row.id)">
                        <el-button
                            size="mini"
                            type="danger"
                            slot="reference"
                            icon="el-icon-delete"
                        />
                    </confirm>
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

export default {
    name: 'ProfileEmailSequence',
    components: {
        Confirm,
        Pagination,
        OptionSelector
    },
    props: ['subscriber_id'],
    data() {
        return {
            sequences: [],
            loading: false,
            removing: false,
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            selected_sequence: '',
            doing_action: false
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get(`sequences/subscriber/${this.subscriber_id}/sequences`, {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            })
                .then(response => {
                    this.sequences = response.sequence_trackers.data;
                    this.pagination.total = response.sequence_trackers.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        removeFromSequence(sequenceId, trackerId) {
            this.removing = true;
            this.$del(`sequences/${sequenceId}/subscribers`, {
                tracker_ids: [trackerId]
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.removing = false;
                });
        },
        addToSequence() {
            this.doing_action = true;
            this.$post('subscribers/do-bulk-action', {
                action_name: 'add_to_email_sequence',
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
