<template>
    <div class="fc_create_flow">
        <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
            <el-breadcrumb-item :to="{ name: 'recurring_campaigns' }">
                {{ $t('Recurring Email Campaigns') }}
            </el-breadcrumb-item>
            <el-breadcrumb-item>
                {{ $t('Create a recurring email broadcast') }}
            </el-breadcrumb-item>
        </el-breadcrumb>

        <el-steps :active="activeStep" simple finish-status="success">
            <el-step v-for="(step, stepIndex) in steps" :key="stepIndex" :title="step.title"
                     :description="step.description"></el-step>
        </el-steps>
        <el-form label-position="top" :data="campaign">
            <div v-loading="loading" class="fluentcrm_body fluentcrm_body_boxed fc_flow_items">
                <div class="step-container" v-if="activeStep == 0">
                    <div class="fc_flow_intro fc_flow_settings">
                        <h3>{{ $t('Basic_Info_Of_Rec_Camp') }}</h3>
                        <basic-settings :campaign="campaign" />
                    </div>
                    <div class="flow_nav">
                        <el-row :gutter="20">
                            <el-col :span="12">
                                &nbsp;
                            </el-col>
                            <el-col :span="12" class="text-align-right">
                                <el-button v-loading="loading" @click="nextStep(1)" size="small" type="success">
                                    {{ $t('Continue to next step [conditions]') }}
                                </el-button>
                            </el-col>
                        </el-row>
                    </div>
                </div>
                <div class="step-container" v-if="activeStep == 1">
                    <div class="fc_flow_conditions fc_flow_settings">
                        <h3>Conditions for sending emails {{ campaign.settings.scheduling_settings.type }} email</h3>
                        <conditions-settings :sending_conditions="campaign.settings.sending_conditions" />
                    </div>
                    <div class="flow_nav">
                        <el-row :gutter="20">
                            <el-col :span="12">
                                <el-button
                                    size="small"
                                    type="text"
                                    @click="goToPrev(0)"
                                > {{ $t('Back') }}
                                </el-button>
                            </el-col>
                            <el-col :span="12" class="text-align-right">
                                <el-button v-loading="loading" @click="nextStep(2)" size="small" type="success">
                                    {{ $t('Cam_Continue_TNS_') }}
                                </el-button>
                            </el-col>
                        </el-row>
                    </div>
                </div>
                <div class="step-container" v-if="activeStep == 2">
                    <div class="fc_flow_subscribers fc_flow_settings">
                        <h3>{{ $t('Select Subscribers') }}</h3>
                        <recipient-tagger-form v-model="campaign.settings.subscribers_settings"/>
                    </div>
                    <el-row :gutter="20">
                        <el-col :span="12">
                            <el-button
                                size="small"
                                type="text"
                                @click="goToPrev(1)"
                            > {{ $t('Back') }}
                            </el-button>
                        </el-col>
                        <el-col :span="12" class="text-align-right">
                            <el-button v-loading="loading" @click="createCampaign()" size="small" type="success">
                                {{ $t('Create Recurring Campaign') }}
                            </el-button>
                        </el-col>
                    </el-row>
                </div>
            </div>
        </el-form>
    </div>
</template>
<script type="text/babel">
import RecipientTaggerForm from '@/Pieces/RecipientTaggerForm';
import BasicSettings from './parts/_basic.vue';
import ConditionsSettings from './parts/_conditions.vue';

export default {
    name: 'CreateRecurringCampaignFlow',
    components: {
        RecipientTaggerForm,
        BasicSettings,
        ConditionsSettings
    },
    data() {
        return {
            loading: false,
            creating: false,
            activeStep: 0,
            steps: [
                {
                    title: this.$t('Start'),
                    description: this.$t('Provide campaign details')
                },
                {
                    title: this.$t('Conditions'),
                    description: this.$t('Select automation conditions')
                },
                {
                    title: this.$t('Recipients'),
                    description: this.$t('Select Email Recipients')
                }
            ],
            campaign: {
                title: '',
                email_subject: '',
                email_pre_header: '',
                email_body: '',
                status: 'draft',
                settings: {
                    subscribers_settings: {
                        subscribers: [
                            {
                                list: 'all',
                                tag: 'all'
                            }
                        ],
                        excludedSubscribers: [
                            {
                                list: null,
                                tag: null
                            }
                        ],
                        sending_filter: 'list_tag',
                        dynamic_segment: {
                            id: '',
                            slug: ''
                        },
                        advanced_filters: [
                            []
                        ]
                    },
                    scheduling_settings: {
                        type: 'weekly',
                        day: '',
                        time: '',
                        send_automatically: 'yes'
                    },
                    sending_conditions: [
                        [
                            {
                                object_type: 'cpt',
                                object_name: 'post',
                                object_key: 'post_date',
                                comparison_type: 'within_days',
                                compare_value: 7
                            }
                        ]
                    ]
                }
            }
        }
    },
    methods: {
        nextStep(step) {
            if (step === 1) {
                if (!this.campaign.title) {
                    this.$notify.error(this.$t('Please provide an unique title'));
                    return;
                }

                if (!this.campaign.settings.scheduling_settings.time) {
                    this.$notify.error(this.$t('Please provide a time'));
                    return;
                }

                if (this.campaign.settings.scheduling_settings.type == 'weekly' && !this.campaign.settings.scheduling_settings.day) {
                    this.$notify.error(this.$t('Please provide a day'));
                    return;
                }
            } else if (step == 2) {
                if (this.campaign.settings.sending_conditions.length) {
                    const firstCompareValue = this.campaign.settings.sending_conditions[0][0].compare_value;
                    if (firstCompareValue < 1) {
                        this.$notify.error(this.$t('Please provide condition value'));
                        return;
                    }
                }
            }

            this.activeStep = step;
        },
        goToPrev(step) {
            this.activeStep = step;
        },
        createCampaign() {
            this.creating = true;
            this.$post('recurring-campaigns', {
                campaign: JSON.stringify(this.campaign)
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'view_recurring_campaign',
                        params: {
                            campaign_id: response.campaign_id
                        }
                    })
                })
                .catch((errors) => {
                    this.handleError(errors);
                    if (errors.go_to_step === 0 || errors.go_to_step) {
                        this.activeStep = errors.go_to_step;
                    }
                })
                .finally(() => {
                    this.creating = false;
                });
        }
    }
}
</script>
