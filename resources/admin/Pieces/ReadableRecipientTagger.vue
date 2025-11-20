<template>
    <div v-loading="fetchingData">
        <template v-if="settings.sending_filter == 'list_tag'">
            <div class="">
                <div class="fc_section_heading">
                    <h3>{{ $t('Sending To Contacts') }}</h3>
                </div>
                <table class="fc_horizontal_table">
                    <thead>
                    <tr>
                        <th>{{ $t('List') }}</th>
                        <th>{{ $t('Tag') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(formItem, key) in settings.subscribers" :key="key">
                        <td>
                            <el-select :disabled="true" size="small" :placeholder="$t('Choose a List')"
                                       v-model="formItem.list"
                                       filterable
                                       popper-class="list">
                                <el-option :label="$t('All Lists')" value="all">
                                         <span>
                                            {{ $t('All available subscribers') }}
                                        </span>
                                    <span class="list-metrics">
                                            {{ $t('Rec_This_wfaac') }}
                                        </span>
                                </el-option>
                                <el-option
                                    v-for="list in lists"
                                    :key="list.id"
                                    :label="list.title"
                                    :value="String(list.id)"
                                >
                                        <span>
                                            {{ list.title }}
                                        </span>
                                    <span class="list-metrics">
                                            {{ list.subscribersCount }} {{ $t('subscribed contacts') }}
                                        </span>
                                </el-option>
                            </el-select>
                        </td>
                        <td>
                            <el-select :disabled="true" :placeholder="$t('Select Tag')" size="small" filterable
                                       v-model="formItem.tag">
                                <el-option-group
                                    v-for="(group, index) in all_tag_groups"
                                    :key="index"
                                    :label="group.title"
                                >
                                    <el-option
                                        v-for="(item, index) in group.options"
                                        :key="index"
                                        :label="item.title"
                                        :value="String(item.id)"
                                    >
                                        <span>{{ item.title }}</span>
                                    </el-option>
                                </el-option-group>
                            </el-select>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="settings.excludedSubscribers && settings.excludedSubscribers.length">
                <div class="fc_section_heading">
                    <h3>{{ $t('Excluded Contacts') }}</h3>
                </div>
                <table class="fc_horizontal_table">
                    <thead>
                    <tr>
                        <th>{{ $t('List') }}</th>
                        <th>{{ $t('Tag') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(formItem, key) in settings.excludedSubscribers" :key="key">
                        <td>
                            <el-select :disabled="true" size="small" clearable :placeholder="$t('Choose a List')"
                                       v-model="formItem.list"
                                       filterable
                                       popper-class="list">
                                <el-option :label="$t('All Lists')" value="all">
                                         <span>
                                            {{ $t('All available contacts') }}
                                        </span>
                                    <span class="list-metrics">
                                            {{ $t('Rec_This_wfaac') }}
                                        </span>
                                </el-option>
                                <el-option
                                    v-for="list in lists"
                                    :key="list.id"
                                    :label="list.title"
                                    :value="String(list.id)"
                                >
                                        <span>
                                            {{ list.title }}
                                        </span>
                                    <span class="list-metrics">
                                            {{ list.subscribersCount }} {{ $t('subscribed contacts') }}
                                        </span>
                                </el-option>
                            </el-select>
                        </td>
                        <td>
                            <el-select :disabled="true" clearable filterable :placeholder="$t('Select')" size="small"
                                       v-model="formItem.tag">
                                <el-option-group
                                    v-for="(group, index) in all_tag_groups"
                                    :key="index"
                                    :label="group.title"
                                >
                                    <el-option
                                        v-for="(item, index) in group.options"
                                        :key="index"
                                        :label="item.title"
                                        :value="String(item.id)"
                                    >
                                        <span>{{ item.title }}</span>
                                    </el-option>
                                </el-option-group>
                            </el-select>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </template>
        <template v-else-if="settings.sending_filter == 'dynamic_segment'">
            <div>
                <template>
                    <div class="fc_section_heading">
                        <h3>{{ $t('Dynamic Segment') }}</h3>
                    </div>
                    <el-select :disabled="true" filterable :placeholder="$t('Select')" value-key="uid"
                               v-model="settings.dynamic_segment">
                        <el-option
                            v-for="segment in segments"
                            :key="segment.slug+'_'+segment.id"
                            :value="{uid: segment.slug+'_'+segment.id, slug: segment.slug, id: segment.id}"
                            :label="segment.title"
                        ></el-option>
                    </el-select>
                </template>
            </div>
        </template>
        <template v-else-if="settings.sending_filter == 'advanced_filters'">
            <div class="fc_rich_container" v-if="has_campaign_pro">
                <div class="fc_section_heading">
                    <h3>{{ $t('Advanced Filter') }}</h3>
                </div>
                <div class="fc_rich_wrap">
                    <div v-for="(rich_filter, filterIndex) in advanced_filters" :key="filterIndex">
                        <div class="fc_rich_filter">
                            <rich-filter :add_label="FilterLabel" :view_only="true" :items="rich_filter"/>
                        </div>
                        <div class="fc_cond_or">
                            <em>{{ $t('OR') }}</em>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script type="text/babel">
import RichFilter from '../Modules/Contacts/RichFilters/Filters';

export default {
    name: 'RecipientTaggerView',
    props: ['settings'],
    components: {
        RichFilter
    },
    data() {
        return {
            fetchingData: false,
            lists: [],
            tags: [],
            segments: [],
            advanced_filters: [[]],
            FilterLabel: this.$t('Filters.instruction')
        }
    },
    computed: {
        all_tag_groups() {
            return {
                all: {
                    title: '',
                    options: [
                        {
                            title: this.$t('Rec_All_coSLs'),
                            slug: 'all',
                            id: 'all'
                        }
                    ]
                },
                tags: {
                    title: this.$t('Tags'),
                    options: this.tags
                }
            };
        }
    },
    methods: {
        fetch() {
            this.fetchingData = true;
            this.$get('reports/options', {fields: 'lists,tags,segments', with_count: ['lists']})
                .then(response => {
                    this.lists = response.options.lists;
                    this.tags = response.options.tags;
                    this.segments = response.options.segments;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.fetchingData = false;
                });
        }
    },
    mounted() {
        this.fetch();
    },
    created() {
        if (this.settings.advanced_filters) {
            this.advanced_filters = this.settings.advanced_filters;
        } else {
            this.settings.advanced_filters = this.advanced_filters;
        }
    }
}
</script>
