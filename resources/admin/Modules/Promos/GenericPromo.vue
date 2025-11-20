<template>
    <div class="fc_promo_body">
        <div style="background: white;padding: 10px;text-align: center;display: block;overflow: hidden;" class="promo_block">
            <h2>{{heading || $t('This is a pro feature')}}</h2>
            <p>
                {{$t('Lin_This_iapfPdtFPta')}}
            </p>
            <div class="">
                <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">{{$t('Get FluentCRM Pro')}}</a>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'GenericPromo',
        props: {
            heading: {
                type: String,
                required: false,
                default: ''
            }
        }
    };
</script>
