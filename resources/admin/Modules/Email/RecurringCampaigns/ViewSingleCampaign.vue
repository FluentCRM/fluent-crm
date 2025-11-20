<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div v-if="campaign" class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'recurring_campaigns' }">
                        {{ $t('Recurring Campaigns') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ campaign.title }}
                        <span style="width: auto" class="status"> - {{ campaign.status }}</span>
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <ul v-if="campaign.id" class="fc_action_menu">
                    <li style="padding: 0 15px 0 0;display: flex;align-items: center;column-gap: 7px;">
                        <el-switch @change="changeStatus()" v-model="campaign.status" active-value="active"
                                   inactive-value="draft"></el-switch>
                        {{ campaign.status | ucFirst }}
                    </li>
                    <li>
                        <router-link :to="{name: 'view_recurring_campaign', params: { campaign_id: campaign.id }}">
                            {{ $t('Email Configuration') }}
                        </router-link>
                    </li>
                    <li>
                        <router-link :to="{name: 'recurring_campaign_settings', params: { campaign_id: campaign.id }}">
                            {{ $t('Settings') }}
                        </router-link>
                    </li>
                    <li>
                        <router-link :to="{name: 'past_recurring_emails', params: { campaign_id: campaign.id }}">
                            {{ $t('Email History') }}
                        </router-link>
                    </li>
                </ul>
            </div>
        </div>
        <div :class="$route.name" v-if="campaign.id" class="fluentcrm_body fluentcrm_body_boxed">
            <router-view :campaign="campaign"/>
        </div>
        <div  v-else-if="loading" class="fluentcrm_body fluentcrm_body_boxed">
            <el-skeleton :animated="true" style="padding: 20px;" :rows="7"></el-skeleton>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ViewSingleCampaign',
    props: ['campaign_id'],
    data() {
        return {
            campaign: {},
            loading: true
        }
    },
    methods: {
        fetchCampaign() {
            this.loading = true;
            this.$get('recurring-campaigns/' + this.campaign_id)
                .then(response => {
                    this.campaign = response.campaign;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        changeStatus() {
            this.$post('recurring-campaigns/' + this.campaign.id + '/change-status', {
                status: this.campaign.status
            })
                .then(response => {
                    this.$notify.success(response.message)
                })
                .catch((errors) => {
                    this.handleError(errors);
                    if (this.campaign.status === 'active') {
                        this.campaign.status = 'draft';
                    }
                });
        }
    },
    mounted() {
        this.fetchCampaign();
    }
}
</script>
