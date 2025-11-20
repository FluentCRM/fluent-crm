<template>
    <div class="fc_narrow_box fluentcrm_databox text-align-center">
        <h2 class="">{{$t('Incoming Webhooks')}}</h2>
        <p class="text-align-center">{{$t('Create or Update contacts from Incoming Webhooks')}}</p>
        <hr />
        <p>{{$t('Upgrade_To_Pro')}}</p>
        <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">{{$t('Get FluentCRM Pro')}}</a>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'IncomingWebhookPromo'
};
</script>
