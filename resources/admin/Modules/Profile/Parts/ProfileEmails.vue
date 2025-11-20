<template>
    <div class="fluentcrm_databox">

        <el-row :gutter="20">
            <el-col :span="12">
                <h3 style="margin-top: 6px;">
                    {{ $t('Pro_Emails_fdcaa') }}
                </h3>
            </el-col>
            <el-col id="fc_sub_email_actions" class="text-align-right" :span="12">

                <el-popover
                    v-if="hasPermission('fcrm_manage_contacts') && emailTab === 'crm'"
                    :title="$t('Filter')"
                    :width="240"
                    placement="bottom"
                    popper-class="fcrm_sort_popover"
                    trigger="click"
                >
                    <template #reference>
                        <el-button :title="$t('Sort')" class="fcrm-sort-btn" size="mini"><i
                            class="el-icon-sort"></i></el-button>
                    </template>

                    <div class="fcrm_sorting_action_wrap">
                        <el-radio-group v-model="emailFilter" class="fc_filter_emails" size="small" @change="fetch">
                            <el-radio label="all">{{ $t('All') }}</el-radio>
                            <el-radio label="open">{{ $t('Opened') }}</el-radio>
                            <el-radio label="click">{{ $t('Clicked') }}</el-radio>
                            <el-radio label="unopened">{{ $t('Unopened') }}</el-radio>
                        </el-radio-group>
                    </div>
                </el-popover>

                <el-button :disabled="!(subscriber.status == 'subscribed' || subscriber.status == 'transactional')"
                           v-if="has_campaign_pro && hasPermission('fcrm_manage_contacts') && emailTab == 'crm'"
                           @click="openCustomEmailModal()" size="mini" type="primary">
                    {{ $t('Send Email') }}
                </el-button>
                <el-switch v-if="appVars.has_fluentsmtp" v-model="emailTab" active-text="FluentSMTP Logs"
                           active-value="fluentsmtp" inactive-value="crm" style="margin-left: 10px;"
                           @change="fetch"></el-switch>
            </el-col>
        </el-row>

        <el-table
            v-if="emailTab === 'crm'"
            :empty-text="$t('No Data Available')"
            stripe border
            :data="emails"
            style="width: 100%" v-loading="loading || resending"
            @selection-change="handleSelectionChange"
        >
            <el-table-column
                type="selection"
                width="55">
            </el-table-column>
            <el-table-column :label="$t('Subject')">
                <template slot-scope="scope">
                    {{ scope.row.email_subject }}
                    <span :title="$t('Total Clicks')" class="ns_counter"><i
                        class="el-icon el-icon-position"></i> {{ scope.row.click_counter || 0 }}</span>
                    <span :title="$t('Email opened')" v-show="scope.row.click_counter || scope.row.is_open == 1"
                          class="ns_counter"><i class="el-icon el-icon-folder-opened"></i></span>
                </template>
            </el-table-column>
            <el-table-column width="190" :label="$t('Date')">
                <template slot-scope="scope">
                    {{ scope.row.scheduled_at }}
                </template>
            </el-table-column>
            <el-table-column width="120" :label="$t('Status')" align="center">
                <template slot-scope="scope">
                                    <span :class="'status-' +scope.row.status">
                                        {{ trans(scope.row.status) | ucFirst }}
                                    </span>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Preview')" width="180" align="center">
                <template slot-scope="scope">
                    <el-button
                        size="mini"
                        type="primary"
                        icon="el-icon-view"
                        @click="previewEmail(scope.row.id)"
                    />
                    <el-button
                        v-if="(scope.row.status == 'sent' || scope.row.status == 'failed') && scope.row.campaign_id"
                        size="mini"
                        type="info"
                        @click="resendEmail(scope.row)"
                        icon="el-icon-refresh-right"
                    >{{ $t('Resend') }}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>

        <SMTPEmailLogs v-else-if="emailTab === 'fluentsmtp'" v-loading="loading || resending" :emails="smtpEmailLogs"/>

        <el-row>
            <el-col :md="4" :sm="24">

                <confirm @yes="deleteSelected()" placement="top-start" v-if="selections.length">
                    <el-button
                        v-loading="deleting"
                        v-if="selections.length"
                        style="margin-top: 10px;"
                        size="mini"
                        type="danger"
                        slot="reference"
                        icon="el-icon-delete"
                    >
                        {{ $t('Delete Selected') }}
                    </el-button>
                </confirm>
                &nbsp;
            </el-col>
            <el-col :md="20" :sm="24">
                <pagination :pagination="pagination" @fetch="fetch"/>
            </el-col>
        </el-row>

        <template v-if="has_campaign_pro">
            <hr style="margin: 40px -20px 30px; border-color: #eaeef5;"/>
            <email-sequences :subscriber_id="subscriber_id"/>
            <hr style="margin: 40px -20px 30px; border-color: #eaeef5;"/>
            <profile-automations :subscriber_id="subscriber_id"/>
        </template>

        <email-preview :preview="preview"/>

        <el-dialog
            class="fc_funnel_block_modal"
            :visible.sync="sendEmailModal"
            width="60%"
            :append-to-body="true"
            :destroy-on-close="true"
            :close-on-click-modal="false"
            :before-close="fireCloseEmailComposer"
            :title="$t('Send Custom Email')">
            <div v-if="loadingCustomEmailSettings">
                <h3>{{ $t('Loading Settings...') }}</h3>
            </div>
            <div v-else>
                <div v-if="sendEmailModal" class="fc_block_white">
                    <email-composer
                        :enable_test="true"
                        :disable_fixed="true"
                        class="fc_into_modal"
                        :campaign="custom_email"
                        label_align="top">
                        <template slot="after_block_composer">
                            <el-form style="margin-bottom: 20px;" v-if="!loadingCustomEmailSettings" v-model="custom_email.settings.mailer_settings">
                                <mailer-config :mailer_settings="custom_email.settings.mailer_settings"/>
                                <el-checkbox true-label="yes" false-label="no" v-model="is_transactional">
                                    {{ $t('Mark_Transactional') }}
                                    <span class="fc_checkbox_note">({{ $t('transaction_checkbox_note') }})</span>
                                </el-checkbox>
                            </el-form>
                        </template>
                    </email-composer>
                </div>
                <span slot="footer" class="dialog-footer">
                    <el-button size="small" @click="fireCloseEmailComposer(); sendEmailModal = false">
                        {{ $t('Cancel') }}
                    </el-button>
                    <el-button v-loading="sending_custom_email" size="small" type="primary" @click="sendCustomEmail()">
                        {{ $t('Send Email') }}
                    </el-button>
                </span>
            </div>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import EmailPreview from '@/Modules/Email/Campaigns/_components/EmailPreview';
import EmailSequences from './ProfileEmailSequences';
import ProfileAutomations from './ProfileAutomations';
import EmailComposer from '@/Pieces/EmailComposer';
import Confirm from '@/Pieces/Confirm';
import SMTPEmailLogs from './_SMTPEmailLogs';
import MailerConfig from '@/Pieces/FormElements/_MailerConfig';

export default {
    name: 'ProfileEmails',
    props: ['subscriber_id', 'subscriber'],
    components: {
        SMTPEmailLogs,
        Pagination,
        EmailPreview,
        EmailSequences,
        ProfileAutomations,
        EmailComposer,
        Confirm,
        MailerConfig
    },
    data() {
        return {
            emailTab: 'crm',
            loading: false,
            emails: [],
            smtpEmailLogs: [],
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            preview: {
                id: null,
                isVisible: false
            },
            sendEmailModal: false,
            custom_email: {},
            resending: false,
            selections: [],
            deleting: false,
            loadingCustomEmailSettings: false,
            sending_custom_email: false,
            emailFilter: 'all',
            is_transactional: 'no'
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get(`subscribers/${this.subscriber_id}/emails`, {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                filter: this.emailFilter,
                tab: this.emailTab
            })
                .then(response => {
                    this.emails = response.emails.data;
                    if (this.emailTab === 'fluentsmtp') {
                        this.smtpEmailLogs = this.formatLogs(response?.emails?.data);
                    }
                    this.pagination.total = response.emails.total;
                })
                .catch((errors) => {
                    console.log(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        resendEmail(email) {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('_Ca_Please_utptutf'));
                return false;
            }
            this.resending = true;
            this.$post(`campaigns-pro/${email.campaign_id}/resend-emails`, {
                email_ids: [email.id]
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.resending = false;
                });
        },
        previewEmail(emailId) {
            this.preview.id = emailId;
            this.preview.isVisible = true;
        },
        sendCustomEmail() {
            this.sending_custom_email = true;
            this.custom_email.settings.is_transactional = this.is_transactional;
            this.$post(`subscribers/${this.subscriber_id}/emails/send`, {
                campaign: this.custom_email
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fireCloseEmailComposer();
                    this.sendEmailModal = false;
                    this.custom_email = {};
                    this.fetch();
                })
                .catch((errors) => {
                    console.log(errors);
                })
                .finally(() => {
                    this.sending_custom_email = false;
                });
        },
        handleSelectionChange(val) {
            this.selections = val;
        },
        deleteSelected() {
            this.deleting = true;
            const selectionIds = this.selections.map(item => item.id);
            this.$del(`subscribers/${this.subscriber_id}/emails`, {
                email_ids: selectionIds
            })
                .then((response) => {
                    this.selections = [];
                    this.$notify.success(response.message);
                    this.fetch();
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        openCustomEmailModal() {
            this.loadingCustomEmailSettings = true;
            this.custom_email.email_body = '';
            this.sendEmailModal = true;
            if (window.fc_subscriber_email_mock) {
                this.custom_email = JSON.parse(JSON.stringify(window.fc_subscriber_email_mock));
                if (!this.custom_email.settings.mailer_settings) {
                    this.custom_email.settings.mailer_settings = {
                        from_name: '',
                        from_email: '',
                        reply_to_name: '',
                        reply_to_email: '',
                        is_custom: 'no'
                    };
                } else {
                    this.custom_email.settings.mailer_settings.is_custom = 'no';
                }

                this.loadingCustomEmailSettings = false;
                return;
            }

            this.$get(`subscribers/${this.subscriber_id}/emails/template-mock`)
                .then(response => {
                    const mailerMock = response.email_mock;
                    if (!mailerMock.settings.mailer_settings) {
                        mailerMock.settings.mailer_settings = {
                            from_name: '',
                            from_email: '',
                            reply_to_name: '',
                            reply_to_email: '',
                            is_custom: 'no'
                        };
                    }
                    window.fc_subscriber_email_mock = mailerMock;
                    this.custom_email = mailerMock;
                })
                .catch((errors) => {
                    console.log(errors);
                })
                .finally(() => {
                    this.loadingCustomEmailSettings = false;
                });
        },
        fireCloseEmailComposer(done = false) {
            this.unmountBlockEditor();
            if (done) {
                done();
            }
        },
        formatLogs(logs) {
            jQuery.each(logs, (i, log) => {
                logs[i] = this.formatLog(log);
            });

            return logs;
        },
        formatLog(log) {
            log.to = this.formatAddresses(log.to);
            return log;
        },
        formatAddresses(addresses) {
            if (!addresses) {
                return '';
            }

            if (this.isEmptyValue(addresses)) {
                return '';
            }

            if (typeof addresses == 'string') {
                return addresses;
            }

            const result = [];
            jQuery.each(addresses, (i, val) => {
                if (val.name) {
                    result[i] = this.escapeHtml(
                        `${val.name} <${val.email}>`
                    );
                } else {
                    result[i] = this.escapeHtml(val.email);
                }
            });
            return result.join(', ');
        },
        escapeHtml(text) {
            if (!text) {
                return text;
            }
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return text.replace(/[&<>"']/g, (m) => map[m]);
        }
    },
    mounted() {
        this.fetch();
        this.doAction('fluent_crm_profile_emails_mounted', this);
    }
}
</script>

<style lang="scss">
#fc_sub_email_actions {
    .fc_filter_emails {
        .el-radio-button {
            &.non-open {
                .el-radio-button__inner {
                    position: relative;

                    .non-open-icons span {
                        position: absolute;
                        right: 10px;
                        top: 5px;
                        border: 1px solid #e55353;
                        color: #e55353;
                        padding: 0;
                        width: 10px;
                        height: 10px;
                        line-height: 7px;
                        text-align: center;
                        font-size: 10px;
                        border-radius: 50%;
                        background: #ffffff;
                        transform: rotate(45deg);
                    }
                }
            }
        }
    }
}

</style>
