<template>
    <div class="fluentcrm_recipient_tagger">
        <el-form v-loading="fetchingData">
            <div style="margin-bottom: 20px" class="text-align-center">
                <el-radio-group v-model="settings.sending_filter">
                    <el-radio-button class="fc_list_tag_selector" label="list_tag">{{ $t('By List & Tag') }}</el-radio-button>
                    <el-radio-button class="fc_dynamic_segment_selector" label="dynamic_segment">{{ $t('By Dynamic Segment') }}</el-radio-button>
                    <el-radio-button class="fc_advanced_filters_selector" label="advanced_filters">{{ $t('By Advanced Filter') }}</el-radio-button>
                </el-radio-group>
            </div>
            <template v-if="settings.sending_filter == 'list_tag'">
                <div class="fc_narrow_box fc_white_inverse">
                    <div class="fc_section_heading">
                        <h3>{{ $t('Included Contacts') }}</h3>
                        <p>
                            {{ $t('Rec_Select_LaTtywtse') }}
                        </p>
                    </div>
                    <table class="fc_horizontal_table">
                        <thead>
                        <tr>
                            <th>{{ $t('Select A List') }}</th>
                            <th>{{ $t('Select Tag') }}</th>
                            <th>{{ $t('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(formItem, key) in settings.subscribers" :key="key">
                            <td>
                                <el-select size="small" :placeholder="$t('Choose a List')" v-model="formItem.list" filterable
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
                                <el-select :disabled="!formItem.list" :placeholder="formItem.list ? $t('Select Tag') : $t('Select List First')" size="small" filterable v-model="formItem.tag">
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
                            <td>
                                <el-button-group>
                                    <el-button size="small" @click="add('subscribers', key)">+</el-button>
                                    <el-button size="small" @click="remove('subscribers', key)">-</el-button>
                                </el-button-group>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <p>{{ $t('Rec_Select_Loasfatst') }}</p>
                </div>

                <div class="fc_narrow_box fc_white_inverse">
                    <div class="fc_section_heading">
                        <h3>{{ $t('Excluded Contacts') }}</h3>
                        <p>{{ $t('Rec_Select_LaTtywtef') }}</p>
                    </div>
                    <table class="fc_horizontal_table">
                        <thead>
                        <tr>
                            <th>{{ $t('Select A List') }}</th>
                            <th>{{ $t('Select Tag') }}</th>
                            <th>{{ $t('Actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(formItem, key) in settings.excludedSubscribers" :key="key">
                            <td>
                                <el-select size="small" clearable filterable :placeholder="$t('Choose a List')" v-model="formItem.list"
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
                                <el-select :disabled="!formItem.list" clearable filterable :placeholder="formItem.list ? $t('Select Tag') : $t('Select List First')" size="small" v-model="formItem.tag">
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
                            <td>
                                <el-button-group>
                                    <el-button size="small" @click="add('excludedSubscribers', key)">+</el-button>
                                    <el-button size="small" @click="remove('excludedSubscribers', key)">-</el-button>
                                </el-button-group>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template v-else-if="settings.sending_filter == 'dynamic_segment'">
                <div class="fc_narrow_box fc_white_inverse">
                    <template v-if="has_campaign_pro">
                        <div class="fc_section_heading">
                            <h3>{{ $t('Select Dynamic Segment') }}</h3>
                            <p>{{ $t('Rec_Please_stwdsywts') }}</p>
                        </div>
                        <el-select :placeholder="$t('Select')" value-key="uid" v-model="settings.dynamic_segment">
                            <el-option
                                v-for="segment in segments"
                                :key="segment.slug+'_'+segment.id"
                                :value="{uid: segment.slug+'_'+segment.id, slug: segment.slug, id: segment.id}"
                                :label="segment.title"
                            ></el-option>
                        </el-select>
                    </template>
                    <dynamic-segment-campaign-promo v-else></dynamic-segment-campaign-promo>
                </div>
            </template>
            <template v-else-if="settings.sending_filter == 'advanced_filters'">
                <div class="fc_narrow_box fc_white_inverse">
                    <div class="fc_rich_container" v-if="has_campaign_pro">
                        <div class="fc_section_heading">
                            <h3>{{ $t('Select custom contacts by advanced filters') }}</h3>
                        </div>
                        <div class="fc_rich_wrap">
                            <div v-for="(rich_filter, filterIndex) in advanced_filters" :key="filterIndex">
                                <div class="fc_rich_filter">
                                    <rich-filter :add_label="FilterLabel" @maybeRemove="maybeRemoveGroup(filterIndex)" :items="rich_filter"/>
                                </div>
                                <div class="fc_cond_or">
                                    <em>{{$t('OR')}}</em>
                                </div>
                            </div>
                        </div>

                        <div class="fc_cond_or">
                            <em @click="addConditionGroup()"
                                style="cursor: pointer; color: rgb(0, 119, 204); font-weight: bold;"><i
                                class="el-icon-plus"></i> {{$t('OR')}}</em>
                        </div>
                        <el-button type="primary" size="small" @click="fetchEstimatedCount()">{{$t('Filter')}}</el-button>
                        <el-button size="small" @click="settings.advanced_filters = [[]]; fetch()">
                            {{$t('Clear Filters')}}
                        </el-button>
                    </div>
                    <advanced-filter-promo v-else/>
                </div>
            </template>
            <h3 v-loading="estimating" style="margin-bottom: 40px;" class="text-align-center fc_counting_heading">
                <span>{{ estimated_count }}</span>
                {{ $t('Rec_contacts_fboys') }}
            </h3>
        </el-form>
        <slot name="fc_tagger_bottom"></slot>
    </div>
</template>

<script type="text/babel">
import DynamicSegmentCampaignPromo from '../Modules/Promos/DynamicSegmentCampaignPromo';
import RichFilter from '../Modules/Contacts/RichFilters/Filters';
import AdvancedFilterPromo from '../Modules/Promos/AdvancedFilterPromo';

export default {
    name: 'recipientTagger',
    props: ['value'],
    components: {
        DynamicSegmentCampaignPromo,
        RichFilter,
        AdvancedFilterPromo
    },
    data() {
        return {
            settings: this.value,
            fetchingData: false,
            settings_mock: {
                subscribers: [
                    {
                        list: null,
                        tag: null
                    }
                ],
                excludedSubscribers: [
                    {
                        list: null,
                        tag: null
                    }
                ],
                sending_filter: 'list_tag',
                dynamic_segment: {
                    id: '',
                    slug: ''
                },
                advanced_filters: [[]]
            },
            lists: [],
            tags: [],
            segments: [],
            advanced_filters: [[]],
            estimated_count: 0,
            estimating: false,
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
    watch: {
        settings: {
            handler(newVal, oldVal) {
                this.$emit('input', newVal);
                if (this.settings.sending_filter != 'advanced_filters') {
                    this.fetchEstimatedCount();
                }
            },
            deep: true
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
        },
        add(source, key) {
            this.settings[source].splice((key + 1), 0, {
                list: (source == 'subscribers') ? 'all' : null,
                tag: 'all'
            });
        },
        remove(source, key) {
            this.settings[source].length > 1 && this.settings[source].splice(key, 1);
        },
        fetchEstimatedCount() {
            const settings = this.settings;
            const postData = {
                sending_filter: settings.sending_filter
            };

            if (settings.sending_filter == 'list_tag') {
                const data = settings.subscribers.filter(i => i.list && i.tag);
                const excludeData = settings.excludedSubscribers.filter(i => i.list && i.tag);

                if (!data.length) {
                    return false;
                }

                if (excludeData.length) {
                    postData.excludedSubscribers = excludeData;
                }

                postData.subscribers = data;
            } else if (settings.sending_filter == 'dynamic_segment') {
                if (!settings.dynamic_segment.uid) {
                    return false;
                }
                postData.dynamic_segment = settings.dynamic_segment;
            } else if (settings.sending_filter == 'advanced_filters') {
                postData.advanced_filters = JSON.stringify(this.advanced_filters);
            } else {
                return false;
            }

            this.estimating = true;
            this.$post('campaigns/estimated-contacts', postData)
                .then(response => {
                    this.estimated_count = response.count;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.estimating = false;
                });
        },
        addConditionGroup() {
            this.advanced_filters.push([]);
        },
        maybeRemoveGroup(index) {
            if (this.advanced_filters.length > 1) {
                this.advanced_filters.splice(index, 1);
            }
        }
    },
    mounted() {
        this.fetch();
        this.fetchEstimatedCount();
    },
    created() {
        const settingsMock = JSON.parse(JSON.stringify(this.settings_mock));
        if (!this.settings) {
            this.settings = settingsMock;
            return;
        }
        if (!this.settings.subscribers) {
            this.settings.subscribers = settingsMock.subscribers;
        }

        if (!this.settings.excludedSubscribers) {
            this.settings.excludedSubscribers = settingsMock.excludedSubscribers;
        }

        if (!this.settings.dynamic_segment) {
            this.settings.dynamic_segment = settingsMock.dynamic_segment;
        }

        if (this.settings.advanced_filters) {
            this.advanced_filters = this.settings.advanced_filters;
        } else {
            this.settings.advanced_filters = this.advanced_filters;
        }

        if (!this.settings.sending_filter) {
            this.settings.sending_filter = settingsMock.sending_filter;
        }
    }
}
</script>
