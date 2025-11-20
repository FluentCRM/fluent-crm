<template>
    <div v-loading="loading" class="fluentcrm-lists fluentcrm_min_bg fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'dynamic_segments' }">
                        {{ $t('Dynamic Segments') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ segment.title }}
                    </el-breadcrumb-item>
                </el-breadcrumb>
                <p>{{ segment.subtitle }}</p>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    @click="$router.push({ name: 'dynamic_segments' })"
                    size="small">
                    {{ $t('Back') }}
                </el-button>
            </div>
        </div>

        <div class="fluentcrm_pad_around">
            <div class="fc_segment_desc_block">
                <h3>{{ segment.description }}</h3>
                <div v-if="!segment.is_system">
                    <el-button :type="is_editing?'danger':'info'" @click="is_editing = !is_editing">
                        <span v-if="is_editing">{{ $t('Cancel Editing') }}</span>
                        <span v-else>{{ $t('Edit Configuration') }}</span>
                    </el-button>
                </div>
                <div v-if="is_editing" class="text-align-left fc_segment_editor">
                    <el-form v-loading="field_loading" label-position="top" class="fc_segment_form" :data="segment">
                        <div class="fc_section_heading">
                            <h3>{{ $t('Name this Custom Segment') }}</h3>
                            <p>{{ $t('custom_segment.name_desc') }}</p>
                            <el-input :placeholder="$t('eg: Active Contacts')" type="text"
                                      v-model="segment.title"></el-input>
                        </div>
                        <custom-segment-settings
                            @loaded="() => { field_loading = false; }"
                            v-model="segment.filters"
                            @updateSegment="updateSegment(false)"
                            :segment_id="segment.id"/>
                    </el-form>
                </div>
            </div>

            <div class="lists-table fcrm_segment_contacts">
                <contacts-table
                    :always_searchbar="true"
                    :options="options"
                    :query_data="query_data"
                    :is_loading="loading"
                    :ui_config="ui_config"
                    :subscribers="subscribers"
                    @fetch="fetchSegment"
                    :pagination="pagination"
                />
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import CustomSegmentSettings from './_CustomSegementSettings';
import ContactsTable from '../Contacts/_ContactsTable';

export default {
    name: 'DynamicSegmentViewer',
    props: ['slug', 'id'],
    components: {
        CustomSegmentSettings,
        ContactsTable
    },
    data() {
        return {
            segment: {},
            loading: true,
            saving: false,
            subscribers: [],
            columns: [],
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0
            },
            is_editing: false,
            field_loading: true,
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
        fetchSegment() {
            this.loading = true;
            this.$get(`dynamic-segments/${this.slug}/subscribers/${this.id}`, {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                ...this.query_data
            })
                .then((response) => {
                    this.segment = response.segment;
                    this.subscribers = response.subscribers.data;
                    this.pagination.total = response.subscribers.total;
                    this.changeTitle(this.segment.title + ' - Dynamic Segments');
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        updateSegment(isClosed = true) {
            if (!this.segment.title) {
                this.$notify.error(this.$t('Cre_Please_pNotS'));
                return;
            }
            this.saving = true;
            this.$put(`dynamic-segments/${this.segment.id}`, {
                segment: JSON.stringify({
                    title: this.segment.title,
                    filters: this.segment.filters
                }),
                with_subscribers: false
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.pagination.current_page = 1;
                    this.fetchSegment();
                    if (isClosed) {
                        this.is_editing = false;
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.saving = false;
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
