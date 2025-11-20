<template>
    <div v-loading="loading" style="border: 2px solid #deb373;" class="settings-section fluentcrm_databox">
        <div style="background-color: white; padding: 0 0 10px 0; margin-bottom: 20px" class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('Data Cleanup') }}</h3>
                <p>{{ $t('cleanup_old_data_like_email_log') }}</p>
            </div>
        </div>
        <div class="fc_global_form_builder">
            <div class="text-align-center" v-if="reset_message">
                <h3>{{ reset_message }}</h3>
            </div>
            <el-form v-else-if="!showing_log" :data="cleanup" label-position="top">
                <el-form-item :label="$t('Select Logs you want to delete')">
                    <el-checkbox-group v-model="cleanup.selected_logs">
                        <el-checkbox v-for="(logTitle,logValue) in log_options" :label="logValue" :key="logValue">
                            {{ logTitle }}
                        </el-checkbox>
                    </el-checkbox-group>
                    <p>{{ $t('Your Selected logs will be deleted permanently') }}</p>
                </el-form-item>
                <el-form-item class="mb-10" :label="$t('Delete older data more than?')">
                    <el-input type="number" :min="7" v-model="cleanup.days_before"/>
                    <p style="margin-top: 20px;">{{ $t('All the selected logs that are') }} <b
                        style="color: red;">{{ $t('older than') }} {{ cleanup.days_before }} {{ $t('days') }}</b>
                        {{ $t('will be deleted') }}</p>
                </el-form-item>
                <template v-if="cleanup.selected_logs.length">
                    <el-button size="small" type="danger" @click="getOldLogs()">{{ $t('Preview Log Summary') }}
                    </el-button>
                </template>
            </el-form>
            <div v-else>
                <h3>{{ $t('Please review before you delete your old logs') }}</h3>
                <el-table :empty-text="$t('No Data Found')" :data="log_counters" stripe border>
                    <el-table-column :label="$t('Type')" prop="title"></el-table-column>
                    <el-table-column :label="$t('Counts')" prop="count"></el-table-column>
                </el-table>
                <p></p>
                <confirm placement="top-start" :message="$t('delete_old_log_data_permanently_notice')"
                         @yes="deleteOldLogs()">
                    <el-button slot="reference" type="danger">
                        {{ $t('Yes, I want to delete Old Logs') }}
                    </el-button>
                </confirm>
                <el-button @click="resetData()">{{ $t('Never-mind, I changed my mind') }}</el-button>
                <p style="color: #f56c6b; margin-top: 30px;">{{ $t('All the selected logs that are') }} <b
                    style="color: red;">{{ $t('older than') }} {{ cleanup.days_before }} {{ $t('days') }}</b>
                    {{ $t('will be deleted') }} {{ $t('permanently') }}.
                    <b>{{ $t('You can not reverse this action.') }}</b></p>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'DataCleanup',
    components: {
        Confirm
    },
    data() {
        return {
            loading: false,
            cleanup: {
                selected_logs: [],
                days_before: 30
            },
            log_options: {
                emails: this.$t('Email History Logs'),
                email_clicks: this.$t('Email Click Logs'),
                email_open: this.$t('Email Open Logs'),
                system_logs: this.$t('System Logs'),
                activity_logs: this.$t('Activity Logs')
            },
            log_counters: [],
            showing_log: false,
            reset_message: ''
        }
    },
    methods: {
        deleteOldLogs() {
            this.loading = true;
            this.$del('setting/old_logs', {
                ...this.cleanup
            })
                .then(response => {
                    if (response.has_more) {
                        this.deleteOldLogs();
                        return;
                    }

                    this.$notify.success(response.message);
                    this.resetData();
                    this.reset_message = response.message;
                    this.loading = false;
                })
                .catch(errors => {
                    this.handleError(errors);
                    this.loading = false;
                });
        },
        getOldLogs() {
            this.loading = true;
            this.$get('setting/old_logs', {
                ...this.cleanup
            })
                .then(response => {
                    this.showing_log = true;
                    this.log_counters = response.log_counts;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        resetData() {
            this.cleanup = {
                selected_logs: [],
                days_before: 30
            }
            this.showing_log = false;
            this.log_counters = [];
        }
    }
}
</script>
