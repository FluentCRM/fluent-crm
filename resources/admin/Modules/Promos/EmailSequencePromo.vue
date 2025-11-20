<template>
    <div class="fc_narrow_box fluentcrm_databox text-align-center">
        <h2 class="">{{$t('Email Sequences')}}</h2>
        <p class="text-align-center">{{$t('EmailSequencePromo.title')}}</p>
        <iframe width="764" height="430" src="https://www.youtube.com/embed/6nYOK2UzoVk" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>        <hr />
        <p>{{$t('Upgrade_To_Pro')}}</p>
        <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">{{$t('Get FluentCRM Pro')}}</a>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'EmailSequencePromo'
};
</script>
