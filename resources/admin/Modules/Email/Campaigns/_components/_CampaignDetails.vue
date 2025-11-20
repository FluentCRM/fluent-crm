<template>
    <div class="campaign_summary">
        <div class="line">
            <el-row :gutter="40">
                <el-col :span="4"><strong>{{ $t('Title') }}</strong></el-col>
                <el-col :span="20">: {{ campaign.title }}</el-col>
            </el-row>
        </div>

        <div class="line">
            <el-row :gutter="40">
                <el-col :span="4"><strong>{{ $t('Scheduled on') }}</strong></el-col>
                <el-col :span="20">: {{ scheduledAt(campaign.scheduled_at) }}</el-col>
            </el-row>
        </div>

        <div class="line">
            <el-row :gutter="40">
                <el-col :span="4"><strong>{{ $t('Subject') }}</strong></el-col>
                <el-col :span="20">
                    : {{ campaign.email_subject }}
                </el-col>
            </el-row>
        </div>

        <div class="line">
            <el-row :gutter="40">
                <el-col :span="4"><strong>{{ $t('Total Recipients') }}</strong></el-col>
                <el-col :span="20">: {{ campaign.recipients_count }}</el-col>
            </el-row>
        </div>

        <div class="line" v-if="campaign.sent_by">
            <el-row :gutter="40">
                <el-col :span="4"><strong>{{ $t('Sent By') }}</strong></el-col>
                <el-col :span="20">: {{ campaign.sent_by }}</el-col>
            </el-row>
        </div>

        <div style="margin-top: 30px; max-width: 800px; max-height: 650px;" class="template-preview fluentcrm_email_body_preview">
            <preview-iframe-builder frame_height="600px" :campaign_id="campaign.id" :show_audit="false" />
        </div>
    </div>
</template>

<script type="text/babel">
import PreviewIframeBuilder from '@/Pieces/PreviewIframeBuilder';

export default {
    name: 'CampaignDetails',
    components: {
        PreviewIframeBuilder
    },
    props: ['campaign'],
    methods: {
        scheduledAt(date) {
            if (date === null) {
                return this.$t('Not Scheduled');
            }
            return this.nsDateFormat(date, 'MMMM Do, YYYY [at] h:mm A');
        }
    }
}
</script>
