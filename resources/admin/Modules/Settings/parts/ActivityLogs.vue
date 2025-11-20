<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header d-flex items-center justify-between">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('Activity Logs') }}</h3>
                <p>{{ $t('activity_log_info_2') }}</p>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div class="fc_right_search">
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
                <confirm
                    v-if="logs.length"
                    width="200"
                    placement="left-start"
                    :message="confirm_message"
                    @yes="resetActivityLogs()"
                >
                    <el-button
                        style="margin: 0px 10px"
                        type="danger"
                        size="mini"
                        v-loading="loading"
                        slot="reference"
                        icon="el-icon-delete"
                    >{{$t('Reset Logs')}}
                    </el-button>
                </confirm>
                <el-button size="mini" @click="fetch">
                    {{ $t('Refresh') }}
                </el-button>
            </div>
        </div>
        <div class="fluentcrm_pad_b_20" style="position: relative;">
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table v-else :empty-text="$t('No Data Found')" stripe :data="logs" style="width: 100%">
                <el-table-column :label="$t('#')" width="60">
                    <template slot-scope="scope">
                        {{ scope.$index + 1 }}
                    </template>
                </el-table-column>
                <el-table-column min-width="250" :label="$t('Activity By')">
                    <template slot-scope="scope">
                        <div v-html="scope.row.activity_by_email"></div>
                    </template>
                </el-table-column>
                <el-table-column width="200" :label="$t('Action')">
                    <template slot-scope="scope">
                        <div v-html="scope.row.action"></div>
                    </template>
                </el-table-column>
                <el-table-column min-width="280" :label="$t('Description')">
                    <template slot-scope="scope">
                        <div v-html="scope.row.description"></div>
                    </template>
                </el-table-column>
                <el-table-column
                    :label="$t('Date Time')"
                    width="190"
                    property="created_at"
                >
                    <template slot-scope="scope">
                        <template v-if="scope.row.created_at">
                            <i class="el-icon-time"></i>
                            {{ scope.row.created_at }}
                        </template>
                    </template>
                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetch" layout="total, sizes, prev, pager, next, jumper"/>
        </div>
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'ActivityLogs',
    components: {
        Pagination,
        Confirm
    },
    data() {
        return {
            logs: [],
            pagination: {
                total: 0,
                current_page: 1,
                per_page: 20
            },
            loading: false,
            search: '',
            confirm_message: '<b>' + this.$t('Are you sure to reset?') + '</b><br />' + this.$t('reset_all_activity_logs_notice')
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get('setting/activity-logs', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search
            })
                .then(response => {
                    this.logs = response.logs.data;
                    this.pagination.total = response.logs.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        resetActivityLogs() {
            this.loading = true;
            this.$get('setting/activity-logs/reset')
                .then(response => {
                    this.$notify.success(response.message);
                    this.logs = [];
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
