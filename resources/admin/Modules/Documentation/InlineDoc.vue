<template>
    <div class="fc_inline_doc" style="display: inline-block;">
        <el-button title="View related documentation" size="mini" type="text" plain @click="showDoc()"><span class="dashicons dashicons-welcome-learn-more"></span></el-button>
        <el-drawer
            v-if="show_doc"
            :title="doc.title"
            :visible.sync="show_doc"
            :append-to-body="true"
            :size="docSize"
            direction="rtl">
            <div v-if="!loading" class="doc_read" v-html="doc.content" />
            <div class="doc_read" v-else>
                <el-skeleton :animated="true" :rows="10" />
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'InlineDoc',
    props: ['doc_id'],
    data() {
        return {
            loading: false,
            doc: {},
            show_doc: false,
            docSize: '700px',
            isLoaded: false
        }
    },
    methods: {
        showDoc() {
            this.show_doc = true;
            if (!this.isLoaded) {
                this.getDoc();
            }
        },
        getDoc() {
            this.loading = true;
            this.$get('docs/' + this.doc_id)
                .then(response => {
                    this.doc = response;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                    this.isLoaded = true;
                });
        }
    },
    mounted() {
        if (window.outerWidth < 701) {
            this.docSize = (window.outerWidth - 50) + 'px';
        }
    }
}
</script>
