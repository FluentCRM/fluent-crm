<template>
    <div class="fluentcrm-templates fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <email-header-pop-nav :head_title="$t('Email Templates')" />
                    <span class="ff_small" v-if="pagination.total">({{ pagination.total | formatMoney }})</span>
                </h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div class="fc-search-box">
                    <el-input
                        clearable
                        size="mini"
                        v-model="search"
                        @clear="fetch"
                        @keyup.enter.native="fetch"
                        :placeholder="$t('Type and Enter...')"
                    >
                        <el-button @click="fetch" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>
                <el-button style="margin-left: 10px;" v-if="hasPermission('fcrm_manage_email_templates')" size="small" type="primary"
                           icon="el-icon-plus" @click="openBuiltinTemplateDrawer()">
                    {{ $t('Create New Template') }}
                </el-button>
                <el-button @click="$router.push({ name: 'import_template' })" size="small" type="info"
                           icon="el-icon-upload">
                    {{ $t('Import') }}
                </el-button>
                <inline-doc :doc_id="1729" />
            </div>
        </div>

        <div class="fluentcrm_body" style="position: relative;">
            <div v-if="loading" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="7"></el-skeleton>

            <div v-if="!loading" class="templates-table">
                <div v-if="selection" class="fluentcrm-header-secondary">
                    <bulk-template-action
                        @refetch="fetch"
                        :selectedTemplates="selectedTemplates"/>
                </div>

                <el-table
                    border
                    :data="templates"
                    style="width:100%"
                    stripe
                    :default-sort="{prop: orderBy, order: order}"
                    @sort-change="sortTemplates"
                    @selection-change="onSelection"
                >
                    <el-table-column type="selection" :width="45"/>

                    <el-table-column :label="$t('ID')" width="100" prop="ID" sortable="custom"/>

                    <el-table-column prop="post_title" min-width="450" :label="$t('Title')" sortable="custom">
                        <template slot-scope="scope">
                            <router-link :to="{name: 'edit_template', params: { template_id: scope.row.ID }}">
                                <span v-if="scope.row.design_template == 'visual_builder'">
                                    <img style="width: 15px;" :src="appVars.images_url + '/drag-drop.png'"/>
                                </span>
                                {{ scope.row.post_title }}
                            </router-link>
                            <el-button @click="showPreview(scope.row)" icon="el-icon-view" size="mini" type="text"></el-button>
                        </template>
                    </el-table-column>

                    <el-table-column width="190" :label="$t('Created At')" prop="post_date" sortable="custom">
                        <template slot-scope="scope">
                            <span :title="scope.row.post_date">
                                {{ scope.row.post_date | nsHumanDiffTime }}
                            </span>
                        </template>
                    </el-table-column>

                    <el-table-column fixed="right" :label="$t('Actions')" align="center" min-width="150">
                        <template slot-scope="scope">
                            <el-tooltip effect="dark" content="Edit" placement="top">
                                <el-button
                                    type="info"
                                    size="mini"
                                    icon="el-icon-edit"
                                    @click="edit(scope.row)"
                                >{{$t('edit')}}
                                </el-button>
                            </el-tooltip>

                            <el-dropdown trigger="click">
                                <span class="el-dropdown-link">
                                    <i style="font-weight: bold; cursor: pointer;"
                                       class="el-icon-more icon-90degree el-icon--right"></i>
                                </span>
                                <el-dropdown-menu slot="dropdown">
                                    <el-dropdown-item class="fc_dropdown_action">
                                    <span class="el-popover__reference" @click="duplicate(scope.row)">
                                        <span class="el-icon el-icon-copy-document"></span> {{ $t('Duplicate') }}
                                    </span>
                                    </el-dropdown-item>
                                    <el-dropdown-item class="fc_dropdown_action">
                                        <span class="el-popover__reference" @click="exportTemplate(scope.row)">
                                            <span class="el-icon el-icon-download"></span> {{ $t('Export') }}
                                        </span>
                                    </el-dropdown-item>
                                    <el-dropdown-item class="fc_dropdown_action">
                                        <confirm placement="top-start"
                                                 :message="$t('Delete_Template_Alert')"
                                                 @yes="remove(scope.row)">
                                        <span slot="reference">
                                            <span class="el-icon el-icon-delete"></span>
                                            {{ $t('Delete Template') }}
                                        </span>
                                        </confirm>
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </el-dropdown>
                        </template>
                    </el-table-column>

                </el-table>

                <pagination :pagination="pagination" @fetch="fetch"/>

                <div v-if="showTemplatePreview">
                    <email-preview @modalClosed="() => { showTemplatePreview = false; }" :auto_load="true" :show_audit="true" :campaign="email_template"/>
                </div>
            </div>
        </div>

        <builtin-template-drawer
            :create_mode="true"
            :open_drawer="open_drawer"
            @update:open_drawer="open_drawer = $event"
        />
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import Pagination from '@/Pieces/Pagination';
import BulkTemplateAction from './_BulkTemplateAction'
import InlineDoc from '@/Modules/Documentation/InlineDoc';
import EmailHeaderPopNav from '@/Pieces/EmailHeaderPopNav.vue';
import EmailPreview from '@/Pieces/EmailElements/EmailPreview.vue';
import BuiltinTemplateDrawer from '@/Pieces/EmailElements/BuiltinTemplateDrawer.vue';

export default {
    name: 'Templates',
    components: {
        EmailPreview,
        Confirm,
        Pagination,
        BulkTemplateAction,
        InlineDoc,
        EmailHeaderPopNav,
        BuiltinTemplateDrawer
    },
    data() {
        return {
            loading: false,
            templates: [],
            pagination: {
                current_page: 1,
                per_page: 20,
                total: 0
            },
            url: '',
            title: '',
            dialogVisible: false,
            order: 'descending',
            orderBy: 'ID',
            search: '',
            selection: false,
            selectedTemplates: [],
            open_drawer: false,
            show_drawer: false,
            builtInTemplates: [],
            isLoadingTemplates: false,
            importing: false,
            oneTimeFetch: true,
            showTemplatePreview: false,
            previewTemplateId: '',
            email_template: {
                post_title: '',
                post_content: '',
                post_excerpt: '',
                email_subject: '',
                edit_type: 'html',
                design_template: 'simple',
                settings: {
                    template_config: {}
                }
            }
        };
    },
    methods: {
        getStyleOfPostStatus(status) {
            return {
                fontWeight: 500,
                color: status === 'publish' ? 'green' : 'gray'
            };
        },
        setup() {
            let queryParams = this.$route.query;

            if (window.fcrm_template_sub_params) {
                queryParams = window.fcrm_template_sub_params;
            }

            this.search = queryParams.search || '';
            this.order = (queryParams.order === 'ascending') ? 'ASC' : 'DESC';
            this.orderBy = queryParams.orderBy;

            if (queryParams.page) {
                this.pagination.current_page = parseInt(queryParams.page);
            }
            if (queryParams.per_page) {
                this.pagination.per_page = parseInt(queryParams.per_page);
            }

            return false;
        },
        fetch() {
            this.loading = true;
            this.selection = false;

            const query = {
                order: (this.order == 'ascending') ? 'ASC' : 'DESC',
                orderBy: this.orderBy,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search
            };

            const params = {};

            Object.keys(query).forEach(key => {
                if (query[key] !== undefined) {
                    params[key] = query[key];
                }
            });

            window.fcrm_template_sub_params = params;
            params.t = Date.now();

            this.$router.replace({
                name: 'templates', query: params
            });

            query.types = ['publish', 'draft'];

            this.$get('templates', query).then(response => {
                this.templates = response.templates.data;
                this.pagination.total = response.templates.total;
                this.loading = false;
            });
        },
        onSelection(templates) {
            this.selection = !!templates.length;
            this.selectedTemplates = templates;
        },
        duplicate(template) {
            this.duplicating = true;
            this.$post(`templates/duplicate/${template.ID}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                    this.$router.push({
                         name: 'edit_template',
                         params: {
                             template_id: response.template_id
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
        edit(template) {
            this.$router.push({
                name: 'edit_template',
                params: {
                    template_id: template.ID
                }
            });
        },
        remove(template) {
            this.$del(`templates/${template.ID}`)
                .then(response => {
                    this.fetch();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                });
        },
        syncVisibility() {
            this.dialogVisible = false;
        },
        onDialogClose() {
            this.fetch();
            this.url = '';
        },
        sortTemplates(sort) {
            if (sort.order === 'descending') {
                this.orderBy = sort.prop;
                this.order = sort.order;
            } else {
                this.orderBy = sort.prop;
                this.order = sort.order;
            }
            this.fetch();
        },
        exportTemplate(template) {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Template_Export_Alert'));
                return;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_template',
                template_id: template.ID
            });
        },
        showPreview(template) {
            this.previewTemplateId = template.ID;
            this.fetchTemplate();
        },
        openBuiltinTemplateDrawer() {
            this.open_drawer = true;
        },
        fetchTemplate() {
            this.$get(`templates/${this.previewTemplateId}`)
                .then(response => {
                    this.email_template = response.template;
                    this.showTemplatePreview = true;
                })
                .catch((error) => {
                    console.log(error);
                })
                .finally(() => {

                });
        }
    },
    mounted() {
        this.setup();
        this.fetch();
        this.changeTitle(this.$t('Email Templates'));
    }
}
</script>
