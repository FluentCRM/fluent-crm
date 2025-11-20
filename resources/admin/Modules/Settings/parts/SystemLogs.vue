<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header d-flex items-center justify-between">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('System Logs') }}</h3>
                <p>Logs from FluentCRM System Events - Useful for debugging purpose</p>
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
                <el-button type="danger" size="mini" @click="deleteAll">
                    {{ $t('Reset') }}
                </el-button>
                <el-button size="mini" @click="fetch">
                    {{ $t('Refresh') }}
                </el-button>
            </div>
        </div>
        <div class="fluentcrm_pad_b_20" style="position: relative;">
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table v-else :empty-text="$t('No Data Found')" stripe :data="logs" style="width: 100%">
                <el-table-column :label="$t('ID')" prop="id" width="80"/>
                <el-table-column :label="$t('Title')" width="280">
                    <template slot-scope="scope">
                        {{ scope.row.title }}
                    </template>
                </el-table-column>
                <el-table-column min-width="250" :label="$t('Description')">
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

export default {
    name: 'SystemLogs',
    components: {
        Pagination
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
            search: ''
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get('setting/system-logs', {
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
        deleteAll() {
            this.loading = true;
            this.$get('setting/system-logs/reset')
                .then(response => {
                    this.$notify.success(response.message);
                    this.logs = [];
                    this.pagination.total = 0;
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
