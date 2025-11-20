<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <email-header-pop-nav :head_title="$t('All Email Activities')" />
                    <span class="ff_small" v-if="pagination.total">({{ pagination.total | formatMoney }})</span>
                </h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <template v-if="statuses">
                    <el-select placeholder="By Status" size="mini" @change="fetchEmails" v-model="selectStatus">
                        <el-option value="" label="All" />
                        <el-option
                            v-for="(statusCount, statusName) in statuses"
                            :key="statusName"
                            :label="ucFirst(statusName) + ' (' + statusCount + ')'"
                            :value="statusName"
                            />
                    </el-select>
                </template>
            </div>
        </div>
        <div class="fluentcrm_body fluentcrm_pad_b_20" style="position: relative">

            <div v-if="loading || resending" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading && !pagination.total" :rows="8"></el-skeleton>

            <el-table
                border
                stripe
                v-loading="loading"
                :data="emails"
                style="width: 100%"
                @selection-change="handleSelectionChange"
                >
                <el-table-column
                    type="selection"
                    width="55"
                    fixed
                >
                </el-table-column>
                <el-table-column width="64" fixed>
                    <template slot-scope="scope">
                        <router-link v-if="scope.row.subscriber"
                                     :to="{ name: 'subscriber', params: { id: scope.row.subscriber_id } }">
                            <img :title="$t('Contact ID:')+scope.row.subscriber_id" class="fc_contact_photo"
                                 :src="scope.row.subscriber.photo"/>
                        </router-link>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Contact')" width="240">
                    <template slot-scope="scope">
                        <router-link v-if="scope.row.subscriber" :to="{
                        name: 'subscriber', params: { id:  scope.row.subscriber_id } }">
                            {{ scope.row.subscriber.full_name }}
                        </router-link>
                        <span v-else>{{ scope.row.email_address }}</span>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Subject')">
                    <template slot-scope="scope">
                        <span>{{ scope.row.email_subject }}</span>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Source')">
                    <template slot-scope="scope">
                        <span v-if="scope.row.campaign">{{ scope.row.campaign.title }}</span>
                        <span v-else>{{$t('n/a')}}</span>
                    </template>
                </el-table-column>
                <el-table-column width="120" :label="$t('Status')">
                    <template slot-scope="scope">
                        {{ scope.row.status }}
                    </template>
                </el-table-column>
                <el-table-column width="180" :label="$t('Sending Time')">
                    <template slot-scope="scope">
                        <span :title="scope.row.scheduled_at">
                            {{ scope.row.scheduled_at | nsHumanDiffTime }}
                        </span>
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Preview')" fixed="right" width="180" align="center">
                    <template slot-scope="scope">
                        <template v-if="scope.row.subscriber">
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
                            >{{$t('Resend')}}
                            </el-button>
                        </template>
                    </template>
                </el-table-column>
            </el-table>
            <el-row v-if="emails.length">
                <el-col :md="12" :sm="24">
                    <confirm placement="top-start" @yes="deleteSelected()" v-if="selections.length">
                        <el-button
                            v-loading="deleting"
                            style="margin: 10px 0 0 15px;"
                            icon="el-icon-delete"
                            slot="reference"
                            size="mini"
                            type="danger">
                            {{$t('Delete Selected')}}
                        </el-button>
                    </confirm>
                    &nbsp;
                </el-col>
                <el-col :md="12" :sm="24">
                    <pagination :pagination="pagination" @fetch="fetchEmails" :extra_sizes="[200, 250, 300, 400, 600]" />
                </el-col>
            </el-row>

        </div>
        <email-preview :preview="preview"/>
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import EmailPreview from '@/Modules/Email/Campaigns/_components/EmailPreview';
import Confirm from '@/Pieces/Confirm';
import EmailHeaderPopNav from '@/Pieces/EmailHeaderPopNav.vue';

export default {
    name: 'AllEmails',
    components: {
        Pagination,
        EmailPreview,
        Confirm,
        EmailHeaderPopNav
    },
    data() {
        return {
            emails: [],
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            loading: false,
            preview: {
                id: null,
                isVisible: false
            },
            resending: false,
            selections: [],
            deleting: false,
            statuses: null,
            selectStatus: ''
        }
    },
    methods: {
        fetchEmails() {
            this.loading = true;
            const query = {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                status: this.selectStatus
            };

            this.$get('reports/emails', query)
                .then(response => {
                    this.emails = response.emails.data;
                    if (response.statuses) {
                        this.statuses = response.statuses;
                    }
                    this.pagination.total = response.emails.total;
                    document.body.scrollTop = 0; // For Safari
                    document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(r => {
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
                    this.fetchEmails();
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
        deleteSelected() {
            this.deleting = true;
            const selectionIds = this.selections.map(item => item.id);
            this.$del('reports/emails', {
                email_ids: selectionIds
            })
                .then((response) => {
                    this.selections = [];
                    this.$notify.success(response.message);
                    this.fetchEmails();
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
        }
    },
    mounted() {
        this.fetchEmails();
        this.changeTitle(this.$t('All Emails'));
    }
}
</script>
