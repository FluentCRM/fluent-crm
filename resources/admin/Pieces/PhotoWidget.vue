<template>
    <div class="fluentcrm_photo_card">
        <div v-if="app_ready" class="fluentcrm_photo_holder">
            <img v-if="value && !btn_mode" :src="value"/>
            <el-button size="small" @click="initUploader" :type="btn_type">{{btn_text}}</el-button>
            <slot name="after"></slot>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'photo_widget',
        props: {
            value: {
                required: false,
                type: String
            },
            btn_mode: {
                type: Boolean,
                default() {
                    return false
                }
            },
            btn_text: {
                required: false,
                default() {
                    return '+ Upload';
                }
            },
            btn_type: {
                required: false,
                default() {
                    return 'default';
                }
            }
        },
        data() {
            return {
                app_ready: false,
                image_url: this.value
            }
        },
        methods: {
            initUploader(event) {
                const that = this;
                const sendAttachmentBkp = wp.media.editor.send.attachment;
                wp.media.editor.send.attachment = function (props, attachment) {
                    that.$emit('input', attachment.url);
                    that.$emit('changed', attachment.url);
                    that.image_url = attachment.url;
                    wp.media.editor.send.attachment = sendAttachmentBkp;
                }
                wp.media.editor.open(jQuery(event.target));
                return false;
            },
            getThumb(attachment) {
                return attachment.url;
            }
        },
        mounted() {
            if (!window.wpActiveEditor) {
                window.wpActiveEditor = null;
            }
            this.app_ready = true;
        }
    };
</script>

<style lang="scss">
    .fluentcrm_photo_holder {
        display: inline-flex;
        align-items: flex-end;
        img {
            max-height: 100px;
            margin-right: 6px;
        }
    }
</style>
