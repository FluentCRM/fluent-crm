<template>
    <div v-loading="loading" class="fc_segment_fields">

        <div class="fc_rich_container">
            <div class="fc_rich_wrap">
                <div v-for="(rich_filter, filterIndex) in filters" :key="filterIndex">
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
                    class="el-icon-plus"></i> {{ $t('OR') }}</em>
            </div>
            <el-button v-if="segment_id" type="success" size="small" @click="fetch()">{{$t('Update Segment')}}</el-button>
            <el-button v-else type="info" size="small" @click="fetch()">{{$t('Filter contacts')}}</el-button>
        </div>

        <h3 class="fc_counting_heading text-align-center" v-if="estimated_count !== ''" v-loading="estimating">
            {{ $t('_Cu_Estimated_Cboys') }} <span>{{ estimated_count }}</span>
        </h3>
    </div>
</template>

<script type="text/babel">
import RichFilter from '../Contacts/RichFilters/Filters.vue';

export default {
    name: 'customSegmentEditor',
    props: ['value', 'segment_id'],
    components: {
        RichFilter
    },
    data() {
        return {
            fields: {},
            loading: false,
            filters: this.value,
            estimated_count: '',
            estimating: false,
            require_estimating: false,
            FilterLabel: this.$t('Filters.instruction')
        }
    },
    watch: {
        filters: {
            deep: true,
            handler() {
                this.$emit('input', this.filters);
                this.require_estimating = true;
                if (!this.estimating) {
                    this.getEstimatedCount();
                } else if (!this.changed_in_time) {
                    this.changed_in_time = true;
                }
            }
        }
    },
    methods: {
        fetchFields() {
            this.loading = true;
            this.$get('dynamic-segments/custom-fields')
                .then((response) => {
                    this.fields = response.fields;
                    if (!this.segment_id) {
                        this.$set(this, 'settings', response.settings_defaults);
                    }
                    this.$emit('loaded');
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        getEstimatedCount() {
            this.estimating = true;
            this.$post('dynamic-segments/estimated-contacts', {filters: this.filters})
                .then(response => {
                    this.estimated_count = response.count;
                    if (this.changed_in_time) {
                        this.changed_in_time = false;
                        this.getEstimatedCount();
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.estimating = false;
                });
        },
        addConditionGroup() {
            this.filters.push([]);
        },
        maybeRemoveGroup(index) {
            if (this.filters.length > 1) {
                this.filters.splice(index, 1);
            }
        },
        fetch() {
            this.$emit('updateSegment');
            this.getEstimatedCount();
        }
    },
    mounted() {
        this.fetchFields();
    }
}
</script>
