<template>
    <div v-loading="loading" class="fluentcrm_link_metrics">
        <div v-if="!hide_title" class="fluentcrm_inner_header">
            <h3 class="fluentcrm_inner_title">{{$t('Campaign Link Clicks')}}</h3>
            <div class="fluentcrm_inner_actions">
                <el-button @click="fetchReport" size="mini"><i class="el-icon el-icon-refresh"></i></el-button>
            </div>
        </div>

        <template v-if="has_campaign_pro">
            <el-table height="270" v-if="links.length" :empty-text="$t('No Data Found')" stripe border :data="links" style="width: 100%" v-loading="loading">
                <el-table-column :label="$t('URL')">
                    <template slot-scope="scope">
                        <a :href="scope.row.url" target="_blank" rel="noopener">{{ scope.row.url }}</a>
                    </template>
                </el-table-column>
                <el-table-column width="150" :label="$t('Unique Clicks')" prop="total"></el-table-column>
            </el-table>
            <el-empty :image-size="135" :description="$t('No link activity recorded yet')" v-else />
        </template>
        <div style="padding: 20px 20px 40px;" class="text-align-center" v-else>
            <p>This feature is not available on your plan. Please upgrade to the PRO plan to unlock all these awesome features including <b>Link clicks analytics</b></p>
            <a :href="appVars.crm_pro_url" target="_blank" rel="noopener" class="el-button el-button--danger">{{ $t('Get FluentCRM Pro') }}</a>
        </div>
    </div>
</template>
<script type="text/babel">
export default {
    name: 'CampaignLinkMetrics',
    props: ['campaign_id', 'hide_title'],
    data() {
        return {
            loading: false,
            links: []
        }
    },
    methods: {
        fetchReport() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign_id}/link-report`)
                .then(response => {
                    this.links = response.links;
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(r => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        if (this.has_campaign_pro) {
            this.fetchReport();
        }
    }
}
</script>
