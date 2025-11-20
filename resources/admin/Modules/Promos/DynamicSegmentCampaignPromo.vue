<template>
    <div class="fc_promo_body">
        <div class="promo_block">
            <el-row :gutter="20">
                <el-col :sm="24" :md="12">
                    <h2>{{$t('DynamicSegmentCampaignPromo.title')}}</h2>
                    <p>
                        {{$t('create_as_many_dynamic_segment_introduction')}}
                    </p>
                    <div class="">
                        <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                            {{$t('Get FluentCRM Pro')}}
                        </a>
                    </div>
                </el-col>
                <el-col :sm="24" :md="12">
                    <div class="promo_content">
                        <img class="promo_image" :src="appVars.images_url+'/promo/segment_campaign.png'"/>
                    </div>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'DynamicSegmentCampaignPromo'
};
</script>
