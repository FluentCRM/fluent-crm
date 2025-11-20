<template>
    <div v-if="has_campaign_pro" class="fc_sequence_root">
        <router-view></router-view>
    </div>
    <div v-else>
        <email-sequence-promo></email-sequence-promo>
    </div>
</template>

<script type="text/babel">
import EmailSequencePromo from '../../Promos/EmailSequencePromo';
export default {
    name: 'sequence-view',
    components: {
        EmailSequencePromo
    },
    data() {
        return {}
    },
    mounted() {
        this.changeTitle(this.$t('Email Sequences'));
    }
};
</script>
