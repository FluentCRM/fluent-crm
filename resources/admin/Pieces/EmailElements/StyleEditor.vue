<template>
    <span style="display: inline-block;" class="fc_style_editor">
        <el-button v-if="!isEmptyValue(template_config)" @click="showBodyConfig = true" size="mini"
                   icon="el-icon-setting"
                   type="info"></el-button>
        <el-dialog
            :close-on-click-modal="false"
            :title="$t('Email Styling Settings')"
            :visible.sync="showBodyConfig"
            :append-to-body="true"
            width="60%"
        >

            <el-tabs v-model="activeTab" class="fc_settings_popup">
                <el-tab-pane label="Global Settings" name="global">
                    <div class="fc_2col_form_wrapper">
                        <form-builder v-if="showBodyConfig" :formData="template_config" :fields="settingsFields"/>
                    </div>
                </el-tab-pane>
                <el-tab-pane label="Block Settings" name="block">
                    <el-collapse v-model="activeBlock" accordion class="fc_block_collapse">
                        <el-collapse-item title="Block: Paragraph" name="paragraph">
                            <div class="fc_3col_form_wrapper">
                                <form-builder v-if="showBodyConfig" :formData="template_config" :fields="blockParagraphSettingsFields" />
                            </div>
                        </el-collapse-item>
                        <el-collapse-item title="Block: Heading" name="heading">
                            <div class="fc_3col_form_wrapper">
                                <form-builder v-if="showBodyConfig" :formData="template_config" :fields="blockHeadingSettingsFields" />
                            </div>
                        </el-collapse-item>
                    </el-collapse>
                </el-tab-pane>
                <el-tab-pane label="Email Footer" name="email_footer">
                    <p>{{ $t('Customize_Email_Footer_Sec') }}</p>
                    <el-radio-group v-model="footer_settings.custom_footer">
                        <el-radio label="no">{{ $t('Use Global Email Footer') }}</el-radio>
                        <el-radio label="yes">{{ $t('Use Custom Email Footer') }}</el-radio>
                    </el-radio-group>
                    <pre>{{footer_settings}}</pre>
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
            activeBlock: 'paragraph'
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
        blockParagraphSettingsFields() {
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
                paragraph_color: {
                    label: this.$t('Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                paragraph_font_size: {
                    label: this.$t('Font Size'),
                    type: 'input-number',
                    min: 12,
                    step: 1
                },
                paragraph_font_family: {
                    label: this.$t('Font Family'),
                    type: 'input-option',
                    options: fontOptions
                },
                paragraph_line_height: {
                    label: this.$t('Line Height'),
                    type: 'input-number',
                    min: 16,
                    step: 1
                }
            }

            const configKeys = Object.keys(this.template_config);

            const validFields = {};

            this.each(designElements, (element, elementKey) => {
                if (configKeys.indexOf(elementKey) != -1) {
                    validFields[elementKey] = element;
                }
            });

            return validFields;
        },
        blockHeadingSettingsFields() {
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
                headings_color: {
                    label: this.$t('Color'),
                    type: 'input-color',
                    colorFormat: 'hex',
                    showAlpha: false
                },
                heading_font_family: {
                    label: this.$t('Font Family'),
                    type: 'input-option',
                    options: fontOptions
                }
            }

            const configKeys = Object.keys(this.template_config);

            const validFields = {};

            this.each(designElements, (element, elementKey) => {
                if (configKeys.indexOf(elementKey) != -1) {
                    validFields[elementKey] = element;
                }
            });

            return validFields;
        },
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
            styles += `${prefix} .fc_editor_body .wp-block-paragraph {color: ${config.paragraph_color}; font-size: ${config.paragraph_font_size}px;font-family: ${config.paragraph_font_family} !important; line-height: ${config.paragraph_line_height}px;}`
            styles += `${prefix} .fc_editor_body li, ol { color: inherit; font-size: inherit; }`;

            styles += `${prefix} .fc_editor_body h1,
            ${prefix} .fc_editor_body h2,
            ${prefix} .fc_editor_body h3,
            ${prefix} .fc_editor_body h4 { color: ${config.headings_color}; font-family: ${config.heading_font_family} !important; }
            ${prefix} .fc_editor_body a { color: ${config.link_color} };`;

            jQuery('#fc_mail_config_style').html('<style type="text/css">' + styles + '</style>');
        }
    },
    mounted() {
        this.generateStyles();
    }
}
</script>
