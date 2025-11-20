<template>
    <div class="tab-content">
        <el-button-group class="content-center">
            <el-button
                type="primary"
                size="small"
                :loading="btnSending"
                @click="send"
            >{{$t('Send')}}</el-button>

            <el-button
                type="primary"
                size="small"
                @click="toggleSheduler"
            >{{$t('Schedule')}}</el-button>
        </el-button-group>

        <div v-if="sheduler">
            <h3>{{$t('Set up your schedule')}}</h3>

            <el-form label-position="top">
                <el-row :gutter="10">
                    <el-col :md="12" :lg="12">
                        <el-form-item :label="$t('Delivery Date')">
                            <el-date-picker
                                v-model="form.date"
                                type="date"
                                format="dd-MM-yyyy"
                                value-format="yyyy-MM-dd"
                                :placeholder="$t('Pick a date')">
                            </el-date-picker>
                        </el-form-item>
                    </el-col>

                    <el-col :md="12" :lg="12">
                        <el-form-item :label="$t('Delivery time')">
                            <el-time-picker
                                v-model="form.time"
                                format="hh:mm:ss A"
                                value-format="HH:mm:ss"
                                :placeholder="$t('Delivery Date & time')">
                            </el-time-picker>
                        </el-form-item>
                    </el-col>
                </el-row>

                <el-form-item>
                    <el-button
                        type="primary"
                        size="small"
                        :disabled="disable"
                        @click="schedule"
                        :loading="btnScheduling"
                    >{{$t('Schedule Campaign')}}</el-button>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'Schedule',
        props: ['campaign', 'redirect', 'redirectWithWarning'],
        data() {
            return {
                sheduler: false,
                form: {
                    date: null,
                    time: null
                },
                btnSending: false,
                btnScheduling: false
            }
        },
        computed: {
            disable() {
                return !(this.form.date && this.form.time);
            }
        },
        methods: {
            send() {
                this.btnSending = true;
                this.sendEmails(null);
            },
            schedule() {
                this.btnScheduling = true;

                const datetime = this.form.date + ' ' + this.form.time;

                this.sendEmails((new Date(datetime)).toUTCString());

                this.toggleSheduler();
            },
            sendEmails(time) {
                let sendMessage;

                if (time === null) {
                    sendMessage = this.$t('Sch_Emails_wbsast');
                } else {
                    sendMessage = this.$t('Emails will be sent now.');
                }

                this.$post('campaigns-schedule', {
                    campaign_id: this.campaign.id,
                    schedule: { time }
                }).then(r => {
                    this.redirect();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: sendMessage,
                        offset: 19
                    });
                }).catch(r => {
                    this.redirectWithWarning(r.message);
                }).finally(r => {
                    this.btnSending = false;
                    this.btnScheduling = false;
                });
            },
            toggleSheduler() {
                this.sheduler = !this.sheduler;
            }
        },
        created() {
            if (this.campaign.scheduled_at && this.campaign.scheduled_at.substr(0, 1) !== '0') {
                const d = this.campaign.scheduled_at;
                this.form.date = this.nsDateFormat(d, 'YYYY-MM-DD');
                this.form.time = this.nsDateFormat(d, 'HH:mm:ss A');
            }
        }
    }
</script>
