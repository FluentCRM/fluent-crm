<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3 v-if="!listName">{{$t('Double Opt-in Email Settings Details')}}</h3>
                <h3 v-else>{{$t('Double Opt-in Email Settings')}} <br><span >({{listName}})</span></h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    type="success"
                    size="medium"
                    @click="save"
                    :loading="btnFromLoading || loading"
                >
                    {{$t('Save Settings')}}
                </el-button>
            </div>
        </div>
        <div v-loading="loading" class="fluentcrm_pad_around">
            <!-- Campaign Settings -->
            <div class="settings-section">

              <template v-if="listId">
                <el-radio-group v-model="global_double_optin" class="mb-10" style="margin-bottom: 10px;">
                    <el-radio label="yes">
                        {{$t('Use Global Double Opt-in Settings')}}
                    </el-radio>
                    <el-radio label="no">{{$t('Use List Specific Double Opt-in Settings')}}</el-radio>
                </el-radio-group>
                
                <div style="margin-bottom: 20px;">
                    <p><strong>{{$t('Global Double Opt-in Settings')}}:</strong> {{$t('When selected, this list will adhere to the global double opt-in configuration.')}}</p>
                    <p><strong>{{$t('List-Specific Double Opt-in Settings')}}:</strong> {{$t('When selected, subscribers added to this list will follow the list-specific double opt-in configuration. If no list-specific settings are configured, the global double opt-in settings will apply.')}}</p>
                </div>

                <hr style="margin-bottom: 25px;">

                <template v-if="global_double_optin == 'no'">
                  <form-builder v-if="settings_loaded" :fields="fields" :formData="settings"></form-builder>
                </template>
              </template>
              <template v-else>
                <form-builder v-if="settings_loaded" :fields="fields" :formData="settings"></form-builder>
              </template>
            </div>
            <el-button
                type="success"
                size="medium"
                @click="save"
                :loading="btnFromLoading || loading"
            >
                {{$t('Save Settings')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'DoubleOptionIn',
    props: {
        listId: {
            type: Number,
            default: null
        },
        listName: {
            type: String,
            default: ''
        },
        drawerKey: {
            type: Number,
            default: 0
        }
    },
    components: {
        FormBuilder
    },
    watch: {
        drawerKey(newValue) {
            if (this.listId) {
                this.fetchSettings();
                this.changeTitle(this.$t('Double Opt-in Settings'));
            }
        }
    },
    data() {
        return {
            btnFromLoading: false,
            loading: false,
            settings: {},
            settings_loaded: false,
            fields: {},
            global_double_optin: 'yes'
        };
    },
    methods: {
        save() {
          const query = {
              settings: this.settings,
              list_id: this.listId
          };
          if (this.listId) {
              query.global_double_optin = this.global_double_optin
          }
            this.btnFromLoading = true;
            this.$put('setting/double-optin', query)
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.btnFromLoading = false;
                    this.settings_loaded = true;
                });
        },
        fetchSettings() {
            this.loading = true;
            this.settings = {};
            this.fields = {};
            this.$get('setting/double-optin', {with: ['settings_fields'], list_id: this.listId})
                .then(response => {
                    this.settings = response.settings;
                    this.fields = response.settings_fields;
                    this.global_double_optin = response.global_double_optin ? response.global_double_optin : 'yes';

                    // if we have list id then we will skip tag based redirection
                    if (this.listId) {
                        const skipKeys = ['tag_based_redirect'];

                        this.fields = Object.entries(this.fields).reduce((acc, [key, value]) => {
                            if (!skipKeys.includes(key)) {
                                acc[key] = value;
                            }
                            return acc;
                        }, {});
                    }
                })
                .catch((error) => {
                    console.log(error);
                })
                .finally(() => {
                    this.loading = false;
                    this.settings_loaded = true;
                });
        }
    },
    mounted() {
        this.fetchSettings();
        this.changeTitle(this.$t('Double Opt-in Settings'));
    }
}
</script>

<style>
.settings-section {
    margin-bottom: 30px;
}
</style>
