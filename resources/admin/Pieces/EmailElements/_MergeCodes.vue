<template>
    <popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" style="display: inline-block;" btnType="text" :buttonText="'{{ }}'" class="popover-wrapper"
             :data="editorShortcodes"
             @command="handleCommand"></popover>
</template>

<script type="text/babel">
import popover from '@/Pieces/input-popover-dropdown.vue'

export default {
    name: 'MergeCodes',
    components: {
        popover
    },
    props: ['extra_tags'],
    data() {
        return {
            editorShortcodes: []
        }
    },
    methods: {
        handleCommand(code) {
            this.copyItem(code);
        },
        copyItem(text) {
            this.copy_success = false;
            let result = false;
            if (window.clipboardData && window.clipboardData.setData) {
                // Internet Explorer-specific code path to prevent textarea being shown while dialog is visible.
                window.clipboardData.clipboardData.setData('Text', text);
                result = true;
            } else if (document.queryCommandSupported && document.queryCommandSupported('copy')) {
                const textarea = document.createElement('textarea');
                textarea.textContent = text;
                textarea.style.position = 'fixed';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    result = true;
                } catch (ex) {
                    console.warn('Copy to clipboard failed.', ex);
                    result = false;
                } finally {
                    document.body.removeChild(textarea);
                }
            }

            if (result) {
                this.copy_success = true;
                this.$notify({
                    message: this.$t('Smartcode has been copied to your clipboard'),
                    position: 'bottom-right',
                    customClass: 'bottom_right fc_notify_z',
                    type: 'success'
                });
            } else {
                this.$notify({
                    message: this.$t('Your Browser does not support JS copy. Please copy manually'),
                    position: 'bottom-right',
                    customClass: 'bottom_right',
                    type: 'error'
                });
            }
        }
    },
    mounted() {
        if (this.extra_tags && this.extra_tags.length) {
            this.editorShortcodes.push(...this.extra_tags);
        }

        if (window.fcAdmin.globalSmartCodes) {
            this.editorShortcodes.push(...window.fcAdmin.globalSmartCodes);
        }

        if (window.fcAdmin.extendedSmartCodes) {
            this.editorShortcodes.push(...window.fcAdmin.extendedSmartCodes);
        }
    }
}
</script>
