<template>
    <div class="camapign_review_wrapper">
        <div class="campaign_review_items">
            <div class="camapign_review_item">
                <el-row>
                    <el-col :span="20">
                        <h3>{{ $t('Recipients') }}</h3>
                        <p v-loading="count === null" style="cursor: pointer;"
                           @click="show_selections = !show_selections">{{ $t('Total:') }} <b>{{ count || '~0+' }}
                            {{ $t('Recipients') }}</b></p>
                        <div v-if="show_selections">
                            <h3>{{ $t('Recipient Sections') }}</h3>
                            <readable-recipients :settings="campaign.settings"/>
                            <el-button size="small" type="info" @click="showing_recipients = !showing_recipients">
                                {{ $t('Show Individual Recipients') }}
                            </el-button>
                        </div>
                    </el-col>
                    <el-col class="text-align-right" :span="4">
                        <el-button @click="goToStep(2)" size="small">{{ $t('Edit Recipients') }}</el-button>
                    </el-col>
                </el-row>
            </div>
            <div class="camapign_review_item">
                <el-row>
                    <el-col :span="20">
                        <h3>{{ $t('Subject') }}</h3>
                        <p>{{ campaign.email_subject }}</p>
                        <p style="color: gray;">{{ $t('Preview Text:') }} {{ campaign.email_pre_header }}</p>
                    </el-col>
                    <el-col class="text-align-right" :span="4">
                        <el-button @click="goToStep(1)" size="small">{{ $t('Edit Subject') }}</el-button>
                    </el-col>
                </el-row>
            </div>
            <div class="camapign_review_item">
                <el-row>
                    <el-col :span="20">
                        <h3>{{ $t('Email Body') }}</h3>
                        <div style="padding: 0; max-width: 800px; min-height: 350px"
                             class="fluentcrm_email_body_preview">
                            <preview-iframe-builder :show_audit="true" frame_height="300px" :campaign="campaign"
                                                    :campaign_id="campaign.id"/>
                        </div>
                        <br/>
                        <send-test-email placement="right" :campaign="campaign"/>
                    </el-col>
                    <el-col class="text-align-right" :span="4">
                        <el-button @click="goToStep(0)" size="small">
                            {{ $t('Edit Email Body') }}
                        </el-button>
                    </el-col>
                </el-row>
            </div>
            <div class="camapign_review_item">
                <h3>{{ $t('Cam_Broadcast_Schedu') }}</h3>
                <p>{{ $t('Cam_If_yteiaYcbten') }}</p>
                <hr/>
                <el-row :gutter="20">
                    <el-col :span="12">
                        <h4>{{ $t('When you send the emails?') }}</h4>
                        <el-radio-group @change="sendingTypeChanged()" class="fluentcrm_line_items"
                                        v-model="sending_type">
                            <el-radio label="send_now">{{ $t('Send the emails right now') }}</el-radio>
                            <el-radio label="schedule">{{ $t('Schedule the emails') }}</el-radio>
                            <el-radio label="range_schedule">
                                {{ $t('Schedule_Date_Time_Info') }}
                            </el-radio>
                        </el-radio-group>
                    </el-col>
                    <el-col v-if="showScheduleTimer" :span="12">
                        <template v-if="sending_type == 'schedule'">
                            <h4>{{ $t('Please set date and time') }}</h4>
                            <div class="block">
                                <el-date-picker
                                    value-format="yyyy-MM-dd HH:mm:ss"
                                    v-model="schedule_date_time"
                                    required
                                    type="datetime"
                                    :picker-options="pickerOptions"
                                    :placeholder="$t('Select date and time')">
                                </el-date-picker>
                            </div>
                            <p>{{ $t('Cam_Current_ST_oySS') }}: <code>{{ campaign.server_time }}</code></p>
                            <br/><br/>
                        </template>
                        <template v-else-if="sending_type == 'range_schedule'">
                            <h4>{{ $t('Select_Date_Time_Alert') }}</h4>
                            <div class="block">
                                <el-date-picker
                                    :disabled="!this.has_campaign_pro"
                                    value-format="yyyy-MM-dd HH:mm:ss"
                                    v-model="schedule_date_time"
                                    required
                                    type="datetimerange"
                                    :picker-options="emailDateRangeConfig"
                                    :placeholder="$t('Select date and time')">
                                </el-date-picker>
                            </div>
                            <p v-if="!this.has_campaign_pro">{{ $t('To use this feature you need FluentCRM Pro.') }} <a target="_blank" :href="appVars.upgrade_url">{{ $t('Please upgrade.') }}</a></p>
                            <p>{{ $t('Camp_Notice_About_Time') }}</p>
                            <p>{{ $t('Cam_Current_ST_oySS') }}: <code>{{ campaign.server_time }}</code></p>
                            <br/><br/>
                        </template>
                    </el-col>
                </el-row>
                <confirm placement="top-start" :message="campaignConfirmMessage" @yes="sendEmails()">
                    <el-button v-loading="btnSending" type="success" size="big" slot="reference">
                        <span v-if="sending_type == 'send_now'">
                            {{ $t('Send Emails Now') }}
                        </span>
                        <span v-else>{{ $t('Schedule this campaign') }}</span>
                    </el-button>
                </confirm>
            </div>
        </div>

        <el-dialog
            width="70%"
            title="Campaign Recipients"
            :append-to-body="true"
            :close-on-click-modal="false"
            :visible.sync="showing_recipients"
        >
            <view-segment-recipients v-if="showing_recipients" :campaign_id="campaign.id"/>
            <span slot="footer" class="dialog-footer">
                <el-button type="info" @click="showing_recipients=false">{{ $t('Close') }}</el-button>
            </span>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
import ReadableRecipients from '@Pieces/ReadableRecipientTagger';
import SendTestEmail from '@Pieces/TestEmail.vue';
import ViewSegmentRecipients from './_ViewSegmentRecipient';
import PreviewIframeBuilder from '@Pieces/PreviewIframeBuilder';
import {emailDateConfig, emailDateRangeConfig} from '@/Bits/data_config';
import Confirm from '@/Pieces/Confirm';
// const moment = window.moment;

export default {
    name: 'CampaignReview',
    props: ['campaign'],
    components: {
        ReadableRecipients,
        SendTestEmail,
        ViewSegmentRecipients,
        PreviewIframeBuilder,
        Confirm
    },
    data() {
        return {
            campaignConfirmMessage: '<b>' + this.$t('Cam_Send_Now_Confirm_Header') + '</b><br />' + this.$t('Cam_Send_Now_Message'),
            btnSending: false,
            sending_type: 'send_now',
            schedule_date_time: '',
            subscribers_modal: false,
            showing_recipients: false,
            pickerOptions: emailDateConfig,
            emailDateRangeConfig: emailDateRangeConfig,
            show_selections: false,
            count: null,
            showScheduleTimer: true
        }
    },
    watch: {
        sending_type(newType, oldType) {
            if (newType === 'send_now') {
                this.campaignConfirmMessage = '<b>' + this.$t('Cam_Send_Now_Confirm_Header') + '</b><br />' + this.$t('Cam_Send_Now_Message');
            } else {
                this.campaignConfirmMessage = '<b>' + this.$t('Schedule_Cam_Confirm_Header') + '</b>';
            }
        }
    },
    methods: {
        // isValidDateTime() {
        //     const currentTime = moment().valueOf();
        //     const selectedTime = moment(this.schedule_date_time).valueOf();
        //
        //     return currentTime <= selectedTime;
        // },
        sendEmails() {
            const data = {};
            if (this.sending_type === 'schedule') {
                if (!this.schedule_date_time) {
                    this.$notify.error(this.$t('Please select a date and time'));
                    return;
                }
                data.scheduled_at = this.schedule_date_time;
                data.sending_type = 'schedule';
            } else if (this.sending_type === 'range_schedule') {
                if (!this.schedule_date_time[0] || !this.schedule_date_time[1]) {
                    this.$notify.error(this.$t('Please select a date and time'));
                    return;
                }

                if (!this.has_campaign_pro) {
                    this.$notify.error(this.$t('You need pro version to use this feature'));
                    return;
                }

                data.scheduled_at = this.schedule_date_time;
                data.sending_type = 'range_schedule';
            }

            this.btnSending = true;

            this.$post(`campaigns/${this.campaign.id}/schedule`, data)
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                    this.$router.push({
                        name: 'campaign-view',
                        params: {
                            id: this.campaign.id
                        }
                    });
                })
                .catch(error => {
                    this.$notify.error(error.message);
                })
                .finally(() => {
                    this.btnSending = false;
                });
        },
        sendingTypeChanged() {
            this.showScheduleTimer = false;
            if (this.sending_type === 'send_now') {
                this.schedule_date_time = ''
            } else if (this.sending_type === 'schedule') {
                this.schedule_date_time = ''
            } else if (this.sending_type === 'range_schedule') {
                this.schedule_date_time = ['', '']
            }
            this.$nextTick(() => {
                this.showScheduleTimer = true;
            });
        },
        goToStep(step) {
            this.$emit('goToStep', step);
        },
        saveThisStep() {
            this.$post(`campaigns/${this.campaign.id}/step`, {
                next_step: 3
            });
        },
        getCount() {
            this.$get(`campaigns/${this.campaign.id}/estimated-recipients-count`)
                .then(response => {
                    this.count = response.estimated_count
                });
        }
    },
    mounted() {
        this.saveThisStep();
        this.getCount();
    }
}
</script>
