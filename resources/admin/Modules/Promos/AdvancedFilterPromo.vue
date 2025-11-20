<template>
    <div class="fc_promo_body">
        <div style="background: #f9fafa;padding: 20px;text-align: center;display: block;overflow: hidden;" class="promo_block">
            <h2>{{$t('Advanced Filter is a pro feature')}}</h2>
            <p>
                {{$t('segment_your_contacts') }}
            </p>
            <div class="">
                <a class="el-button el-button--primary" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                    {{$t('Get FluentCRM Pro')}}
                </a>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'AdvancedFilterPromo'
    };
</script>
