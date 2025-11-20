<template>
    <div class="fc_raw_body">
        <popover class="popover-wrapper" :data="editorShortcodes"
                 @command="handleCommand"></popover>
        <el-input
            type="textarea"
            :rows="30"
            class="wp_editor_raw_html"
            :placeholder="$t('Raw_Please_PHoyE')"
            @keyup.native="updateCursorPos"
            v-model="content">
        </el-input>
    </div>
</template>

<script type="text/babel">
import popover from '@/Pieces/input-popover-dropdown.vue'

export default {
    name: 'RawtextEditor',
    props: ['value', 'editor_design'],
    components: {
        popover
    },
    data() {
        return {
            content: this.value || '',
            editorShortcodes: window.fcAdmin.globalSmartCodes,
            cursorPos: (this.value) ? this.value.length : 0
        }
    },
    watch: {
        content() {
            this.$emit('input', this.content);
        }
    },
    methods: {
        handleCommand(code) {
            var part1 = this.content.slice(0, this.cursorPos);
            var part2 = this.content.slice(this.cursorPos, this.content.length);
            this.content = part1 + code + part2;
            this.cursorPos += code.length;
        },
        updateCursorPos(event) {
            var cursorPos = jQuery('.wp_editor_raw_html textarea').prop('selectionStart');
            this.$set(this, 'cursorPos', cursorPos);
        }
    },
    mounted() {
        jQuery('.wp_editor_raw_html textarea').on('click', (e) => {
            this.updateCursorPos(e);
        });
    },
    created() {
        if (window.fcAdmin.extendedSmartCodes) {
            this.editorShortcodes = [...this.editorShortcodes, ...window.fcAdmin.extendedSmartCodes];
        }

        if (window.fcrm_funnel_context_codes) {
            this.editorShortcodes = [...this.editorShortcodes, ...window.fcrm_funnel_context_codes];
        }
    }
};
</script>
