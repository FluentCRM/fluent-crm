<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Tools')}}</h3>
            </div>
        </div>
        <div class="fluentcrm_pad_around">
            <div class="settings-section fluentcrm_databox">
                <div style="background-color: white; padding: 0 0 10px 0; margin-bottom: 20px" class="fluentcrm_header">
                    <div class="fluentcrm_header_title">
                        <h3>{{$t('Rest API Status')}}</h3>
                    </div>
                </div>
                <div class="fc_global_form_builder">
                    <ul>
                        <li v-for="(restStatus,StatusKey) in rest_statuses" :key="StatusKey">
                            <b>{{ StatusKey }}</b>: {{ restStatus }}
                        </li>
                    </ul>
                </div>
            </div>

            <div v-loading="fetching_cron" class="settings-section fluentcrm_databox">
                <div style="background-color: white; padding: 0 0 10px 0; margin-bottom: 20px" class="fluentcrm_header">
                    <div class="fluentcrm_header_title">
                        <h3>{{$t('CRON Job Status')}}</h3>
                    </div>
                    <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                        <el-button size="mini" icon="el-icon-refresh" @click="fetchCronEvents"></el-button>
                    </div>
                </div>

                <div class="fc_global_form_builder">
                    <el-table :empty-text="$t('No Data Found')" :data="cron_events" border stripe>
                        <el-table-column :label="$t('Event Name')" prop="human_name"></el-table-column>
                        <el-table-column :label="$t('Next Run')" prop="next_run">
                            <template slot-scope="scope">
                                <span style="color: red;" v-if="scope.row.is_overdue">Overdue: </span>
                                <span v-else>{{$t('In')}}</span>
                                {{scope.row.next_run}}
                            </template>
                        </el-table-column>
                        <el-table-column :label="$t('Action')">
                            <template slot-scope="scope">
                                <el-button @click="runCron(scope.row.hook)" size="small" type="danger">
                                    {{$t('Run Manually')}}
                                </el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                    <template v-if="server_info">
                        <p style="margin-top: 20px;">
                            Server Memory Limit: <code>{{server_info.memory_limit}}</code>. Current usage: <code>{{server_info.usage_percent}}%</code>. Max Execution Time: <code>{{server_info.max_execution_time}}</code>
                        </p>
                        <p style="color: red;" v-if="!server_info.has_server_cron">
                            Server Side Cron is not enabled. Please consider enabling server side cron. <a target="_blank" rel="noopener" href="https://fluentcrm.com/docs/fluentcrm-cron-job-basics-and-checklist/">Read more about server side cron here.</a>
                        </p>

                    </template>
                </div>
            </div>

            <div style="border: 2px solid red;" class="settings-section fluentcrm_databox">
                <h3 style="margin-top: 0">{{$t('Danger Zone')}}</h3>
                <p>{{$t('delete_all_crm_specific_data')}}</p>
                <p>{{$t('You have to add')}} <code>define('FLUENTCRM_IS_DEV_FEATURES', true);</code> {{$t('in your')}} wp-config.php {{$t('to make this feature work')}}</p>
                <hr>
                <div v-loading="loading" class="fc_global_form_builder text-align-center">
                    <h3>{{$t('Reset FluentCRM Database tables')}}</h3>
                    <p style="color: red;">{{$t('reset_db_warning')}}</p>
                    <confirm placement="top-start" :message="$t('reset_db_conf_warning')"
                             @yes="resetDatabase()">
                        <el-button slot="reference" type="danger">
                            {{$t('Yes, Delete and Reset All CRM Data')}}
                        </el-button>
                    </confirm>
                </div>
            </div>

            <data-cleanup />

        </div>
    </div>
</template>

<script type="text/babel">
    import Confirm from '@/Pieces/Confirm';
    import DataCleanup from './_DataCleanup';

    export default {
        name: 'SettingsTools',
        components: {
            Confirm,
            DataCleanup
        },
        data() {
            return {
                rest_statuses: {
                    get_request_status: 'checking',
                    post_request_status: 'checking',
                    put_request_status: 'checking',
                    delete_request_status: 'checking'
                },
                fetching_cron: false,
                loading: false,
                cron_events: [],
                server_info: false
            }
        },
        methods: {
            checkRestRequest(key, type, callbackFunc) {
                this[type]('setting/test')
                    .then((response) => {
                        if (response.message) {
                            this.rest_statuses[key] = this.$t('OK');
                        } else {
                            this.rest_statuses[key] = this.$t('SettingsTools.checkRestRequest_message');
                        }
                    })
                    .catch((errors) => {
                        this.rest_statuses[key] = this.$t('SettingsTools.checkRestRequest_message');
                    })
                    .finally(() => {
                        if (callbackFunc) {
                            callbackFunc();
                        }
                    });
            },
            resetDatabase() {
                this.loading = true;
                this.$post('setting/reset_db')
                    .then(response => {
                        this.$notify.success(response.message);
                        this.loading = false;
                    })
                    .catch(errors => {
                        this.handleError(errors);
                        this.loading = false;
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            fetchCronEvents() {
                this.fetching_cron = true;
                this.$get('setting/cron_status')
                    .then(response => {
                        this.cron_events = response.cron_events;
                        this.server_info = response.server;
                    })
                    .catch(errors => {
                        this.handleError(errors)
                    })
                    .finally(() => {
                        this.fetching_cron = false;
                    })
            },
            runCron(hookName) {
                this.fetching_cron = true;
                this.$post('setting/run_cron', {
                    hook: hookName
                })
                    .then(response => {
                        this.$notify.success(response.message);
                    })
                    .catch(errors => {
                        this.handleError(errors)
                    })
                    .finally(() => {
                        this.fetching_cron = false;
                    })
            }
        },
        mounted() {
            this.fetchCronEvents();
            this.checkRestRequest('put_request_status', '$put', () => {
                this.checkRestRequest('delete_request_status', '$del', () => {
                    this.checkRestRequest('get_request_status', '$get', () => {
                        this.checkRestRequest('post_request_status', '$post');
                    });
                });
            });

            this.changeTitle(this.$t('Tools'));
        }
    }
</script>
