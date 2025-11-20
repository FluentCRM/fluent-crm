<template>
    <div class="wp_vue_editor_wrapper">
        <popover v-if="editorShortcodes && editorShortcodes.length" class="popover-wrapper"
                 doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/"
                 :class="{'popover-wrapper-plaintext': !hasWpEditor}" :data="editorShortcodes"
                 @command="handleCommand"></popover>
        <textarea v-if="hasWpEditor" class="wp_vue_editor" :id="editor_id">{{ value }}</textarea>
        <textarea v-else
                  class="wp_vue_editor wp_vue_editor_plain"
                  v-model="plain_content"
                  @click="updateCursorPos">
        </textarea>

        <button-designer v-if="showButtonDesigner" @close="() => {showButtonDesigner = false}" @insert="insertHtml"
                         :visibility="showButtonDesigner"></button-designer>

    </div>
</template>

<script type="text/babel">
import popover from '@/Pieces/input-popover-dropdown.vue'
import ButtonDesigner from './MCE/button';
import {emailFontFamilies} from '@/Bits/data_config.js';

export default {
    name: 'wp_editor',
    components: {
        popover,
        ButtonDesigner
    },
    props: {
        editor_id: {
            type: String,
            default() {
                return 'wp_editor_' + Date.now() + parseInt(Math.random() * 1000);
            }
        },
        value: {
            type: String,
            default() {
                return '';
            }
        },
        editorShortcodes: {
            type: Array,
            default() {
                return []
            }
        },
        height: {
            type: Number,
            default() {
                return 250;
            }
        },
        extra_style: {
            default() {
                return ''
            }
        }
    },
    data() {
        return {
            showButtonDesigner: false,
            hasWpEditor: (!!window.wp.editor && !!wp.editor.autop) || !!window.wp.oldEditor,
            editor: window.wp.oldEditor || window.wp.editor,
            plain_content: this.value,
            cursorPos: (this.value) ? this.value.length : 0,
            app_ready: false,
            buttonInitiated: false,
            currentEditor: false
        }
    },
    watch: {
        plain_content() {
            this.$emit('input', this.plain_content);
            this.$emit('change', this.plain_content);
        }
    },
    methods: {
        initEditor() {
            if (!this.hasWpEditor) {
                return;
            }

            const formFormats = [];

            this.each(emailFontFamilies, (name, key) => {
                 formFormats.push(key + '=' + name);
            });

            this.editor.remove(this.editor_id);
            const that = this;
            this.editor.initialize(this.editor_id, {
                mediaButtons: true,
                tinymce: {
                    height: that.height,
                    fontsize_formats: '8px 10px 12px 14px 16px 18px 24px 30px 36px 45px',
                    toolbar1: 'formatselect,fontselect,fontsizeselect,customInsertButton,table,bold,italic,bullist,numlist,link,blockquote,alignleft,aligncenter,alignright,underline,strikethrough,forecolor,removeformat,codeformat,outdent,indent,undo,redo',
                    font_formats: formFormats.join('; '),
                    setup(editor) {
                        editor.on('change', function (ed, l) {
                            that.changeContentEvent();
                        });
                        if (!that.buttonInitiated) {
                            that.buttonInitiated = true;
                            editor.addButton('customInsertButton', {
                                text: that.$t('Button'),
                                classes: 'fluentcrm_editor_btn',
                                onclick() {
                                    that.showInsertButtonModal(editor);
                                }
                            });
                        }
                    },
                    formats: {
                        // Changes the alignment buttons to add a class to each of the matching selector elements
                        alignleft: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'align-left', styles: { 'text-align': 'left' } },
                        aligncenter: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'align-center', styles: { 'text-align': 'center' }, attributes: { align: 'center' } },
                        alignright: { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li,table,img', classes: 'align-right', styles: { 'text-align': 'right' }, attributes: { align: 'right' } }
                    },
                    content_style: that.extra_style
                },
                quicktags: true
            });

            jQuery('#' + this.editor_id).on('change', function (e) {
                that.changeContentEvent();
            });
        },
        showInsertButtonModal(editor) {
            this.currentEditor = editor;
            this.showButtonDesigner = true;
        },
        insertHtml(content) {
            this.currentEditor.insertContent(content);
        },
        changeContentEvent() {
            const content = this.editor.getContent(this.editor_id);
            this.$emit('input', content);
            this.$emit('change', content);
        },
        handleCommand(command) {
            if (this.hasWpEditor) {
                window.tinymce.activeEditor.insertContent(command);
            } else {
                var part1 = this.plain_content.slice(0, this.cursorPos);
                var part2 = this.plain_content.slice(this.cursorPos, this.plain_content.length);
                this.plain_content = part1 + command + part2;
                this.cursorPos += command.length;
            }
        },
        updateCursorPos() {
            var cursorPos = jQuery('.wp_vue_editor_plain').prop('selectionStart');
            this.$set(this, 'cursorPos', cursorPos);
        }
    },
    mounted() {
        this.initEditor();
        this.app_ready = true;
    }
}
</script>
<style lang="scss">
.wp_vue_editor {
    width: 100%;
    min-height: 100px;
}

.wp_vue_editor_wrapper {
    position: relative;

    .wp-media-buttons .insert-media {
        vertical-align: middle;
    }

    .popover-wrapper {
        z-index: 2;
        position: absolute;
        top: 0;
        right: 0;

        &-plaintext {
            left: auto;
            right: 0;
            top: -32px;
        }
    }

    .wp-editor-tabs {
        float: left;
    }
}

.mce-fluentcrm_editor_btn {
    button {
        font-size: 10px !important;
        border: 1px solid gray;
        margin-top: 3px;
    }

    &:hover {
        border: 1px solid transparent !important;
    }
}
</style>
