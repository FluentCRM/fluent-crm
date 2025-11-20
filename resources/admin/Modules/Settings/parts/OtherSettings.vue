<template>
    <div v-loading="fetching" class="fluentcrm-settings fc-general-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('General Settings')}}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button v-loading="loading" type="success" v-if="app_ready" @click="saveSettings()" size="medium">
                    {{$t('Save Settings')}}
                </el-button>
            </div>
        </div>
        <div v-if="app_ready" class="fluentcrm_pad_around">
            <div class="settings-section fluentcrm_databox" style="padding-bottom: 15px;">
                <h2>{{ user_syncing_fields.title }}</h2>
                <p>{{ user_syncing_fields.sub_title }}</p>
                <hr/>
                <form-builder class="mt-20" :formData="user_syncing_settings" :fields="user_syncing_fields.fields"></form-builder>
            </div>

            <div class="settings-section fluentcrm_databox">
                <h2>{{ registration_fields.title }}</h2>
                <p>{{ registration_fields.sub_title }}</p>
                <hr/>
                <form-builder class="mt-20" :formData="registration_setting" :fields="registration_fields.fields"></form-builder>
            </div>

            <div class="settings-section fluentcrm_databox" style="padding-bottom: 15px;">
                <h2>{{ role_based_tagging_settings_fields.title }}</h2>
                <p>{{ role_based_tagging_settings_fields.sub_title }}</p>
                <hr/>
                <p style="color: red;" v-if="!has_campaign_pro && role_based_tagging_settings.status == 'yes'">{{$t('This feature only available on FluentCRM Pro.')}} <a target="_blank" rel="noopener" href="https://fluentcrm.com?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp">{{$t('Please purchase Pro')}}</a> {{$t('to enable this feature')}}</p>
                <form-builder class="mt-20" :formData="role_based_tagging_settings" :fields="role_based_tagging_settings_fields.fields"></form-builder>
            </div>

            <div class="settings-section fluentcrm_databox" style="padding-bottom: 15px;">
                <h2>{{ comment_fields.title }}</h2>
                <p>{{ comment_fields.sub_title }}</p>
                <hr/>
                <form-builder class="mt-20" :formData="comment_settings" :fields="comment_fields.fields"></form-builder>
            </div>

            <div v-if="woo_checkout_fields" class="settings-section fluentcrm_databox" style="padding-bottom: 15px">
                <h2>{{ woo_checkout_fields.title }}</h2>
                <p>{{ woo_checkout_fields.sub_title }}</p>
                <p style="color: red;" v-if="!has_campaign_pro">{{$t('This feature only available on FluentCRM Pro.')}} <a target="_blank" rel="noopener" href="https://fluentcrm.com?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp">{{$t('Please purchase Pro')}}</a> {{$t('to enable this feature')}}</p>
                <hr/>
                <form-builder class="mt-20" :formData="woo_checkout_settings" :fields="woo_checkout_fields.fields"></form-builder>
            </div>

            <el-button v-loading="loading" type="success" v-if="app_ready" @click="saveSettings()" size="medium">
                {{$t('Save Settings')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">

import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'General-Settings',
    components: {
        FormBuilder
    },
    data() {
        return {
            loading: false,
            fetching: false,
            user_syncing: {},
            user_syncing_fields: {},
            registration_setting: {},
            registration_fields: {},
            role_based_tagging_settings: {},
            role_based_tagging_settings_fields: {},
            user_syncing_settings: {},
            comment_settings: {},
            comment_fields: {},
            woo_checkout_fields: false,
            woo_checkout_settings: {},
            app_ready: false
        }
    },
    methods: {
        getSettings() {
            this.fetching = true;
            this.$get('setting/auto_subscribe_settings', {with: ['fields']})
                .then((response) => {
                    this.user_syncing_settings = response.user_syncing_settings;
                    this.user_syncing_fields = response.user_syncing_fields;
                    this.registration_setting = response.registration_setting;
                    this.registration_fields = response.registration_fields;
                    this.comment_settings = response.comment_settings;
                    this.comment_fields = response.comment_fields;

                    this.role_based_tagging_settings = response.role_based_tagging_settings;
                    this.role_based_tagging_settings_fields = response.role_based_tagging_settings_fields;

                    if (response.woo_checkout_fields) {
                        this.woo_checkout_fields = response.woo_checkout_fields;
                        this.woo_checkout_settings = response.woo_checkout_settings;
                    }

                    this.app_ready = true;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                });
        },
        saveSettings() {
            this.loading = true;
            this.$post('setting/auto_subscribe_settings', {
                registration_setting: this.registration_setting,
                comment_settings: this.comment_settings,
                user_syncing_settings: this.user_syncing_settings,
                woo_checkout_settings: this.woo_checkout_settings,
                role_based_tagging_settings: this.role_based_tagging_settings
            })
                .then(response => {
                    this.$notify.success(response.message);
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
        this.getSettings();
        this.changeTitle(this.$t('General Settings'));
    }
}
</script>
