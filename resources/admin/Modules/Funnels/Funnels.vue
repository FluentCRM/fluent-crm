<template>
    <div class="fluentcrm_settings_wrapper">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('Automation Funnels') }}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-input @keyup.enter.native="getFunnels" size="mini" :placeholder="$t('Search')"
                          v-model="search" class="input-with-select mr-5">
                    <el-button @click="getFunnels()" slot="append" icon="el-icon-search"></el-button>
                </el-input>

                <el-select
                    size="mini"
                    multiple
                    clearable
                    :placeholder="$t('Filter by Labels')"
                    filterable
                    v-model="labelFilter"
                    class="mr-5"
                    style="max-width: 194px;"
                >
                    <el-option
                        v-for="label in options.labels"
                        :key="label.id"
                        :value="label.id"
                        :label="label.title"
                    >
                        <span
                            :style="'background:'+label.settings.color+';padding: 2px 5px 4px 5px;border-radius: 4px;'"
                        >
                            {{ label.title }}
                        </span>
                    </el-option>
                </el-select>

                <el-button v-if="hasPermission('fcrm_write_funnels')" icon="el-icon-plus" size="small" type="primary"
                           @click="create_modal = true">
                    {{ $t('New Automation') }}
                </el-button>

                <toggler @dataChanged="maybeReFetch()" v-model="columns"/>

                <inline-doc :doc_id="2362" />

                <el-dropdown trigger="click">
                    <span class="el-dropdown-link">
                        <i style="cursor: pointer;" class="el-icon-more icon-90degree el-icon--right"></i>
                    </span>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item class="fc_dropdown_action">
                            <span class="el-popover__reference" @click="$router.push({ name: 'import_funnel' })">
                                {{ $t('Import') }}
                            </span>
                        </el-dropdown-item>
                        <el-dropdown-item class="fc_dropdown_action">
                            <span class="el-popover__reference" @click="showLabelDialog">
                                {{ $t('Manage Labels') }}
                            </span>
                        </el-dropdown-item>
                        <el-dropdown-item class="fc_dropdown_action">
                            <span class="el-popover__reference" @click="$router.push({ name: 'funnel_activities' })">
                                {{ $t('Activities') }}
                            </span>
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>
            </div>
        </div>
        <div class="fluentcrm_body fluentcrm_pad_b_20" style="position: relative">
            <div v-if="selection" class="fluentcrm-header-secondary">
                <bulk-funnel-actions
                    @refetch="getFunnels"
                    :selectedFunnels="selectedFunnels"
                    :options="options"/>
            </div>

            <div v-if="loading" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table border v-if="!loading" :default-sort="{ prop: sortBy, order: (sortType == 'DESC') ? 'descending' : 'ascending' }" :empty-text="$t('No Data Found')" @sort-change="handleSortable"
                      v-loading="duplicating" @selection-change="onSelection" stripe :data="funnels" style="width: 100%">
                <el-table-column type="selection" width="45" fixed/>

                <el-table-column sortable="custom" property="id" width="60" :label="$t('ID')">
                    <template slot-scope="scope">
                        {{ scope.row.id }}
                    </template>
                </el-table-column>
                <el-table-column min-width="250" sortable="custom" property="title" :label="$t('Title')">
                    <template slot-scope="scope">
                        <router-link :to="{ name: 'edit_funnel', params: { funnel_id: scope.row.id } }">
                            <h4 class="no-margin">
                                {{ scope.row.title }}
                            </h4>
                        </router-link>
                        <span v-if="scope.row.description" class="funnel_description">{{ scope.row.description }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="columns.indexOf('trigger') != -1"
                    min-width="250"
                    sortable="custom"
                    property="trigger_name"
                    :label="$t('Trigger')"
                >
                    <template slot-scope="scope">
                        <span v-html="getTriggerTitle(scope.row.trigger_name)"></span>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="columns.indexOf('labels') != -1"
                    width="250"
                    :label="$t('Labels')"
                >
                    <template slot-scope="scope">
                    
                        <div v-if="scope.row.labels" class="fc_funnel_labels">
                            <el-tag v-for="(label, index) in scope.row.labels" :key="index" size="mini" :style="'background:' + label.color">
                                {{  label.title }}
                                <Confirm @yes="applyLabels(scope.row, label.id, 'detach')" :message="$t('Remove_Label_From_funnel_Message')">
                                    <i slot="reference" class="el-tag__close el-icon-close"></i>
                                </Confirm>
                            </el-tag>
                        </div>
                    </template>

                </el-table-column>
                <el-table-column
                    sortable="custom"
                    property="status"
                    width="100"
                    :label="$t('Status')"
                >
                    <template slot-scope="scope">
                        <el-tag v-loading="working" :type="(scope.row.status == 'published') ? 'success' : 'info'" size="mini">{{ $t(scope.row.status) | ucFirst }}</el-tag>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="columns.indexOf('stats') != -1"
                    width="100"
                    :label="$t('Stats')"
                >
                    <template slot-scope="scope">
                        <span class="stats_badge_inline">
                            <span><i class="el-icon el-icon-user"></i> {{ scope.row.subscribers_count }}</span>
                        </span>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="columns.indexOf('pause/run') != -1"
                    width="100"
                    :label="$t('Pause/Run')"
                >
                    <template slot-scope="scope">
                        <el-switch v-model="scope.row.status" inactive-value="draft" active-value="published" @change="updateStatus(scope.row)"></el-switch>
                    </template>
                </el-table-column>
                <el-table-column
                    v-if="columns.indexOf('action') != -1"
                    fixed="right"
                    min-width="150"
                    :label="$t('Action')"
                >
                    <template slot-scope="scope">
                        <el-button
                            type="info"
                            size="mini"
                            icon="el-icon-data-line"
                            @click="subscribers(scope.row)"
                        >
                            {{ $t('Reports') }}
                        </el-button>
                        <template v-if="hasPermission('fcrm_write_funnels')">

                            <el-popover :visible="isOpenFunnelAction" placement="bottom" popper-class="fc-funnel-actions-popover" :width="200">
                                <div ref="fcFunnelActions">
                                    <div v-if="!showActionButtons" class="fc_funnel_action_header" @click="handleBackAction"><i class="el-icon-back"></i> {{ $t('Back') }}</div>
                                    <div v-if="showActionButtons" class="fc_funnel_acton_field">
                                        <el-button
                                            class="fc_funnel_action_btn"
                                            link
                                            icon="el-icon-copy-document"
                                            @click="duplicate(scope.row)">
                                            {{ $t('Duplicate') }}
                                        </el-button>
                                        <el-button
                                            class="fc_funnel_action_btn"
                                            link
                                            icon="el-icon-price-tag"
                                            @click="applyLabelSetting(scope.row)">
                                            {{ $t('Apply Labels') }}
                                        </el-button>
                                        <el-button
                                            class="fc_funnel_action_btn"
                                            link
                                            icon="el-icon-download"
                                            @click="exportFunnel(scope.row)">
                                            {{ $t('Export') }}
                                        </el-button>
                                        <confirm placement="top-start" :message="$t('Automation_Delete_Alert')"
                                                 @yes="remove(scope.row)">
                                            <span slot="reference">
                                                <el-button class="fc_funnel_action_btn" icon="el-icon-delete">
                                                    {{ $t('Delete Automation') }}
                                                </el-button>
                                            </span>
                                        </confirm>
                                    </div>
                                    <el-form class="fc_label_form" v-if="showApplyLabelSetting">
                                        <el-form-item :label="$t('Labels')">
                                            <el-select v-model="selectedLabels" multiple placeholder="Select Labels">
                                                <el-option
                                                    v-for="label in options.labels"
                                                    :key="label.id"
                                                    :label="label.title"
                                                    :value="label.id">
                                                </el-option>
                                            </el-select>
                                        </el-form-item>
                                        <el-form-item>
                                            <el-button type="primary" size="small" class="fc_primary_button" @click.native="applyLabels(scope.row, [], 'attach')">
                                                {{ $t('Apply') }}
                                            </el-button>
                                        </el-form-item>
                                    </el-form>
                                </div>

                                <template #reference>
                                    <span ref="buttonRefMoreFiled" class="fbs_show_board_show_section_actions"
                                          @click="toggleComponent">
                                        <i style="font-weight: bold; cursor: pointer;" class="el-icon-more icon-90degree el-icon--right"></i>
                                    </span>
                                </template>
                            </el-popover>
                        </template>
                    </template>

                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="getFunnels"/>
        </div>
        <create-funnel-modal :triggers="triggers" @close="() => { create_modal = false }" v-if="create_modal"
                             :visible="create_modal"/>

        <labels v-if="showingLabelsConfig" :open="showingLabelsConfig" @close="closeDrawer" @callFetchLabels="fetchLabels"  />
    </div>
</template>

<script type="text/babel">
import CreateFunnelModal from './parts/_CreateFunnelModal2'
import Confirm from '@/Pieces/Confirm';
import Pagination from '@/Pieces/Pagination';
import BulkFunnelActions from './_BulkFunnelActions';
import InlineDoc from '@/Modules/Documentation/InlineDoc';
import Labels from '@/Modules/Labels/Labels.vue';
import Toggler from './Filter/Toggler.vue';

export default {
    name: 'AutomationFunnels',
    components: {
        Labels,
        CreateFunnelModal,
        Confirm,
        Pagination,
        BulkFunnelActions,
        InlineDoc,
        Toggler
    },
    data() {
        return {
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            funnels: [],
            search: '',
            create_modal: false,
            triggers: {},
            loading: false,
            duplicating: false,
            sortBy: 'id',
            sortType: 'DESC',
            selection: false,
            selectedFunnels: [],
            options: {
                labels: [],
                statuses: [
                    {
                        id: 'published',
                        title: this.$t('Publish')
                    },
                    {
                        id: 'draft',
                        title: this.$t('Draft')
                    }
                ],
                delimiter: 'comma'
            },
            working: false,
            visibleLabelsForm: false,
            selectedLabels: [],
            labelFilter: [],
            showingLabelsConfig: false,
            isOpenFunnelAction: false,
            showActionButtons: false,
            showApplyLabelSetting: false,
            columns: [],
            initialFired: false
        }
    },
    methods: {
        maybeReFetch() {
            let willRefetch = false;
            if (!this.initialFired) {
                willRefetch = true;
            }
            if (willRefetch) {
                this.getFunnels();
                this.initialFired = true;
            }
        },
        getFunnels() {
            this.loading = true;
            this.$get('funnels', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                labels: this.labelFilter,
                with: ['triggers'],
                search: this.search,
                sort_by: this.sortBy,
                sort_type: this.sortType
            }).then(response => {
                this.funnels = response.funnels.data;
                this.pagination.total = response.funnels.total;
                this.triggers = response.triggers;
                this.selection = false;

                if (this.storage.get('funnel_per_page') != this.pagination.per_page) {
                    this.storage.set('funnel_per_page', this.pagination.per_page);
                }
            })
                .catch(errors => {
                    this.storage.set('funnel_per_page', 10);
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        onSelection(funnels) {
            this.selection = !!funnels.length;
            this.selectedFunnels = funnels;
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.sortBy = sorting.prop;
                this.sortType = 'DESC';
            } else {
                this.sortBy = sorting.prop;
                this.sortType = 'ASC';
            }
            this.getFunnels();
        },
        getTriggerTitle(triggerkey) {
            if (this.triggers[triggerkey]) {
                return this.triggers[triggerkey].label;
            }
            return '<strong style="color: red;">' + triggerkey + ' (' + this.$t('INACTIVE') + ')</strong>';
        },
        edit(funnel) {
            this.$router.push({
                name: 'edit_funnel',
                params: {
                    funnel_id: funnel.id
                }
            });
        },
        subscribers(funnel) {
            this.$router.push({
                name: 'funnel_subscribers',
                params: {
                    funnel_id: funnel.id
                }
            });
        },
        remove(funnel) {
            this.$del(`funnels/${funnel.id}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.getFunnels();
                })
                .catch(error => {
                    this.handleError(error);
                });
        },
        duplicate(funnel) {
            this.duplicating = true;
            this.$post(`funnels/${funnel.id}/clone`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'edit_funnel',
                        params: {
                            funnel_id: response.funnel.id
                        },
                        query: {
                            is_new: 'yes'
                        }
                    });
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.duplicating = false;
                })
        },
        exportFunnel(funnel) {
            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_funnel',
                funnel_id: funnel.id
            });
        },
        updateStatus(funnel) {
            this.working = true;
            const newStatus = funnel.status;
            this.$put('funnels/' + funnel.id, {
                status: newStatus
            })
            .then((response) => {
                this.$notify.success(response.message);
                funnel.status = newStatus;
            })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {
                this.working = false;
            });
        },
        showLabelDialog() {
            this.showingLabelsConfig = true;
        },
        fetchLabels() {
            this.$get('labels')
                .then((response) => {
                    this.options.labels = response.labels;
                }).catch((errors) => {
                this.handleError(errors);
            }).finally(() => {

            });
        },
        toggleComponent() {
            this.isOpenFunnelAction = !this.isOpenFunnelAction;
            if (this.isOpenFunnelAction) {
                this.showActionButtons = true;
                setTimeout(() => {
                    document.addEventListener('click', this.handleClickOutside);
                }, 0);
            } else {
                document.removeEventListener('click', this.handleClickOutside);
            }
        },
        handleClickOutside(event) {
            if (!this.$refs.fcFunnelActions?.contains(event.target)) {
                this.closePopOver();
            }
        },
        closePopOver() {
            this.isOpenFunnelAction = false;
            this.showApplyLabelSetting = false;
            this.showActionButtons = true;
        },
        applyLabelSetting(funnel) {
            this.showApplyLabelSetting = true;
            this.showActionButtons = false;
            this.selectedLabels = funnel.labels.map(label => label.id);
        },
        applyLabels(funnel, labelId, action = 'attach') {
            this.$put('funnels/' + funnel.id + '/update-labels', {
                action: action,
                label_ids: action == 'attach' ? this.selectedLabels : labelId
            })
            .then((response) => {
                this.$notify.success(response.message);
                this.getFunnels();
            })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {
                this.showApplyLabelSetting = false;
                this.showActionButtons = true;
            });
        },
        handleBackAction() {
            this.showActionButtons = true;
            this.showApplyLabelSetting = false;
        },
        closeDrawer() {
            this.showingLabelsConfig = false;
        }
    },
    watch: {
        labelFilter() {
            this.getFunnels();
        }
    },
    mounted() {
        this.pagination.per_page = parseInt(this.storage.get('funnel_per_page', 10));
        this.getFunnels();
        this.changeTitle(this.$t('Automation Funnels'));
        this.fetchLabels();
    }
}
</script>
