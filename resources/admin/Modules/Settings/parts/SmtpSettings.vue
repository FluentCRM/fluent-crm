<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('SMTP/Email Sending Service Settings')}}</h3>
            </div>
        </div>
        <div class="fluentcrm_pad_around">
            <div v-if="!appVars.has_fluentsmtp" class="settings-section fluentcrm_databox fc_smtp_desc">
                <h3>{{$t('SmtpSettings.FluentSMTP.title')}}</h3>
                <hr/>
                <p>{{$t('learn_about_fluentSMTP')}} <a target="_blank" href="https://fluentsmtp.com">{{$t('Learn more about FluentSMTP Plugin.')}}</a></p>
                <el-row :gutter="12">
                    <el-col :span="12">
                        <h3>{{$t('Dedicated API and SMTP connections')}}</h3>
                        <ul>
                            <li>Amazon SES</li>
                            <li>Mailgun</li>
                            <li>SendGrid</li>
                            <li>SendInBlue</li>
                            <li>PepiPost</li>
                            <li>SparkPost</li>
                            <li>{{ $t('+ Any SMTP Provider') }}</li>
                        </ul>
                    </el-col>
                    <el-col :span="12">
                        <h3>{{ $t('Features of Fluent SMTP Plugin') }}</h3>
                        <ul>
                            <li>{{$t('Optimized API connection with Mail Service Providers')}}</li>
                            <li>{{$t('Email Logging for better visibility')}}</li>
                            <li>{{$t('Email Routing based on the sender email address')}}</li>
                            <li>{{$t('Real-Time Email Delivery')}}</li>
                            <li>{{$t('Resend Any Emails')}}</li>
                            <li>{{$t('In Details Reporting')}}</li>
                            <li>{{$t('Super fast UI powered by VueJS')}}</li>
                        </ul>
                    </el-col>
                </el-row>
                <hr />
                <div class="fc_call_action text-align-center">
                    <el-button :disabled="installing" v-loading="installing" @click="installFluentSMTP()" type="success">
                        {{$t('install_fluentSMTP')}}
                    </el-button>
                </div>
            </div>
            <div class="settings-section fluentcrm_databox fc_smtp_desc text-center" v-else-if="fluentsmtp_just_installed">
                <h3>{{$t('FluentSMTP plugin has successfully installed')}}</h3>
                <p>{{$t('fluentSMTP_requires_to_configure_properly')}}</p>
                <a :href="fluentsmtp_config_url" class="button button-primary">{{$t('Configure FluentSMTP')}}</a>
            </div>
            <div v-else-if="fluentsmtp_info" class="settings-section fluentcrm_databox fc_smtp_desc">
                <template v-if="fluentsmtp_info.configured">
                    <h3>{{$t('SmtpSettings.Configured.title')}}</h3>
                    <hr />
                    <h3>{{$t('Verified Email Senders')}}</h3>
                    <ul>
                        <li v-for="sender in fluentsmtp_info.verified_senders" :key="sender">{{sender}}</li>
                    </ul>
                    <a :href="fluentsmtp_info.config_url" class="button">{{$t('Goto FluentSMTP Settings')}}</a>
                </template>
                <template v-else>
                    <h3>{{$t('SmtpSettings.Configured.failed.desc')}}</h3>
                    <a :href="fluentsmtp_info.config_url" class="button button-primary">{{$t('Configure FluentSMTP')}}</a>
                </template>
            </div>
            <div v-if="!fetching" class="settings-section fluentcrm_databox fc_smtp_desc">
                <h2>{{$t('Bounce Handling Settings')}}</h2>
                <hr />
                <h3>{{$t('Select Your Email Service Provider')}}</h3>
                <el-select @change="switchingProvider()" v-model="active_provider_key">
                    <el-option v-for="(service, serviceKey) in bounce_settings" :key="serviceKey" :value="serviceKey" :label="service.label"></el-option>
                </el-select>
                <hr style="margin: 20px 0 15px 0;" />
                <template v-if="!switching">
                    <h2>{{ activeProvider.label }} {{$t('Bounce Handler')}}</h2>
                    <p style="font-size: 14px;">{{$t('If you use')}} <b>{{activeProvider.label}}</b> {{$t('for sending your WordPress emails. This section is for you')}}</p>
                    <p>{{ activeProvider.input_title }}</p>
                    <item-copier :text="activeProvider.webhook_url" />
                    <p v-html="activeProvider.input_info"></p>
                    <p>{{$t('For Step by Step instruction please')}} <a target="_blank" rel="noopener" :href="activeProvider.doc_url">{{$t('follow this tutorial')}}</a></p>
                </template>
                <el-skeleton :rows="8" v-else></el-skeleton>
            </div>
            <el-skeleton v-else class="fluentcrm_databox"></el-skeleton>
        </div>
    </div>
</template>

<script type="text/babel">
import ItemCopier from '@/Pieces/ItemCopier.vue';

export default {
    name: 'SmtpSettings',
    components: {
        ItemCopier
    },
    data() {
        return {
            loading: false,
            fetching: false,
            bounce_settings: {
                ses: {}
            },
            active_provider_key: 'ses',
            installing: false,
            fluentsmtp_info: false,
            fluentsmtp_just_installed: false,
            fluentsmtp_config_url: '#',
            switching: false
        }
    },
    computed: {
        activeProvider() {
            return this.bounce_settings[this.active_provider_key];
        }
    },
    methods: {
        getBounceSettings() {
            this.fetching = true;
            this.$get('setting/bounce_configs')
            .then(response => {
                this.bounce_settings = response.bounce_settings;
                if (response.fluentsmtp_info) {
                    this.fluentsmtp_info = response.fluentsmtp_info;
                }
            })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {
                this.fetching = false;
            })
        },
        installFluentSMTP() {
            this.installing = true;
            this.$post('setting/install-fluentsmtp')
                .then(response => {
                    if (!response.is_installed) {
                        this.$notify.error(this.$t('Plugin_Not_Installed_Alert'));
                        return;
                    }
                    this.$notify.success(response.message);
                    this.appVars.has_fluentsmtp = true;
                    this.appVars.disable_fluentmail_suggest = true;
                    this.fluentsmtp_just_installed = true;
                    this.fluentsmtp_config_url = response.config_url;
                    this.getBounceSettings();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.installing = false;
                });
        },
        switchingProvider() {
            this.switching = true;
            setTimeout(() => {
                this.switching = false;
            }, 300);
        }
    },
    mounted() {
        this.getBounceSettings();
        this.changeTitle(this.$t('Email Service Provider Settings'));
    }
}
</script>
