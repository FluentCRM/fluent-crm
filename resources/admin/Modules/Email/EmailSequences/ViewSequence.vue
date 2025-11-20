<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb
                    class="fluentcrm_spaced_bottom"
                    separator="/"
                    :style="{ display: 'flex', alignItems: 'center' }"
                >
                    <el-breadcrumb-item :to="{ name: 'email-sequences' }">
                        {{$t('Email Sequences')}}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        <el-input
                            ref="titleInput"
                            size="mini"
                            v-if="show_title_input"
                            @blur="handleTitleBlur"
                            :placeholder="$t('Internal Title')"
                            v-model="sequence.title"
                        />
                        <span v-else @click="showInputField">{{ sequence.title }}</span>
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button v-if="hasPermission('fcrm_manage_emails')" @click="showSequenceSettings = true" type="info" size="small"
                           icon="el-icon-setting"></el-button>
                <div v-if="hasPermission('fcrm_manage_emails')" class="text-align-center ml-5">
                    <el-button @click="toSequenceEmailEdit(0)" size="small" icon="el-icon-plus" type="primary">
                        {{$t('Add a Sequence Email')}}
                    </el-button>
                </div>

                <el-button @click="gotToSubscribers()" size="small">
                    {{$t('View Subscribers')}}
                </el-button>

                <el-button v-if="hasPermission('fcrm_manage_emails')" @click="reapplySequence()" type="success" size="small">
                    {{$t('Re-apply Sequence')}}
                </el-button>
            </div>
        </div>
        <div class="fluentcrm_body">
            <div class="fluentcrm_body" style="position: relative;">
                <div v-if="loading" class="fc_loading_bar">
                    <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
                </div>
                <el-skeleton style="padding: 20px;" v-if="loading || duplicating" :rows="7"></el-skeleton>

                <div v-if="!sequence_emails.length && !loading">
                    <div class="fluentcrm_hero_box">
                        <h2>{{ $t('All_Looks_lydnsasey') }}</h2>
                        <el-button icon="el-icon-plus" size="small" type="success" @click="toSequenceEmailEdit(0)">
                            {{ $t('All_Create_YFES') }}
                        </el-button>
                    </div>
                </div>

                <div v-else>
                    <div class="fluentcrm_title_cards">
                        <div class="fc_sequence_section" v-for="(sequence_email, sequenceIndex) in sequence_emails" :key="sequence_email.id">
                            <div class="fluentcrm_title_card">
                                <el-row :gutter="20" class="d-flex items-center fluentcrm_title_card_row">
                                    <el-col :sm="24" :md="12">
                                        <span class="fc_seq_number">{{sequenceIndex + 1}}</span>
                                        <div class="fluentcrm_card_desc">
                                            <div title="From beginning date" class="fluentcrm_card_sub">
                                                {{ getScheduleTiming(sequence_email.settings.timings) }}
                                            </div>
                                            <div class="fluentcrm_card_title">
                                                <router-link
                                                    :to="{name: 'edit-sequence-email', params: { sequence_id: sequence_email.parent_id, email_id: sequence_email.id }, query: { t: (new Date()).getTime() }}">
                                                    {{ sequence_email.title }}
                                                </router-link>
                                            </div>
                                            <div class="fluentcrm_card_actions fluentcrm_card_actions_hidden">
                                                <router-link
                                                    :to="{name: 'edit-sequence-email', params: { sequence_id: sequence_email.parent_id, email_id: sequence_email.id }, query: { t: (new Date()).getTime() }}">
                                                    <el-button
                                                        type="text"
                                                        size="mini"
                                                        icon="el-icon-edit"
                                                    >
                                                        {{$t('edit')}}
                                                    </el-button>
                                                </router-link>
                                                <el-button class="fluentcrm-report-text-btn" icon="el-icon-data-analysis" type="text" size="mini" @click="showEmailReport(sequence_email.id)">{{$t('Show Report')}}</el-button>
                                                <el-button
                                                    size="mini"
                                                    type="text"
                                                    icon="el-icon-document-copy"
                                                    @click="duplicateSequence(sequence_email)"
                                                    class="fluentcrm-duplicate-campaign"
                                                >{{ $t('Duplicate') }}
                                                </el-button>
                                                <confirm placement="top-start" @yes="remove(sequence_email)">
                                                    <el-button
                                                        size="mini"
                                                        type="text"
                                                        slot="reference"
                                                        icon="el-icon-delete"
                                                        class="fluentcrm-delete-campaign"
                                                    >
                                                        {{$t('Delete')}}
                                                    </el-button>
                                                </confirm>
                                            </div>
                                        </div>
                                    </el-col>
                                    <el-col :sm="24" :md="12">
                                        <div class="fluentcrm_card_stats">
                                            <ul class="fluentcrm_inline_stats">
                                                <li style="cursor: pointer;" @click="showEmailReport(sequence_email.id)">
                                                    <span class="fluentcrm_digit">{{ sequence_email.stats.sent || '--' }}</span>
                                                    <p>{{$t('Sent')}}</p>
                                                </li>
                                                <li>
                                            <span
                                                class="fluentcrm_digit">{{
                                                    percent(sequence_email.stats.views, sequence_email.stats.sent)
                                                }}</span>
                                                    <p>{{$t('Opened')}}</p>
                                                </li>
                                                <li style="cursor: pointer;" @click="showLinkReport(sequence_email.id)">
                                            <span
                                                class="fluentcrm_digit">{{
                                                    percent(sequence_email.stats.clicks, sequence_email.stats.sent)
                                                }}</span>
                                                    <p>{{$t('Clicked')}}</p>
                                                </li>
                                                <li>
                                            <span class="fluentcrm_digit">{{
                                                    percent(sequence_email.stats.unsubscribers, sequence_email.stats.sent)
                                                }}</span>
                                                    <p>{{$t('Unsubscribed')}}</p>
                                                </li>
                                                <li v-if="sequence_email.stats.revenue">
                                                    <span class="fluentcrm_digit">
                                                        {{ sequence_email.stats.revenue.total }}
                                                    </span>
                                                    <p>{{sequence_email.stats.revenue.label}}</p>
                                                </li>
                                            </ul>
                                        </div>
                                    </el-col>
                                </el-row>
                            </div>
                        </div>
                        <div v-if="hasPermission('fcrm_manage_emails') && !loading" class="text-align-center" style="padding-bottom: 30px;">
                            <el-button @click="toSequenceEmailEdit(0)" icon="el-icon-plus" type="primary">
                                {{$t('Vie_Add_aSE')}}
                            </el-button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <el-dialog v-loading="savingSequence" :close-on-click-modal="false" :title="$t('Edit Sequence and Settings')" width="60%" :append-to-body="true"
                   :visible.sync="showSequenceSettings">
            <el-form v-if="sequence.settings" label-position="top" :data="sequence">
                <el-form-item :label="$t('Internal Title')">
                    <el-input :placeholder="$t('Internal Title')" v-model="sequence.title"/>
                </el-form-item>
                <template v-if="sequence.settings.mailer_settings">
                    <mailer-config :mailer_settings="sequence.settings.mailer_settings" />
                </template>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button type="success" @click="saveSequence()">{{$t('Save Settings')}}</el-button>
            </span>
        </el-dialog>

        <el-dialog :close-on-click-modal="false" @closed="show_email_report_id = ''" :title="$t('View Sequence Emails')" width="60%" :append-to-body="true"
                   :visible.sync="show_email_report">
            <campaign-emails :campaign_id="show_email_report_id" v-if="show_email_report_id" />
        </el-dialog>
        <el-dialog :close-on-click-modal="false" @closed="link_click_id = ''" :title="$t('Link Metrics')" width="60%" :append-to-body="true"
                   :visible.sync="link_clicks_modal">
            <link-metrics :campaign_id="link_click_id" :hide_title="true" v-if="link_click_id" />
        </el-dialog>

    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import CampaignEmails from '@/Modules/Email/Campaigns/_components/_CampaignEmails';
import LinkMetrics from '@/Modules/Email/Campaigns/_components/_LinkMetrics';
import MailerConfig from '@/Pieces/FormElements/_MailerConfig';

export default {
    name: 'EditSequence',
    components: {
        Confirm,
        CampaignEmails,
        LinkMetrics,
        MailerConfig
    },
    props: ['id'],
    data() {
        return {
            sequence: {},
            sequence_emails: [],
            loading: false,
            addEmailModal: false,
            showSequenceSettings: false,
            savingSequence: true,
            show_email_report: false,
            show_email_report_id: false,
            link_clicks_modal: false,
            link_click_id: false,
            order: 'desc',
            orderBy: 'id',
            duplicating: false,
            show_title_input: false,
            original_title: ''
        }
    },
    methods: {
        showInputField() {
            this.original_title = this.sequence.title;
            this.show_title_input = true;
            this.$nextTick(() => {
                this.$refs.titleInput.focus();
            });
        },
        handleTitleBlur() {
            if (this.sequence.title !== this.original_title) {
                this.saveSequence();
            } else {
                this.show_title_input = false;
            }
        },
        showEmailReport(campaignId) {
            this.show_email_report_id = campaignId;
            this.show_email_report = true;
        },
        showLinkReport(campaignId) {
            this.link_click_id = campaignId;
            this.link_clicks_modal = true;
        },
        fetchSequence() {
            this.loading = true;
            const query = {
                with: ['sequence_emails', 'email_stats'],
                order: this.order,
                orderBy: this.orderBy
            };

            this.$get(`sequences/${this.id}`, query)
                .then(response => {
                    this.sequence = response.sequence;
                    this.sequence_emails = response.sequence_emails;
                    this.changeTitle(this.sequence.title + ' - Sequence');
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        gotToSubscribers() {
            this.$router.push({
                name: 'sequence-subscribers',
                params: {
                    id: this.id
                },
                query: {t: (new Date()).getTime()}
            });
        },
        toSequenceEmailEdit(id) {
            this.$router.push({
                name: 'edit-sequence-email',
                params: {
                    sequence_id: this.sequence.id,
                    email_id: id
                }
            })
        },
        remove(row) {
            this.$del(`sequences/${this.sequence.id}/email/${row.id}`)
                .then(response => {
                    this.fetchSequence();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                })
                .catch(errors => {
                    this.handleError(errors);
                });
        },
        getScheduleTiming(timings) {
            if (!timings.delay || timings.delay === '0') {
                return this.$t('Immediately');
            }
            return `After ${timings.delay} ${timings.delay_unit} from starting point`;
        },
        getRelativeWaitTimes(sequenceIndex) {
            const delay = this.sequence_emails[sequenceIndex].delay;
            if (sequenceIndex == 0) {
                return delay;
            }
            const prevDelay = this.sequence_emails[sequenceIndex - 1].delay;
            return delay - prevDelay;
        },
        saveSequence() {
            this.savingSequence = true;
            this.$put('sequences/' + this.sequence.id, {
                title: this.sequence.title,
                settings: this.sequence.settings
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    this.savingSequence = false;
                    if (!this.show_title_input) {
                        this.fetchSequence();
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.showSequenceSettings = false;
                    this.show_title_input = false;
                });
        },
        duplicateSequence(sequence) {
            this.duplicating = true;
            this.$post(`sequences/${sequence.id}/email/duplicate`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchSequence();
                    this.duplicating = false;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.duplicating = false;
                });
        },
        reapplySequence() {
            this.$confirm(this.$t('Are you sure you want to re-apply new sequence emails to completed subscribers?'), this.$t('Confirm'), {
                confirmButtonText: this.$t('Yes'),
                cancelButtonText: this.$t('No'),
                type: 'warning'
            }).then(() => {
                this.loading = true;
                this.$post('sequences/' + this.sequence.id + '/reapply')
                    .then(response => {
                        this.$notify.success(response.message);
                        this.fetchSequence();
                    })
                    .catch(error => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            });
        }
    },
    mounted() {
        this.fetchSequence();
        this.changeTitle(this.$t('View Sequence'));
    }
}
</script>
