<template>
    <el-dialog
        :title="$t('Design Your Button')"
        :visible.sync="visibility"
        :append-to-body="true"
        :show-close="false"
        :close-on-click-modal="false"
        width="60%">
        <div class="fluentcrm_preview_mce">
            <el-row :gutter="20">
                <el-col :span="14">
                    <el-form label-position="top">
                        <el-col :span="12" v-for="(control,controlName) in controls" :key="controlName">
                            <el-form-item :label="control.label">
                                <template v-if="control.type == 'text' || control.type == 'url'">
                                    <el-input :type="control.type" v-model="control.value"></el-input>
                                </template>
                                <template v-else-if="control.type == 'color_picker'">
                                    <el-color-picker
                                        v-model="control.value"
                                        @active-change="(color) => { control.value = color}"
                                    ></el-color-picker>
                                </template>
                                <div v-else-if="control.type == 'slider'">
                                    <el-slider
                                        v-model="control.value"
                                        :min="control.min"
                                        :max="control.max"
                                    ></el-slider>
                                </div>
                                <template v-else-if="control.type == 'checkboxes'">
                                    <el-checkbox-group v-model="control.value">
                                        <el-checkbox
                                            v-for="(optionLabel, optionValue) in control.options"
                                            :key="optionValue"
                                            :label="optionValue">{{optionLabel}}
                                        </el-checkbox>
                                    </el-checkbox-group>
                                </template>
                            </el-form-item>
                        </el-col>
                    </el-form>
                </el-col>
                <el-col :span="10">
                    <div class="fluentcrm_button_preview">
                        <div class="preview_header">
                            {{$t('Button Preview')}}:
                        </div>
                        <div class="preview_body">
                            <a @click="insert()" :style="style" href="#">{{controls.button_text.value}}</a>
                        </div>
                    </div>
                </el-col>
            </el-row>
        </div>
        <span slot="footer" class="dialog-footer">
            <el-button @click="close()">{{$t('Cancel')}}</el-button>
            <el-button type="primary" @click="insert()">{{$t('Insert')}}</el-button>
        </span>
    </el-dialog>
</template>

<script type="text/babel">
    export default {
        name: 'tinyButtonDesigner',
        props: ['visibility'],
        data() {
            return {
                controls: {
                    button_text: {
                        type: 'text',
                        label: this.$t('Button Text'),
                        value: this.$t('click here')
                    },
                    button_url: {
                        label: this.$t('Button URL'),
                        type: 'url',
                        value: ''
                    },
                    backgroundColor: {
                        label: this.$t('Background Color'),
                        type: 'color_picker',
                        value: '#0072ff'
                    },
                    textColor: {
                        label: this.$t('Text Color'),
                        type: 'color_picker',
                        value: '#ffffff'
                    },
                    borderRadius: {
                        label: this.$t('Border Radius'),
                        type: 'slider',
                        value: 5,
                        max: 50,
                        min: 0
                    },
                    fontSize: {
                        label: this.$t('Font Size'),
                        type: 'slider',
                        value: 16,
                        min: 8,
                        max: 40
                    },
                    fontStyle: {
                        label: this.$t('Font Style'),
                        type: 'checkboxes',
                        value: [],
                        options: {
                            bold: 'Bold',
                            italic: 'Italic',
                            underline: 'Underline'
                        }
                    }
                },
                style: ''
            }
        },
        watch: {
            controls: {
                handler() {
                    this.generateStyle();
                },
                deep: true
            }
        },
        methods: {
            close() {
                this.$emit('close');
            },
            insert() {
                if (!this.controls.button_url.value || !this.controls.button_text.value) {
                    this.$notify.error('Button Text and URL is required');
                    return;
                }
                const html = `<a  style="${this.style}" href="${this.controls.button_url.value}">${this.controls.button_text.value}</a>`;
                this.$emit('insert', html);
                this.close();
            },
            generateStyle() {
                const fontStyles = this.controls.fontStyle.value;
                const textDecoration = (fontStyles.indexOf('underline') === -1) ? 'none' : 'underline';
                const fontWeight = (fontStyles.indexOf('bold') === -1) ? 'normal' : 'bold';
                const fontStyle = (fontStyles.indexOf('italic') === -1) ? 'normal' : 'italic';
                this.style = `color:${this.controls.textColor.value};` +
                    `background-color:${this.controls.backgroundColor.value};` +
                    `font-size:${this.controls.fontSize.value}px;` +
                    `border-radius:${this.controls.borderRadius.value}px;` +
                    `text-decoration:${textDecoration};` +
                    `font-weight:${fontWeight};` +
                    `font-style:${fontStyle};` +
                    'padding:0.8rem 1rem;border-color:#0072ff;';
            }
        },
        mounted() {
            this.generateStyle();
        }
    }
</script>

<style lang="scss">
    .fluentcrm_button_preview {
        border: 1px solid #dde6ed !important;
        border-radius: .3rem !important;
        display: flex;
        height: 100% !important;
        flex-direction: column !important;
        text-align: center;

        .preview_header {
            background-color: #f2f5f9 !important;
            color: #53657a !important;
        }

        .preview_body {
            align-items: center !important;
            height: 100%;
            padding: 150px 0px;
        }
    }
</style>
