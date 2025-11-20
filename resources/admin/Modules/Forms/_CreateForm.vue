<template>
    <div v-loading="fetching" class="fc_create_form_wrapper">
        <div style="padding: 20px;" class="fc_created_form text-align-center" v-if="created_form">
            <h3>{{ $t('_Cr_Your_fhbcs') }}</h3>
            <p>{{ $t('CreateForm.desc') }}</p>
            <code>{{ created_form.shortcode }}</code>
            <hr style="margin: 12px 0;"/>
            <ul class="fc_links_inline d-flex items-center justify-center">
                <li><a class="el-button el-button--primary el-button--small" target="_blank" :href="created_form.preview_url">{{ $t('Preview The Form') }}</a></li>
                <li class="ml-5"><a class="el-button el-button--info el-button--small" target="_blank" :href="created_form.edit_url">{{ $t('Edit The Form') }}</a></li>
                <li class="ml-5"><a class="el-button el-button--info el-button--small" target="_blank" :href="created_form.feed_url">{{ $t('Edit Connection') }}</a></li>
            </ul>
        </div>
        <template v-else>
            <div style="padding: 0 20px 40px;" v-if="active_step == 'template_selection'"
                 class="fc_select_template_wrapper">
                <h3>{{ $t('Select a template') }}</h3>
                <el-radio-group @change="changeToStep('config')" v-model="form.template_id">
                    <el-radio v-for="(item,itemIndex) in templates" :key="itemIndex" :label="item.id">
                        <el-tooltip :content="item.label" placement="bottom">
                            <div>
                                <img style="width: 200px; height: 168px;" :src="item.image"/>
                            </div>
                        </el-tooltip>
                    </el-radio>
                </el-radio-group>
            </div>
            <template v-else-if="active_step == 'config'">
                <div style="padding: 40px 20px 10px 20px;" class="fc_config_template">
                    <el-form :data="form" label-position="top">
                        <el-form-item :label="$t('Form Title')">
                            <el-input type="text" v-model="form.title"
                                      :placeholder="$t('Please Provide a Form Title')"/>
                        </el-form-item>
                        <el-form-item :label="$t('Add to List')">
                            <option-selector v-model="form.selected_list"
                                             :field="{ option_key: 'lists', placeholder: $t('Select a List') }"></option-selector>
                        </el-form-item>
                        <el-form-item :label="$t('Add to Tags')">
                            <option-selector v-model="form.selected_tags"
                                             :field="{ option_key: 'tags', creatable: true, placeholder: $t('Select Tags'), is_multiple: true }"></option-selector>
                        </el-form-item>
                        <el-form-item :label="$t('Double Opt-In')">
                            <el-checkbox v-model="form.double_optin">{{ $t('_Cr_Enable_DOC') }}</el-checkbox>
                        </el-form-item>
                    </el-form>
                </div>
                <span slot="footer" class="dialog-footer">

                    <div class="fc_drawer_footer_wrap">
                        <p style="text-align: left;margin-top: 0px">{{ $t('_Cr_This_fwbciFFaycc') }}</p>
                        <el-button v-loading="creating" size="small" type="primary"
                                   @click="create()">{{ $t('Create Form') }}</el-button>
                    </div>
            </span>
            </template>
        </template>
    </div>
</template>

<script type="text/babel">
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';

export default {
    name: 'CreateForm',
    components: {
        OptionSelector
    },
    data() {
        return {
            active_step: 'template_selection',
            templates: [],
            fetching: false,
            form: {
                template_id: '',
                title: '',
                selected_tags: [],
                selected_list: '',
                double_optin: true
            },
            created_form: false,
            creating: false
        }
    },
    methods: {
        create() {
            if (!this.form.template_id || !this.form.title || !this.form.selected_list) {
                return this.$notify.error(this.$t('_Cr_Please_fuatf'));
            }

            this.creating = true;
            this.$post('forms', this.form)
                .then(response => {
                    this.$notify.success(response.message);
                    this.created_form = response.created_form;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.creating = false;
                });
        },
        changeToStep(name) {
            this.active_step = name;
        },
        fetchFormTemplates() {
            this.fetching = true;
            this.$get('forms/templates')
                .then(response => {
                    this.templates = response.templates;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                });
        }
    },
    mounted() {
        this.fetchFormTemplates();
    }
}
</script>
