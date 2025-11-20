<template>
    <div class="template">
        <div class="fc_narrow_box fc_white_inverse">
            <el-form label-position="top" :model="campaign">
                <email-subjects :mailer_settings="true" :multi_subject="true" label_align="top" :campaign="campaign"/>
                <el-form-item>
                    <test-email :campaign="campaign" />
                </el-form-item>
            </el-form>
            <p style="background: white;" class="el-alert el-alert--error is-light" v-if="inline_errors" v-html="inline_errors"></p>
        </div>

        <el-row style="max-width: 860px; margin: 0 auto" :gutter="20">
            <el-col :span="12">
                <el-button
                    size="small"
                    type="text"
                    @click="goToPrev()"
                > {{$t('Back')}}
                </el-button>
            </el-col>
            <el-col :span="12" class="text-align-right">
                <el-button v-loading="loading" @click="nextStep()" size="small" type="success">
                    {{$t('Cam_Continue_TNS_')}}
                </el-button>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
    import EmailSubjects from '@Pieces/EmailSubjects';
    import TestEmail from '@Pieces/TestEmail.vue';

    export default {
        name: 'CampaignTemplate',
        props: ['campaign', 'label_align'],
        components: {
            EmailSubjects,
            TestEmail
        },
        data() {
            return {
                fetchingTemplate: false,
                editor_status: true,
                loading: false,
                smart_codes: [],
                inline_errors: ''
            };
        },
        methods: {
            nextStep() {
                this.inline_errors = '';
                if (!this.campaign.email_subject) {
                    return this.$notify.error({
                        title: this.$t('Oops!'),
                        message: this.$t('Cam_Please_peS'),
                        offset: 19
                    });
                }

                if (this.campaign.subjects && this.campaign.subjects.length) {
                    const validSubjects = this.campaign.subjects.filter((subject) => {
                        return subject.key && subject.value;
                    });

                    if (!validSubjects.length) {
                        return this.$notify.error({
                            title: this.$t('Oops!'),
                            message: this.$t('Cam_Please_pSLfAT'),
                            offset: 19
                        });
                    }
                }

                this.updateCampaign();
            },
            updateCampaign() {
                this.loading = true;
                const campaign = JSON.parse(JSON.stringify(this.campaign));

                const updateData = {
                    title: campaign.title,
                    update_subjects: true,
                    next_step: 2,
                    email_subject: campaign.email_subject,
                    email_pre_header: campaign.email_pre_header,
                    utm_status: campaign.utm_status,
                    utm_source: campaign.utm_source,
                    utm_medium: campaign.utm_medium,
                    utm_campaign: campaign.utm_campaign,
                    utm_term: campaign.utm_term,
                    utm_content: campaign.utm_content,
                    settings: campaign.settings,
                    subjects: campaign.subjects
                };

                this.$put(`campaigns/${campaign.id}`, updateData)
                    .then(response => {
                        this.campaign.subjects = response.campaign.subjects;
                        this.$emit('next', 1);
                    })
                    .catch(error => {
                        if (error && error.compliance_failed) {
                           this.inline_errors = error.message;
                        } else {
                            this.inline_errors = '';
                        }
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            goToPrev() {
                this.$emit('prev', 0);
            }
        }
    };
</script>
