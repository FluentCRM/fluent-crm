<template>
    <div class="fc_revenue_report">
        <h3>{{$t('Revenue from this email campaign')}}</h3>
        <template v-if="!loading">
            <el-table :data="orders" border stripe>
                <el-table-column v-for="(label, labelKey) in labels" :key="labelKey" :label="label">
                    <template slot-scope="scope">
                        <span v-html="scope.row[labelKey]"></span>
                    </template>
                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetchOrders"/>
        </template>
        <el-skeleton v-else />
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'RevenueReport',
    props: ['campaign'],
    components: {
        Pagination
    },
    data() {
        return {
            orders: [],
            labels: [],
            pagination: {
                per_page: 10,
                current_page: 1,
                total: 0
            },
            loading: false
        }
    },
    methods: {
        fetchOrders() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign.id}/revenues`, {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            })
                .then(response => {
                    this.orders = response.orders;
                    this.labels = response.labels;
                    this.pagination.total = response.total;
                })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {
                this.loading = false;
            })
        }
    },
    mounted() {
        this.fetchOrders();
    }
}
</script>
