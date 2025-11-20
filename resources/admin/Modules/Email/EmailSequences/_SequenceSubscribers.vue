<template>
    <div class="fluentcrm_campaign_emails">
        <h3>{{ $t('Sequence Subscribers') }}</h3>
        <el-table :empty-text="$t('No Data Found')" @selection-change="handleSelectionChange"
                  stripe border
                  :data="subscribers"
                  style="width: 100%"
                  v-loading="loading">
            <el-table-column
                type="selection"
                width="55">
            </el-table-column>
            <el-table-column label=""
                             width="64"
                             fixed
            >
                <template slot-scope="scope">
                    <router-link :to="{ name: 'subscriber', params: { id: scope.row.subscriber_id } }">
                        <img :title="$t('Contact ID:')+scope.row.subscriber_id" class="fc_contact_photo"
                             :src="scope.row.subscriber.photo"/>
                    </router-link>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Name')" width="250">
                <template slot-scope="scope">
                    <span>{{ scope.row.subscriber.full_name }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Email')">
                <template slot-scope="scope">
                    <router-link :to="{ name: 'subscriber', params: { id:  scope.row.subscriber_id } }">{{
                            scope.row.subscriber.email
                        }}
                    </router-link>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Status')">
                <template slot-scope="scope">
                    <span>{{ scope.row.status }}</span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Started At')">
                <template slot-scope="scope">
                    <span :title="scope.row.created_at">
                        {{ scope.row.created_at | nsHumanDiffTime }}
                    </span>
                    <span style="position: relative;" v-if="scope.row.notes">
                        <el-popover
                            width="500"
                            placement="bottom"
                            trigger="click">
                            <el-table border :data="scope.row.notes">
                                <el-table-column label="Email" prop="email"></el-table-column>
                                <el-table-column :width="160" label="Date Time" prop="scheduled_at"></el-table-column>
                            </el-table>
                            <span slot="reference"><i class="el-icon el-icon-info"></i></span>
                        </el-popover>
                    </span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Next Email')">
                <template slot-scope="scope">
                    <span v-if="scope.row.status == 'active'" :title="scope.row.created_at">
                        {{ scope.row.next_execution_time | nsHumanDiffTime }}
                    </span>
                    <span v-else>
                        --
                    </span>
                </template>
            </el-table-column>
        </el-table>

        <el-row style="margin-top: 10px;" :guter="20">
            <el-col :xs="24" :md="12">
                <confirm :message="$t('SequenceSubscribers.DeleteInfo')"
                         v-loading="removing" v-if="selected_subscribers.length" @yes="removeSubscribers()">
                    <el-button
                        size="mini"
                        type="danger"
                        slot="reference"
                        icon="el-icon-delete"
                    > {{ $t('Remove From Sequence') }}
                    </el-button>
                </confirm>
                <div v-else>&nbsp;</div>
            </el-col>
            <el-col :xs="24" :md="12">
                <pagination :pagination="pagination" @fetch="fetch"/>
            </el-col>
        </el-row>

    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'SequenceSubscribers',
    components: {
        Pagination,
        Confirm
    },
    props: ['sequence_id', 'reload_count'],
    data() {
        return {
            loading: false,
            subscribers: [],
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1
            },
            selected_subscribers: [],
            removing: false
        }
    },
    watch: {
        reload_count() {
            this.page = 1;
            this.fetch();
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.selected_subscribers = [];
            const query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            };

            this.$get(`sequences/${this.sequence_id}/subscribers`, query)
                .then(response => {
                    this.subscribers = response.data;
                    this.pagination.total = response.total;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(r => {
                    this.loading = false;
                });
        },
        removeSubscribers() {
            const selectedIds = [];
            this.each(this.selected_subscribers, (selection) => {
                selectedIds.push(selection.id);
            });
            this.removing = true;
            this.$del(`sequences/${this.sequence_id}/subscribers`, {
                tracker_ids: selectedIds
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
        handleSelectionChange(val) {
            this.selected_subscribers = val;
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
