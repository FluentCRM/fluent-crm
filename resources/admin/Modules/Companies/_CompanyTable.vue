<template>
    <div style="position: relative;" class="fc_contacts_wrap">
        <div class="fluentcrm-header-secondary">
            <bulk-action-menu>
                <div v-if="selection" class="fc_filter_boxes">
                    <bulk-company-actions @refetch="fetch()" :selectedCompanies="selectedCompanies" :options="options" :pagination="pagination"/>
                </div>
                <div v-else class="fc_filter_boxes">
                    <ul v-if="query_data.inline_filters && !isEmptyValue(query_data.inline_filters)"
                        class="fc_inline_filters">
                        <li v-for="(filterVal, filterKey) in query_data.inline_filters" :key="filterKey">
                            {{ filterItems[filterKey]?.title }}: <span
                            v-if="filterVal && filterVal.length">{{ filterVal.join(', ') }}</span>
                            <span style="cursor: pointer;" @click="removeFilterItem(filterKey)"
                                  class="clickable el-icon el-icon-close"></span>
                        </li>
                    </ul>

                    <el-popover
                        ref="companies_inline_filters"
                        v-if="currentFilterableItems.length" size="mini"
                        placement="right"
                        width="400"
                        trigger="click">

                        <div class="fc_filter_item">
                            <el-select v-model="popFilter.key" placeholder="Select Filter">
                                <el-option
                                    v-for="item in currentFilterableItems"
                                    :key="item.key"
                                    :label="item.title"
                                    :value="item.key">
                                </el-option>
                            </el-select>
                            <template v-if="popFilter.key && appVars[popFilter.key]">
                                <p>Select Search Options</p>
                                <el-select :filterable="true" :multiple="true" v-model="popFilter.value">
                                    <el-option
                                        v-for="item in appVars[popFilter.key]"
                                        :key="item"
                                        :label="item"
                                        :value="item">
                                    </el-option>
                                </el-select>
                                <el-button @click="pushInlineSearch" style="margin-top: 20px;" size="mini">Search
                                    Companies
                                </el-button>
                            </template>
                        </div>

                        <el-button slot="reference" size="mini">
                            <span class="el-icon el-icon-plus"></span>
                            <span>Add Filter</span>
                        </el-button>
                    </el-popover>
                </div>
                <div class="fc_search_box">
                    <slot name="before_search_box"></slot>
                    <searcher v-model="query_data.search"/>
                    <toggler @dataChanged="maybeReFetch()" v-model="columns"/>
                </div>
            </bulk-action-menu>
        </div>
        <div class="fc_subscribers_table">
            <el-skeleton style="padding: 20px;" v-if="show_skeleton" :rows="10"></el-skeleton>
            <el-table
                border
                v-else
                :default-sort="{ prop: query_data.sort_by, order: (query_data.sort_order == 'DESC') ? 'descending' : 'ascending' }"
                :empty-text="$t('No Companies Found')" :data="companies"
                id="fluentcrm-subscribers-table"
                @selection-change="onSelection"
                style="width: 100%"
                stripe
                @sort-change="handleSortable"
                ref="companiesTable"
            >
                <el-table-column
                    v-if="hasPermission('fcrm_manage_contact_cats') || hasPermission('fcrm_manage_contact_cats_delete')"
                    type="selection"/>

                <el-table-column label=""
                                 property="id"
                                 sortable="custom"
                                 width="55"
                >
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'view_company', params: { company_id: scope.row.id } }">
                            <img v-if="scope.row.logo" :title="$t('Company ID:')+' '+scope.row.id"
                                 class="fc_contact_photo"
                                 :src="scope.row.logo"/>
                            <span :title="$t('Company ID:')+' '+scope.row.id" class="fc_empty_logo" v-else></span>
                        </router-link>
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Name')"
                    min-width="180"
                    property="name"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'view_company', params: { company_id: scope.row.id } }">
                            {{ scope.row.name }}
                        </router-link>
                    </template>
                </el-table-column>

                <el-table-column :label="$t('Company Email')"
                                 min-width="180px"
                                 property="email"
                                 sortable="custom"
                >
                </el-table-column>

                <el-table-column
                    v-if="columns.indexOf('owner_id') != -1"
                    :label="$t('Company Owner')"
                    property="owner_id"
                    width="200"
                >
                    <template slot-scope="scope">
                        <span v-if="scope.row.owner">
                            <router-link class="fc_photo_text"
                                         :to="{ name: 'subscriber', params: { id: scope.row.owner.id } }">
                                <img :src="scope.row.owner.photo" class="fc_contact_photo"/>
                                <span>{{ scope.row.owner.full_name || scope.row.owner.email }}</span>
                            </router-link>
                        </span>
                        <span v-else>--</span>
                    </template>
                </el-table-column>

                <el-table-column :label="$t('Industry')"
                                 v-if="columns.indexOf('industry') != -1"
                                 min-width="250px"
                                 property="industry"
                                 sortable="custom"
                >
                </el-table-column>

                <el-table-column
                    sortable="custom" min-width="120" v-if="columns.indexOf('type') != -1" :label="$t('Type')"
                    property="type">
                </el-table-column>

                <el-table-column min-width="120" v-if="columns.indexOf('phone') != -1" :label="$t('Phone')"
                                 property="phone">
                </el-table-column>

                <el-table-column
                    min-width="260"
                    v-if="columns.indexOf('address') != -1"
                    :label="$t('Address')"
                    property="address_line_1"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        <span>{{ getFormattedAddress(scope.row) }}</span>
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="130"
                    v-if="columns.indexOf('city') != -1"
                    :label="$t('City')"
                    property="city">
                    <template slot-scope="scope">
                        <span>{{ scope.row.city }}</span>
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="130"
                    v-if="columns.indexOf('country') != -1"
                    :label="$t('Country')"
                    property="country">
                    <template slot-scope="scope">
                        <span>{{ countryName(scope.row.country) }}</span>
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Website')"
                    width="150"
                    v-if="columns.indexOf('website') != -1"
                    property="website"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        <a v-if="scope.row.website" target="_blank" rel="noopener" :title="scope.row.website"
                           :href="scope.row.website">
                            {{ getDomainName(scope.row.website) }}
                        </a>
                        <span v-else>n/a</span>
                    </template>
                </el-table-column>

                <el-table-column width="150" v-for="customField in appVars.company_custom_fields"
                                 :label="customField.label"
                                 :key="customField.slug"
                                 v-if="columns.indexOf('_custom_' + customField.slug) != -1"
                >
                    <template slot-scope="scope">
                        {{ getCustomValue(scope.row, customField.slug) }}
                    </template>
                </el-table-column>

                <el-table-column width="100" label="Contacts" prop="contacts_count"/>

                <el-table-column
                    v-if="columns.indexOf('employees_number') != -1"
                    width="100"
                    :label="$t('Employees')"
                    property="employees_number"/>

                <el-table-column v-if="columns.indexOf('updated_at') != -1"
                                 :label="$t('Last Changed')"
                                 width="190"
                                 property="updated_at"
                                 sortable="custom"
                >
                    <template slot-scope="scope">
                        <template v-if="scope.row.updated_at">
                            <i class="el-icon-time"></i>
                            <span :title="scope.row.updated_at">{{ scope.row.updated_at | nsHumanDiffTime }}</span>
                        </template>
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Date Added')"
                    width="190"
                    property="created_at"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        <i class="el-icon-time"></i>
                        <span :title="scope.row.created_at">{{ scope.row.created_at | nsHumanDiffTime }}</span>
                    </template>
                </el-table-column>

            </el-table>
        </div>
        <el-row v-if="!show_skeleton" :guter="20" class="d-flex items-center">
            <el-col :xs="24" :md="12">
                <div style="padding-left: 15px;" v-if="selection" class="fc_filter_boxes mt-5">
                    <bulk-company-actions @refetch="fetch()" :selectedCompanies="selectedCompanies" :options="options"/>
                </div>
                <div v-else>&nbsp;</div>
            </el-col>
            <el-col :xs="24" :md="12">
                <pagination @per_page_change="(size) => {storage.set('company_perpage', size)}"
                            :pagination="pagination" :hide_on_single="false" :extra_sizes="[200, 250, 300, 400, 600]"
                            @fetch="fetch"/>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import BulkActionMenu from '@/Pieces/BulkActionMenu';
import Toggler from './Filter/Toggler';
import Searcher from '@/Modules/Contacts/Searcher/Searcher';
import Pagination from '@/Pieces/Pagination';
import BulkCompanyActions from './_BulkCompanyActions';
import {getDomainName, getFormattedAddress} from '@/Bits/data_config.js';
import isArray from 'lodash/isArray';

export default {
    name: 'CompanyTable',
    components: {
        BulkActionMenu,
        Toggler,
        Searcher,
        Pagination,
        BulkCompanyActions
    },
    props: ['companies', 'always_searchbar', 'show_skeleton', 'is_loading', 'query_data', 'pagination', 'options'],
    data() {
        return {
            selection: false,
            initialFired: false,
            selectedCompanies: [],
            selectionCount: 0,
            columns: [],
            countries: window.fcAdmin.countries,
            filterItems: {
                company_categories: {
                    key: 'company_categories',
                    title: 'Industry',
                    type: 'selections'
                },
                company_types: {
                    key: 'company_types',
                    title: 'Company Type',
                    type: 'selections'
                }
            },
            popFilter: {
                key: '',
                value: ''
            }
        }
    },
    computed: {
        currentFilterableItems() {
            const items = [];
            for (const key in this.filterItems) {
                if (!this.query_data.inline_filters[key]) {
                    items.push(this.filterItems[key]);
                }
            }
            return items;
        }
    },
    methods: {
        onSelection(companies) {
            this.selection = !!companies.length;
            this.selectedCompanies = companies;
            this.selectionCount = companies.length;
        },
        match(item, container) {
            if (container[item.slug]) {
                container[item.slug]++;
            } else {
                container[item.slug] = 1;
            }
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_order = 'DESC';
            } else {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_order = 'ASC';
            }
            this.fetch();
        },
        fetch() {
            this.selection = false;
            this.selectedCompanies = [];
            this.$emit('fetch');
        },
        maybeReFetch() {
            let willRefetch = false;

            if (!this.initialFired) {
                willRefetch = true;
            }

            if (willRefetch) {
                this.fetch();
                this.initialFired = true;
            }
        },
        listeners() {
            this.addAction('search-subscribers', 'fluentcrm', query => {
                this.query_data.search = query;
                this.pagination.current_page = 1;
                this.fetch();
            });

            this.addAction('loading', 'fluentcrm', status => {
                this.loading = status;
            });
        },
        countryName(countryCode) {
            let countryTitle = '';
            this.countries.map(country => {
                if (country.code == countryCode) {
                    countryTitle = country.title;
                }
            });
            return countryTitle;
        },
        getFormattedAddress,
        getDomainName,
        pushInlineSearch() {
            if (this.popFilter.key && this.popFilter.value) {
                this.$set(this.query_data.inline_filters, this.popFilter.key, this.popFilter.value);
                this.fetch();
                this.$refs.companies_inline_filters.doClose();
                this.popFilter = {
                    key: '',
                    value: ''
                };
            }
        },
        removeFilterItem(filterKey) {
            this.$delete(this.query_data.inline_filters, filterKey);
            this.fetch();
        },
        getCustomValue(row, key) {
            const meta = row.meta;
            if (!meta || this.isEmptyValue(meta.custom_values)) {
                return '';
            }

            const value = meta.custom_values[key] || '';

            if (isArray(value)) {
                return value.join(', ');
            }

            return value;
        }
    },
    mounted() {
        this.listeners();
    }
}
</script>

<style lang="scss">
ul.fc_inline_filters {
    margin: 0;
    padding: 0;
    list-style: none;

    li {
        margin: 0 5px 0 0;
        padding: 3px 7px;
        border: 1px solid #dddfe6;
        border-radius: 5px;
        color: #606266;
        font-weight: normal;
        display: inline-block;
    }
}
</style>
