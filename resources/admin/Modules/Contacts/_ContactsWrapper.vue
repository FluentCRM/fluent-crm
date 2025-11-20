<template>
    <div class="fc_subscribers_wrap">
        <div class="fluentcrm-header-secondary">
            <bulk-action-menu>
                <div class="fc_filter_boxes">
                    <manager
                        v-if="!listId"
                        type="lists"
                        :selection="selection"
                        :options="options.lists"
                        :matched="matched_lists"
                        :subscribers="subscribers"
                        :total_match="pagination.total"
                        :selected="selected_lists"
                        :selectionCount="selectionCount"
                        @filter="filter"
                        @search="search"
                        @subscribe="subscribe"
                    />

                    <manager
                        v-if="!tagId"
                        type="tags"
                        :selection="selection"
                        :options="options.tags"
                        :matched="matched_tags"
                        :selected="selected_tags"
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
                            :selected="selected_statuses"
                            :selectionCount="selectionCount"
                            @filter="filter"
                            @search="search"
                            @subscribe="subscribe"
                        />
                        <manager
                            v-if="false"
                            type="contact_types"
                            :selection="false"
                            :options="options.contact_types"
                            :matched="matched_types"
                            :subscribers="subscribers"
                            :total_match="pagination.total"
                            :selected="selected_types"
                            :selectionCount="selectionCount"
                            @filter="filter"
                            @subscribe="subscribe"
                        />
                        <toggler @dataChanged="maybeReFetch()" v-model="columns"/>
                    </template>
                    <template v-else>
                        <property-changer :selectedSubscribers="selectedSubscribers" :label="$t('Status')"
                                          prop_key="status"
                                          :options="options.statuses"
                                          @fetch="fetch"/>
                    </template>
                </div>
                <div class="fc_search_box">
                    <searcher />
                </div>
            </bulk-action-menu>
        </div>
    </div>
</template>

<script type="text/babel">
import Manager from './Manager';
import BulkActionMenu from '@/Pieces/BulkActionMenu';


export default {
    name: 'ContactTableWrapper',
    components: {
        Manager,
        BulkActionMenu
    },
    props: {
        listId: {
            default: ''
        },
        tagId: {
            default: ''
        },
        action_config: {
            type: Object,
            default() {
                return {
                    show_search: true,
                    show_filters: true,
                    show_selections: true,
                    show_columns: true,
                    show_actions: true
                }
            }
        },
        fetch: {
            type: Function,
            required: true
        },
        filter_settings: {
            type: Object,
            default() {
                return {
                    tags: [],
                    lists: [],
                    search: '',
                    statuses: [],
                    sort_by: 'id',
                    sort_type: 'DESC',
                    custom_fields: false
                }
            }
        }
    },
    data() {
        return {
            subscribers: [],
            loading: false
        }
    }
}
</script>
