<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Business Settings')}}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    type="success"
                    size="medium"
                    @click="save"
                    :loading="btnFromLoading || loading"
                >{{$t('Save Settings')}}
                </el-button>
            </div>
        </div>
        <div v-loading="loading" class="fluentcrm_pad_around">
            <!-- Campaign Settings -->
            <div class="settings-section">

                <form-builder v-if="settings_loaded" :formData="settings" :fields="fields" />

            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'BusinessSettings',
    components: {
        FormBuilder
    },
    data() {
        return {
            btnFromLoading: false,
            loading: false,
            settings: {},
            settings_loaded: false,
            fields: {
                business_name: {
                    type: 'input-text',
                    placeholder: this.$t('MyAwesomeBusiness Inc.'),
                    label: this.$t('Business Name'),
                    help: this.$t('business_settings.name_help')
                },
                business_address: {
                    type: 'input-text',
                    placeholder: this.$t('street, state, zip, country'),
                    label: this.$t('Business Full Address'),
                    help: this.$t('business_settings.address_help')
                },
                logo: {
                    type: 'photo-widget',
                    label: this.$t('Logo'),
                    help: this.$t('business_settings.logo_help'),
                    btn_text: this.$t('Upload')
                },
                admin_email: {
                    type: 'input-text',
                    placeholder: this.$t('Admin Email Address'),
                    label: this.$t('Admin Email Addresses (Internal Use only)'),
                    help: this.$t('Admin_Email_Help'),
                    inline_help: this.$t('Admin_Email_Inline_Help')
                }
            }
        };
    },
    methods: {
        save() {
            this.btnFromLoading = true;
            this.$put('setting', {settings: {business_settings: this.settings}})
                .then(r => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: this.$t('Settings Updated.'),
                        offset: 19
                    });
                })
                .catch(error => {
                    this.$notify.error(this.$t(error.message));
                })
                .finally(() => {
                    this.btnFromLoading = false;
                });
        },
        fetchSettings() {
            this.loading = true;
            this.$get('setting', {
                    settings_keys: ['business_settings']
                }
            )
                .then(response => {
                    if (response.business_settings) {
                        this.settings = response.business_settings;
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
        this.changeTitle(this.$t('Business Settings'));
    }
};
</script>

<style>
.settings-section {
    margin-bottom: 30px;
}
</style>
