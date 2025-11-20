<template>
    <div :class="{ fc_is_guten: selectedDesign && selectedDesign.use_gutenberg }" class="fluentcrm_visual_editor">
        <div class="fluentcrm_header fc_visual_header">
            <div class="fluentcrm_header_title">
                <el-button v-if="selectedDesign.use_gutenberg" @click="showInserter()" size="small"
                           icon="el-icon-plus"></el-button>
                <h3>
                    <el-popover
                        placement="top-start"
                        title="Tips"
                        width="300"
                        trigger="hover">
                        <div class="text-align-left">
                            <ul>
                                <li>{{ $t('Type') }} <code>@</code> {{ $t('to see smart tags') }}</li>
                                <li>{{ $t('Type') }} <code>/</code> {{ $t('to see All Available Blocks') }}</li>
                            </ul>
                            <p>{{ $t('Please') }} <a href="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/"
                                         target="_blank">{{ $t('read the doc for advanced usage') }}</a></p>
                        </div>
                        <span slot="reference" class="el-icon el-icon-help"></span>
                    </el-popover>
                    {{ $t('Email Body') }}
                    <email-style-editor
                        class="ml-5"
                        v-if="campaign.settings"
                        @save="triggerSave()"
                        :footer_settings="footerSettings"
                        :template_config="campaign.settings.template_config"/>
                    <email-preview :show_audit="show_audit" :campaign="campaign"/>
                </h3>
                <image-radio-tool-tip
                    style="margin: -10px 0px !important;"
                    :boxWidth="53"
                    :boxHeight="45"
                    :field="{ options: email_template_designs }"
                    tooltip_prefix="Template - "
                    v-model="campaign.design_template"/>
            </div>
            <div class="fluentcrm-actions d-flex items-center">
                <el-button :title="$t('Import Template')" v-if="enable_templates" @click="fetchTemplates" type="info"
                           size="small" icon="el-icon-folder-opened">
                    <span>{{ $t('Import Template') }}</span>
                </el-button>
                <slot name="fc_editor_actions"></slot>
                <template v-if="enable_template_save">
                    <el-dropdown trigger="click">
                        <span class="el-dropdown-link">
                            <i style="font-weight: bold; cursor: pointer;"
                               class="el-icon-more icon-90degree el-icon--right"></i>
                        </span>
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item>
                                <el-popover
                                    placement="right-start"
                                    width="400"
                                    v-model="new_template_pop"
                                    trigger="click">
                                    <label>{{ $t('Template Name') }}</label>
                                    <el-input :placeholder="$t('Template Name')" style="margin: 10px 0;" type="text"
                                              v-model="new_template_name"/>
                                    <el-button v-loading="saving_template" :disabled="saving_template" @click="saveAsTemplate()" type="primary" size="small">{{ $t('Save') }}
                                    </el-button>
                                    <p style="font-size: 90%;" v-if="campaign.design_template == 'visual_builder'">{{ $t('Will be stored from your last saved email contents') }}</p>
                                    <el-button v-loading="saving_template" :disabled="saving_template"
                                               icon="el-icon-plus" type="text"
                                               slot="reference">
                                        {{ $t('Save as template') }}
                                    </el-button>
                                </el-popover>
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </template>
                <merge-codes v-if="show_merge" :extra_tags="extra_tags"/>
            </div>
        </div>
        <div v-if="editor_status" class="fc_visual_body">
            <template v-if="selectedDesign.use_gutenberg">
                <block-editor @changed="$emit('changed')" :design_template="campaign.design_template"
                              v-model="campaign[email_body_key]"/>
            </template>
            <template v-else>
                <div :class="'fc_design_template_' + selectedDesign.id + '_wrapper'" class="fc_normal_editor_wrapper">
                    <el-row :gutter="30">
                        <el-col class="fc_composer_body" :span="19">
                            <div class="fc_composer_classic" v-if="selectedDesign.template_type == 'classic_editor'">
                                <wp-editor :height="350" :extra_style="classic_styles"
                                           v-model="campaign[email_body_key]"/>
                            </div>
                            <div :class="'fc_composer_'+selectedDesign.id"
                                 v-else-if="selectedDesign.template_type == 'custom_component'">
                                <component @save="triggerSave()" v-model="campaign[email_body_key]"
                                           :extra_tags="extra_tags" :campaign="campaign"
                                           :is="selectedDesign.component"/>
                            </div>
                            <div v-else-if="selectedDesign.template_type == 'visual_builder_demo'">
                                <div style="max-width: 600px; margin: 40px auto; text-align: center;"
                                     class="fluentcrm_databox">
                                    <h3>{{ $t('Build Email By Drag and Drop Visual Editor') }}</h3>
                                    <p>{{ $t('Visual_Email_Builder_Alert') }}</p>
                                    <p>
                                        <a class="el-button el-button--danger el-button--large"
                                           href="https://fluentcrm.com/?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp"
                                           target="_blank" rel="noopener">{{ $t('Upgrade to FluentCRM Pro') }}</a>
                                    </p>
                                </div>
                            </div>
                            <div class="fc_composer_raw_hrml" v-else>
                                <raw-editor v-model="campaign[email_body_key]"></raw-editor>
                            </div>
                        </el-col>
                        <el-col :span="5">
                            <div class="fc_template_info">
                                <div v-html="selectedDesign.template_info"></div>
                            </div>
                        </el-col>
                    </el-row>
                </div>
            </template>
            <div class="fc_complience_suggest"
                 v-if="campaign.settings.template_config && campaign.settings.template_config.disable_footer == 'yes'">
                <p class="fc_editor_warnning">{{ $t('Default footer has been disabled. Please include') }} <code>##crm.unsubscribe_url##</code>
                    {{ $t('or') }} <code>##crm.manage_subscription_url##</code>
                    {{ $t('in your email body for compliance') }}</p>
                <div><b>{{ $t('Suggested text to include:') }}</b>
                    <p v-html="'{{crm.unsubscribe_html|Unsubscribe}} | {{crm.manage_subscription_html|Manage Preference}}'"></p>
                </div>
            </div>
        </div>
        <el-dialog
            :close-on-click-modal="false"
            v-if="enable_templates"
            :title="$t('Select Template')"
            :visible.sync="templates_modal"
            :append-to-body="true"
            width="60%"
            class="fluentcrm_import_email_templates"
        >
            <div class="fc-select-template-header">
                <el-button size="small" @click="openBuiltinTemplateDrawer()">
                    {{ $t('See Built In Templates') }}
                </el-button>
                <div class="fc-search-box">
                    <el-input
                        clearable
                        size="mini"
                        v-model="search"
                        @clear="fetchTemplates"
                        @keyup.enter.native="fetchTemplates"
                        :placeholder="$t('Type and Enter...')"
                    >
                        <el-button @click="fetchTemplates" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>
            </div>

            <el-table
                :empty-text="$t('No Data Available')"
                v-loading="loading"
                :data="templates"
                stripe
                @sort-change="handleSortable"
                border
                style="width: 100%">

                <el-table-column
                    :label="$t('ID')"
                    property="ID"
                    width="100"
                    sortable="custom">
                    <template slot-scope="scope">
                        {{ scope.row.ID }}
                    </template>
                </el-table-column>

                <el-table-column prop="post_title" :label="$t('Title')" sortable="custom">
                    <template slot-scope="scope">
                        <h3 class="template-name" @click="InsertChange(scope.row.ID)">
                            <span v-if="scope.row.design_template == 'visual_builder'">
                                <img style="width: 15px;" :src="appVars.images_url + '/drag-drop.png'"/>
                            </span>
                            {{ scope.row.post_title }}
                        </h3>
                    </template>
                </el-table-column>
                <el-table-column width="200" :label="$t('Action')">
                    <template slot-scope="scope">
                        <div class="fc-action-btns">
                            <el-button @click="InsertChange(scope.row.ID)" icon="el-icon-upload" size="mini">
                                {{ $t('Import') }}
                            </el-button>
                            <el-button @click="showPreview(scope.row)" icon="el-icon-view" size="mini" type="text">
                                {{ $t('Preview') }}
                            </el-button>
                        </div>
                    </template>
                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetchTemplates"/>
        </el-dialog>
        <div v-if="showTemplatePreview">
            <email-preview @modalClosed="() => { showTemplatePreview = false; }" :auto_load="true" :show_audit="true" :campaign="email_template"/>
        </div>
        <builtin-template-drawer
            :open_drawer="open_drawer"
            @update:open_drawer="open_drawer = $event"
        />
    </div>
</template>

<script type="text/babel">
import RawEditor from './RawEditor';
import BlockEditor from './BlockEditor';
import ImageRadioToolTip from '@/Pieces/FormElements/_ImageRadioToolTip';
import EmailStyleEditor from './StyleEditorOld';
import EmailPreview from './EmailPreview';
import WpEditor from '@/Pieces/FormElements/_WpEditorField'
import Pagination from '@/Pieces/Pagination';
import MergeCodes from './_MergeCodes';
import omitBy from 'lodash/omitBy';
import BuiltinTemplateDrawer from '@/Pieces/EmailElements/BuiltinTemplateDrawer.vue';

export default {
    name: 'BlockComposer',
    props: ['campaign', 'enable_templates', 'disable_fixed', 'body_key', 'enable_template_save', 'show_merge', 'extra_tags', 'show_audit', 'disabled_templates'],
    components: {
        ImageRadioToolTip,
        RawEditor,
        EmailStyleEditor,
        BlockEditor,
        EmailPreview,
        WpEditor,
        Pagination,
        MergeCodes,
        BuiltinTemplateDrawer
    },
    data() {
        return {
            loadingTemplates: false,
            templates: [],
            fetchingTemplate: false,
            loading: false,
            editor_status: true,
            templates_modal: false,
            email_body_key: this.body_key || 'email_body',
            search: '',
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0
            },
            query_data: {
                sort_by: 'ID',
                sort_type: 'DESC'
            },
            new_template_name: '',
            new_template_pop: false,
            saving_template: false,
            footerSettings: {
                custom_footer: (this.campaign.settings.footer_settings) ? this.campaign.settings.footer_settings.custom_footer : 'no',
                footer_content: (this.campaign.settings.footer_settings) ? this.campaign.settings.footer_settings.footer_content : ''
            },
            previewTemplateId: '',
            showTemplatePreview: false,
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
            },
            open_drawer: false
        }
    },
    watch: {
        'campaign.settings.footer_settings': {
            deep: true,
            handler(value) {
                this.footerSettings = value;
            }
        },
        'campaign.design_template'(value, prevvalue) {
            if (this.editor_status) {
                if (value == 'raw_classic' || value == 'raw_html' || value == 'visual_builder') {
                    this.unmountBlockEditor();
                }

                if (this.email_template_designs[value]) {
                    const oldConfig = this.campaign.settings.template_config;
                    const designConfig = this.email_template_designs[value].config;

                    if (!designConfig || !oldConfig) {
                        this.campaign.settings.template_config = designConfig;
                    }

                    this.each(designConfig, (confValue, confKey) => {
                        if (confKey == 'body_bg_color' || confKey == 'content_bg_color') {
                            return;
                        }
                        if (oldConfig[confKey]) {
                            designConfig[confKey] = oldConfig[confKey];
                        }
                    });
                    this.campaign.settings.template_config = designConfig;
                }
            }
        }
    },
    computed: {
        selectedDesign() {
            const design = this.email_template_designs[this.campaign.design_template];
            if (!design) {
                return this.email_template_designs.simple;
            }
            return design;
        },
        classic_styles() {
            let fontFamily = this.campaign.settings.template_config.content_font_family;
            if (!fontFamily) {
                fontFamily = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'";
            }

            return 'body {font-family: ' + fontFamily + ';line-height: 150%;padding: 0px 20px 20px;} body p, ul, li, ol { font-size: 16px; }'
        },
        email_template_designs() {
            if (!this.disabled_templates) {
                return window.fcAdmin.email_template_designs;
            }
            return omitBy(window.fcAdmin.email_template_designs, (value, key) => {
                return this.disabled_templates[key];
            });
        }
    },
    methods: {
        triggerSave() {
            this.campaign.settings.footer_settings = this.footerSettings;
            this.$nextTick(() => {
                this.$emit('save');
            });
        },
        fetchTemplates() {
            this.loading = true;
            this.loadingTemplates = true;
            this.templates_modal = true;

            const query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search,
                orderBy: this.query_data.sort_by,
                order: this.query_data.sort_type
            };

            this.$get('templates', query)
                .then(response => {
                    this.templates = response.templates.data;
                    this.pagination.total = response.templates.total;
                    this.loading = false;
                })
                .catch(error => {
                    console.log(error);
                })
                .finally(() => {
                    this.loadingTemplates = false;
                    this.loading = false;
                    this.editor_status = true;
                });
        },
        viewTemplateDetail(id) {
            if (!id) {
                return;
            }
            this.templates_modal = false;
            this.$router.push({
                name: 'edit_template',
                params: {
                    template_id: id
                }
            });
            this.$nextTick(() => {
                this.$emit('fetch', id);
            });
        },
        InsertChange(val) {
            if (!val) {
                return;
            }

            this.unmountBlockEditor();
            this.editor_status = false;
            this.fetchingTemplate = true;
            this.$get(`templates/${val}`)
                .then(response => {
                    if (this.disabled_templates && this.disabled_templates[response.template.design_template]) {
                        this.$notify.error(this.$t('Email_Campaign_Insert_Error_Alert'));
                        return;
                    }

                    this.campaign.template_id = val;
                    this.campaign[this.email_body_key] = response.template.post_content;
                    this.campaign.email_subject = response.template.email_subject;
                    this.campaign.email_pre_header = response.template.post_excerpt;
                    this.campaign.design_template = response.template.design_template;
                    this.campaign.settings.template_config = response.template.settings.template_config;
                    this.campaign.settings.footer_settings = (response.template.settings.footer_settings) ? response.template.settings.footer_settings : { custom_footer: 'no', footer_content: '' };
                    if (response.template._visual_builder_design) {
                        this.campaign._visual_builder_design = response.template._visual_builder_design;
                    }
                    this.$emit('template_inserted', response.template);
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.fetchingTemplate = false;
                    this.editor_status = true;
                    this.templates_modal = false;
                });
        },
        handleFixed() {
            if (this.disable_fixed) {
                return;
            }
            window.addEventListener('scroll', (e) => {
                const domElement = document.querySelector('.fluentcrm_visual_editor.fc_is_guten');
                if (!domElement) {
                    return;
                }
                const topPosition = domElement.getBoundingClientRect().top;
                if (topPosition < 32) {
                    domElement.classList.add('fc_element_fixed');
                    document.querySelector('.fc_visual_header').style.width = domElement.offsetWidth + 'px';
                } else {
                    domElement.classList.remove('fc_element_fixed');
                }
            });
        },
        showInserter() {
            jQuery('.fce_inserter button.block-editor-inserter__toggle').trigger('click');
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_type = 'desc';
            } else {
                this.query_data.sort_by = sorting.prop;
                this.query_data.sort_type = 'asc';
            }
            this.fetchTemplates();
        },
        saveAsTemplate() {
            if (!this.new_template_name) {
                this.$notify.error(this.$t('Please provide a template name'));
                return false;
            }

            const templateData = {
                post_content: this.campaign[this.email_body_key],
                email_subject: this.campaign.email_subject,
                post_excerpt: this.campaign.email_pre_header,
                design_template: this.campaign.design_template,
                settings: {
                    template_config: this.campaign.settings.template_config,
                    footer_settings: this.footerSettings
                },
                post_title: this.new_template_name,
                edit_type: 'html'
            };

            if (this.campaign.design_template == 'visual_builder') {
                templateData._visual_builder_design = this.campaign._visual_builder_design;
            }

            this.saving_template = true;
            this.$post('templates', {
                template: JSON.stringify(templateData)
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.new_template_name = '';
                    this.new_template_pop = false;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving_template = false;
                })
        },

        showPreview(template) {
            this.previewTemplateId = template.ID;
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
        },
        openBuiltinTemplateDrawer() {
            this.open_drawer = true;
        }
    },
    mounted() {
        this.handleFixed();
    }
}
</script>
