<template>
    <div class="fc_promo_wrapper">
        <div class="text-align-center fluentcrm_header fc_promo_heading">
            <h1 class="">{{$t('Dynamic Segments')}}</h1>
            <p>{{$t('create_dynamic_segments_of_contacts_introduction')}}</p>
            <a class="el-button el-button--success" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                {{$t('Get FluentCRM Email Pro')}}
            </a>
        </div>
        <div class="fc_promo_body fluentcrm_body fluentcrm_pad_around">
            <div class="promo_block">
                <el-row :gutter="20">
                    <el-col :sm="24" :md="12">
                        <div class="promo_content">
                            <img class="promo_image" :src="appVars.images_url+'/promo/segment_create.png'"/>
                        </div>
                    </el-col>
                    <el-col :sm="24" :md="12">
                        <h2>{{$t('Filter By Property and find appropriate Contacts')}}</h2>
                        <p>{{$t('simple_enough_promo_dynamic_segment')}}</p>
                        <div class="">
                            <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                                {{$t('Get FluentCRM Pro')}}
                            </a>
                        </div>
                    </el-col>
                </el-row>
            </div>
            <div class="promo_block">
                <el-row :gutter="20">
                    <el-col :sm="24" :md="12">
                        <h2>{{$t('Use your dynamic segment to broadcast Email Campaigns')}}</h2>
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
            <div class="promo_block">
                <el-row :gutter="20">
                    <el-col :sm="24" :md="12">
                        <div class="promo_content">
                            <img class="promo_image" :src="appVars.images_url+'/promo/dynamic_segment_all.png'"/>
                        </div>
                    </el-col>
                    <el-col :sm="24" :md="12">
                        <h2>{{$t('Get Pre-Defined Contact Lists')}}</h2>
                        <p>
                            {{$t('use_the_predefined_dynamic_segment_introduction')}}
                        </p>
                        <div class="">
                            <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                                {{$t('Get FluentCRM Pro')}}
                            </a>
                        </div>
                    </el-col>
                </el-row>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'DynamicSegmentPromo'
};
</script>
