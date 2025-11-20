<template>
    <div v-if="has_campaign_pro"
         class="fluentcrm-lists fluentcrm_min_bg fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <contact-header-pop-nav :head_title="$t('Dynamic Segments')"/>
                </h3>
                <p>{{ $t('AllSegments.desc') }}</p>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    v-if="hasPermission('fcrm_manage_contact_cats')"
                    @click="$router.push({ name: 'create_custom_segment' })"
                    size="small"
                    type="primary"
                >
                    {{ $t('Create Custom Segment') }}
                </el-button>
                <el-button @click="$router.push({ name: 'import_segment' })" size="small" type="info"
                           icon="el-icon-upload">
                    {{ $t('Import') }}
                </el-button>
            </div>
        </div>
        <div class="fluentcrm_body" style="position: relative;">
            <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <div v-else class="lists-table">
                <el-table border :empty-text="$t('No Data Available')" :data="segments">
                    <el-table-column min-width="300px" :label="$t('Title')">
                        <template slot-scope="scope">
                            <router-link
                                :to="{ name: 'view_segment', params: { slug: scope.row.slug, id: scope.row.id } }">
                                <h3 class="no-margin url">
                                    {{ scope.row.title }}
                                </h3>
                            </router-link>

                            <span class="list-description">
                                {{ scope.row.subtitle }}
                            </span>
                        </template>
                    </el-table-column>

                    <el-table-column width="150" :label="$t('Contacts')">
                        <template slot-scope="scope">
                            <el-skeleton v-if="fetchingStats" :rows="1" :animated="true" />
                            <span v-else-if="segmentStats">{{ segmentStats[scope.row.slug + '_' + scope.row.id] ? formatMoney(segmentStats[scope.row.slug + '_' + scope.row.id], 0) : 'n/a' }}</span>
                            <span v-else>n/a</span>
                        </template>
                    </el-table-column>
                    <el-table-column
                        width="150"
                        :label="$t('Actions')">
                        <template slot-scope="scope">
                            <p v-if="scope.row.is_system">{{ $t('System Defined') }}</p>
                            <div v-else class="text-align-left">
                                <el-button
                                    v-if="hasPermission('fcrm_manage_contact_cats')"
                                    type="info"
                                    size="mini"
                                    icon="el-icon-edit"
                                    @click="edit(scope.row)"
                                >Edit
                                </el-button>

                                <el-dropdown trigger="click">
                                    <span class="el-dropdown-link">
                                        <i style="font-weight: bold; cursor: pointer;"
                                           class="el-icon-more icon-90degree el-icon--right"></i>
                                    </span>
                                    <el-dropdown-menu slot="dropdown">
                                        <el-dropdown-item class="fc_dropdown_action">
                                            <span class="el-popover__reference" @click="duplicate(scope.row)">
                                                <span class="el-icon el-icon-copy-document"></span> {{
                                                    $t('Duplicate')
                                                }}
                                            </span>
                                        </el-dropdown-item>
                                        <el-dropdown-item class="fc_dropdown_action">
                                            <span class="el-popover__reference" @click="exportSegment(scope.row)">
                                                <span class="el-icon el-icon-download"></span> {{ $t('Export') }}
                                            </span>
                                        </el-dropdown-item>
                                        <el-dropdown-item class="fc_dropdown_action">
                                            <confirm v-if="hasPermission('fcrm_manage_contact_cats_delete')"
                                                     @yes="remove(scope.row)">
                                                <span slot="reference">
                                                    <span class="el-icon el-icon-delete"></span>
                                                    {{ $t('Delete') }}
                                                </span>
                                            </confirm>
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </el-dropdown>
                            </div>
                        </template>
                    </el-table-column>
                </el-table>
            </div>
        </div>
    </div>
    <dynamic-segment-promo v-else></dynamic-segment-promo>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import DynamicSegmentPromo from '../Promos/DynamicSegmentPromo';
import ContactHeaderPopNav from '@/Pieces/ContactHeaderPopNav.vue';

export default {
    name: 'AllDynamicSegments',
    components: {
        Confirm,
        DynamicSegmentPromo,
        ContactHeaderPopNav
    },
    data() {
        return {
            segments: [],
            loading: false,
            fetchingStats: true,
            segmentStats: {}
        }
    },
    methods: {
        fetchSegments() {
            this.loading = true;
            setTimeout(() => {
                this.$get('dynamic-segments')
                    .then((response) => {
                        this.segments = response.dynamic_segments;
                    })
                    .catch((error) => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }, 500);
        },
        ferchStats() {
            this.fetchingStats = true;
            this.$get('dynamic-segments/stats')
                .then((response) => {
                    this.segmentStats = response.stats;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.fetchingStats = false;
                });
        },

        edit(row) {
            this.$router.push({
                name: 'view_segment',
                params: {
                    slug: row.slug,
                    id: row.id
                }
            });
        },
        remove(row) {
            this.loading = true;
            this.$del(`dynamic-segments/${row.id}`)
                .then((response) => {
                    this.fetchSegments();
                    this.$notify.success(response.message);
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        exportSegment(row) {
            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluent_crm_export_dynamic_segment',
                segment_id: row.id
            });
        },
        duplicate(row) {
            this.loading = true
            this.$post(`dynamic-segments/duplicate/${row.id}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchSegments();
                    this.$router.push({
                        name: 'view_segment',
                        params: {
                            slug: 'custom_segment',
                            id: response.segment_id
                        }
                    });
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        if (this.has_campaign_pro) {
            this.fetchSegments();
            this.ferchStats();
        }
        this.changeTitle(this.$t('Dynamic Segments'));
        this.$emit('changeMenu', 'dynamic_segments');
    }
}
</script>
