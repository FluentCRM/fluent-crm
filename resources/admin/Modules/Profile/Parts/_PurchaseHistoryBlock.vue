<template>
    <div class="purchase_history_block">
        <el-row :gutter="30">
            <el-col :md="sidebar_html ? 16 : 24" :sm="24">
                <h3 class="history_title">
                    {{provider.title}}
                    <el-button @click="fetch('yes')" v-if="has_recount" type="default" size="mini">{{ $t('Re-Sync') }}</el-button>
                </h3>
                <div class="provider_data">
                    <el-skeleton v-if="loading" animated :rows="10" />
                    <el-table v-else :empty-text="$t('No Data Found')" v-loading="loading" border stripe :data="orders" @sort-change="handleSortable">
                        <el-table-column v-for="(column,columnKey) in table_columns" :key="columnKey"
                                         :width="(columnsConfig[columnKey]) ? columnsConfig[columnKey].width : ''"
                                         :label="(columnsConfig[columnKey] && columnsConfig[columnKey].label) ? columnsConfig[columnKey].label : ucWords($t(columnKey))"
                                         :prop="columnsConfig[columnKey]?.sortable ? columnsConfig[columnKey].key : ''"
                                         :sortable="!!columnsConfig[columnKey]?.sortable">
                            <template slot-scope="scope">
                                <div v-html="scope.row[columnKey]"></div>
                            </template>
                        </el-table-column>
                        <template slot="empty">
                            <p>{{$t('Purchase History from')}} <b>{{provider.name}}</b> {{$t('PurchaseHistoryBlock.empty_desc')}}</p>
                        </template>
                    </el-table>
                    <pagination :pagination="pagination" @fetch="fetch" />
                </div>
                <div v-if="after_html" class="fc_history_before" v-html="after_html"></div>
            </el-col>
            <el-col v-if="sidebar_html"  :md="8" :sm="24">
                <div class="fc_history_sidebar" v-html="sidebar_html"></div>
            </el-col>
        </el-row>
    </div>
</template>
<script type="text/babel">
    import Pagination from '@/Pieces/Pagination';
    export default {
        name: 'PurchaseHistoryBlock',
        props: ['provider', 'subscriber_id'],
        components: {
            Pagination
        },
        data() {
            return {
                loading: false,
                orders: [],
                pagination: {
                    per_page: 10,
                    current_page: 1,
                    total: 0
                },
                sidebar_html: '',
                after_html: '',
                has_recount: false,
                columnsConfig: {},
                query_data: {
                    sort_by: '',
                    sort_type: ''
                },
                sortState: {
                    currentProp: '',
                    direction: '' // '' -> 'ASC' -> 'DESC' -> ''
                }
            }
        },
        computed: {
            table_columns() {
                let columns = [];
                if (this.orders && this.orders.length) {
                    columns = this.orders[0];
                }
                return columns;
            }
        },
        methods: {
            handleSortable(sorting) {
                if (sorting.prop) {
                    this.query_data.sort_by = sorting.prop;
                    // Implement sorting toggle logic
                    if (this.sortState.currentProp !== sorting.prop) {
                        // New column selected - start with ASC
                        this.sortState.currentProp = sorting.prop;
                        this.sortState.direction = 'ASC';
                    } else {
                        // Same column clicked - toggle between ASC and DESC
                        this.sortState.direction = this.sortState.direction === 'ASC' ? 'DESC' : 'ASC';
                    }
                    
                    this.query_data.sort_type = this.sortState.direction;
                    this.fetch();
                } else {
                    // Reset sorting if no property is selected
                    this.query_data.sort_by = '';
                    this.query_data.sort_type = '';
                    this.sortState.currentProp = '';
                    this.sortState.direction = '';
                }
            },
            fetch(willRecount = false) {
                this.loading = true;
                this.$get(`subscribers/${this.subscriber_id}/purchase-history`, {
                    provider: this.provider.provider_key,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    sort_by: this.query_data.sort_by,
                    sort_type: this.query_data.sort_type,
                    will_recount: willRecount
                })
                    .then(response => {
                        this.orders = response.orders.data;
                        this.pagination.total = parseInt(response.orders.total);
                        if (response.orders.sidebar_html) {
                            this.sidebar_html = response.orders.sidebar_html;
                        }
                        if (response.orders.after_html) {
                            this.after_html = response.orders.after_html;
                        }

                        this.has_recount = response.orders.has_recount;

                        if (response.orders.columns_config) {
                            this.columnsConfig = response.orders.columns_config;
                        }
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
