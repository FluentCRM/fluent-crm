<template>
    <div>
        <div v-if="!is_inline" class="fc_visual_builder_wrap">
            <div v-if="!is_active" class="fc_visual_wrap">
                <div class="fc_visual_preview_inline">
                    <div class="fc_visual_intro">
                        <h3>{{ $t('Visually design your email with Drag & Drop Builder') }}</h3>
                        <div class="fc_visual_starter" v-if="editor_type == 'new'">
                            <h1>{{ $t('Select a starter design to build your email') }}</h1>
                            <div class="fc_visual_blocks">
                                <div v-for="visual in predefinedTemplates" @click="loadTemplate(visual.id, true)" :key="visual.id" class="fc_visual_block">
                                    <img :src="visual.image" :alt="visual.name" />
                                    <h3>{{visual.name}}</h3>
                                </div>
                            </div>
                        </div>
                        <el-button v-else id="fc_launch_editor_button" type="primary" @click="loadingFrame()"> {{ $t('Launch Visual Editor') }}
                        </el-button>

                        <p v-if="!editor_loaded" style="position: absolute; right: 2px;"><span
                            class="el-icon el-icon-loading"></span></p>
                    </div>
                    <iframe-builder v-if="!is_active" frame_height="800px" :preview_html="value"/>
                </div>
            </div>
            <div v-show="is_active" class="fc_builder_modal_wrap">
                <div class="fc_visual_modal">
                    <div v-if="!editor_loaded">
                        <div class="fc_editor_header">
                            <div class="fc_head_left">
                                <img :src="appVars.images_url + '/fluentcrm-logo.svg'"/>
                                <span>Pro</span>
                            </div>
                            <div class="fc_head_right">
                                <el-button type="primary" size="small" :disabled="true">{{ $t('Save') }}</el-button>
                                <el-button type="danger" size="small" :disabled="true">{{ $t('Save & close') }}</el-button>
                            </div>
                        </div>
                        <loader-skeleton />
                    </div>
                    <div class="fc_visual_parent">
                        <merge-codes id="fc_merge_code_wrap" :extra_tags="extra_tags"/>
                    </div>
                    <iframe v-if="frame_url" :style="{ visibility: (editor_loaded) ? 'visible' : 'hidden' }"
                            id="fc_visual_frame"
                            style="width: 100%;" :src="frame_url"/>
                </div>
            </div>
        </div>
        <div v-else>
            <div class="fc_visual_starter" v-if="editor_type == 'new'">
                <h1>{{ $t('Select a starter design to build your email') }}</h1>
                <div class="fc_visual_blocks">
                    <div v-for="visual in predefinedTemplates" @click="loadTemplate(visual.id)" :key="visual.id" class="fc_visual_block">
                        <img :src="visual.image" :alt="visual.name" />
                        <h3>{{visual.name}}</h3>
                    </div>
                </div>
            </div>
            <div v-else-if="!editor_loaded" style="background: white;">
                <loader-skeleton />
            </div>
            <iframe v-if="frame_url" id="fc_visual_frame" :style="{ visibility: (editor_loaded && editor_type != 'new') ? 'visible' : 'hidden' }" style="width: 100%; min-height: 100vh;"
                    :src="frame_url + '&inline=yes'"/>
        </div>
        <template v-if="showDisplayCondition">
            <el-dialog class="fc_force_modal" title="Select your Condition"
                        :visible.sync="showDisplayCondition"
                       :append-to-body="true"
                       :modal-append-to-body="true"
                        width="30%">
                <display-condition :editing_condition="editing_condition" @insertTag="fireConditionTag" />
            </el-dialog>
        </template>
    </div>
</template>

<script type="text/babel">
import IframeBuilder from '../Pieces/IframeBuilder';
import MergeCodes from '../Pieces/EmailElements/_MergeCodes';
import LoaderSkeleton from './_LoaderSkeleton';
import DisplayCondition from './DisplayCondition';

export default {
    name: 'VisualEditor',
    props: ['value', 'campaign', 'extra_tags'],
    components: {
        IframeBuilder,
        MergeCodes,
        LoaderSkeleton,
        DisplayCondition
    },
    data() {
        return {
            is_active: false,
            is_loading: false,
            editor_loaded: false,
            editor_type: 'old',
            frame_url: '',
            isVerified: false,
            target_origin: window.fcVisualVars.editor_domain,
            context: this.$route?.name || 'contact_email',
            inlineCallBack: null,
            predefinedTemplates: [
                {
                    id: 243318,
                    name: 'Blank',
                    image: this.appVars.images_url + '/templates/blank.jpg'
                },
                {
                    id: 228634,
                    name: 'Standard',
                    image: this.appVars.images_url + '/templates/standard.jpg'
                },
                {
                    id: 249301,
                    name: 'Sales',
                    image: this.appVars.images_url + '/templates/sales.jpg'
                }
            ],
            loadedtemplateId: 243318,
            showDisplayCondition: false,
            editing_condition: {}
        }
    },
    computed: {
        mergeTags() {
            const tags = {};

            let allTags = [...window.fcAdmin.globalSmartCodes, ...window.fcAdmin.extendedSmartCodes];

            if (this.extra_tags && this.extra_tags.length) {
                allTags = [...allTags, ...this.extra_tags];
            }

            this.each(allTags, (tagGroup) => {
                if (!tags[tagGroup.key]) {
                    tags[tagGroup.key] = {
                        name: tagGroup.title,
                        mergeTags: {}
                    };
                }
                let counter = 1;
                this.each(tagGroup.shortcodes, (value, key) => {
                    tags[tagGroup.key].mergeTags[counter + '_' + key] = {
                        name: value,
                        value: key
                    }
                    counter++;
                });
            });
            return tags;
        },
        is_inline() {
            return this.context == 'edit_template' || this.context == 'campaign' || this.context == 'edit-sequence-email';
        }
    },
    methods: {
        loadingFrame() {
            document.body.classList.add('fc_locked');
            this.is_loading = true;
            this.is_active = true;
            const frame = document.getElementById('fc_visual_frame');
            if (!this.isVerified) {
                frame.contentWindow.postMessage({
                    from: 'fc_parent',
                    action: 'verify'
                }, this.target_origin);
                this.isVerified = true;
            }
        },
        iframeEvent(event) {
            const item = event.data;

            if (item.type != 'fc_editor') {
                return;
            }

            if (item.action == 'editor_loaded') {
                document.body.classList.add('fc_locked_loaded');
                const frame = document.getElementById('fc_visual_frame');
                const design = this.getInitialContent();
                if (design) {
                    frame.contentWindow.postMessage({
                        from: 'fc_parent',
                        action: 'load_design',
                        data: design,
                        mergeTags: this.mergeTags
                    }, this.target_origin);
                } else {
                    frame.contentWindow.postMessage({
                        from: 'fc_parent',
                        action: 'load_template',
                        template_id: this.loadedtemplateId,
                        mergeTags: this.mergeTags
                    }, this.target_origin);
                }
                setTimeout(() => {
                    this.is_loading = false;
                    this.editor_loaded = true;
                }, 1000);
            } else if (item.action == 'save_design') {
                this.saveContent(item);
                this.$notify({
                    message: this.$t('Saved'),
                    position: 'bottom-right',
                    customClass: 'fc_notify_z bottom_right',
                    type: 'success',
                    duration: 500
                });
            } else if (item.action == 'save_close') {
                this.saveContent(item);
                document.body.classList.remove('fc_locked', 'fc_locked_loaded');
                this.is_active = false;

                if (this.context == 'edit_funnel') {
                    this.frame_url = '';
                    this.$nextTick(() => {
                        this.setFrameUrl();
                    });
                }
            } else if (item.action == 'image_selector') {
                this.initUploader();
            } else if (item.action == 'open_merge_codes') {
                jQuery('#fc_merge_code_wrap button').trigger('click');
            } else if (item.action == 'updated_design') {
                this.saveContent(item);
                if (this.inlineCallBack) {
                    this.inlineCallBack(item);
                }
            } else if (item.action == 'display_condition') {
                if (!window.fcVisualVars.has_conditions) {
                    this.$notify.error(this.$t('Please update FluentCRM Pro first'));
                    return;
                }
                this.initDisplayCondition(item.items);
            } else {
                console.log(item);
            }
        },
        initDisplayCondition(item) {
            this.editing_condition = item;
            this.showDisplayCondition = true;
        },
        loadTemplate(id, isFull) {
            const frame = document.getElementById('fc_visual_frame');
            this.loadedtemplateId = id;
            frame.contentWindow.postMessage({
                from: 'fc_parent',
                action: 'load_template',
                template_id: id,
                mergeTags: this.mergeTags
            }, this.target_origin);
            this.editor_type = 'old';

            if (isFull) {
                this.loadingFrame();
            } else {
                setTimeout(() => {
                    this.is_loading = false;
                    this.editor_loaded = true;
                }, 1000);
            }
        },
        saveContent(item) {
            this.campaign._visual_builder_design = item.design;
            this.$emit('input', item.html);
            if (item.reference != 'update_only') {
                this.$nextTick(() => {
                    this.$emit('save');
                });
            }
            this.editor_type = 'old';
        },
        getInitialContent() {
            return this.campaign._visual_builder_design || null;
        },
        initUploader() {
            wp.media.editor.remove('fc_launch_editor_button');
            const sendAttachmentBkp = wp.media.editor.send.attachment;
            const that = this;
            wp.media.editor.send.attachment = function (props, attachment) {
                const frame = document.getElementById('fc_visual_frame');
                let media = {
                    url: attachment.url,
                    width: attachment.width,
                    height: attachment.height,
                    altText: attachment.alt,
                    alternateText: attachment.alt
                };

                if (props.size != 'full' && attachment.sizes && attachment.sizes[props.size]) {
                    if (attachment.sizes[props.size].width < 1000) {
                        props.size = 'large';
                    }

                    if (attachment.sizes[props.size] && attachment.sizes[props.size].width > 1000) {
                        media = attachment.sizes[props.size];
                    }
                }

                frame.contentWindow.postMessage({
                    from: 'fc_parent',
                    action: 'add_media',
                    media: media
                }, that.target_origin);

                wp.media.editor.send.attachment = sendAttachmentBkp;
            }
            wp.media.editor.open('fc_launch_editor_button', {
                frame: 'post',
                state: 'insert',
                title: this.$t('Select Image for Your Email Body'),
                multiple: false
            });
            return false;
        },
        setFrameUrl() {
            if (!window.fcVisualVars) {
                return '';
            }
            const url = new URL(window.fcVisualVars.url);
            this.each(window.fcVisualVars.params, (paramValue, param) => {
                url.searchParams.set(param, paramValue);
            });
            url.searchParams.set('context', this.context);
            url.searchParams.set('version', this.appVars.app_version);

            if (this.appVars.disable_ai) {
                url.searchParams.set('disable_ai', 'yes');
            }

            this.frame_url = url.href;
        },
        listenBus(e) {
            if (!this.editor_loaded) {
               this.$notify.error(this.$t('Editor is loading. Please wait'));
                return;
            }
            if (e.callback) {
                this.inlineCallBack = e.callback;
            } else {
                this.inlineCallBack = null;
            }
            const frame = document.getElementById('fc_visual_frame');
            frame.contentWindow.postMessage({
                from: 'fc_parent',
                action: 'fire_save_data',
                reference: e.reference
            }, this.target_origin);
        },
        fireConditionTag(item) {
            const frame = document.getElementById('fc_visual_frame');
            frame.contentWindow.postMessage({
                from: 'fc_parent',
                action: 'apply_condition',
                item: item
            }, this.target_origin);
            this.showDisplayCondition = false;
        }
    },
    mounted() {
        this.setFrameUrl();

        if (this.campaign._visual_builder_design) {
            this.editor_type = 'old';
        } else if (!this.value || this.value.indexOf(this.$t('Start Writing Here')) !== -1 || jQuery(this.value).text().trim().length < 20) {
            this.editor_type = 'new';
        } else {
            this.editor_type = 'existing_content';
        }

        window.addEventListener('message', this.iframeEvent, false);

        if (!window.wpActiveEditor) {
            window.wpActiveEditor = null;
        }

        this.$bus.$on('getVisualData', this.listenBus);
    },
    beforeDestroy() {
        window.removeEventListener('message', this.iframeEvent, false);
        document.body.classList.remove('fc_locked', 'fc_locked_loaded');
        this.$bus.$off('getVisualData', this.listenBus);
    }
}
</script>

<style lang="scss">
.fc_visual_modal {
    .el-dialog {
        margin-top: 0 !important;
    }
}

.fc_builder_modal_wrap {
    position: fixed;
    z-index: 99999;
    background-color: rgba(0, 0, 0, .5);
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;

    .fc_visual_modal {
        background-color: #fff;
        padding: 0;
        width: 100%;
        height: 100%;
        position: relative;
        overflow: hidden;

        iframe {
            width: 100%;
            height: 100%;
            z-index: 9999;
        }
    }
}

.fc_designer_wrapper {
    iframe {
        width: 100%;
        min-height: calc(100vh - 150px);
    }
}

.fc_design_template_visual_builder_wrapper {
    .fc_composer_body {
        width: 100% !important;
        padding: 0px 15px;
    }
}

.fc_visual_intro {
    margin: 20px auto;
    max-width: 600px;
    text-align: center;
    background: white;
    padding: 30px;
}

.fc_locked {
    overflow: hidden;
}

.fc_visual_preview_inline {
    max-width: 900px;
    margin: 0 auto;
    position: relative;

    .fc_iframe_wrap {
        width: 100%;
        height: 800px;
        overflow: hidden;
        position: relative;
    }

    .fc_iframe_wrap:before {
        content: ' ';
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #4443438c;
        position: absolute;
    }
}

.fc_visual_intro {
    position: absolute;
    top: 40px;
    z-index: 9999;
    left: calc(50% - 225px);
    box-shadow: 0 0 12px 12px #464646;
}

.fc_visual_parent {
    position: absolute;
    right: 10px;
    top: 12px;
    visibility: hidden;
    z-index: 0;
}

.fc_editor_header {
    box-shadow: 1px 1px 3px rgb(115 113 113 / 10%);
    position: relative;
    display: flex;
    padding: 8px 50px 7px 20px;
    justify-content: space-between;

    .fc_head_left {
        img {
            width: 32px;
            height: 32px;
        }

        span {
            position: absolute;
            top: 10px;
            color: black;
            padding-left: 5px;
            font-size: 10px;
        }
    }

    .fc_head_right {
        display: flex;
        align-items: center;
    }
}

/*
* Funnel Modal hack
 */

body.fc_locked_loaded.fc_locked .el-dialog {
    margin: 0 !important;

    .fc_funnerl_editor {
        > div {
            display: none !important;
            z-index: 0 !important;

            &.fc_email_writer {
                display: block !important;
                z-index: 999999 !important;
            }
        }
    }

    .fluentcrm_visual_editor {
        margin: 0 !important;
    }

    .fluentcrm-sequence_control {
        display: none;
    }

    .fluentcrm_block_editor_body > form > div {
        display: none;

        &.fc_funnerl_editor {
            display: inherit;
        }
    }

    .fc_design_template_visual_builder_wrapper .el-row > div {
        display: none;

        &.fc_composer_body {
            display: inherit;
        }
    }
}

body.fc_locked_loaded div#wpwrap {
    z-index: 0;
}

.fc_visual_starter {
    margin: 30px 0;
    text-align: center;
    h1 {
        color: #6e6e71;
        font-size: 24px;
        margin-bottom: 20px;
    }
}
.fc_visual_blocks {
    display: flex;
    margin: 0 auto;
    max-width: 1000px;
    .fc_visual_block {
        cursor: pointer;
        background: white;
        text-align: center;
        margin: 20px;
        min-width: 150px;
        border: 1px solid #e3e8ee;
        border-radius: 4px;
        img {
            border-top-left-radius: 4px;
            border-top-right-radius: 4px;
            max-width: 100%;
        }
        &:hover {
            border: 1px solid #3f9eff;
            h3 {
                color: #3f9eff;
            }
        }
    }
}
</style>
