<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Compliance Settings')}}</h3>
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
                anonymize_ip: {
                    type: 'inline-checkbox',
                    checkbox_label: this.$t('Anonymize ip Address for associate contact data'),
                    true_label: 'yes',
                    false_label: 'no',
                    wrapper_class: 'fc_mb_0'
                },
                delete_contact_on_user: {
                    type: 'inline-checkbox',
                    checkbox_label: this.$t('Delete connected contact when a user get deleted'),
                    true_label: 'yes',
                    false_label: 'no',
                    wrapper_class: 'fc_mb_0'
                },
                personal_data_export: {
                    type: 'inline-checkbox',
                    checkbox_label: this.$t('Include Contact Info in Personal Data export by WP'),
                    true_label: 'yes',
                    false_label: 'no',
                    wrapper_class: 'fc_mb_0'
                },
                one_click_unsubscribe: {
                    type: 'inline-checkbox',
                    checkbox_label: this.$t('Enable one-click unsubscribe (if enabled then no feedback will be asked)'),
                    true_label: 'yes',
                    false_label: 'no',
                    wrapper_class: 'fc_mb_0'
                },
                enable_gravatar: {
                    type: 'inline-checkbox',
                    checkbox_label: this.$t('Enable Gravatar Photo Service for Contact Photo'),
                    true_label: 'yes',
                    false_label: 'no',
                    wrapper_class: 'fc_mb_0'
                },
                gravatar_fallback: {
                    type: 'inline-checkbox',
                    checkbox_label: this.$t('Gravatar Fallback (ui-avatars.com)'),
                    true_label: 'yes',
                    false_label: 'no',
                    wrapper_class: 'fc_mb_0 pl-24',
                    dependency: {
                        depends_on: 'enable_gravatar',
                        value: 'yes',
                        operator: '='
                    }
                }
            }
        };
    },
    watch: {
        // 'fields.enable_gravatar.true_label'(oldVal, newVal) {
        //     console.log('enable_gravatar', oldVal, newVal)
        // }
    },
    methods: {
        save() {
            this.btnFromLoading = true;
            this.$post('setting/compliance', {
                ...this.settings
            })
                .then(r => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: this.$t('Settings Updated.'),
                        offset: 19
                    });
                })
                .finally(() => {
                    this.btnFromLoading = false;
                });
        },
        fetchSettings() {
            this.loading = true;
            this.$get('setting/compliance')
                .then(response => {
                    this.settings = response.settings;
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
        this.changeTitle(this.$t('Compliance Settings'));
    }
};
</script>
