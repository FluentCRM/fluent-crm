<template>
    <div v-loading="loading" class="fc_unsubscribers_table">
        <div v-if="pagination.total || loading">
            <el-table stripe :data="unsubscribes" border>
                <el-table-column :label="$t('Name')" width="250">
                    <template slot-scope="scope">
                        <img style="display: inline-block; margin-bottom: -6px;"
                             :title="'Contact ID: '+scope.row.subscriber.id" class="fc_contact_photo"
                             :src="scope.row.subscriber.photo"/>
                        <span>{{ scope.row.subscriber.full_name }}</span>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Email')">
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'subscriber', params: { id:  scope.row.subscriber_id } }">
                            {{ scope.row.subscriber.email }}
                        </router-link>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Reason')">
                    <template slot-scope="scope">
                        {{ scope.row.subscriber.reason }}
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Date')">
                    <template slot-scope="scope">
                        <span :title="scope.row.created_at">{{ scope.row.created_at | nsHumanDiffTime }}</span>
                    </template>
                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetch"/>
        </div>

        <el-empty v-else :image-size="135" :description="$t('Unsubscribes.instruction')" />
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'Unsbscribers',
    props: ['campaign_id'],
    components: {
        Pagination
    },
    data() {
        return {
            loading: true,
            unsubscribes: [],
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1
            }
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign_id}/unsubscribers`, {
                page: this.pagination.current_page,
                per_page: this.pagination.per_page
            })
                .then(response => {
                    this.unsubscribes = response.unsubscribes.data;
                    this.pagination.total = response.unsubscribes.total;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(r => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
