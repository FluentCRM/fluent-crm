<template>
    <div class="fluentcrm-subscribers fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <contact-header-pop-nav :head_title="$t('Companies')"></contact-header-pop-nav>
                    <span v-show="pagination.total" class="ff_small">({{ pagination.total | formatMoney }})</span>
                </h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <action-menu :query_data="query_data" v-if="hasPermission('fcrm_manage_contact_cats')"
                             :options="options" @fetch="fetch"/>
            </div>
        </div>
        <div class="fluentcrm_body fluentcrm_pad_b_15 fluentcrm_subscribers">
            <company-table
                :always_searchbar="false"
                v-if="app_ready"
                :options="options"
                :query_data="query_data"
                :is_loading="false"
                :companies="companies"
                @fetch="fetch"
                :pagination="pagination"
                :show_skeleton="first_loading"
                :ui_config="ui_config"
            >
            </company-table>
        </div>
    </div>
</template>

<script type="text/babel">
import ActionMenu from './ActionMenu';
import CompanyTable from './_CompanyTable';
import ContactHeaderPopNav from '@/Pieces/ContactHeaderPopNav.vue';

export default {
    name: 'AllCompanies',
    components: {
        ActionMenu,
        CompanyTable,
        ContactHeaderPopNav
    },
    data() {
        return {
            companies: [],
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1
            },
            query_data: {
                search: '',
                sort_by: 'id',
                sort_order: 'DESC',
                page: null,
                categories: [],
                inline_filters: {}
            },
            loading: false,
            app_ready: false,
            first_loading: true,
            options: {
                types: [],
                categories: []
            },
            ui_config: {
                show_category: true
            },
            categoryId: null
        }
    },
    methods: {
        getOptions() {
            this.options = {
                categories: window.fcAdmin.company_categories,
                types: window.fcAdmin.company_types
            };
        },
        setup() {
            let queryParams = this.$route.query;

            if (window.fcrm_company_query_params) {
                queryParams = window.fcrm_company_query_params;
            }

            const queryDefaults = {
                search: queryParams.search || '',
                sort_by: queryParams.sort_by || 'id',
                sort_order: queryParams.sort_order || 'DESC',
                inline_filters: {}
            };

            if (queryParams.page) {
                this.pagination.current_page = parseInt(queryParams.page);
            }
            this.query_data = queryDefaults;

            let status = false;

            if (this.$route.params.categoryId) {
                status = true;
                this.categoryId = this.$route.params.categoryId;
                this.query_data.categories = [parseInt(this.$route.params.categoryId)];
                this.ui_config.show_category = false;
            } else if (this.$route.params.categories) {
                console.log(this.$route.params.categories);
            }

            this.app_ready = true;
            return status;
        },
        fetch() {
            this.loading = true;
            let query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                sort_by: this.query_data.sort_by,
                sort_order: this.query_data.sort_order
            };

            query = {...this.query_data, ...query};

            const params = {};

            this.each(query, (val, key) => {
                if (!this.isEmptyValue(val)) {
                    params[key] = val;
                }
            });

            window.fcrm_company_query_params = params;

            params.t = Date.now();

            this.$router.replace({
                name: 'companies', query: params
            });

            this.$get('companies', query)
                .then(response => {
                    this.companies = response.companies.data;
                    this.pagination.total = response.companies.total;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                    this.first_loading = false;
                });
        }
    },
    mounted() {
        this.pagination.per_page = parseInt(this.storage.get('company_perpage', 10)) || 10;
        this.getOptions();
        this.setup();
        this.changeTitle(this.$t('Companies'));
    }
}
</script>
