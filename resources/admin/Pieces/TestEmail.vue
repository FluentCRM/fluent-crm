<template>
    <el-popover
        :placement="placement"
        width="400"
        popper-class="fcrm-send-test-email-popover"
        trigger="click">
        <div>
            <p>{{$t('Cam_Type_cetstolbtsc')}}</p>
            <el-input :placeholder="$t('Email Address')" v-model="test_email">
                <template slot="append">
                    <el-button :disabled="sending_test" v-loading="sending_test" @click="sendTestEmail()" type="success">{{$t('Send')}}</el-button>
                </template>
            </el-input>
        </div>
        <el-button :type="btn_type" slot="reference" size="small">
            {{btn_text || $t('Send a test email')}}
        </el-button>
        <p style="font-size: 90%;">{{ $t('Some_SmartCode_Not_Work_Alert') }}</p>
    </el-popover>
</template>

<script type="text/babel">
const userEmail = window.fcAdmin.auth.email;

export default {
    name: 'SendTestEmail',
    props: {
        campaign: {
            type: Object,
            default() {
                return {}
            }
        },
        btn_text: {
            type: String,
            default() {
                return '';
            }
        },
        btn_type: {
            type: String,
            default() {
                return 'default';
            }
        },
        placement: {
            type: String,
            default() {
                return 'top';
            }
        }
    },
    data() {
        return {
            sending_test: false,
            test_email: userEmail
        }
    },
    methods: {
        sendTestEmail() {
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

            window.last_fc_test_email = this.test_email;

            this.sending_test = true;
            this.$post('campaigns/send-test-email', {
                campaign: {
                    id: this.campaign.id,
                    settings: this.campaign.settings,
                    email_subject: this.campaign.email_subject,
                    email_pre_header: this.campaign.email_pre_header,
                    email_body: this.campaign.email_body,
                    design_template: this.campaign.design_template
                },
                test_campaign: 'yes',
                email: this.test_email
            })
                .then(response => {
                    this.$notify.success(response.message);
                })
                .catch(error => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.sending_test = false;
                });
        }
    },
    mounted() {
        if (window.last_fc_test_email) {
            this.test_email = window.last_fc_test_email;
        }
    }
}
</script>
