<template>
    <div v-if="!loading" class="fluentcrm_databox">
        <div class="fc_external_company_profile_data" :class="'fc_section_'+profile_section" v-html="content_html">

        </div>
    </div>

    <el-skeleton style="margin-top: 20px;" v-else class="fc_skeleton_loader" animated>
        <template slot="template">
            <el-row :gutter="30">
                <el-col :span="12">
                    <el-skeleton :rows="10"/>
                </el-col>
                <el-col :span="12">
                    <el-skeleton :rows="3"/>
                </el-col>
            </el-row>
        </template>
    </el-skeleton>
</template>

<script type="text/babel">

export default {
    name: 'CompanyExternalView',
    props: ['company', 'company_id'],
    data() {
        return {
            profile_section: '',
            heading: '',
            content_html: '',
            loading: true,
            crud: null,
            showForm: false,
            crudConfig: {},
            saving: false
        }
    },
    watch: {
        '$route.query': {
            handler(newQuery) {
                this.profile_section = newQuery.handler;
                this.fetchData();
            },
            deep: true,
            immediate: true
        }
    },
    methods: {
        fetchData() {
            this.loading = true;
            this.$get(`companies/${this.company_id}/custom_tab_view`, {
                section_provider: this.profile_section
            })
                .then(response => {
                    this.heading = response.heading;
                    this.content_html = response.content_html;
                    if (response.crud) {
                        this.crud = response.crud;
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.fetchData();
    }
}
</script>
