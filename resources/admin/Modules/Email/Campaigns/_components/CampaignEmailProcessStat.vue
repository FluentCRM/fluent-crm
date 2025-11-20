<template>
    <div class="fc_campaign_process_wrap">
        <div class="fc_proc_sum" v-if="campaign.status == 'processing'">
            <h3 style="margin-bottom: 20px;" class="no_spaced">{{ $t('Emails are currently on processing') }}</h3>
            <el-progress :text-inside="true" :stroke-width="26" :percentage="processing_percent"></el-progress>
            <p style="text-align: center;" v-loading="loading_processing_stat">{{ completed }} / {{ contact_count }}</p>
            <p style="font-size: 16px;" v-if="campaign.scheduling_range">The emails will be scheduled randomly between the date-time of <b>{{campaign.scheduling_range.start}}</b> and <b>{{campaign.scheduling_range.end}}</b></p>
        </div>
        <div class="fc_proc_sum campaign_review_items">
            <div class="camapign_review_item fc_proc_heading">
                <ul>
                    <li><b>{{ $t('Campaign Status:') }}</b> {{ getHumanStatusName(campaign.status) }}</li>
                    <li>
                        <b>{{ $t('Scheduled on:') }}</b> {{ campaign.scheduled_at }} ({{ nsHumanDiffTime(campaign.scheduled_at) }})
                    </li>
                    <li style="cursor: pointer" @click="show_selections = !show_selections" v-loading="contact_count === null">
                        <b>{{ $t('Estimated Contacts:') }}</b> {{ contact_count }}
                    </li>
                </ul>
                <div v-if="show_selections">
                    <readable-recipients :settings="campaign.settings" />
                </div>
            </div>
            <div class="camapign_review_item">
                <h3 class="no_spaced">{{ $t('Subject') }}: {{ campaign.email_subject }}</h3>
                <p style="color: gray;">{{ $t('Preview Text:') }} {{ campaign.email_pre_header }}</p>
            </div>
            <div class="camapign_review_item">
                <h3>{{ $t('Email Body') }}</h3>
                <div style="background: white;" class="">
                    <preview-iframe-builder frame_height="300px" :campaign_id="campaign.id" />
                </div>
                <br/>
                <send-test-email :campaign="campaign" />
            </div>
            <div v-if="canCancelEmail" class="camapign_review_item">
                <el-button v-loading="loading" :disabled="loading" @click="cancelSchedule()" size="small" type="danger"
                           plain>
                    {{ $t('Cancel Schedule') }}
                </el-button>
                <p v-if="scheduling_method == 'scheduled'">{{ $t('Email_Schedule_Info') }}</p>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import ReadableRecipients from '@Pieces/ReadableRecipientTagger';
import SendTestEmail from '@Pieces/TestEmail.vue';
import PreviewIframeBuilder from '@Pieces/PreviewIframeBuilder';

export default {
    name: 'CampaignEmailProcessStat',
    props: ['campaign'],
    components: {
        SendTestEmail,
        ReadableRecipients,
        PreviewIframeBuilder
    },
    data() {
        return {
            contact_count: null,
            loading: false,
            loading_processing_stat: false,
            completed: 1,
            processing_counter: 1,
            show_selections: false,
            scheduling_method: ''
        }
    },
    computed: {
        processing_percent() {
            if (this.campaign.status != 'processing' || !this.completed || !this.contact_count) {
                return 1;
            }
            return parseInt(this.completed / this.contact_count * 100);
        },
        canCancelEmail() {
            const status = this.campaign.status;
            return status == 'scheduled' || status == 'pending-scheduled' || 'processing';
        }
    },
    methods: {
        fetchCount() {
            this.$get(`campaigns/${this.campaign.id}/estimated-recipients-count`)
                .then(response => {
                    this.contact_count = response.estimated_count
                });
        },
        getHumanStatusName(status) {
            if (status == 'pending-scheduled') {
                return this.$t('Scheduled');
            }
            return this.ucFirst(status);
        },
        cancelSchedule() {
            this.loading = true;
            this.$post(`campaigns/${this.campaign.id}/un-schedule`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$emit('unscheduled');
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchProcessingStat() {
            this.loading_processing_stat = true;
            this.$get(`campaigns/${this.campaign.id}/processing-stat`, {
                counter: this.processing_counter
            })
                .then(response => {
                    if (!response.campaign) {
                        window.location.reload(true);
                    }

                    if (this.campaign.status != response.campaign.status) {
                        window.location.reload(true);
                    }

                    this.loading_processing_stat = false;
                    this.completed = response.campaign.recipients_count;
                    this.campaign.status = response.campaign.status;
                    this.scheduling_method = response.scheduling_method;

                    this.campaign.scheduling_range = response.campaign.scheduling_range;

                    if (response.campaign.status == 'processing') {
                        this.fetchStatAgain();
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                    this.loading_processing_stat = false;
                })
                .finally(() => {

                });
        },
        fetchStatAgain() {
            setTimeout(() => {
                this.processing_counter += 1;
                this.fetchProcessingStat();
            }, 3000);
        }
    },
    mounted() {
        this.fetchCount();
        this.fetchProcessingStat();
    }
}
</script>
