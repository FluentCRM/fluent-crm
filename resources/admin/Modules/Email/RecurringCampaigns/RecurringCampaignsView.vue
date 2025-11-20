<template>
    <div v-if="has_campaign_pro" class="fc_recurring_root">
        <router-view></router-view>
    </div>
    <div v-else>
        <email-recurring-promo />
    </div>
</template>

<script type="text/babel">
import EmailRecurringPromo from '../../Promos/EmailRecurringPromo.vue';
export default {
    name: 'RecurringCampaignsView',
    components: {
        EmailRecurringPromo
    }
}
</script>
