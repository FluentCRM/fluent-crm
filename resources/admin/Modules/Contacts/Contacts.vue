<template>
    <div class="fluentcrm-subscribers fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <contact-header-pop-nav :head_title="$t('Contacts')"></contact-header-pop-nav>
                    <span v-show="pagination.total" class="ff_small">({{ pagination.total | formatMoney }})</span>
                </h3>
                <el-button v-if="isFilterEnabled" type="default" size="small" @click="clearAllFilter">
                    {{ $t('Clear Filters') }}
                </el-button>
            </div>
            <div v-if="hasPermission('fcrm_manage_contacts')" class="fluentcrm-actions">
                <action-menu :search_query="{...query_data, advanced_filters, filter_type}" :options="options"
                             :listId="listId" :tagId="tagId"
                             @fetch="fetch">
                </action-menu>
            </div>
        </div>
        <div class="fluentcrm_body fluentcrm_pad_b_15 fluentcrm_subscribers">
            <contacts-table
                :always_searchbar="false"
                v-if="app_ready"
                :options="options"
                :query_data="query_data"
                :is_loading="false"
                :ui_config="ui_config"
                :subscribers="subscribers"
                @fetch="fetch"
                :filter_type="filter_type"
                :pagination="pagination"
                :show_skeleton="first_loading"
            >
                <div slot="before_search_box">
                    <el-switch class="fc_advanced_toggle" @change="changedFilterType()" active-value="advanced"
                               inactive-value="simple" v-model="filter_type" :active-text="$t('Advanced Filter')"/>
                </div>
                <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                    <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
                </div>
                <div v-if="filter_type == 'advanced'" class="fc_rich_container" slot="before_contacts_table">
                    <div class="fc_rich_wrap">
                        <div v-for="(rich_filter, filterIndex) in advanced_filters" :key="filterIndex">
                            <div class="fc_rich_filter">
                                <rich-filter :add_label="filterLabel" @maybeRemove="maybeRemoveGroup(filterIndex)" :items="rich_filter" @showClearFilterButton="showClearFilterButton"/>
                            </div>
                            <div class="fc_cond_or">
                                <em>{{ $t('OR') }}</em>
                            </div>
                        </div>
                    </div>
                    <div class="fc_cond_or">
                        <em @click="addConditionGroup()"
                            style="cursor: pointer; color: rgb(0, 119, 204); font-weight: bold;"><i
                            class="el-icon-plus"></i> {{ $t('OR') }}</em>
                    </div>
                    <template v-if="has_campaign_pro">
                        <el-row :gutter="20">
                            <el-col :md="12" :xs="24">
                                <el-button type="primary" size="small" @click="fetch()">{{ $t('Filter') }}</el-button>
                            </el-col>
                            <el-col :md="12" :xs="24">
                                <div class="text-align-right">
                                    <dynamic-segment-save :advance_filters="advanced_filters" />
                                </div>
                            </el-col>
                        </el-row>
                        <div class="el-alert el-alert--info is-light" style="margin-top: 20px;"
                             v-if="appVars.advanced_filter_suggestions.length">
                            <ul class="fc_list">
                                <li
                                    v-for="suggestion in appVars.advanced_filter_suggestions"
                                    :key="suggestion.provider"
                                >
                                    {{ suggestion.title }} <a style="font-weight: bold;"
                                                              :href="suggestion.btn_url">{{ suggestion.btn_text }}</a>
                                </li>
                            </ul>
                        </div>

                    </template>
                    <div v-else>
                        <advanced-filter-promo/>
                    </div>
                </div>
            </contacts-table>
        </div>
    </div>
</template>

<script type="text/babel">
import ActionMenu from './ActionMenu';
import ContactsTable from './_ContactsTable';
import RichFilter from './RichFilters/Filters';
import AdvancedFilterPromo from '../Promos/AdvancedFilterPromo';
import DynamicSegmentSave from './_DynamicSegmentSave';
import ContactHeaderPopNav from '@/Pieces/ContactHeaderPopNav.vue';

const isArray = require('lodash/isArray');

export default {
    name: 'Subscribers',
    components: {
        ActionMenu,
        ContactsTable,
        RichFilter,
        AdvancedFilterPromo,
        DynamicSegmentSave,
        ContactHeaderPopNav
    },
    data() {
        return {
            app_ready: false,
            subscribers: [],
            loading: true,
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0
            },
            advanced_filters: [[]],
            filter_type: 'simple',
            listId: null,
            tagId: null,
            query_data: {
                tags: [],
                lists: [],
                search: '',
                statuses: [],
                sort_by: 'id',
                sort_type: 'DESC',
                custom_fields: false,
                has_commerce: false
            },
            ui_config: {
                show_list: true,
                show_tag: true,
                disable_filters_without_selection: false
            },
            options: {
                tags: [],
                lists: [],
                statuses: [],
                countries: [],
                sampleCsv: null,
                delimiter: 'comma'
            },
            first_loading: true,
            filterLabel: this.$t('Filters.instruction'),
            fcrmSubParams: window.fcrm_sub_params ?? this.$route.query,
            isClearFilterVisible: false
        }
    },
    computed: {
        isFilterEnabled() {
            const query = this.fcrmSubParams;
            if (!query) return false;

            if (query.filter_type === 'advanced') {
                const advanceFilters = JSON.parse(query.advanced_filters);
                return this.isClearFilterVisible || advanceFilters.length > 1 || advanceFilters[0].length > 0;
            }
            
            // For simple filter type, check other parameters
            return !!(
                (query.tags && query.tags.length) ||
                (query.lists && query.lists.length) ||
                (query.statuses && query.statuses.length) ||
                query.search
            );
        }
    },
    methods: {
        setup() {
            let queryParams = this.$route.query;

            if (window.fcrm_sub_params) {
                queryParams = window.fcrm_sub_params;
            }

            if (queryParams.tags && !isArray(queryParams.tags)) {
                queryParams.tags = [queryParams.tags];
            }

            if (queryParams.lists && !isArray(queryParams.lists)) {
                queryParams.lists = [queryParams.lists];
            }

            if (queryParams.statuses && !isArray(queryParams.statuses)) {
                queryParams.statuses = [queryParams.statuses];
            }

            const queryDefaults = {
                tags: (queryParams.tags) ? queryParams.tags.map(function (item) {
                    return parseInt(item);
                }) : [],
                lists: (queryParams.lists) ? queryParams.lists.map(function (item) {
                    return parseInt(item);
                }) : [],
                search: queryParams.search || '',
                statuses: queryParams.statuses || [],
                sort_by: queryParams.sort_by || 'id',
                sort_type: queryParams.sort_type || 'DESC',
                custom_fields: false,
                filter_type: queryParams.filter_type || 'simple'
            };

            if (queryParams.page) {
                this.pagination.current_page = parseInt(queryParams.page);
            }

            if (queryParams.filter_type == 'advanced') {
                this.filter_type = 'advanced';
                this.ui_config.disable_filters_without_selection = true;
                if (queryParams.advanced_filters && queryParams.advanced_filters != '[object Object]') {
                    if (typeof queryParams.advanced_filters == 'string') {
                        this.advanced_filters = JSON.parse(queryParams.advanced_filters);
                    } else {
                        this.advanced_filters = queryParams.advanced_filters;
                    }
                }
            } else {
                this.ui_config.disable_filters_without_selection = false;
            }

            this.query_data = queryDefaults;

            let status = false;

            if (this.$route.params.listId) {
                status = true;
                this.listId = this.$route.params.listId;
                this.query_data.lists = [parseInt(this.$route.params.listId)];
                this.ui_config.show_list = false;
            } else if (this.$route.params.lists) {
                console.log(this.$route.params.lists);
            }

            if (this.$route.params.tagId) {
                status = true;
                this.tagId = this.$route.params.tagId;
                this.query_data.tags = [this.$route.params.tagId];
                this.ui_config.show_tag = false;
            }

            this.app_ready = true;
            return status;
        },
        fetch() {
            this.loading = true;
            let query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                filter_type: this.filter_type,
                custom_fields: this.query_data.custom_fields,
                has_commerce: this.query_data.has_commerce,
                sort_by: this.query_data.sort_by,
                sort_type: this.query_data.sort_type
            };

            if (!this.has_campaign_pro) {
                query.filter_type = 'simple';
            }

            if (this.filter_type == 'advanced' && this.has_campaign_pro) {
                query = {advanced_filters: JSON.stringify(this.advanced_filters), ...query}
            } else {
                query = {...this.query_data, ...query};
            }

            const params = {};

            this.each(query, (val, key) => {
                if (!this.isEmptyValue(val)) {
                    params[key] = val;
                }
            });

            window.fcrm_sub_params = params;

            params.t = Date.now();

            this.$router.replace({
                name: 'subscribers', query: params
            });
            this.fcrmSubParams = params;

            this.$get('subscribers', query)
                .then(response => {
                    this.pagination.total = response.subscribers.total;
                    this.subscribers = response.subscribers.data;
                })
                .catch((error) => {
                    this.handleError(error);
                    this.storage.set('contact_perpage', 10);
                })
                .finally(() => {
                    this.loading = false;
                    this.first_loading = false;
                });
        },
        getOptions() {
            this.options = {
                tags: this.appVars.available_tags,
                lists: this.appVars.available_lists,
                statuses: this.appVars.available_contact_statuses,
                contact_types: this.appVars.available_contact_types,
                sampleCsv: this.appVars.contact_sample_csv,
                custom_fields: this.appVars.available_custom_fields
            };
        },
        addConditionGroup() {
            this.advanced_filters.push([]);
            this.isClearFilterVisible = true;
        },
        maybeRemoveGroup(index) {
            if (this.advanced_filters.length > 1) {
                this.advanced_filters.splice(index, 1);
            }
        },
        changedFilterType() {
            if (this.filter_type == 'simple') {
                this.ui_config.disable_filters_without_selection = false;
            } else {
                this.ui_config.disable_filters_without_selection = true;
            }

            this.fetch();
        },
        clearAllFilter() {
            this.advanced_filters = [[]];
            this.query_data = {
                tags: [],
                lists: [],
                search: '',
                statuses: [],
                sort_by: 'id',
                sort_type: 'DESC',
                custom_fields: false,
                has_commerce: false
            };
            this.isClearFilterVisible = false;
            this.fetch();
        },
        showClearFilterButton() {
            this.isClearFilterVisible = true;
        }
    },
    created() {
        this.pagination.per_page = parseInt(this.storage.get('contact_perpage', 10)) || 10;
        this.getOptions();
        this.setup();
        this.changeTitle(this.$t('Contacts'));
    }
}
</script>
