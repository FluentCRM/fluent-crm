<template>
    <div class="fluentcrm_visual_editor">
        <div class="fluentcrm_header fc_visual_header">
            <div class="fluentcrm_header_title">
                <h3>
                    {{$t('Email Body')}}
                    <el-button v-if="!isEmptyValue(template_config)" @click="showBodyConfig = true" size="mini"
                               icon="el-icon-setting"
                               type="info"></el-button>
                </h3>
            </div>
            <div class="fluentcrm-actions">
                <slot name="fc_editor_actions"></slot>
            </div>
        </div>
        <div class="fc_visual_body">
            <div id="fluentcrm_block_editor" class="fc_block_editor" :class="'fc_skin_' + editor_design">
                {{$t('Loading Editor...')}}
            </div>
        </div>
        <el-dialog
            :close-on-click-modal="false"
            :title="$t('Email Styling Settings')"
            :visible.sync="showBodyConfig"
            :append-to-body="true"
            width="60%"
        >
            <el-form label-position="top" v-if="showBodyConfig" :data="template_config">
                <el-row :gutter="20">
                    <el-col :md="8" :sm="24">
                        <el-form-item :label="$t('Body Background Color')">
                            <el-color-picker @active-change="(color) => { template_config.body_bg_color = color; }"
                                             color-format="hex" :show-alpha="false"
                                             v-model="template_config.body_bg_color"></el-color-picker>
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item :label="$t('Content Max Width (PX)')">
                            <el-input-number :min="400" :step="10" v-model="template_config.content_width"></el-input-number>
                            <small style="display: block; line-height: 100%;">{{$t('Gut_Suggesting_vB6t8')}}</small>
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item :label="$t('Content Background Color')">
                            <el-color-picker @active-change="(color) => { template_config.content_bg_color = color; }"
                                             color-format="hex" :show-alpha="false"
                                             v-model="template_config.content_bg_color"></el-color-picker>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :md="8" :sm="24">
                        <el-form-item :label="$t('Default Content Color')">
                            <el-color-picker @active-change="(color) => { template_config.text_color = color; }"
                                             color-format="hex" :show-alpha="false"
                                             v-model="template_config.text_color"></el-color-picker>
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item :label="$t('Default Headings Color')">
                            <el-color-picker @active-change="(color) => { template_config.headings_color = color; }"
                                             color-format="hex" :show-alpha="false"
                                             v-model="template_config.headings_color"></el-color-picker>
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item :label="$t('Footer Text Color')">
                            <el-color-picker @active-change="(color) => { template_config.footer_text_color = color; }"
                                             color-format="hex" :show-alpha="false"
                                             v-model="template_config.footer_text_color"></el-color-picker>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :span="8">
                        <el-form-item :label="$t('Content Font Family')">
                            <el-select clearable v-model="template_config.content_font_family">
                                <el-option v-for="(fontValue,fontLabel) in email_font_families" :key="fontLabel"
                                           :label="fontLabel" :value="fontValue">
                                </el-option>
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="8">
                        <el-form-item :label="$t('Headings Font Family')">
                            <el-select clearable v-model="template_config.headings_font_family">
                                <el-option v-for="(fontValue,fontLabel) in email_font_families" :key="fontLabel"
                                           :label="fontLabel" :value="fontValue">
                                </el-option>
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <span slot="footer" class="dialog-footer text-align-right">
                <el-button size="small" type="success" @click="triggerUpdate()">{{$t('Update Settings')}}</el-button>
            </span>
        </el-dialog>
        <div id="fc_mail_config_style"></div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'GutenbergEditor',
    props: ['value', 'editor_design', 'template_config'],
    data() {
        return {
            content: this.value || '<!-- wp:paragraph --><p>' + this.$t('Start Writing Email Here') + '</p><!-- /wp:paragraph -->',
            showBodyConfig: false,
            email_font_families: {
                Arial: "Arial, 'Helvetica Neue', Helvetica, sans-serif",
                'Comic Sans': "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
                'Courier New': "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
                Georgia: "Georgia, Times, 'Times New Roman', serif",
                Helvetica: 'Helvetica , Arial, Verdana, sans-serif',
                Lucida: "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
                Tahoma: 'Tahoma, Verdana, Segoe, sans-serif',
                'Times New Roman': "'Times New Roman', Times, Baskerville, Georgia, serif",
                'Trebuchet MS': "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
                Verdana: 'Verdana, Geneva, sans-serif',
                Lato: "'Lato', 'Helvetica Neue', Helvetica, Arial, sans-serif",
                Lora: "'Lora', Georgia, 'Times New Roman', serif",
                Merriweather: "'Merriweather', Georgia, 'Times New Roman', serif",
                'Merriweather Sans': "'Merriweather Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif",
                'Noticia Text': "'Noticia Text', Georgia, 'Times New Roman', serif",
                'Open Sans': "'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif",
                Roboto: "'Roboto', 'Helvetica Neue', Helvetica, Arial, sans-serif",
                'Source Sans Pro': "'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif"
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
    methods: {
        init() {
            window.fluentCrmBootEmailEditor(this.content, this.handleChange);
        },
        handleChange(blocks) {
            this.$emit('input', blocks);
        },
        handleFixed() {
            window.addEventListener('scroll', (e) => {
                const domElement = document.querySelector('.fluentcrm_visual_editor');
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
        triggerUpdate() {
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
            styles += `${prefix} .fc_editor_body p,
            ${prefix} .fc_editor_body li, ol { color: inherit; font-size: inherit; }`;

            styles += `${prefix} .fc_editor_body h1,
            ${prefix} .fc_editor_body h2,
            ${prefix} .fc_editor_body h3,
            ${prefix} .fc_editor_body h4 { color: ${config.headings_color}; font-family: ${config.headings_font_family} !important; }`;

            jQuery('#fc_mail_config_style').html('<style type="text/css">' + styles + '</style>');
        }
    },
    mounted() {
        this.init();
        this.handleFixed();
        this.generateStyles();
    }
};
</script>

<style lang="scss">

</style>
