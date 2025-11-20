<template>
    <div>
        <el-form label-position="top" :model="campaign">
            <el-row :gutter="20">
                <el-col :sm="24" :md="12">
                    <el-form-item :label="$t('Email Subject')">
                        <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" popper_extra="fc_with_c_fields" v-if="email_subject_status"
                                       :placeholder="$t('Email Subject')" :data="smartcodes"
                                       v-model="campaign.email_subject"/>
                    </el-form-item>
                </el-col>
                <el-col :sm="24" :md="12">
                    <el-form-item :label="$t('Email Pre-Header')">
                        <el-input type="textarea"
                                  :placeholder="$t('Email Pre-Header')"
                                  :rows="2"
                                  v-model="campaign.email_pre_header"
                        ></el-input>
                    </el-form-item>
                </el-col>
            </el-row>
            <el-form-item>
                <el-checkbox true-label="1" false-label="0" v-model="campaign.utm_status">
                    {{ $t('Ema_Add_UPFU') }}
                </el-checkbox>
            </el-form-item>
            <div v-if="campaign.utm_status == 1 || campaign.utm_status == '1'" style="margin-bottom: 20px;"
                 class="fc_form_items_inline fc_is_highlighted">
                <el-form-item :label="$t('Campaign Source (required)')">
                    <el-input :placeholder="$t('The referrer: (e.g. google, newsletter)')"
                              v-model="campaign.utm_source"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Campaign Medium (required)')">
                    <el-input :placeholder="$t('Marketing medium: (e.g. cpc, banner, email)')"
                              v-model="campaign.utm_medium"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Campaign Name (required)')">
                    <el-input :placeholder="$t('Product, promo code, or slogan (e.g. spring_sale)')"
                              v-model="campaign.utm_campaign"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Campaign Term')">
                    <el-input :placeholder="$t('Identify the paid keywords')"
                              v-model="campaign.utm_term"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Campaign Content')">
                    <el-input :placeholder="$t('Use to differentiate ads')"
                              v-model="campaign.utm_content"></el-input>
                </el-form-item>
            </div>
        </el-form>
        <email-block-composer :disabled_templates="{ visual_builder: true }" :show_audit="true" @save="save()" @changed="handleChangeContent()" :show_merge="true"
                              :enable_template_save="true"
                              @template_inserted="resetSubject()" :enable_templates="true" :campaign="campaign">
            <template slot="fc_editor_actions">
                <el-button @click="maybeSave()" size="small" type="success">{{ $t('Save') }}</el-button>
            </template>
        </email-block-composer>
        <send-test-email btn_type="danger" :campaign="{ email_subject: campaign.email_subject,
                    email_pre_header: campaign.email_pre_header,
                    email_body: campaign.email_body,
                    design_template: campaign.design_template,
                    settings: campaign.settings }"/>
    </div>
</template>

<script type="text/babel">
import EmailBlockComposer from '@/Pieces/EmailElements/BlockComposer';
import InputPopover from '@/Pieces/InputPopover';
import SendTestEmail from '@Pieces/TestEmail.vue';

export default {
    name: 'CampaignName',
    components: {
        EmailBlockComposer,
        InputPopover,
        SendTestEmail
    },
    props: ['campaign'],
    data() {
        return {
            loading: false,
            saving: false,
            app_loaded: false,
            smartcodes: window.fcAdmin.globalSmartCodes,
            email_subject_status: true,
            is_dirty: ''
        }
    },
    methods: {
        maybeSave() {
            if (this.campaign.design_template == 'visual_builder') {
                this.$bus.$emit('getVisualData', {});
            } else {
                this.save();
            }
        },
        save() {
            if (!this.campaign.email_body) {
                return this.$notify.error({
                    title: this.$t('Oops!'),
                    message: this.$t('Cam_Please_peb'),
                    offset: 19
                });
            }

            if (!this.campaign.email_subject) {
                return this.$notify.error({
                    title: this.$t('Oops!'),
                    message: this.$t('Cam_Please_peS'),
                    offset: 19
                });
            }
            this.saving = true;
            this.is_dirty = false;

            const data = {
                campaign: JSON.stringify(this.campaign),
                campaign_id: this.campaign.id
            }

            this.$post('recurring-campaigns/update-campaign-data', data).then(response => {
                this.$notify.success(response.message);
            })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        resetSubject() {

        },
        handleChangeContent() {

        },
        initKeyboardSave(e) {
            if ((window.navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.maybeSave();
            }
        }
    },
    mounted() {
        this.changeTitle('Email - ' + this.campaign.title);
        document.addEventListener('keydown', this.initKeyboardSave);
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.initKeyboardSave);
    }
}
</script>
