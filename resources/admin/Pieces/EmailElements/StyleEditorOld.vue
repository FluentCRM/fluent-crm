<template>
    <span style="display: inline-block;" class="fc_style_editor">
        <el-button v-if="!isEmptyValue(template_config)" @click="showBodyConfig = true" size="mini"
                   icon="el-icon-setting"
                   type="info"></el-button>
        <el-dialog
            :close-on-click-modal="false"
            :title="$t('Email Styling Settings & Footer Settings')"
            :visible.sync="showBodyConfig"
            :append-to-body="true"
            width="60%"
        >
             <el-tabs v-if="showBodyConfig" v-model="activeTab" class="fc_settings_popup">
                 <el-tab-pane label="Global Settings" name="global">
                        <div style="margin-top: 20px;" class="fc_2col_form_wrapper">
                            <form-builder v-if="showBodyConfig" :formData="template_config" :fields="settingsFields"/>
                        </div>
                 </el-tab-pane>
                 <el-tab-pane label="Footer Settings" name="email_footer">
                     <div style="margin-top: 20px;">
                         <p>{{ $t('Customize_Email_Footer_Sec') }}</p>
                         <form-builder  :formData="footer_settings" :fields="email_footer_fields"/>
                     </div>
                 </el-tab-pane>
             </el-tabs>
            <span slot="footer" class="dialog-footer text-align-right">
                <el-button size="small" type="success" @click="triggerUpdate()">{{ $t('Update Settings') }}</el-button>
            </span>
        </el-dialog>
        <div id="fc_mail_config_style"></div>
    </span>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';
import {emailFontFamilies} from '@/Bits/data_config.js';

export default {
    name: 'EmailStyleEditor',
    props: ['template_config', 'footer_settings'],
    components: {
        FormBuilder
    },
    data() {
        return {
            showBodyConfig: false,
            email_font_families: emailFontFamilies,
            activeTab: 'global',
            email_footer_fields: {
                custom_footer: {
                    type: 'input-radio',
                    label: this.$t('Email Footer Type'),
                    options: [
                        {
                            id: 'no',
                            label: this.$t('Use Global Email Footer')
                        },
                        {
                            id: 'yes',
                            label: this.$t('Use Custom Email Footer')
                        }
                    ]
                },
                footer_content: {
                    type: 'wp-editor',
                    placeholder: this.$t('Custom Email Footer Text'),
                    label: this.$t('Custom Email Footer Text'),
                    help: this.$t('This email footer text will be used to this email only'),
                    inline_help: this.$t('You should provide your business address') + ' {{crm.business_address}} ' + this.$t('and manage subscription/unsubscribe url is mandatory') + '<br/>' + this.$t('Smartcode:') + ' {{crm.business_name}}, {{crm.business_address}}, ##crm.manage_subscription_url##, ##crm.unsubscribe_url## ' + this.$t('will be replaced with dynamic values.'),
                    dependency: {
                        depends_on: 'custom_footer',
                        operator: '=',
                        value: 'yes'
                    }
                }
            }
        }
    },
    watch: {
        template_config: {
            deep: true,
            handler() {
                this.generateStyles();
            }
        }
    },
    computed: {
        settingsFields() {
            if (this.isEmptyValue(this.template_config)) {
                return {};
            }
            const fontOptions = [];
            this.each(this.email_font_families, (name, index) => {
                fontOptions.push({
                    id: name,
                    label: index
                });
            });
            const designElements = {
                body_bg_color: {
                    label: this.$t('Body Background Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                content_width: {
                    label: this.$t('Content Max Width (PX)'),
                    inline_help: this.$t('Gut_Suggesting_vB6t8'),
                    type: 'input-number',
                    min: 400,
                    step: 10
                },
                content_padding: {
                    label: this.$t('Content Padding Left/Right'),
                    type: 'input-number',
                    min: 0,
                    step: 1
                },
                content_bg_color: {
                    label: this.$t('Content Background Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                text_color: {
                    label: this.$t('Default Content Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                heading_color: {
                    label: this.$t('Default Headings Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                footer_text_color: {
                    label: this.$t('Footer Text Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                link_color: {
                    label: this.$t('Default Link Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                content_font_family: {
                    label: this.$t('Content Font Family'),
                    type: 'input-option',
                    options: fontOptions
                },
                headings_font_family: {
                    label: this.$t('Headings Font Family'),
                    type: 'input-option',
                    options: fontOptions
                },
                disable_footer: {
                    type: 'inline-checkbox',
                    true_label: 'yes',
                    false_label: 'no',
                    checkbox_label: this.$t('Disable Default Email Footer'),
                    inline_help: this.$t('email_will_be_sent_without_footer_contents')
                }
            };
            const configKeys = Object.keys(this.template_config);
            const validFields = {};
            this.each(designElements, (element, elementKey) => {
                if (configKeys.indexOf(elementKey) != -1) {
                    validFields[elementKey] = element;
                }
            });
            return validFields;
        }
    },
    methods: {
        triggerUpdate() {
            this.$post('templates/set-global-style', {
                config: this.template_config
            })
                .then((response) => {
                    console.log(response);
                })
                .catch((errors) => {
                    console.log(errors);
                });
            this.$emit('save');
            this.showBodyConfig = false;
        },
        generateStyles() {
            let styles = '';
            const config = this.template_config;
            if (this.isEmptyValue(config)) {
                jQuery('#fc_mail_config_style').html('');
                return;
            }
            const prefix = '.fluentcrm_visual_editor .fc_visual_body .fce-block-editor ';
            styles += `${prefix} .block-editor-writing-flow { background-color: ${config.body_bg_color}; }`;
            styles += `${prefix} .fc_editor_body { background-color: ${config.content_bg_color}; color: ${config.text_color}; max-width: ${config.content_width}px; font-family: ${config.content_font_family} !important; }`;
            styles += `.fc_skin_plain .fc_editor_body,.fc_skin_classic .fc_editor_body,.fc_skin_simple .fc_editor_body {padding-left: ${config.content_padding}px !important;padding-right: ${config.content_padding}px !important; }`;
            styles += `${prefix} .fc_editor_body p,
            ${prefix} .fc_editor_body li, ol { color: inherit; font-size: inherit; }`;
            styles += `${prefix} .fc_editor_body h1,
            ${prefix} .fc_editor_body h2,
            ${prefix} .fc_editor_body h3,
            ${prefix} .fc_editor_body h4 { color: ${config.headings_color}; font-family: ${config.headings_font_family} !important; }
            ${prefix} .fc_editor_body a { color: ${config.link_color}; }`;
            jQuery('#fc_mail_config_style').html('<style type="text/css">' + styles + '</style>');
        }
    },
    mounted() {
        this.generateStyles();
        if (!this.footer_settings) {
            this.footer_settings = {
                custom_footer: 'no',
                footer_content: ''
            };
        }
        if (!this.footer_settings.footer_content || this.footer_settings.footer_content.length < 10) {
            this.footer_settings.footer_content = window.fcAdmin.global_email_footer
        }
    }
}
</script>
