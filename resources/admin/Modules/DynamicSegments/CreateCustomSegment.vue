<template>
    <div v-loading="field_loading" style="background: #f1f1f1;border: 1px solid #e3e8ee;" class="fluentcrm-lists fluentcrm_min_bg fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'dynamic_segments' }">
                        {{$t('Dynamic Segments')}}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{$t('Create Custom Segment')}}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    @click="$router.push({ name: 'dynamic_segments' })"
                    size="small">{{$t('Back')}}</el-button>
            </div>
        </div>

        <div class="fluentcrm_pad_around">
            <el-form label-position="top" class="fc_segment_form" :data="segment">
                <div class="fc_section_heading">
                    <h3>{{$t('Name this Custom Segment')}}</h3>
                    <p>{{$t('custom_segment.name_desc')}}</p>
                    <el-input :placeholder="$t('eg: Active Contacts')" type="text" v-model="segment.title"></el-input>
                </div>
                <custom-segment-settings
                    @loaded="() => { field_loading = false; }"
                    v-model="segment.filters"
                    :segment_id="false" />

                <el-form-item class="text-align-right">
                    <el-button @click="createSegment()" v-loading="saving" type="primary">{{$t('Create Custom Segment')}}</el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script type="text/babel">
    import CustomSegmentSettings from './_CustomSegementSettings';
    export default {
        name: 'CreateCustomSegment',
        components: {
            CustomSegmentSettings
        },
        data() {
            return {
                loading: false,
                saving: false,
                field_loading: true,
                segment: {
                    title: '',
                    filters: [[]]
                }
            }
        },
        methods: {
            createSegment() {
                if (!this.segment.title) {
                    this.$notify.error(this.$t('Cre_Please_pNotS'));
                    return;
                }
                this.saving = true;
                this.$post('dynamic-segments', {
                    segment: JSON.stringify(this.segment),
                    with_subscribers: false
                })
                    .then(response => {
                        this.$notify.success(response.message);
                        this.$router.push({
                            name: 'view_segment',
                            params: {
                                slug: response.segment.slug,
                                id: response.segment.id
                            }
                        });
                    })
                    .catch((error) => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.saving = false;
                    });
            }
        },
        mounted() {
            this.changeTitle(this.$t('New Dynamic Segment'));
        }
    }
</script>
