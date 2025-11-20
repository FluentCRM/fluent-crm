<template>
    <div class="purchase_history_block">
        <h3 class="history_title">{{provider.title}}</h3>
        <div class="provider_data">
            <el-table :empty-text="$t('No Data Found')" v-loading="loading" border stripe :data="tickets">
                <el-table-column v-for="(column,columnKey) in table_columns" :key="columnKey"
                                 :width="(columnsConfig[columnKey]) ? columnsConfig[columnKey].width : ''"
                                 :label="(columnsConfig[columnKey] && columnsConfig[columnKey].label) ? columnsConfig[columnKey].label : ucFirst(columnKey)">
                >
                    <template slot-scope="scope">
                        <div v-html="scope.row[columnKey]"></div>
                    </template>
                </el-table-column>
                <template slot="empty">
                    <p>{{$t('Support Tickets from')}} <b>{{provider.name}}</b> {{ $t('no_tickets_found_for_this_subscriber') }}</p>
                </template>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetch"/>
        </div>
    </div>
</template>
<script type="text/babel">
    import Pagination from '@/Pieces/Pagination';

    export default {
        name: 'SupportTicketsBlock',
        props: ['provider', 'subscriber_id'],
        components: {
            Pagination
        },
        data() {
            return {
                loading: false,
                tickets: [],
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
                if (this.tickets && this.tickets.length) {
                    columns = this.tickets[0];
                }
                return columns;
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                this.$get(`subscribers/${this.subscriber_id}/support-tickets`, {
                    provider: this.provider.provider_key,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page
                })
                    .then(response => {
                        this.tickets = response.tickets.data;
                        this.pagination.total = parseInt(response.tickets.total);
                        if (response.tickets.columns_config) {
                            this.columnsConfig = response.tickets.columns_config;
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
