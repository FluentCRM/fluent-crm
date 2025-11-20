<template>
    <div v-loading="loading" class="fc_integration_settings">
        <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
            <template v-if="selected_integration">
                <div class="fluentcrm_header">
                    <ul class="fc_settings_sub_menu">
                        <li v-for="(integration, integrationKey) in integrations"
                            @click="switchProvider(integrationKey)"
                            :class="{fc_active: integrationKey == selected_integration}" :key="integrationKey">
                            {{ integration.title }}
                        </li>
                    </ul>
                </div>
                <div v-loading="loading" class="fluentcrm_pad_around">
                    <div class="fc_integration" v-if="currentIntegration">
                        <p style="line-height: 140%">{{ currentIntegration.sub_title }}</p>

                        <div v-if="!switching" class="fc_integration_sync fluentcrm_databox">
                            <div v-if="!currentIntegration.settings.is_enabled">
                                <h2>{{ currentIntegration.sync_title }}</h2>
                                <p>{{ currentIntegration.sync_desc }}</p>
                            </div>
                            <el-form v-loading="syncing_data" v-if="!syncing_status" label-position="top"
                                     :data="currentIntegration.settings">
                                <el-form-item :label="$t('Default List to Contact (Optional)')">
                                    <option-selector v-model="currentIntegration.settings.lists"
                                                     :field="{ option_key: 'lists', creatable: true, is_multiple: true }"></option-selector>
                                </el-form-item>
                                <el-form-item :label="$t('Default Tag for Contact (Optional)')">
                                    <option-selector v-model="currentIntegration.settings.tags"
                                                     :field="{ option_key: 'tags', creatable: true, is_multiple: true }"></option-selector>
                                </el-form-item>
                                <el-form-item :label="$t('Default contact status (for new contacts)')">
                                    <option-selector v-model="currentIntegration.settings.contact_status"
                                                     :field="{ option_key: 'editable_statuses' }"></option-selector>
                                    <p v-if="currentIntegration.settings.contact_status == 'pending'">
                                        {{ $t('IntegrationSettings.send_double_optin') }}</p>
                                </el-form-item>

                                <div v-if="!currentIntegration.settings.is_enabled">
                                    <el-button @click="syncData(true)" type="primary">
                                        {{ currentIntegration.sync_button }}
                                    </el-button>
                                    <p>
                                        {{ $t('IntegrationSettings.sync_desc') }}
                                        {{ currentIntegration.title }}</p>
                                </div>
                                <div v-else>
                                    <el-button @click="saveSettings()" type="success">{{ $t('Save Settings') }}
                                    </el-button>
                                    <p style="margin-top: 30px;">or <span
                                        style="cursor:pointer; text-decoration: underline;"
                                        @click="syncData(true)">{{ $t('click here') }}</span>
                                        {{ $t('to re-sync the data again') }}</p>
                                    <p style="margin-top: 10px;">{{ $t('If you want to disable auto-syncing') }} <span
                                        style="cursor:pointer; text-decoration: underline;"
                                        @click="disableSync()">{{ $t('click here') }}</span></p>
                                </div>
                                <p>{{ $t('WP_CLI_Help') }} <a target="_blank" href="https://fluentcrm.com/docs/wp-cli-commands/">{{ $t('Read CLI Documentation') }}</a></p>
                            </el-form>
                            <div class="text-align-center" v-else>
                                <template v-if="syncing_status.has_more">
                                    <template v-if="syncing_error">
                                        <h3>{{$t('Looks like there had a problem to sync the data')}}</h3>
                                        <el-button @click="syncData(false)" type="success">
                                            {{ $t('Resume') }}
                                        </el-button>
                                        <h5>{{$t('Error Details')}}</h5>
                                        <pre>{{ syncing_error }}</pre>
                                    </template>
                                    <template v-else>
                                        <h3>{{ $t('Importing now...') }}</h3>
                                        <h4>{{ $t('IntegrationSettings.importing_desc') }}</h4>
                                        <h2>{{ syncing_status.current_page }}/{{ syncing_status.page_total }}</h2>
                                        <el-progress :text-inside="true" :stroke-width="24"
                                                     :percentage="parseInt((syncing_status.current_page / syncing_status.page_total) * 100)"
                                                     status="success"></el-progress>
                                        <p v-loading="true">{{$t('Syncing...')}}</p>
                                    </template>
                                </template>
                                <div v-else>
                                    <h3>{{ $t('IntegrationSettings.data_sync_completed') }}</h3>
                                    <el-button @click="reloadPage()" type="success">{{ $t('Reload Page') }}</el-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div class="fc_integrations_list" v-else-if="!loading">
                <div style="padding: 0px 15px" class="fluentcrm_header">
                    <h3>{{ $t('Integrations') }}</h3>
                </div>
                <div class="fluentcrm_pad_around">
                    <p>{{$t('fluentcrtm_integrations_with_all_of_plugins')}} <a
                            rel="noopener noffollow" href="https://fluentcrm.com?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp">{{$t('pro version of FluentCRM')}}</a></p>

                    <h4>{{ $t('ECOMMERCE INTEGRATION:') }}</h4>
                    <ul>
                        <li>
                            <b>{{ $t('WooCommerce Integration:') }}</b> {{$t('IntegrationSettings.woo_desc')}}
                        </li>
                        <li><b>{{ $t('EDD Integration:') }}</b> {{ $t('edd_integration_instruction') }}
                        </li>
                    </ul>

                    <h4>{{ $t('LMS INTEGRATION:') }}</h4>
                    <ul>
                        <li>
                            <b>{{ $t('LifterLMS:') }}</b> {{ $t('lifterlms_integration_instruction') }}
                        </li>
                        <li>
                            <b>{{ $t("LearnDash Integration:") }}</b> {{ $t('learndash_integration_instruction') }}
                        </li>
                        <li>
                            <b>{{ $t('TutorLMS Integration:') }}</b> {{ $t('tutorlms_integration_instruction') }}
                        </li>
                    </ul>

                    <h4>{{ $t('MEMBERSHIP INTEGRATION:') }}</h4>
                    <ul>
                        <li>{{ $t('Paid Membership Pro integration.') }}</li>
                        <li>{{ $t('MemberPress.') }}</li>
                        <li>{{ $t('Wishlist Members.') }}</li>
                        <li>{{ $t('Restrict Content Pro Integration') }}</li>
                        <li>{{ $t('WPFusion Integration') }}</li>
                    </ul>

                    <h4>{{ $t('Form INTEGRATION:') }}</h4>
                    <ul>
                        <li>{{ $t('Fluent Forms Integration') }}</li>
                        <li>{{ $t('Elementor Pro Form Integration') }}</li>
                        <li>{{ $t('Divi Bloom Integration') }}</li>
                        <li>{{ $t('ThriveArchitect') }}</li>
                    </ul>

                    <h4>{{ $t('Page Builder INTEGRATIONS:') }}</h4>
                    <ul>
                        <li>{{ $t('ThriveThemes integration') }}</li>
                        <li>{{ $t('Divi Themes') }}</li>
                        <li>{{ $t('Elementor Page Builder') }}</li>
                        <li>{{ $t('Gutenberg / WordPress Editor Conditional Blocks') }}</li>
                        <li>{{ $t('Oxygen Builder Integration') }}</li>
                    </ul>

                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import OptionSelector from '../../../Pieces/FormElements/_OptionSelector';

export default {
    name: 'IntegrationSettings',
    components: {
        OptionSelector
    },
    data() {
        return {
            loading: false,
            integrations: {},
            selected_integration: '',
            syncing_data: false,
            syncing_status: false,
            syncing_page: 1,
            last_sync_id: 0,
            saving: false,
            switching: false,
            syncing_error: false
        }
    },
    computed: {
        currentIntegration() {
            if (this.isEmptyValue(this.integrations) || !this.selected_integration) {
                return false
            }
            return this.integrations[this.selected_integration];
        }
    },
    methods: {
        fetchIntegrations() {
            this.loading = true;
            this.$get('setting/integrations', {
                with: ['fields']
            })
                .then(response => {
                    this.integrations = response.integrations;

                    if (!this.isEmptyValue(this.integrations)) {
                        if (this.$route.query.selected_integration && this.integrations[this.$route.query.selected_integration]) {
                            this.selected_integration = this.$route.query.selected_integration;
                        } else {
                            this.selected_integration = Object.keys(this.integrations)[0];
                        }
                    }
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        syncData(isNew = false) {
            if (isNew) {
                this.syncing_page = 1;
            }

            const formattedData = {
                tags: this.currentIntegration.settings.tags,
                lists: this.currentIntegration.settings.lists,
                contact_status: this.currentIntegration.settings.contact_status,
                provider: this.selected_integration,
                action: 'sync',
                syncing_page: this.syncing_page,
                last_sync_id: this.last_sync_id
            };

            this.syncing_data = true;
            this.syncing_error = false;

            this.$post('setting/integrations', formattedData)
                .then(response => {
                    if (response && response.syncing_status) {
                        this.syncing_status = response.syncing_status;
                        if (this.syncing_status.has_more) {
                            this.syncing_page += 1;
                            this.last_sync_id = this.syncing_status.last_sync_id;
                            this.syncData(false);
                        } else {
                            this.syncing_data = false;
                        }
                    } else {
                        console.log(response);
                        if (!response) {
                            response = this.$t('Something went wrong, when syncing data');
                        }
                        this.syncing_error = response;
                    }
                })
                .catch(errors => {
                    console.log(errors);
                    if (!errors) {
                        errors = this.$t('Something went wrong!');
                    }
                    this.syncing_error = errors;
                    this.handleError(errors);
                })
                .finally(() => {

                });
        },
        saveSettings() {
            const formattedData = {
                tags: this.currentIntegration.settings.tags,
                lists: this.currentIntegration.settings.lists,
                contact_status: this.currentIntegration.settings.contact_status,
                provider: this.selected_integration,
                action: 'save'
            };

            this.saving = true;
            this.$post('setting/integrations', formattedData)
                .then(response => {
                    this.$notify.success(response.message);
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        reloadPage() {
            window.location.reload(true);
        },
        switchProvider(provider) {
            if (this.syncing_data) {
                return;
            }

            this.switching = true;

            this.$nextTick(() => {
                this.selected_integration = provider;
                this.$router.push({
                    name: 'integration_settings',
                    query: {
                        selected_integration: provider
                    }
                });
                this.switching = false;
                this.syncing_error = '';
            });
        },
        disableSync() {
            const formattedData = {
                tags: this.currentIntegration.settings.tags,
                lists: this.currentIntegration.settings.lists,
                contact_status: this.currentIntegration.settings.contact_status,
                provider: this.selected_integration,
                action: 'disable'
            };

            this.saving = true;
            this.$post('setting/integrations', formattedData)
                .then(response => {
                    this.fetchIntegrations();
                    this.$notify.success(response.message);
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    },
    mounted() {
        this.fetchIntegrations();
    }
}
</script>
