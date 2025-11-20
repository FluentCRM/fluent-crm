<template>
    <div v-if="!loading" class="fluentcrm_databox">
        <div class="fc_card_header">
            <h3 style="margin-top: 0;">{{ heading }}</h3>

            <div v-if="crud && crud.btn_label" class="fluentcrm-actions">
                <el-button @click="initCrudForm()" type="primary" size="mini">
                    {{ crud.btn_label }}
                </el-button>
            </div>

        </div>

        <div class="fc_external_profile_data" :class="'fc_section_'+profile_section" v-html="content_html">
        </div>

        <div v-if="crud && crud.btn_label">
            <el-drawer
                class="fc_company_info_drawer"
                :with-header="true"
                :size="globalDrawerSize"
                :title="crud.form_heading"
                :append-to-body="true"
                :visible.sync="showForm">
                <div v-if="showForm" style="padding: 10px 15px;">
                    <form-builder :formData="crudConfig.model" :fields="crud.fields"/>

                    <div class="fc_drawer_footer">
                        <el-button type="primary" @click="saveData()">
                            {{ crud.save_btn_text || 'Save' }}
                        </el-button>
                    </div>
                </div>
            </el-drawer>
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
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'SubscriberProfileExternalView',
    props: ['subscriber', 'subscriber_id'],
    components: {
        FormBuilder
    },
    data() {
        return {
            profile_section: this.$route.query.handler,
            heading: '',
            content_html: '',
            loading: true,
            crud: null,
            showForm: false,
            crudConfig: {},
            saving: false
        }
    },
    methods: {
        fetchData() {
            this.loading = true;
            this.$get(`subscribers/${this.subscriber_id}/external_view`, {
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
        },
        initCrudForm() {
            if (!this.crud.fields) {
                return;
            }

            const defaultValues = {};
            this.each(this.crud.fields, (field, fieldName) => {
                defaultValues[fieldName] = field.default_value || '';
            });

            this.crudConfig.model = defaultValues;

            this.showForm = true;
        },
        saveData() {
            this.saving = true;
            this.$post(`subscribers/${this.subscriber_id}/external_view`, {
                section_provider: this.profile_section,
                data: this.crudConfig.model
            })
                .then(response => {
                    this.$notify.success(response.message || 'Saved successfully');
                    this.showForm = false;
                    if (response.content_html) {
                        this.content_html = response.content_html;
                        if (response.heading) {
                            this.heading = response.heading;
                        }
                    } else {
                        this.fetchData();
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    },
    mounted() {
        this.fetchData();
    }
}
</script>
