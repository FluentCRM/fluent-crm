<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Global Email Settings')}}</h3>
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
            <div class="settings-section fluentcrm_databox">
                <strong style="font-size:15px;">{{$t('Default From Settings')}}</strong><br/>
                {{$t('email_settings_subheading')}}
                <hr style="margin-bottom: 20px; margin-top: 15px;">
                <form-builder v-if="settings_loaded" :formData="settings" :fields="fields_email"/>
            </div>
            <div class="settings-section fluentcrm_databox fc-global-email-fields">
                <strong style="font-size:15px;">{{$t('Email Footer Settings')}}</strong><br/>
                <hr style="margin-bottom: 20px; margin-top: 15px;">
                <form-builder v-if="settings_loaded" :formData="settings" :fields="fields_footer"/>
            </div>
            <div class="settings-section fluentcrm_databox">
                <strong style="font-size:15px;">{{$t('Email Preference Settings')}}</strong><br/>
                {{$t('EmailSettings.Preference.desc')}}
                <hr style="margin-bottom: 20px; margin-top: 15px;">
                <template v-if="settings_loaded">
                    <form-builder :formData="settings" :fields="email_list_pref_fields"/>
                    <pref-short-code :settings="settings" />
                </template>
            </div>
            <div class="text-align-right">
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
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';
import PrefShortCode from './PrefShortCode';

export default {
    name: 'EmailSettings',
    components: {
        FormBuilder,
        PrefShortCode
    },
    data() {
        return {
            btnFromLoading: false,
            loading: false,
            settings: {},
            settings_loaded: false,
            fields_email: {
                from_name: {
                    wrapper_class: 'fc_item_half',
                    type: 'input-text',
                    placeholder: this.$t('Email From Name'),
                    label: this.$t('From Name'),
                    help: this.$t('Name that will be used to send emails')
                },
                from_email: {
                    wrapper_class: 'fc_item_half',
                    type: 'verified-email-input',
                    placeholder: 'name@domain.com',
                    data_type: 'email',
                    label: this.$t('From Email Address'),
                    help: this.$t('Provide Valid Email Address that will be used to send emails'),
                    inline_help: this.$t('verified_email_help'),
                    show_warning: true
                },
                reply_to_name: {
                    wrapper_class: 'fc_item_half',
                    type: 'input-text',
                    placeholder: this.$t('Reply to Name (Optional)'),
                    label: this.$t('Reply to Name'),
                    help: this.$t('Default Reply to Name (Optional)')
                },
                reply_to_email: {
                    wrapper_class: 'fc_item_half',
                    type: 'input-text',
                    placeholder: 'name@domain.com',
                    data_type: 'email',
                    label: this.$t('Reply to Email (Optional)'),
                    help: this.$t('EmailSettings.replyToEmail.help')
                },
                emails_per_second: {
                    wrapper_class: 'fc_item_half_no_float',
                    type: 'input-number',
                    placeholder: this.$t('Maximum Email Limit Per Second'),
                    data_type: 'number',
                    inline_help: 'Minimum Value is: 4',
                    label: this.$t('Maximum Email Limit Per Second'),
                    help: this.$t('set_maximum_emails_will_be_sent_per_second'),
                    min: 4,
                    max: 100
                }
            },
            fields_footer: {
                email_footer: {
                    type: 'wp-editor',
                    placeholder: this.$t('Email Footer Text'),
                    label: this.$t('Email Footer Text'),
                    help: this.$t('This email footer text will be used to all your email'),
                    inline_help: 'Business Name, Business Address, and Unsubscribe URL are mandatory to comply with the CAN-SPAM Act Guidelines. Without these, your emails may end up in the spam folder. It is also ideal to have the Manage Email Subscriptions URL in the email footer. These are added here by default.<ul><li>Use smartcodes: <b>{{crm.business_name}}</b> to fetch Your Business Name and <b>{{crm.business_address}}</b> to fetch Your Business Address anywhere inside your email.</li><li>Use <b>##crm.manage_subscription_url##, ##crm.unsubscribe_url##</b> shortcode to fetch the Unsubscribe URL and Manage Email Subscriptions URL.</li></ul> It is recommended to keep the texts as default aligned. Your provided email design template will align the texts.'
                },
                unsubscribe_redirect: {
                    type: 'input-text',
                    placeholder: this.$t('URL after redirect'),
                    data_type: 'url',
                    label: this.$t('EmailSettings.unsub_redirect_label'),
                    help: this.$t('EmailSettings.unsub_redirect_help')
                }
            },
            email_list_pref_fields: {
                pref_list_type: {
                    type: 'input-radio',
                    wrapper_class: 'fluentcrm_line_items',
                    label: this.$t('Can a subscriber manage list subscriptions?'),
                    options: [
                        {
                            id: 'no',
                            label: this.$t('No, Contact can not manage list subscriptions')
                        },
                        {
                            id: 'filtered_only',
                            label: this.$t('Contact only see and manage the following list subscriptions')
                        },
                        {
                            id: 'all',
                            label: this.$t('Contact can see all lists and manage subscriptions')
                        }
                    ]
                },
                pref_list_items: {
                    type: 'option-selector',
                    label: this.$t('Select Lists that you want to show for contacts'),
                    option_key: 'lists',
                    is_multiple: true,
                    dependency: {
                        depends_on: 'pref_list_type',
                        operator: '=',
                        value: 'filtered_only'
                    }
                }
            }
        };
    },
    methods: {
        save() {
            this.btnFromLoading = true;
            this.$put('setting', {settings: {email_settings: this.settings}})
                .then(r => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: this.$t('Settings Updated.'),
                        offset: 19
                    });
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.btnFromLoading = false;
                });
        },
        fetchSettings() {
            this.loading = true;
            this.$get('setting', {settings_keys: ['email_settings']})
                .then(response => {
                    if (response.email_settings) {
                        this.settings = response.email_settings;
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
        this.changeTitle(this.$t('Email Settings'));
    }
}
</script>

<style>
.settings-section {
    margin-bottom: 30px;
}
</style>
