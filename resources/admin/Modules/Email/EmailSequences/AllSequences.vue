<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <email-header-pop-nav :head_title="$t('Email Sequences')" />
                    <span v-show="pagination.total" class="ff_small">({{pagination.total | formatMoney}})</span>
                </h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div class="fc-search-box">
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

                <template v-if="hasPermission('fcrm_manage_emails')">
                    <el-button class="ml-5" icon="el-icon-plus" size="small"
                               type="primary"
                               @click="dialogVisible = true">
                        {{ $t('Create New Sequence') }}
                    </el-button>
                    <el-button @click="$router.push({ name: 'import_sequence' })" size="small" type="info"
                               icon="el-icon-upload">
                        {{ $t('Import') }}
                    </el-button>
                </template>
                <inline-doc :doc_id="1601"/>
            </div>
        </div>
        <div class="fluentcrm_body fluentcrm_pad_b_20" style="position: relative;">
            <div v-if="loading" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading || duplicating" :rows="7"></el-skeleton>

            <div v-if="!loading && !duplicating && pagination.total" class="fluentcrm_sequences_table">
                <el-row>
                    <el-col :md="12" :sm="24">
                        <confirm placement="top-start" @yes="deleteSelected()" v-if="selection">
                            <el-button
                                v-loading="deleting"
                                style="margin: 8px 0 8px 10px;"
                                icon="el-icon-delete"
                                slot="reference"
                                size="mini"
                                type="danger">
                                {{ $t('Delete Selected') }}
                            </el-button>
                        </confirm>
                        &nbsp;
                    </el-col>
                </el-row>

                <el-table stripe
                          border
                          :data="sequences"
                          @sort-change="handleSortable"
                          @selection-change="onSelection">
                    <el-table-column type="selection" :width="45"/>

                    <el-table-column sortable property="title" :min-width="250" :label="$t('Title')" prop="title">
                        <template slot-scope="scope">
                            <router-link
                                :to="{ name: 'edit-sequence', params: { id: scope.row.id }, query: { t: (new Date()).getTime() }}">
                                {{ scope.row.title }}
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column :width="190" :label="$t('Emails')">
                        <template slot-scope="scope">
                            <span v-if="scope.row.stats">
                                <router-link
                                    :to="{name: 'edit-sequence', params: { id: scope.row.id }, query: { t: (new Date()).getTime() }}">
                                    {{ $_n('%d Email', '%d Emails', scope.row.stats.emails) }}
                                </router-link>
                                <span :title="$t('Revenue From Sequence Emails')"
                                      v-if="scope.row.stats.revenue && scope.row.stats.revenue.currency"> <span
                                    class="el-icon el-icon-money"></span> {{ scope.row.stats.revenue.currency }} {{ scope.row.stats.revenue.amount }}</span>
                            </span>
                            <span v-else>--</span>
                        </template>
                    </el-table-column>

                    <el-table-column :width="170" sortable property="recipients_count" :label="$t('Subscribers')"
                                     prop="recipients_count">
                        <template slot-scope="scope">
                            <router-link
                                :to="{name: 'sequence-subscribers', params: { id: scope.row.id }, query: { t: (new Date()).getTime() }}">
                                <span>{{ $_n('%d subscriber', '%d subscribers', scope.row.stats.subscribers) }}</span>
                            </router-link>
                        </template>
                    </el-table-column>

                    <el-table-column :width="180" sortable property="created_at" :label="$t('Created at')"
                                     prop="created_at">
                        <template slot-scope="scope">
                            <span>{{ scope.row.created_at | nsHumanDiffTime }}</span>
                        </template>
                    </el-table-column>

                    <el-table-column fixed="right" :label="$t('Actions')" min-width="130">
                        <template slot-scope="scope">
                            <el-tooltip effect="dark" content="Edit Emails" placement="top">
                                <el-button
                                    type="info"
                                    size="mini"
                                    icon="el-icon-edit"
                                    @click="$router.push({name: 'edit-sequence', params: { id: scope.row.id }, query: { t: (new Date()).getTime() }})"
                                >edit
                                </el-button>
                            </el-tooltip>

                            <el-dropdown trigger="click">
                                <span class="el-dropdown-link">
                                    <i style="font-weight: bold; cursor: pointer;"
                                       class="el-icon-more icon-90degree el-icon--right"></i>
                                </span>
                                <el-dropdown-menu slot="dropdown">
                                    <el-dropdown-item class="fc_dropdown_action">
                                    <span class="el-popover__reference" @click="duplicateSequence(scope.row)">
                                        <span class="el-icon el-icon-copy-document"></span> {{ $t('Duplicate') }}
                                    </span>
                                    </el-dropdown-item>
                                    <el-dropdown-item class="fc_dropdown_action">
                                    <span class="el-popover__reference" @click="exportSequence(scope.row)">
                                        <span class="el-icon el-icon-download"></span> {{ $t('Export') }}
                                    </span>
                                    </el-dropdown-item>
                                    <el-dropdown-item class="fc_dropdown_action">
                                        <confirm placement="top-start"
                                                 :message="$t('Are you sure you want to delete this Sequence?')"
                                                 @yes="remove(scope.row)">
                                        <span slot="reference">
                                            <span class="el-icon el-icon-delete"></span>
                                            {{ $t('Delete Sequence') }}
                                        </span>
                                        </confirm>
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </el-dropdown>

                        </template>
                    </el-table-column>

                </el-table>

                <el-row>
                    <el-col :md="12" :sm="24">
                        <confirm placement="top-start" @yes="deleteSelected()" v-if="selection">
                            <el-button
                                v-loading="deleting"
                                style="margin: 10px 0 0 15px;"
                                icon="el-icon-delete"
                                slot="reference"
                                size="mini"
                                type="danger">
                                {{ $t('Delete Selected') }}
                            </el-button>
                        </confirm>
                        &nbsp;
                    </el-col>
                    <el-col :md="12" :sm="24">
                        <pagination :pagination="pagination" @fetch="fetch"/>
                    </el-col>

                </el-row>
            </div>
            <template v-else>
                <div v-if="!loading && !duplicating" class="fluentcrm_hero_box">
                    <h2>{{ $t('All_Looks_lydnsasey') }}</h2>
                    <el-button icon="el-icon-plus" size="small" type="success" @click="dialogVisible = true">
                        {{ $t('All_Create_YFES') }}
                    </el-button>
                </div>
            </template>
        </div>
        <create-sequence
            :dialog-visible.sync="dialogVisible"
            @toggleDialog="(value)=> { dialogVisible = false; }"
        />
    </div>
</template>

<script type="text/babel">
import CreateSequence from './_CreateSequence';
import Confirm from '@/Pieces/Confirm';
import Pagination from '@/Pieces/Pagination';
import InlineDoc from '@/Modules/Documentation/InlineDoc';
import EmailHeaderPopNav from '@/Pieces/EmailHeaderPopNav.vue';

export default {
    name: 'all-sequences',
    components: {
        CreateSequence,
        Confirm,
        Pagination,
        InlineDoc,
        EmailHeaderPopNav
    },
    data() {
        return {
            sequences: [],
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            loading: true,
            dialogVisible: false,
            duplicating: false,
            search: '',
            options: {
                sampleCsv: null,
                delimiter: 'comma'
            },
            order: 'desc',
            orderBy: 'id',
            selection: false,
            selectedSequences: [],
            deleting: false
        }
    },
    methods: {
        setup() {
            let queryParams = this.$route.query;

            if (window.fcrm_seq_sub_params) {
                queryParams = window.fcrm_seq_sub_params;
            }

            this.order = queryParams.order;
            this.orderBy = queryParams.orderBy;
            this.search = queryParams.search;

            if (queryParams.page) {
                this.pagination.current_page = parseInt(queryParams.page);
            }
            if (queryParams.per_page) {
                this.pagination.per_page = parseInt(queryParams.per_page);
            }

            return false;
        },
        fetch() {
            this.loading = true;
            const query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                with: ['stats'],
                order: this.order,
                orderBy: this.orderBy,
                search: this.search
            };

            const params = {};

            Object.keys(query).forEach(key => {
                if (query[key] !== undefined) {
                    params[key] = query[key];
                }
            });

            window.fcrm_seq_sub_params = params;
            params.t = Date.now();

            this.$router.replace({
                name: 'email-sequences', query: params
            });
            delete query.t;

            this.$get('sequences', query).then(response => {
                this.sequences = response.sequences.data;
                this.pagination.total = response.sequences.total;
            })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        remove(sequence) {
            this.$del(`sequences/${sequence.id}`)
                .then(r => {
                    this.fetch();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: r.message,
                        offset: 19
                    });
                })
                .catch(errors => {
                    this.handleError(errors);
                });
        },
        duplicateSequence(sequence) {
            this.duplicating = true;
            this.$post(`sequences/${sequence.id}/duplicate`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'edit-sequence',
                        params: {
                            id: response.sequence.id
                        }
                    })
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.duplicating = true;
                });
        },
        exportSequence(sequence) {
            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_sequence',
                sequence_id: sequence.id
            });
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.orderBy = sorting.prop;
                this.order = 'desc';
            } else {
                this.orderBy = sorting.prop;
                this.order = 'asc';
            }
            this.fetch();
        },
        getPercent(number, total) {
            if (!total || !number) {
                return '--';
            }
            return parseFloat(number / total * 100).toFixed(2) + '%';
        },
        onSelection(funnels) {
            this.selection = !!funnels.length;

            this.selectedSequences = funnels;
        },
        deleteSelected() {
            const sequenceIds = [];
            this.each(this.selectedSequences, (funnel) => {
                sequenceIds.push(funnel.id);
            });

            this.deleting = true;
            this.$post('sequences/do-bulk-action', {
                sequence_ids: sequenceIds
            })
                .then(res => {
                    this.$notify.success(res.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.deleting = false;
                    this.selection = false;
                });
        }
    },
    mounted() {
        this.setup();
        this.fetch();
        this.changeTitle(this.$t('Email Sequences'));
    }
}
</script>
