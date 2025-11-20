<template>
    <el-popover
        placement="bottom"
        :width="560"
        trigger="click"
        popper-class="fcrm_link_stats_popover"
        effect="dark"
    >
        <template #reference>
            <span @click="getUrlMetrics" class="stats_link" :title="$t('Click Rate')">
                <i class="el-icon el-icon-position"></i> <span>
                {{ percent(subscriber.stats.clicks, subscriber.stats.emails) }}</span>
            </span>
        </template>

        <div class="fcrm_link_stats_metrics">
            <div v-if="loadingMetrics" class="fcrm_loader_wrap">
                <el-skeleton :animated="true" :rows="5" />
            </div>
            <el-table v-else sortable :data="urlMetrics" @sort-change="handleSortable">
                <el-table-column :label="$t('URL')">
                    <template slot-scope="scope">
                        <a :href="scope.row.url" target="_blank" rel="noopener">{{ scope.row.url }}</a>
                    </template>
                </el-table-column>
                <el-table-column width="100" sortable prop="counter" :label="$t('Clicks')">
                    <template slot-scope="scope">
                        <span class="counter">{{ scope.row.count }}</span>
                    </template>
                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="getUrlMetrics"/>
        </div>
    </el-popover>
</template>

<script>
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'ProfileStatURL',
    props: {
        subscriber: {
            type: Object,
            required: true,
            default: () => {
                return null
            }
        }
    },
    components: {
        Pagination
    },
    data() {
        return {
            urlMetrics: [],
            loadingMetrics: false,
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            query_data: {
                sort_by: '',
                sort_type: ''
            }
        }
    },
    methods: {
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_type = 'DESC';
            } else {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_type = 'ASC';
            }
            this.getUrlMetrics();
        },
        getUrlMetrics() {
            this.loadingMetrics = true;
            this.$get(`subscribers/${this.subscriber.id}/url-metrics`, {
                sort_by: this.query_data.sort_by,
                sort_type: this.query_data.sort_type,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                subscriber_id: this.subscriber.id
            })
                .then((response) => {
                    this.urlMetrics = response.urlMetrics.data;
                    this.pagination.total = response.urlMetrics.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loadingMetrics = false;
                })
        }
    }
}
</script>
