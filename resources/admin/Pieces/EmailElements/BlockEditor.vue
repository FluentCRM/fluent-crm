<template>
    <div v-if="has_block_editor" id="fluentcrm_block_editor_x" class="fc_block_editor"
         :class="'fc_skin_' + design_template">
        {{ $t('Loading Editor...') }}
    </div>
    <div style="max-width: 800px; margin: 50px auto;" v-else class="fc_classic_editor_fallback">
        <wp-editor :editorShortcodes="editorShortcodes" v-model="content" @change="handleChange"/>
        <p>{{ $t('using_old_wordpress_version') }}</p>
    </div>
</template>

<script type="text/babel">
import WpEditor from '@/Pieces/_wp_editor';

export default {
    name: 'FCBlockEditor',
    props: ['value', 'design_template'],
    components: {
        WpEditor
    },
    data() {
        return {
            content: this.value || '<!-- wp:paragraph --><p>' + this.$t('Start Writing Here') + '</p><!-- /wp:paragraph -->',
            has_block_editor: typeof window.fluentCrmBootEmailEditor == 'function',
            editorShortcodes: window.fcAdmin.globalSmartCodes
        }
    },
    methods: {
        init() {
            if (this.has_block_editor) {
                window.fluentCrmBootEmailEditor(this.content, this.handleChange);
            }
        },
        handleChange(blocks) {
            this.$emit('input', blocks);
            this.$emit('changed');
        }
    },
    mounted() {
        this.init();
        jQuery('.block-editor-block-inspector__no-blocks').html('<div class="text-align-left">' +
            '<b>' + this.$t('Tips') + ':</b>' +
            '<ul><li>- ' + this.$t('Type') + ' <code>/</code> ' + this.$t('to see all the available blocks') + '</li>' +
            '<li>- ' + this.$t('Type') + ' <code>@</code> ' + this.$t('to insert dynamic tags') + '</li>' +
            '<li>- ' + this.$t('Type') + ' <code>[[</code> ' + this.$t('to insert post/page links') + '</li>' +
            '<li>- ' + this.$t('BlockEditor.You_can_Use_Fallback_value') + ' <code>{{contact.first_name|There}}</code></li>' +
            '</ul>' +
            this.$t('Please') + ' <b><a href="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" target="_blank" rel="noopener">' + this.$t('read the doc for advanced usage') + '</a></b>' +
            '</div>'
        );
    }
}
</script>
