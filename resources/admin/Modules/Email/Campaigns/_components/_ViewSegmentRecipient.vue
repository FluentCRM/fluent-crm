<template>
    <div>
        <el-skeleton style="padding: 20px;" v-if="first_loading" :rows="10"></el-skeleton>
        <div v-else>
            <el-input @keyup.enter.native="fetch" size="mini" :placeholder="$t('Search')"
                      v-model="search" class="input-with-select mr-10">
                <el-button @click="fetch()" slot="append" icon="el-icon-search"></el-button>
            </el-input>

            <el-table v-loading="loading" :default-sort="{ prop: sort_by, order: (sort_type == 'DESC') ? 'descending' : 'ascending' }" :empty-text="$t('No Contacts Found')" :data="subscribers"
                      border
                      id="fluentcrm-subscribers-table"
                      style="width: 100%"
                      stripe
                      @sort-change="handleSortable"
                      ref="subscribersTable"
            >

                <el-table-column label=""
                                 property="id"
                                 sortable="custom"
                                 width="55"
                >
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'subscriber', params: { id: scope.row.id } }">
                            <img :title="$t('Contact ID:')+' '+scope.row.id" class="fc_contact_photo"
                                 :src="scope.row.photo"/>
                        </router-link>
                    </template>
                </el-table-column>

                <el-table-column :label="$t('Email')"
                                 property="email"
                                 width="220"
                                 sortable="custom"
                >
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'subscriber', params: { id: scope.row.id } }">
                            {{ scope.row.email }}
                        </router-link>
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Name')"
                    min-width="180"
                    property="first_name"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.full_name }}
                    </template>
                </el-table-column>

                <el-table-column min-width="180" :label="$t('Lists')"
                                 property="lists">
                    <template slot-scope="scope">
                        <span>{{ getRelations(scope.row, 'lists') }}</span>
                    </template>
                </el-table-column>

                <el-table-column min-width="180" :label="$t('Tags')"
                                 property="tags">
                    <template slot-scope="scope">
                        <span>{{ getRelations(scope.row, 'tags') }}</span>
                    </template>
                </el-table-column>

                <el-table-column min-width="120" :label="$t('Phone')"
                                 property="phone">
                    <template slot-scope="scope">
                        {{ scope.row.phone }}
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Type')"
                    width="150"
                    property="contact_type"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ trans(scope.row.contact_type) | ucWords }}
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Date Added')"
                    min-width="190"
                    property="created_at"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        <template v-if="scope.row.created_at">
                            <i class="el-icon-time"></i>
                            <span :title="scope.row.created_at">{{ scope.row.created_at | nsHumanDiffTime }}</span>
                        </template>
                    </template>
                </el-table-column>
            </el-table>
            <pagination @per_page_change="(size) => {storage.set('contact_perpage', size)}"
                        :pagination="pagination" :hide_on_single="false" :extra_sizes="[200, 250, 300, 400, 600]" @fetch="fetch"/>
        </div>

    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'ViewSegmentRecipient',
    props: ['campaign_id'],
    components: {
        Pagination
    },
    data() {
        return {
            subscribers: [],
            loading: false,
            first_loading: true,
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0
            },
            search: '',
            sort_by: 'id',
            sort_type: 'desc'
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get('campaigns/' + this.campaign_id + '/contacts-by-segment', {
                search: this.search,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                sort_by: this.sort_by,
                sort_type: this.sort_type
            })
                .then(response => {
                    this.pagination.total = response.subscribers.total;
                    this.subscribers = response.subscribers.data;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                    this.first_loading = false;
                });
        },
        getRelations(subscriber, type) {
            const items = subscriber[type] || [];
            return items.map(item => item.title).join(', ');
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.sort_by = sorting.prop;
                this.sort_type = 'DESC';
            } else {
                this.sort_by = sorting.prop;
                this.sort_type = 'ASC';
            }
            this.fetch();
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
