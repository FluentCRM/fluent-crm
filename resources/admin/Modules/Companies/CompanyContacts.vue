<template>
    <div v-loading="loading">
        <contacts-table
            :fire_ready_fetch="true"
            :options="options"
            :always_searchbar="true"
            :query_data="query_data"
            :is_loading="loading"
            :ui_config="ui_config"
            :subscribers="subscribers"
            @fetch="fetchContacts()"
            :pagination="pagination"
        >
            <div slot="after_search_box">
                <contact-adder @reloadContacts="fetchContacts()" :company="company" />
            </div>
        </contacts-table>

    </div>
</template>

<script type="text/babel">
import ContactsTable from '../Contacts/_ContactsTable';
import ContactAdder from './Parts/ContactAdder';
export default {
    name: 'CompanyMembers',
    props: ['company_id', 'company'],
    components: {
        ContactsTable,
        ContactAdder
    },
    data() {
        return {
            loading: true,
            subscribers: [],
            columns: [],
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0
            },
            query_data: {
                tags: [],
                lists: [],
                search: '',
                statuses: [],
                sort_by: 'id',
                sort_type: 'DESC',
                custom_fields: false
            },
            ui_config: {
                show_list: true,
                show_tag: true,
                disable_filters_without_selection: true
            },
            options: {
                tags: [],
                lists: [],
                statuses: [],
                countries: [],
                sampleCsv: null,
                delimiter: 'comma'
            }
        }
    },
    methods: {
        fetchContacts() {
            this.loading = true;
            this.$get('subscribers', {
                company_ids: [this.company_id],
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                ...this.query_data
            })
                .then((response) => {
                    this.subscribers = response.subscribers.data;
                    this.pagination.total = response.subscribers.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getOptions() {
            const query = {
                fields: 'tags,lists,statuses,contact_types,sampleCsv,custom_fields'
            };

            this.$get('reports/options', query).then(response => {
                this.options = response.options;
            });
        }
    },
    mounted() {
        this.getOptions();
    }
}
</script>
