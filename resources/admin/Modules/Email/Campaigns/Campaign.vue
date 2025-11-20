<template>
    <div class="fluentcrm-campaign fluentcrm-view-wrapper" v-if="campaign">
        <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
            <el-breadcrumb-item :to="{ name: 'campaigns' }">
                {{ $t('Email Campaigns') }}
            </el-breadcrumb-item>
            <el-breadcrumb-item>
                {{ campaign.title }} <i @click="show_campaign_config = true"
                                        style="cursor: pointer"
                                        class="el-icon el-icon-edit"></i>
            </el-breadcrumb-item>
        </el-breadcrumb>

        <el-steps :active="activeStep" simple finish-status="success">
            <el-step
                v-for="(step, stepIndex) in steps"
                :key="stepIndex"
                @click.native="e => targetStep(e, stepIndex)"
                style="cursor: pointer"
                :title="step.title"
                :description="step.description"></el-step>
        </el-steps>
        <div v-loading="loading" class="fluentcrm_body fluentcrm_body_boxed">
            <div style="margin: -20px -20px 0;" class="step-container" v-if="activeStep == 0">
                <campaign-body-composer @next="next()" :campaign="campaign"/>
            </div>

            <div class="step-container" v-if="activeStep == 1">
                <campaign-template
                    @next="next()"
                    @prev="prev()"
                    :campaign="campaign"
                />
            </div>

            <div class="step-container" v-if="activeStep == 2">
                <Recipients @prev="prev()" @next="next()" :campaign="campaign"/>
            </div>

            <div class="step-container" v-if="activeStep == 3">
                <campaign-review
                    @goToStep="stepChange"
                    :campaign="campaign"
                />
            </div>
        </div>
        <el-dialog
            v-if="campaign.id"
            :title="$t('Campaign Settings')"
            :append-to-body="true"
            :close-on-click-modal="false"
            :visible.sync="show_campaign_config"
            width="60%">
            <el-form @submit.prevent.native="updateCampaignSettings" label-position="top" :data="campaign">
                <el-form-item :label="$t('Campaign Title')">
                    <el-input v-model="campaign.title" :placeholder="$t('Internal Campaign Title')"/>
                </el-form-item>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button v-loading="updating" type="success" @click="updateCampaignSettings">
                    {{ $t('Save') }}
                </el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import CampaignTemplate from './CampaignTemplate';
import CampaignBodyComposer from './CampaignBodyComposer';
import Recipients from './Recipients';
import CampaignReview from './_components/CampaignReview';

export default {
    name: 'Campaign',
    components: {
        CampaignTemplate,
        Recipients,
        CampaignReview,
        CampaignBodyComposer
    },
    data() {
        return {
            activeStep: parseInt(this.$route.query.step) || 0,
            campaign_id: this.$route.params.id,
            campaign: null,
            dialogVisible: false,
            dialogTitle: this.$t('Edit Campaign'),
            loading: false,
            steps: [
                {
                    title: this.$t('Compose'),
                    description: this.$t('Compose Your Email Body')
                },
                {
                    title: this.$t('Subject & Settings'),
                    description: this.$t('Email Subject & Details')
                },
                {
                    title: this.$t('Recipients'),
                    description: this.$t('Select Email Recipients')
                },
                {
                    title: this.$t('Review & Send'),
                    description: this.$t('Cam_Send_osce')
                }
            ],
            show_campaign_config: false,
            updating: false
        };
    },
    methods: {
        targetStep(e, step) {
            if (step === 1 && !this.campaign.email_subject) {
                return;
            }
            if (step === 2 && this.campaign.recipients_count === 0) {
                return;
            }
            if (step === 3 && !this.campaign.scheduled_at) {
                return;
            }
            if (step !== this.activeStep) {
                this.activeStep = step;
                this.$router.push({
                    name: 'campaign',
                    query: {
                        step
                    }
                });
            }
        },
        next() {
            if (this.activeStep === 0) {
                this.unmountBlockEditor();
            }
            if (this.activeStep < 3) this.activeStep++;
            this.$router.push({
                name: 'campaign',
                query: {
                    step: this.activeStep
                }
            })
        },
        prev() {
            if (this.activeStep > 0) this.activeStep--;
            this.$router.push({
                name: 'campaign',
                query: {
                    step: this.activeStep
                }
            });
        },
        stepChange(step) {
            this.activeStep = step;
            this.$router.push({
                name: 'campaign',
                query: {
                    step: this.activeStep
                }
            });
        },
        backToCampaigns() {
            this.activeStep = 0;
            this.$router.push({
                name: 'campaigns',
                query: {t: (new Date()).getTime()}
            });
        },
        fetch() {
            this.$get(`campaigns/${this.campaign_id}`, {
                with: ['subjects', 'template']
            })
                .then(response => {
                    this.campaign = response.campaign;

                    if (this.campaign.status != 'draft') {
                        this.$router.push({
                            name: 'campaign-view',
                            params: {
                                id: this.campaign.id
                            }
                        });
                    }

                    this.changeTitle(this.campaign.title + ' - Campaign');
                })
                .catch(error => {
                    this.redirectToCampaignsWithWarning(error.message);
                });
        },
        redirectToCampaignsWithWarning(message) {
            if (this.$route.name !== 'campaign') {
                return;
            }

            this.$message(message, this.$t('Oops!'), {
                center: true,
                type: 'warning',
                confirmButtonText: this.$t('View Report'),
                dangerouslyUseHTMLString: true,
                callback: action => {
                    this.$router.push({
                        name: 'campaign-view',
                        params: {
                            id: this.campaign_id
                        },
                        query: {t: (new Date()).getTime()}
                    });
                }
            });
        },
        updateCampaignSettings() {
            this.updating = true;
            this.$put(`campaigns/${this.campaign_id}/title`, {
                title: this.campaign.title
            })
                .then(response => {
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                    this.show_campaign_config = false;
                });
        }
    },
    mounted() {
        this.fetch();
        this.changeTitle(this.$t('Campaign'));
    },
    beforeRouteLeave(to, from, next) {
        this.unmountBlockEditor();
        next();
    }
};
</script>

<style lang="scss">
.fluentcrm-campaign .steps-nav {
    margin: 0 auto;
    text-align: center;
}

.fluentcrm-campaign .step-container {
    margin-top: 30px;
}

.fluentcrm-campaign .action-buttons {
    margin: 0;
    text-align: right;
}

.fluentcrm-campaign .action-buttons .campaign-title {
    padding: 10px;
    float: left;
    font-weight: 500;
    font-size: 20px;
    display: inline-block;
}

.fluentcrm-campaign .action-buttons .campaign-title span.status {
    font-weight: normal;
    font-size: 12px;
    color: #909399;
}

.fluentcrm-campaign .action-buttons .campaign-title > span {
    cursor: pointer;
    font-weight: normal;
    font-size: 12px;
    color: #909399;
}

.fluentcrm-campaign .action-buttons .campaign-title > span > span {
    color: #409EFF;
}
</style>
