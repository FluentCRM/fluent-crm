<template>
    <el-input size="mini" :readonly="true" v-model="text" class="fc-item-copier-input">
        <template #append>
            <el-button
                v-if="showViewButton"
                type="primary"
                class="view-btn"
                @click="openUrl">
                <i class="dashicons dashicons-external"></i>
            </el-button>
            <el-button
                type="primary"
                @click="copyItem"
                :icon="icon"></el-button>
        </template>
    </el-input>
</template>

<script type="text/babel">
export default {
    name: 'ItemCopier',
    props: ['text', 'showViewButton'],
    data() {
        return {
            copy_success: false
        }
    },
    computed: {
        icon() {
            if (!this.copy_success) {
                return 'el-icon-copy-document';
            }
            return 'el-icon-check';
        }
    },
    methods: {
        copyItem() {
            this.copy_success = false;
            const text = this.text;
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
                    message: this.$t('Copied to your clipboard'),
                    position: 'bottom-right',
                    customClass: 'bottom_right',
                    type: 'success'
                });
                setTimeout(() => {
                    this.copy_success = false;
                }, 2000);
            } else {
                this.$notify({
                    message: this.$t('Your Browser does not support JS copy. Please copy manually'),
                    position: 'bottom-right',
                    customClass: 'bottom_right',
                    type: 'error'
                });
            }
        },
        openUrl() {
            if (this.text) {
                window.open(this.text, '_blank');
            }
        }
    }
}
</script>
