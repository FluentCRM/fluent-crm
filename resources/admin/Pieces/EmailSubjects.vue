<template>
    <div class="fluentcrm_email_composer">
        <el-form :label-position="label_align" label-width="220px" :model="campaign">
            <el-form-item :label="$t('Email Subject')">
                <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" popper_extra="fc_with_c_fields" :placeholder="$t('Email Subject')" :data="smartcodes" v-model="campaign.email_subject"/>
                <p style="margin: 0" v-if="multi_subject_status">{{ $t('A_B_Testing_Alert') }}</p>
            </el-form-item>

            <template v-if="multi_subject">
                <el-form-item>
                    <el-checkbox @change="maybeResetSubject()" v-model="multi_subject_status">
                        {{$t('Ema_Enable_Atfes')}}
                    </el-checkbox>
                </el-form-item>

                <template v-if="multi_subject_status">
                    <div v-if="has_campaign_pro">
                        <p>{{$t('Ema_Your_ppwbctpair')}}</p>
                        <table class="fc_horizontal_table">
                            <thead>
                            <tr>
                                <th style="width: 60%">{{$t('Subject')}}</th>
                                <th>{{$t('Priority (%)')}}</th>
                                <th>{{$t('Actions')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="(subject,subjectIndex) in campaign.subjects" :key="subjectIndex">
                                <td>
                                    <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" :placeholder="$t('Subject Test')+' '+ (subjectIndex + 1)" :data="smartcodes"
                                                   v-model="subject.value"/>
                                </td>
                                <td>
                                    <el-input-number :min="1" :max="99" v-model="subject.key"/>
                                </td>
                                <td>
                                    <el-button :disabled="campaign.subjects.length == 1"
                                               @click="removeSubject(subjectIndex)" type="danger" size="small"
                                               icon="el-icon-delete"></el-button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="text-align-right">
                            <el-button @click="addSubject" type="info" size="small">{{$t('Add More')}}</el-button>
                        </div>
                    </div>
                    <div v-else>
                        <ab-email-subject-promo />
                    </div>
                </template>
            </template>

            <el-form-item :label="$t('Email Pre-Header')">
                <el-input type="textarea"
                          :placeholder="$t('Email Pre-Header')"
                          :rows="2"
                          v-model="campaign.email_pre_header"
                ></el-input>
            </el-form-item>

            <template v-if="mailer_settings && campaign.settings.mailer_settings">
                <mailer-config class="fc_its_gray" :mailer_settings="campaign.settings.mailer_settings" />
            </template>

            <el-form-item>
                <el-checkbox true-label="1" false-label="0" v-model="campaign.utm_status">
                    {{$t('Ema_Add_UPFU')}}
                </el-checkbox>
            </el-form-item>

            <div class="fluentcrm_highlight_white" v-if="campaign.utm_status == 1 || campaign.utm_status == '1'">
                <el-row :gutter="20">
                    <el-col :md="12" :sm="24">
                        <el-form-item :label="$t('Campaign Source (required)')">
                            <el-input :placeholder="$t('The referrer: (e.g. google, newsletter)')"
                                      v-model="campaign.utm_source"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :md="12" :sm="24">
                        <el-form-item :label="$t('Campaign Medium (required)')">
                            <el-input :placeholder="$t('Marketing medium: (e.g. cpc, banner, email)')"
                                      v-model="campaign.utm_medium"></el-input>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :md="12" :sm="24">
                        <el-form-item :label="$t('Campaign Name (required)')">
                            <el-input :placeholder="$t('Product, promo code, or slogan (e.g. spring_sale)')"
                                      v-model="campaign.utm_campaign"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :md="12" :sm="24">
                        <el-form-item :label="$t('Campaign Term')">
                            <el-input :placeholder="$t('Identify the paid keywords')" v-model="campaign.utm_term"></el-input>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col :md="12" :sm="24">
                        <el-form-item :label="$t('Campaign Content')">
                            <el-input :placeholder="$t('Use to differentiate ads')" v-model="campaign.utm_content"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :md="12" :sm="24">
                        <span> </span>
                    </el-col>
                </el-row>
            </div>
        </el-form>
    </div>
</template>
<script type="text/babel">
import InputPopover from '@/Pieces/InputPopover';
import AbEmailSubjectPromo from '@/Modules/Promos/AbEmailSubjectPromo';
import MailerConfig from '@/Pieces/FormElements/_MailerConfig';

export default {
    name: 'EmailSubjects',
    props: ['campaign', 'label_align', 'multi_subject', 'mailer_settings'],
    components: {
        InputPopover,
        AbEmailSubjectPromo,
        MailerConfig
    },
    data() {
        return {
            loading: false,
            codes_ready: false,
            smartcodes: window.fcAdmin.globalSmartCodes,
            hide_subject: false,
            multi_subject_status: !!(this.campaign.subjects && this.campaign.subjects.length)
        }
    },
    methods: {
        addSubject() {
            this.campaign.subjects.push({
                key: 50,
                value: ''
            });
        },
        removeSubject(index) {
            this.campaign.subjects.splice(index, 1);
        },
        maybeResetSubject() {
            if (this.multi_subject_status && this.has_campaign_pro) {
                if (!this.campaign.subjects || !this.campaign.subjects.length) {
                    this.campaign.subjects = [];
                    this.campaign.subjects.push({
                        key: 50,
                        value: ''
                    });
                }
            } else {
                this.campaign.subjects = [];
            }
        }
    },
    mounted() {

    }
}
</script>
