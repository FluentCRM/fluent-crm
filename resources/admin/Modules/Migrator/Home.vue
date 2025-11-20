<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Transfer Data From Other CRM')}}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
            </div>
        </div>
        <div style="max-width: 1190px; margin: 0 auto;" v-loading="loading" class="fluentcrm_pad_around">

            <el-steps :active="step" align-center>
                <el-step :title="$t('CRM')" :description="$t('Select Current CRM')"></el-step>
                <el-step :title="$t('Connect')" :description="$t('Connect with your CRM')"></el-step>
                <el-step :title="$t('Map Data')" :description="$t('Map the data')"></el-step>
                <el-step :title="$t('Review')" :description="$t('Review & Import')"></el-step>
            </el-steps>

            <div class="settings-section fluentcrm_databox">
                <div v-if="step == 1" class="fc_crm_selection_step">
                    <div class="fc_step_header">
                        <h3>{{$t('select_current_crm_software')}}</h3>
                        <p>{{$t('transfer_data_from_current_ems_to_fluentcrm')}}</p>
                    </div>
                    <div class="fc_crm_lists">
                        <el-radio-group class="sources fc_inline_image_radio" v-model="selected_driver">
                            <el-radio
                                v-for="(driver, driverName) in drivers"
                                :class="'fc_driver_'+driverName"
                                :key="driverName"
                                :label="driverName"
                                class="option">
                                <img style="width: 80px; height: 80px;" :src="driver.logo"/>
                                <span class="fc_text_label">{{ driver.title }}</span>
                            </el-radio>
                        </el-radio-group>
                    </div>

                    <p v-if="current_driver.doc_url"><a style="text-decoration: underline;" target="_blank" rel="noopener" :href="current_driver.doc_url">{{$t('Check the documentation')}}</a> {{$t('for migrating from')}} <b>{{current_driver.title}}</b></p>

                    <div class="text-align-right">
                        <el-button @click="selectDriver()" :disabled="!selected_driver" type="primary">{{$t('Next')}}</el-button>
                    </div>
                </div>
                <div v-else-if="step == 2" class="fc_crm_selection_step">
                    <credential-verify :driver="selected_driver"
                                       :current_driver="current_driver"
                                       @verified="credentialVerified()"
                                       @back="back()"
                                       :cred="cred"/>
                </div>
                <div v-else-if="step == 3" class="fc_crm_selection_step">
                    <div class="fc_step_header">
                        <h3>{{$t('Map your Data')}}</h3>
                        <p>{{$t('Please configure')}} {{ current_driver.title }} {{$t('associate data with FluentCRM')}}</p>
                    </div>

                    <el-form label-position="top" :data="map_settings">
                        <el-form-item v-if="current_driver.supports.lists" :label="$t('Select List')">
                            <el-select @change="maybeRefetchTags()" v-model="map_settings.list_id">
                                <el-option v-for="list in segment_options.lists" :key="list.id" :value="list.id"
                                           :label="list.name"></el-option>
                            </el-select>
                        </el-form-item>
                        <el-form-item v-else-if="current_driver.supports.has_list_mapper && segment_options.mapped_lists && segment_options.mapped_lists.length" :label="$t('Map List')">
                            <tag-mapper
                                option_key="lists"
                                item_label="List"
                                :driver="selected_driver"
                                :current_driver="current_driver"
                                :tag_options="segment_options.mapped_lists"
                            />
                        </el-form-item>

                        <template v-if="segment_options.all_ready">

                            <div style="margin-bottom: 20px;" v-if="current_driver.supports.auto_tag_mapper">
                                <el-checkbox v-model="segment_options.auto_mapping" true-label="yes">Automatically map tags from {{current_driver.title}} in FluentCRM</el-checkbox>
                            </div>

                            <el-form-item v-if="segment_options.tags.length && segment_options.auto_mapping != 'yes'" :label="$t('Map Tags')">
                                <tag-mapper
                                    option_key="tags"
                                    item_label="Tag"
                                    :driver="selected_driver"
                                    :current_driver="current_driver"
                                    :tag_options="segment_options.tags"
                                />
                            </el-form-item>

                            <el-form-item v-if="segment_options.contact_fields && segment_options.contact_fields.length"
                                          :label="$t('Map Contact Fields')">
                                <contact-field-mapper
                                    :driver="selected_driver"
                                    :contact_fields="segment_options.contact_fields"
                                    :contact_fillables="segment_options.contact_fillables"/>
                            </el-form-item>

                            <p style="margin-bottom: 50px;" v-if="current_driver.field_map_info"
                               v-html="current_driver.field_map_info"></p>

                            <el-row :gutter="30">
                                <el-col :md="12" :sm="24">
                                    <el-form-item :label="$t('Assigned List in FluentCRM (optional)')">
                                        <option-selector v-model="map_settings.local_list_id"
                                                         :field="{ is_multiple: false, creatable: true, option_key: 'lists' }"/>
                                        <p>{{$t('Will be applied to all the imported contacts')}}</p>
                                    </el-form-item>
                                </el-col>
                                <el-col v-if="current_driver.supports.empty_tags" :md="12" :sm="24">
                                    <el-form-item :label="$t('Default Tag ID (optional)')">
                                        <option-selector v-model="map_settings.local_tag_id"
                                                         :field="{ is_multiple: false, creatable: true, option_key: 'tags' }"/>
                                        <p>{{$t('Home.Default_tag_id.instruction')}}
                                            {{ selected_driver }}</p>
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <el-form-item v-if="current_driver.supports.active_imports_only">
                                <el-checkbox true-label="yes" false-label="no"
                                             v-model="map_settings.import_active_only">{{$t('Import only active subscribers from')}} {{ selected_driver }}
                                </el-checkbox>
                            </el-form-item>

                            <div style="margin-top: 20px;" class="text-align-right">
                                <el-button :disabled="loading" @click="step++" type="primary">
                                    {{$t('Continue [Review and Import]')}}
                                </el-button>
                            </div>

                        </template>

                    </el-form>
                </div>

                <div v-else-if="step == 4" class="fc_crm_selection_step">
                    <import-runner
                        @prev="() => { step = 3; }"
                        :driver="selected_driver"
                        :credential="cred"
                        :contact_fields="segment_options.contact_fields"
                        :segment_options="segment_options"
                        :map_settings="map_settings"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import TagMapper from './_TagMapper'
import ContactFieldMapper from './_ContactFieldMapper'
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
import ImportRunner from './_ImportRunner';
import CredentialVerify from './_CredentialVerify';

export default {
    name: 'CRMMigrator',
    components: {
        TagMapper,
        ContactFieldMapper,
        OptionSelector,
        ImportRunner,
        CredentialVerify
    },
    data() {
        return {
            loading: false,
            drivers: {},
            selected_driver: '',
            step: 1,
            cred: {
                api_key: ''
            },
            segment_options: {
                lists: [],
                tags: [],
                contact_fields: []
            },
            map_settings: {
                list_id: '',
                local_list_id: '',
                local_tag_id: '',
                import_silently: 'yes',
                import_active_only: 'yes'
            },
            import_summary: {}
        }
    },
    computed: {
        current_driver() {
            return this.drivers[this.selected_driver] || {};
        }
    },
    methods: {
        getDrivers() {
            this.loading = true;
            this.$get('migrators')
                .then(response => {
                    this.drivers = response.drivers;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        back() {
            this.step -= 1;
        },
        selectDriver() {
            this.cred = JSON.parse(JSON.stringify(this.current_driver.credentials));
            this.step = 2;
        },
        credentialVerified() {
            this.listMappings();
            this.step = 3;
        },
        listMappings() {
            this.loading = true;
            this.$get('migrators/list-tag-mappings', {
                driver: this.selected_driver,
                credential: this.cred,
                map_settings: this.map_settings
            })
                .then((response) => {
                    this.segment_options = response.options;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false
                });
        },
        maybeRefetchTags() {
            if (this.current_driver.refresh_on_list_change) {
                this.listMappings();
            }
        }
    },
    mounted() {
        this.getDrivers();
    }
}
</script>
