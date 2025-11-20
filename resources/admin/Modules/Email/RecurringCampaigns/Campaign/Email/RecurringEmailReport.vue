<template>
    <div>
        <template v-if="campaign.status == 'pending-scheduled' || campaign.status == 'processing' || campaign.status == 'scheduled'">
            <campaign-email-process-stat @unscheduled="handleUnscheduled()" :campaign="campaign" />
        </template>
        <template v-else>
            <div style="padding: 20px;">
                <archived-report :campaign_id="campaign.id" />
            </div>
        </template>
    </div>
</template>

<script type="text/babel">
import CampaignEmailProcessStat from '../../../Campaigns/_components/CampaignEmailProcessStat.vue';
import ArchivedReport from './ArchivedReport.vue';

export default {
    name: 'RecurringEmailReport',
    components: {
        CampaignEmailProcessStat,
        ArchivedReport
    },
    props: ['campaign', 'parent_campaign'],
    methods: {
        handleUnscheduled() {
            this.$router.push({name: 'past_recurring_emails', params: { campaign_id: this.parent_campaign.id }});
        },
        getCampaignPercent() {
            return parseInt(this.sent_count / this.campaign.recipients_count * 100);
        }
    }
}
</script>
