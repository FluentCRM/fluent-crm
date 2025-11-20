<template>
    <div :class="{ fc_has_selections: selectedSubscribers.length }" style="position: relative;" class="fc_contacts_wrap">
        <div class="fluentcrm-header-secondary">
            <bulk-action-menu>
                <div v-if="!selection" class="fc_filter_boxes">
                    <template v-if="!ui_config.disable_filters_without_selection">
                        <manager
                            v-if="ui_config.show_list"
                            type="lists"
                            :selection="selection"
                            :options="options.lists"
                            :matched="matched_lists"
                            :subscribers="subscribers"
                            :total_match="pagination.total"
                            :selected="query_data.lists"
                            :selectionCount="selectionCount"
                            @filter="filter"
                            @search="search"
                            @subscribe="subscribe"
                        />

                        <manager
                            v-if="ui_config.show_tag"
                            type="tags"
                            :selection="selection"
                            :options="options.tags"
                            :matched="matched_tags"
                            :selected="query_data.tags"
                            :subscribers="subscribers"
                            :total_match="pagination.total"
                            :selectionCount="selectionCount"
                            @search="search"
                            @filter="filter"
                            @subscribe="subscribe"
                        />

                        <template v-if="!selection">
                            <manager
                                type="statuses"
                                :selection="false"
                                :options="options.statuses"
                                :matched="matched_statuses"
                                :subscribers="subscribers"
                                :total_match="pagination.total"
                                :selected="query_data.statuses"
                                :selectionCount="selectionCount"
                                @filter="filter"
                                @search="search"
                                @subscribe="subscribe"
                            />
                        </template>
                        <template v-else>
                            <property-changer :selectedSubscribers="selectedSubscribers" :label="$t('Status')"
                                              prop_key="status"
                                              :options="options.statuses"
                                              @fetch="fetch"/>
                        </template>
                    </template>
                </div>
                <div v-else class="fc_filter_boxes">
                    <bulk-contact-actions @refetch="fetch()" :pagination="pagination" :selectedSubscribers="selectedSubscribers" :options="options" />
                </div>
                <div class="fc_search_box">
                    <slot name="before_search_box" />
                    <searcher :disabled="filter_type == 'advanced'" v-model="query_data.search"/>
                    <toggler @dataChanged="maybeReFetch()" v-model="columns"/>
                    <slot name="after_search_box"></slot>
                </div>
            </bulk-action-menu>
        </div>
        <slot name="before_contacts_table"></slot>
        <div class="fc_contacts_table">
            <slot name="in_contacts_table"></slot>
            <el-skeleton style="padding: 20px;" v-if="show_skeleton" :rows="10"></el-skeleton>
            <el-table :default-sort="{ prop: query_data.sort_by, order: (query_data.sort_type == 'DESC') ? 'descending' : 'ascending' }" v-else :empty-text="$t('No Contacts Found')" :data="subscribers"
                      border
                      id="fluentcrm-subscribers-table"
                      @selection-change="onSelection"
                      style="width: 100%"
                      stripe
                      @sort-change="handleSortable"
                      ref="subscribersTable"
            >
                <el-table-column v-if="hasPermission('fcrm_manage_contacts')" type="selection" width="40"/>

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
                                 width="200"
                                 sortable="custom"
                >
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'subscriber', params: { id: scope.row.id } }">
                            {{ scope.row.email }}
                        </router-link>
                        <el-tooltip class="item" effect="dark" placement="top">
                            <div slot="content">{{ copiedText.id == scope.row.id ? copiedText.title : $t('Copy Email') }}</div>
                            <i @click="copyText(scope.row.email, scope.row.id)" class="copy-text el-icon-copy-document"></i>
                        </el-tooltip>
                    </template>
                </el-table-column>

                <el-table-column
                    v-if="columns.indexOf('prefix') != -1"
                    :label="$t('Prefix')"
                    min-width="70"
                    property="prefix"
                >
                    <template slot-scope="scope">
                        {{ scope.row.prefix }}
                    </template>
                </el-table-column>

                <el-table-column
                    v-if="columns.indexOf('first_name') != -1"
                    :label="$t('First Name')"
                    min-width="140"
                    property="first_name"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.first_name }}
                    </template>
                </el-table-column>

                <el-table-column
                    v-if="columns.indexOf('last_name') != -1"
                    :label="$t('Last Name')"
                    min-width="140"
                    property="last_name"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.last_name }}
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Full Name')"
                    min-width="180"
                    property="first_name"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.full_name }}
                    </template>
                </el-table-column>

                <el-table-column min-width="180" v-if="columns.indexOf('lists') != -1" :label="$t('Lists')"
                                 property="lists">
                    <template slot-scope="scope">
                        <span>{{ getRelations(scope.row, 'lists') }}</span>
                    </template>
                </el-table-column>

                <el-table-column min-width="230" v-if="columns.indexOf('tags') != -1" :label="$t('Tags')"
                                 property="tags">
                    <template slot-scope="scope">
                        <span>{{ getRelations(scope.row, 'tags') }}</span>
                    </template>
                </el-table-column>

                <el-table-column min-width="230" v-if="has_company_module && columns.indexOf('companies') != -1" :label="$t('Companies')"
                                 property="companies">
                    <template slot-scope="scope">
                        <span>{{ getRelations(scope.row, 'companies') }}</span>
                    </template>
                </el-table-column>

                <el-table-column min-width="220" v-if="has_company_module && columns.indexOf('primary_company') != -1" :label="$t('Primary Company')">
                    <template slot-scope="scope">
                        <router-link v-if="scope.row.company" :to="{ name: 'view_company', params: { company_id: scope.row.company_id } }" class="fc_company_cell fc_photo_text">
                            <span v-if="scope.row.company.logo" class="fc_company_photo">
                                <img :src="scope.row.company.logo"/>
                            </span>
                            <span style="flex: 1;">{{ scope.row.company.name }}</span>
                        </router-link>
                        <span v-else>--</span>
                    </template>
                </el-table-column>

                <template v-if="hasCommerceFields">
                    <el-table-column min-width="120" v-if="columns.indexOf('commerce.total_order_value') != -1"
                                     :label="$t('Lifetime Value')"
                                     property="commerce_by_provider.total_order_value">
                        <template slot-scope="scope">
                            <span v-if="scope.row.commerce_by_provider">
                                 {{ appVars.commerce_currency_sign }}{{
                                    scope.row.commerce_by_provider.total_order_value || '-'
                                }}
                            </span>
                            <span v-else>-</span>
                        </template>
                    </el-table-column>
                    <el-table-column min-width="120" v-if="columns.indexOf('commerce.total_order_count') != -1"
                                     :label="$t('Order Count')"
                                     property="commerce_by_provider.total_order_count">
                        <template slot-scope="scope">
                                <span v-if="scope.row.commerce_by_provider">
                                     {{ scope.row.commerce_by_provider.total_order_count || '-' }}
                                </span>
                            <span v-else>-</span>
                        </template>
                    </el-table-column>
                    <el-table-column min-width="180" v-if="columns.indexOf('commerce.first_order_date') != -1"
                                     :label="$t('Customer Since')"
                                     property="commerce_by_provider.first_order_date">
                        <template slot-scope="scope">
                                <span
                                    v-if="scope.row.commerce_by_provider && scope.row.commerce_by_provider.first_order_date">
                                     {{ nsDateFormat(scope.row.commerce_by_provider.first_order_date,) }}
                                </span>
                            <span v-else>-</span>
                        </template>
                    </el-table-column>
                    <el-table-column min-width="180" v-if="columns.indexOf('commerce.last_order_date') != -1"
                                     :label="$t('Last order')"
                                     property="commerce_by_provider.last_order_date">
                        <template slot-scope="scope">
                                <span
                                    v-if="scope.row.commerce_by_provider && scope.row.commerce_by_provider.last_order_date">
                                     {{ scope.row.commerce_by_provider.last_order_date | nsHumanDiffTime }}
                                </span>
                            <span v-else>-</span>
                        </template>
                    </el-table-column>
                </template>

                <el-table-column min-width="120" v-if="columns.indexOf('phone') != -1" :label="$t('Phone')"
                                 property="phone">
                    <template slot-scope="scope">
                        {{ scope.row.phone }}
                        <el-tooltip v-if="scope.row.phone" class="item" effect="dark" placement="top">
                            <div slot="content">{{ copiedText.id == scope.row.id ? copiedText.title : $t('Copy Phone Number') }}</div>
                            <i @click="copyText(scope.row.phone, scope.row.id)" class="copy-text el-icon-copy-document"></i>
                        </el-tooltip>
                    </template>
                </el-table-column>

                <el-table-column min-width="120" v-if="columns.indexOf('date_of_birth') != -1" :label="$t('Date of Birth')"
                                 property="date_of_birth">
                    <template slot-scope="scope">
                        {{ scope.row.date_of_birth !== '0000-00-00' ? scope.row.date_of_birth : '-' }}
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="160"
                    v-if="columns.indexOf('address_line_1') != -1"
                    :label="$t('Address Line 1')"
                    property="address_line_1"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.address_line_1 }}
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="160"
                    v-if="columns.indexOf('address_line_2') != -1"
                    :label="$t('Address Line 2')"
                    property="address_line_2"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.address_line_2 }}
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="120"
                    v-if="columns.indexOf('city') != -1"
                    :label="$t('City')"
                    property="city"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.city }}
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="120"
                    v-if="columns.indexOf('state') != -1"
                    :label="$t('State')"
                    property="state"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.state }}
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="120"
                    v-if="columns.indexOf('postal_code') != -1"
                    :label="$t('Zip Code')"
                    property="postal_code"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ scope.row.postal_code }}
                    </template>
                </el-table-column>

                <el-table-column
                    min-width="120"
                    v-if="columns.indexOf('country') != -1"
                    :label="$t('Country')"
                    property="country"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ countryName(scope.row.country) }}
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Type')"
                    width="150"
                    v-if="columns.indexOf('contact_type') != -1"
                    property="contact_type"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ trans(scope.row.contact_type) | ucWords }}
                    </template>
                </el-table-column>

                <el-table-column
                    :label="$t('Status')"
                    width="150"
                    v-if="columns.indexOf('status') != -1"
                    property="status"
                    sortable="custom"
                >
                    <template slot-scope="scope">
                        {{ trans(scope.row.status) | ucWords }}
                    </template>
                </el-table-column>

                <el-table-column width="200" v-for="customField in custom_fields" :key="customField.slug"
                                 :label="customField.label"
                >
                    <template slot-scope="scope">
                        {{ formatCellValue(scope.row.custom_fields, customField.slug) }}
                    </template>
                </el-table-column>

                <el-table-column min-width="150" v-if="columns.indexOf('source') != -1" :label="$t('Source')"
                                 property="source">
                    <template slot-scope="scope">
                        {{ scope.row.source }}
                    </template>
                </el-table-column>

                <el-table-column prop="last_activity"
                                 v-if="columns.indexOf('last_activity') != -1"
                                 :label="$t('Last Activity')"
                                 min-width="190"
                                 property="last_activity"
                                 sortable="custom"
                >
                    <template slot-scope="scope">
                        <template v-if="scope.row.last_activity">
                            <i class="el-icon-time"></i>
                            <span>
                                <span
                                    :title="scope.row.last_activity">{{
                                        scope.row.last_activity | nsHumanDiffTime
                                    }}</span>
                        </span>
                        </template>
                    </template>
                </el-table-column>

                <el-table-column
                    v-if="columns.indexOf('created_at') != -1"
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

                <el-table-column v-if="columns.indexOf('updated_at') != -1"
                                 :label="$t('Last Changed')"
                                 min-width="190"
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
            </el-table>
        </div>
        <el-row v-if="!show_skeleton" :guter="20" class="d-flex items-center">
            <el-col :xs="24" :md="12">
                <div style="padding-left: 15px;" v-if="selection" class="fc_filter_boxes mt-5">
                    <bulk-contact-actions @refetch="fetch()" :selectedSubscribers="selectedSubscribers" :options="options" />
                </div>
                <div v-else>&nbsp;</div>
            </el-col>
            <el-col :xs="24" :md="12">
                <pagination @per_page_change="(size) => {storage.set('contact_perpage', size)}"
                            :pagination="pagination" :hide_on_single="false" :extra_sizes="[200, 250, 300, 400, 600]" @fetch="fetch"/>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import Manager from './Manager';
import Toggler from './Filter/Toggler';
import Searcher from './Searcher/Searcher';
import BulkActionMenu from '@/Pieces/BulkActionMenu';
import PropertyChanger from './Filter/PropertyChanger';
import Pagination from '@/Pieces/Pagination';
import BulkContactActions from './_BulkContactActions';

export default {
    name: 'ContactsTable',
    components: {
        Toggler,
        Manager,
        Searcher,
        BulkActionMenu,
        PropertyChanger,
        Pagination,
        BulkContactActions
    },
    props: ['subscribers', 'always_searchbar', 'show_skeleton', 'is_loading', 'query_data', 'pagination', 'options', 'ui_config', 'fire_ready_fetch', 'filter_type'],
    data() {
        return {
            selection: false,
            selectedSubscribers: [],
            selectionCount: 0,
            matched_tags: [],
            matched_lists: [],
            matched_statuses: [],
            columns: [],
            hasCustomFields: false,
            hasCommerceFields: false,
            initialFired: false,
            countries: window.fcAdmin.countries,
            copiedText: {
                id: '',
                title: ''
            }
        }
    },
    computed: {
        custom_fields() {
            const contactCustomFields = this.appVars.contact_custom_fields;
            if (!contactCustomFields) {
                return [];
            }
            return contactCustomFields.filter((item) => {
                return this.columns.indexOf(item.slug) !== -1;
            });
        }
    },
    methods: {
        fetch() {
            this.selection = false;
            this.selectedSubscribers = [];
            this.$emit('fetch');
        },
        getRelations(subscriber, type) {
            const items = subscriber[type] || [];
            let field = 'title';
            if (type === 'companies') {
                field = 'name';
            }
           if (items.length > 0) {
               return items.map(item => item[field]).join(', ');
           }
           return '';
        },
        formatCellValue(obj, slug) {
            if (!obj) {
                return '';
            }
            const value = obj[slug];
            if (value && typeof value == 'object') {
                return value.join(', ');
            }
            return value;
        },
        onSelection(subscribers) {
            this.selection = !!subscribers.length;

            this.selectedSubscribers = subscribers;

            const matchedTags = {};
            const matchedLists = {};

            subscribers.forEach(subscriber => {
                subscriber.tags.forEach(tag => this.match(tag, matchedTags));
                subscriber.lists.forEach(list => this.match(list, matchedLists));
            });

            this.selectionCount = subscribers.length;
            this.matched_tags = matchedTags;
            this.matched_lists = matchedLists;
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
                this.query_data.sort_type = 'DESC';
            } else {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_type = 'ASC';
            }
            this.fetch();
        },
        subscribe({type, payload}) {
            const {attach, detach} = payload;

            this.loading = true;

            const query = {
                type,
                attach,
                detach,
                subscribers: this.selectedSubscribers.map(item => item.id)
            };

            this.$post('subscribers/sync-segments', query).then(response => {
                response.subscribers.forEach(subscriber => {
                    const index = this.subscribers.findIndex(item => {
                        return item.id === subscriber.id;
                    });

                    if (index !== -1) {
                        this.subscribers.splice(index, 1, subscriber);
                    }

                    this.$refs.subscribersTable.toggleRowSelection(this.subscribers[index]);
                });

                const selected = `selected_${type}`;

                if (this[selected] && this[selected].length && detach && detach.length) {
                    const items = this[selected].filter(item => detach.includes(item));

                    if (items.length) {
                        this.filter({type, payload: items})
                    }
                }

                this.loading = false;

                this.$notify.success({
                    title: this.$t('Great!'),
                    message: response.message,
                    offset: 19
                });
            });
        },
        maybeReFetch() {
            let willRefetch = false;

            if (!this.hasCommerceFields && this.appVars.commerce_provider) {
                const hasCommerceSelection = this.columns.filter((item) => {
                    return item.indexOf('commerce.') === 0;
                }).length;

                if (hasCommerceSelection) {
                    willRefetch = true;
                    this.hasCommerceFields = true;
                    this.query_data.has_commerce = true;
                }
            }

            if (this.columns.indexOf('primary_company') !== -1) {
                this.query_data.primary_company = true;
                willRefetch = true;
            }

            if ((!this.hasCustomFields && this.custom_fields.length)) {
                this.query_data.custom_fields = true;
                this.hasCustomFields = true;
                this.willRefetch = true;
            }

            if (!this.initialFired) {
                willRefetch = true;
            }

            if (willRefetch) {
                this.fetch();
                this.initialFired = true;
            }
        },
        filter({type, payload}) {
            this.$set(this.query_data, type, payload);
            this.pagination.current_page = 1;
            return this.fetch();
        },
        search(type, value) {
            this.options[type] = value;
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
        copyText(string, id) {
            this.copiedText.id = id;

            if (!string) {
                this.copiedText.title = this.$t('Nothing to copy');
                return '';
            }

            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = string; // this.textToCopy;
            document.body.appendChild(tempInput);

            // Select the text in the input
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); // For mobile devices

            // Copy the text to the clipboard
            document.execCommand('copy');

            // Remove the temporary input element
            document.body.removeChild(tempInput);
            this.copiedText.title = this.$t('Copied');
        }
    },
    mounted() {
        this.listeners();
    }
}
</script>
