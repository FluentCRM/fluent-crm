<template>
    <div class="purchase_history_block">
        <h3 class="history_title">{{provider.title}}</h3>
        <div class="provider_data">
            <el-table :empty-text="$t('No Data Found')" v-loading="loading" border stripe :data="submissions">
                <el-table-column v-for="(column,columnKey) in table_columns" :key="columnKey"
                     :width="(columnsConfig[columnKey]) ? columnsConfig[columnKey].width : ''"
                     :label="(columnsConfig[columnKey] && columnsConfig[columnKey].label) ? columnsConfig[columnKey].label : ucFirst(columnKey)">
                >
                    <template slot-scope="scope">
                        <div v-html="scope.row[columnKey]"></div>
                    </template>
                </el-table-column>
                <template slot="empty">
                    <p>{{$t('Form Submissions from')}} <b>{{provider.name}}</b> {{$t('no_form_submissions_found_for_this_subscriber')}}</p>
                </template>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetch" />
        </div>
    </div>
</template>
<script type="text/babel">
    import Pagination from '@/Pieces/Pagination';
    export default {
        name: 'FormSubmissionsBlock',
        props: ['provider', 'subscriber_id'],
        components: {
            Pagination
        },
        data() {
            return {
                loading: false,
                submissions: [],
                pagination: {
                    per_page: 10,
                    current_page: 1,
                    total: 0
                },
                columnsConfig: {}
            }
        },
        computed: {
            table_columns() {
                let columns = [];
                if (this.submissions.length) {
                    columns = this.submissions[0];
                }
                return columns;
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                this.$get(`subscribers/${this.subscriber_id}/form-submissions`, {
                    provider: this.provider.provider_key,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page
                })
                    .then(response => {
                        this.submissions = response.submissions.data;
                        this.pagination.total = parseInt(response.submissions.total);

                        if (response.submissions.columns_config) {
                            this.columnsConfig = response.submissions.columns_config;
                        }
                    })
                    .catch((errors) => {
                        console.log(errors);
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
