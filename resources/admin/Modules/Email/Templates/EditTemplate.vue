<template>
    <div class="fluentcrm-templates fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3 v-if="template_id == 0">{{ $t('Create Email Template') }}</h3>
                <div v-else>
                    <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                        <el-breadcrumb-item :to="{ name: 'templates' }">
                            {{ $t('Email Templates') }}
                        </el-breadcrumb-item>
                        <el-breadcrumb-item>
                            {{ email_template.post_title }}
                        </el-breadcrumb-item>
                    </el-breadcrumb>
                </div>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button @click="maybeSaveTemplate" v-if="template_id == 0" size="small" type="primary">
                    {{ $t('Create Template') }}
                </el-button>
                <template v-else>
                    <el-button style="margin-right: 10px;" @click="maybeSaveTemplate" size="small" type="primary">
                        {{ $t('Save Template') }}
                    </el-button>
                    <send-test-email btn_type="danger" :campaign="{ email_subject: email_template.email_subject,
                    email_pre_header: email_template.post_excerpt,
                    email_body: email_template.post_content,
                    design_template: email_template.design_template,
                    settings: email_template.settings }"/>
                    <el-dropdown trigger="click">
                        <span class="el-dropdown-link">
                            <i style="font-weight: bold; cursor: pointer;"
                               class="el-icon-more icon-90degree el-icon--right"></i>
                        </span>
                        <el-dropdown-menu slot="dropdown">
                            <el-dropdown-item class="fc_dropdown_action">
                            <span class="el-popover__reference" @click="exportTemplate()">
                                <span class="el-icon el-icon-download"></span> {{ $t('Export Template') }}
                            </span>
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </template>
            </div>
        </div>
        <div v-loading="loading" class="fluentcrm_body fluentcrm_body_boxed" style="position: relative;">
            <div v-if="loading" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>

            <div v-if="app_ready">
                <el-form label-position="top" label-width="120px" :model="email_template">
                    <el-row :gutter="30">
                        <el-col :sm="24" :md="12">
                            <el-form-item :label="$t('Template Title')">
                                <el-input :placeholder="$t('Template Title')"
                                          v-model="email_template.post_title"></el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="30">
                        <el-col :sm="24" :md="12">
                            <el-form-item :label="$t('Email Subject')">
                                <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/"
                                               popper_extra="fc_with_c_fields" :placeholder="$t('Email Subject')"
                                               :data="smart_codes"
                                               v-model="email_template.email_subject"/>
                            </el-form-item>
                        </el-col>
                        <el-col :sm="24" :md="12">
                            <el-form-item :label="$t('Email Pre-Header')">
                                <el-input class="min_textarea_40" type="textarea"
                                          :placeholder="$t('Email Pre-Header')"
                                          :rows="1"
                                          v-model="email_template.post_excerpt"
                                ></el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>
                </el-form>
            </div>
            <div style="margin: 0 -20px;" v-if="app_ready && codes_ready && !loading">
                <email-block-composer @save="saveTemplate()" @fetch="fetchTemplate()" @changed="handleChangeContent()"
                                      :show_merge="true"
                                      :enable_templates="true"
                                      :show_audit="true"
                                      body_key="post_content"
                                      :campaign="email_template"/>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import EmailBlockComposer from '@/Pieces/EmailElements/BlockComposer'
import InputPopover from '@/Pieces/InputPopover';
import SendTestEmail from '@Pieces/TestEmail.vue';

export default {
    name: 'edit_template',
    props: ['template_id'],
    components: {
        InputPopover,
        EmailBlockComposer,
        SendTestEmail
    },
    data() {
        return {
            email_template: {
                post_title: '',
                post_content: ' ',
                post_excerpt: '',
                email_subject: '',
                edit_type: 'html',
                design_template: 'simple',
                settings: {
                    template_config: {}
                }
            },
            email_template_designs: window.fcAdmin.email_template_designs,
            smart_codes: [],
            loading: true,
            app_ready: false,
            codes_ready: false,
            is_dirty: false,
            prevContent: ''
        }
    },
    methods: {
        fetchSmartCodes() {
            this.codes_ready = false;
            this.$get('templates/smartcodes', {})
                .then(response => {
                    this.smart_codes = response.smartcodes;
                })
                .catch((error) => {
                    console.log(error);
                })
                .finally(() => {
                    this.codes_ready = true;
                });
        },
        fetchTemplate() {
            this.loading = true;
            this.$get(`templates/${this.template_id}`)
                .then(response => {
                    this.email_template = response.template;

                    this.$nextTick(() => {
                        this.app_ready = true;
                        this.prevContent = this.email_template.post_content;
                    });
                })
                .catch((error) => {
                    console.log(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        saveTemplate() {
            this.loading = true;
            this.is_dirty = false;
            let $request = {};
            if (parseInt(this.template_id)) {
                $request = this.$post('templates', {
                    template: JSON.stringify(this.email_template),
                    template_id: this.template_id
                })
            } else {
                $request = this.$post('templates', {
                    template: JSON.stringify(this.email_template)
                });
            }

            this.prevContent = this.email_template.post_content;

            $request.then(response => {
                this.$notify.success(response.message);
                if (!parseInt(this.template_id)) {
                    this.$router.push({
                        name: 'edit_template',
                        params: {
                            template_id: response.template_id
                        }
                    });
                }
            })
                .catch((error) => {
                    console.log(error);
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        maybeSaveTemplate() {
            if (this.email_template.design_template == 'visual_builder') {
                this.$bus.$emit('getVisualData', {});
            } else {
                this.saveTemplate();
            }
        },
        handleChangeContent() {
            this.is_dirty = true;
        },
        exportTemplate() {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Template_Export_Alert'));
                return;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_template',
                template_id: this.template_id
            });
        },
        initKeyboardSave(e) {
            if ((window.navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.maybeSaveTemplate();
            }
        },
        handleBeforeUnload(e) {
            if (this.is_dirty && this.prevContent !== this.email_template.post_content) {
                e.preventDefault();
                e.returnValue = ''; // Required for Chrome to show the prompt
                return '';
            }
        }
    },
    mounted() {
        this.fetchSmartCodes();
        this.fetchTemplate();
        this.changeTitle(this.$t('Edit Template'));
        document.addEventListener('keydown', this.initKeyboardSave);
        // Listen for page unloads (like WP sidebar clicks)
        window.addEventListener('beforeunload', this.handleBeforeUnload);
    },
    beforeRouteLeave(to, from, next) {
        if (this.is_dirty) {
            if (this.prevContent != this.email_template.post_content) {
                const answer = window.confirm(this.$t('Unsaved_Confirm_Msg'))
                if (!answer) return false;
            }
        }
        this.unmountBlockEditor();
        // remove the event listener
        document.removeEventListener('keydown', this.initKeyboardSave);
        next();
    },
    watch: {
        template_id(newValue) {
            if (newValue && newValue > 0) {
                this.fetchTemplate();
            }
        }
    }
}
</script>
