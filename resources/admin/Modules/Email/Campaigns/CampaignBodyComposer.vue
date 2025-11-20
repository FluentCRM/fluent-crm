<template>
    <div class="template">
        <email-block-composer @save="updateCampaign()" :show_audit="true" :extra_tags="extra_tags" :show_merge="true"
                              :enable_template_save="true" :enable_templates="true" :campaign="campaign">
            <template slot="fc_editor_actions">
                <el-button v-loading="loading" size="small" type="success" @click="maybeUpdateCampaign()">
                    {{ $t('Save') }}
                </el-button>
                <el-button v-loading="loading" size="small" type="primary" icon="el-icon-right"
                           @click="maybeNextStep()">{{ $t('Cam_Continue_S') }}
                </el-button>
            </template>
        </email-block-composer>
    </div>
</template>

<script type="text/babel">
import EmailBlockComposer from '@/Pieces/EmailElements/BlockComposer';

export default {
    name: 'CampaignBodyTemplate',
    props: ['campaign', 'extra_tags'],
    components: {
        EmailBlockComposer
    },
    data() {
        return {
            loading: false
        }
    },
    methods: {
        nextStep() {
            if (!this.campaign.email_body) {
                return this.$notify.error({
                    title: this.$t('Oops!'),
                    message: this.$t('Cam_Please_peb'),
                    offset: 19
                });
            }
            this.updateCampaign((response) => {
                this.$emit('next');
            });
        },
        updateCampaign(callback) {
            this.loading = true;
            const campaign = JSON.parse(JSON.stringify(this.campaign));
            delete campaign.template;
            const updateData = {
                next_step: 1,
                title: campaign.title,
                email_subject: campaign.email_subject,
                email_pre_header: campaign.email_pre_header,
                settings: campaign.settings,
                design_template: campaign.design_template,
                email_body: campaign.email_body,
                template_id: campaign.template_id,
                campaign_id: this.campaign.id
            }
            if (campaign._visual_builder_design && campaign.design_template == 'visual_builder') {
                updateData._visual_builder_design_string = JSON.stringify(campaign._visual_builder_design);
            }

            this.$post('campaigns/update-single-campaign', updateData)
                .then(response => {
                    if (callback) {
                        callback(response);
                    } else {
                        this.$notify.success(
                            {
                                message: this.$t('Cam_Email_bsu')
                            });
                    }
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        updateCampaignAjax(callback) {
            this.loading = true;
            const campaign = JSON.parse(JSON.stringify(this.campaign));
            delete campaign.template;

            const updateData = {
                next_step: 1,
                title: campaign.title,
                email_subject: campaign.email_subject,
                email_pre_header: campaign.email_pre_header,
                settings: campaign.settings,
                design_template: campaign.design_template,
                email_body: campaign.email_body,
                template_id: campaign.template_id
            }

            if (campaign._visual_builder_design && campaign.design_template == 'visual_builder') {
                updateData._visual_builder_design_string = JSON.stringify(campaign._visual_builder_design);
            }

            window.jQuery.post(window.ajaxurl, {
                action_data: JSON.stringify(updateData),
                campaign_id: campaign.id,
                action: 'fluentcrm_save_campaign_email_body',
                query_timestamp: Date.now()
            })
                .then(response => {
                    if (callback) {
                        callback(response);
                    } else {
                        this.$notify.success(
                            {
                                message: this.$t('Cam_Email_bsu')
                            });
                    }
                })
                .catch(error => {
                    this.handleError(error.responseJSON);
                })
                .always(() => {
                    this.loading = false;
                });
        },
        maybeUpdateCampaign() {
            if (this.campaign.design_template == 'visual_builder') {
                this.$bus.$emit('getVisualData', {
                    reference: 'save'
                });
            } else {
                this.updateCampaign();
            }
        },
        maybeNextStep() {
            if (this.campaign.design_template == 'visual_builder') {
                const that = this;
                this.$bus.$emit('getVisualData', {
                    callback: function (data) {
                        that.nextStep();
                    },
                    reference: 'update_only'
                });
            } else {
                this.nextStep();
            }
        },
        initKeyboardSave(e) {
            if ((window.navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.maybeUpdateCampaign();
            }
        }
    },
    mounted() {
        document.addEventListener('keydown', this.initKeyboardSave);
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.initKeyboardSave);
    }
};
</script>
