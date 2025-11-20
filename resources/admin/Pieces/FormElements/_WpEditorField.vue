<template>
    <wp-base-editor :height="height" :extra_style="extra_style" :editorShortcodes="smartcodes" v-model="model" />
</template>

<script type="text/babel">

import WpBaseEditor from '../_wp_editor';

export default {
    name: 'WPEditorField',
    props: {
        value: {
            type: String,
            default() {
                return '';
            }
        },
        field: {
            type: Object,
            default() {
                return {}
            }
        },
        extra_style: {
            default() {
                return ''
            }
        },
        height: {
            type: Number,
            default() {
                return 250;
            }
        }
    },
    components: {
        WpBaseEditor
    },
    data() {
        return {
            model: this.value,
            smartcodes: window.fcAdmin.globalSmartCodes
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    mounted() {
        console.log(this.field);
    }
}
</script>
