<template>
    <div class="fluentcrm_campaign_emails">
        <div class="fluentcrm_inner_header">
            <h3 class="fluentcrm_inner_title">{{$t('Recipients')}}</h3>
            <div class="fluentcrm_inner_actions d-flex items-center">
                <div class="fluentcrm-searcher mr-5">
                    <el-input @keyup.enter.native="fetch" style="width: 200px" size="mini" :placeholder="$t('Search')" v-model="search" class="input-with-select">
                        <el-button @click="fetch()" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>
                <el-radio-group @change="changeFilter()" v-model="filter_type" size="mini">
                    <el-radio-button label="all">{{ $t('All') }}</el-radio-button>
                    <el-tooltip effect="dark" :content="$t('Click')" placement="top">
                        <el-radio-button label="click"><i class="el-icon el-icon-position"></i></el-radio-button>
                    </el-tooltip>
                    <el-tooltip effect="dark" :content="$t('View')" placement="top">
                        <el-radio-button label="view"><i class="el-icon el-icon-folder-opened"></i></el-radio-button>
                    </el-tooltip>
                    <el-tooltip effect="dark" :content="$t('Unopened')" placement="top">
                        <el-radio-button label="unopened" class="non-open"><div class="non-open-icons"><i class="el-icon el-icon-message"></i> <span>+</span></div></el-radio-button>
                    </el-tooltip>
                    <el-tooltip v-if="failed_counts" effect="dark" :content="$t('failed')" placement="top">
                        <el-radio-button label="failed" class="non-open">
                            <div class="failed-email-icons">
                                <i class="el-icon el-icon-message"></i>
                                <span class="warning-icon"><CustomIcon type="failed-emails" /></span>
                            </div>
                        </el-radio-button>
                    </el-tooltip>
                </el-radio-group>

                <el-button
                    v-if="hasPermission('fcrm_manage_contacts_export')"
                    size="mini"
                    type="info"
                    icon="el-icon-download"
                    class="ml-5"
                    @click="exportCampaignEmails()">
                    {{$t('Export')}}
                </el-button>
                <el-button @click="fetch" size="mini" class="ml-5 refresh-campaign-btn"><i class="el-icon el-icon-refresh"></i></el-button>
            </div>
        </div>

        <div v-loading="retrying" class="fc_highlight_gray fc_m_30 text-align-center" v-if="failed_counts">
            <h3>{{failed_counts}} {{$t('_Ca_failed_tstrTR')}}</h3>
            <generic-promo v-if="!has_campaign_pro"></generic-promo>
            <el-button v-else @click="retrySending()" type="primary" size="small">{{$t('Retry Sending')}}</el-button>
        </div>

        <confirm @yes="resendUnopenedEmails()" placement="top-start" v-if="filter_type === 'unopened'" :message="$t('Are you sure to Resend Unopened Emails?')">
            <el-button
                style="margin-bottom: 10px;"
                size="mini"
                type="primary"
                slot="reference"
                icon="el-icon-refresh-right"
            >
                {{ $t('Resend Unopened Emails') }}
            </el-button>
        </confirm>
        <el-table
            stripe
            border
            :data="emails"
            @selection-change="handleSelectionChange"
            style="width: 100%" v-loading="loading || resending">
            <el-table-column
                v-if="manage_mode"
                type="selection"
                width="55">
            </el-table-column>
            <el-table-column min-width="110" :label="$t('Name')">
                <template slot-scope="scope">
                    <template v-if="scope.row.subscriber">
                        <img
                            v-if="scope.row.subscriber.photo"
                            style="display: inline-block; margin-bottom: -6px;"
                            :title="$t('Contact ID:')+scope.row.subscriber.id"
                            class="fc_contact_photo"
                            :src="scope.row.subscriber.photo"
                        />
                        <span>{{ scope.row.subscriber.full_name || $t('Unknown') }}</span>
                    </template>
                    <template v-else>
                        <i class="el-icon-user" style="margin-right: 6px;"></i>
                        <span>{{ $t('Unknown') }}</span>
                    </template>
                </template>
            </el-table-column>
            <el-table-column min-width="200" :label="$t('Email')">
                <template slot-scope="scope">
                    <router-link
                        v-if="scope.row.subscriber_id"
                        :to="{ name: 'subscriber', params: { id: scope.row.subscriber_id } }"
                    >
                        {{ scope.row.email_address || $t('Unknown') }}
                    </router-link>
                    <span v-else>{{ scope.row.email_address || $t('Unknown') }}</span>
                    <br>
                    <span v-if="!scope.row.subscriber_id">(custom email)</span>
                </template>
            </el-table-column>

            <el-table-column width="170" :label="$t('Actions')">
                <template slot-scope="scope">
                    <span title="Total Clicks" class="ns_counter"><i
                        class="el-icon el-icon-position"></i> {{ scope.row.click_counter || 0 }}</span>
                    <span title="Email opened" v-show="scope.row.click_counter || scope.row.is_open == 1"
                          class="ns_counter"><i class="el-icon el-icon-folder-opened"></i></span>
                    <span
                        style="display: block; margin-top: 5px;"
                        v-if="scope.row.subscriber && scope.row.subscriber.status && scope.row.subscriber.status !== 'subscribed'"
                        class="ns_counter"
                    >
                        {{scope.row.subscriber.status|ucFirst}}
                    </span>
                </template>
            </el-table-column>

            <el-table-column prop="scheduled_at" width="190" :label="$t('Date')"/>
            <el-table-column width="110" :label="$t('Status')" align="center">
                <template slot-scope="scope">
                    <span :class="'status-' +scope.row.status">
                        {{ scope.row.status | ucFirst }}
                    </span>
                </template>
            </el-table-column>

            <el-table-column :label="$t('Preview')" width="160" align="center">
                <template slot-scope="scope">
                    <el-button
                        size="mini"
                        type="primary"
                        icon="el-icon-view"
                        @click="previewEmail(scope.row.id)"
                    />
                    <el-button
                        v-if="canResend(scope.row)"
                        size="mini"
                        type="info"
                        @click="resendEmail(scope.row.id)"
                        icon="el-icon-refresh-right"
                    >{{$t('Resend')}}
                    </el-button>
                </template>
            </el-table-column>
        </el-table>
        <el-row>
            <el-col :span="12">
                <div style="margin-top: 10px">
                    <el-button icon="el-icon-delete" v-loading="deleting" @click="deleteSelected()" type="danger" size="small"
                               v-if="selections.length">
                        {{$t('Delete Selected')}} ({{ selections.length }})
                    </el-button>
                </div>
            </el-col>
            <el-col :span="12">
                <pagination :pagination="pagination" @fetch="fetch"/>
            </el-col>
        </el-row>
        <email-preview :preview="preview"/>
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import Confirm from '@/Pieces/Confirm';
import EmailPreview from './EmailPreview';
import GenericPromo from '@/Modules/Promos/GenericPromo.vue';
import CustomIcon from '@/Pieces/CustomIcon.vue';

export default {
    name: 'CampaignEmails',
    components: {
        CustomIcon,
        Pagination,
        EmailPreview,
        GenericPromo,
        Confirm
    },
    props: ['campaign_id', 'manage_mode'],
    data() {
        return {
            loading: false,
            emails: [],
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1
            },
            preview: {
                id: null,
                isVisible: false
            },
            filter_type: 'all',
            selections: [],
            search: '',
            deleting: false,
            failed_counts: 0,
            retrying: false,
            resending: false
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            const query = {
                viewCampaign: null,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                filter_type: this.filter_type,
                search: this.search
            };

            this.$get(`campaigns/${this.campaign_id}/emails`, query)
                .then(response => {
                    this.emails = response.emails.data;
                    this.pagination.total = response.emails.total;
                    this.failed_counts = parseInt(response.failed_counts);
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(r => {
                    this.loading = false;
                });
        },
        changeFilter() {
            this.pagination.current_page = 1;
            this.fetch();
        },
        previewEmail(emailId) {
            this.preview.id = emailId;
            this.preview.isVisible = true;
        },
        deleteSelected() {
            this.deleting = true;
            const selectionIds = this.selections.map(item => item.id);
            this.$del(`campaigns/${this.campaign_id}/emails`, {
                email_ids: selectionIds
            })
                .then((response) => {
                    this.selections = [];
                    this.$notify.success(response.message);
                    this.$emit('updateCount', response.recipients_count);
                    this.fetch();
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        handleSelectionChange(val) {
            this.selections = val;
        },
        retrySending() {
            this.retrying = true;
            this.$post(`campaigns-pro/${this.campaign_id}/resend-failed-emails`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                    this.$emit('fetchCampaign');
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.retrying = false;
                });
        },
        resendEmail(campaignEmailIds) {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('_Ca_Please_utptutf'));
                return false;
            }
            if (!Array.isArray(campaignEmailIds)) {
                campaignEmailIds = [campaignEmailIds];
            }
            this.resending = true;
            this.$post(`campaigns-pro/${this.campaign_id}/resend-emails`, {
                email_ids: campaignEmailIds
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
        resendUnopenedEmails() {
           if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('_Ca_Please_utptutf'));
                return false;
            }
            
            this.resending = true;
            this.$post(`campaigns-pro/${this.campaign_id}/resend-unopened-emails`)
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
        exportCampaignEmails() {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Exporting_archived_campaign_Emails_alert'));
                return;
            }

            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_archived_campaign_emails',
                campaign_id: this.campaign_id,
                filter_type: this.filter_type
            });
        },
        // Ensure resend button only shows when safe
        canResend(row) {
            const statusOk = row && (row.status === 'sent' || row.status === 'failed');
            const permOk = this.hasPermission && this.hasPermission('fcrm_manage_emails');
            const subscribed =
                row && row.subscriber && row.subscriber.status === 'subscribed';
            return statusOk && permOk && subscribed;
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
