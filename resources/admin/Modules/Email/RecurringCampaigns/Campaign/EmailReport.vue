<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div v-if="parent_campaign" class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'recurring_campaigns' }">
                        {{ $t('Recurring Campaigns') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item :to="{ name: 'past_recurring_emails', params: { campaign_id: campaign_id } }">
                        {{ parent_campaign.title }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ campaign.title }} ({{campaign.status}}) / {{ active_step }}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
        </div>
        <div v-if="campaign.id" class="fluentcrm_body">
            <template v-if="active_step == 'edit'">
                <email-editor @goToNext="gotToNextStep()" @updateCampaign="updateCampaign()" :saving="saving"
                              :campaign="campaign"/>
            </template>
            <template v-else-if="active_step == 'review'">
                <mail-config @goToNext="maybePublishCampaign()" @updateCampaign="updateCampaign()" :campaign="campaign"/>
            </template>
            <template v-else-if="active_step == 'reports'">
                <recurring-email-report @reload="reloadPage()" :campaign="campaign" :parent_campaign="parent_campaign" />
            </template>
        </div>
        <div v-else-if="loading" class="fluentcrm_body fluentcrm_body_boxed">
            <el-skeleton :animated="true" style="padding: 20px;" :rows="7"></el-skeleton>
        </div>
    </div>
</template>

<script type="text/babel">
import EmailEditor from './Email/MailEditor.vue';
import MailConfig from './Email/MailConfig.vue';
import RecurringEmailReport from './Email/RecurringEmailReport.vue';

export default {
    name: 'ViewSingleCampaignReport',
    props: ['campaign_id', 'email_id'],
    components: {
        EmailEditor,
        MailConfig,
        RecurringEmailReport
    },
    data() {
        return {
            parent_campaign: {},
            campaign: {},
            loading: true,
            saving: false,
            active_step: '' // edit -> review -> reports
        }
    },
    methods: {
        fetchCampaign() {
            this.loading = true;
            this.$get('recurring-campaigns/' + this.campaign_id + '/emails/' + this.email_id)
                .then(response => {
                    this.parent_campaign = response.campaign;
                    this.campaign = response.email;

                    if (response.email.status == 'draft') {
                        this.active_step = 'edit';
                    } else if (response.email.status == 'cancelled') {
                        this.$router.push({
                            name: 'past_recurring_emails',
                            params: {
                                campaign_id: this.campaign_id
                            }
                        });
                    } else {
                        this.active_step = 'reports';
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        maybePublishCampaign() {
            this.campaign.status = 'pending-scheduled';

            this.updateCampaign(() => {
                this.active_step = 'reports';
            });
        },

        gotToNextStep() {
            this.updateCampaign(() => {
                if (this.active_step == 'edit') {
                    this.active_step = 'review';
                } else if (this.active_step == 'review') {
                    this.active_step = 'reports';
                }
            });
        },

        updateCampaign(callback) {
            this.saving = true;
            this.$post('recurring-campaigns/' + this.campaign_id + '/emails/update-email', {
                email: JSON.stringify(this.campaign),
                step: this.active_step
            })
                .then(response => {
                    this.$notify.success(response.message);
                    if (callback) {
                        callback(response);
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                    if (this.active_step == 'review') {
                        this.campaign.status = 'draft';
                    }
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        reloadPage() {
            window.location.reload(true);
        }
    },
    mounted() {
        this.fetchCampaign();
    }
}
</script>
