<template>
    <div>
        <div v-if="loading">
            <el-skeleton :animated="true" style="padding: 20px;" :rows="7"></el-skeleton>
        </div>
        <template v-else-if="drafts.length || history.length">
            <div v-if="drafts.length">
                <h3>{{ $t('Drafts') }}</h3>
                <p>{{ $t('Draft_Email_Info') }}</p>
                <div class="fc_mail_cards">
                    <div v-for="draft in drafts" :key="draft.id" class="fc_mail_card fc_shadow">
                        <el-row :gutter="30">
                            <el-col :sm="24" :md="12">
                                <div class="fc_mail_desc">
                                    <p class="fc_top_sub">{{ draft.status }} | {{ draft.scheduled_at }}</p>
                                    <h3>{{ draft.email_subject }}</h3>
                                </div>
                            </el-col>
                            <el-col :sm="24" :md="12">
                                <div class="fc_mail_actions">
                                    <el-button @click="$router.push({ name: 'recurring_email_report', params: { campaign_id: campaign.id, email_id: draft.id } })" type="primary" size="small">Review & Schedule</el-button>
                                    <el-button @click="showPreview(draft)" type="default" size="small">
                                        {{ $t('Preview Email') }}
                                    </el-button>
                                    <el-button :disabled="updatingStatus" v-loading="updatingStatus" @click="changeCampaignStatus('cancelled',draft)" type="danger" plain size="small">
                                        {{ $t('Cancel This email') }}
                                    </el-button>
                                </div>
                            </el-col>
                        </el-row>
                    </div>
                </div>
            </div>
            <div v-if="history.length">
                <h3>{{ $t('Previous Emails') }}</h3>
                <p>{{ $t('Previous_Email_Hist') }}</p>

                <div class="fc_mail_cards">
                    <div v-for="draft in history" :key="draft.id" class="fc_mail_card fc_shadow">
                        <el-row :gutter="30">
                            <el-col :sm="24" :md="12">
                                <div class="fc_mail_desc">
                                    <p class="fc_top_sub">{{ draft.status }} | {{ draft.scheduled_at }}</p>
                                    <h3>{{ draft.email_subject }}</h3>
                                </div>
                            </el-col>
                            <el-col :sm="24" :md="12">
                                <div class="fc_mail_actions">
                                    <el-button @click="showPreview(draft)" type="default" size="small">
                                        {{ $t('Preview Email') }}
                                    </el-button>
                                    <template v-if="draft.status == 'cancelled'">
                                        <el-button :disabled="updatingStatus" v-loading="updatingStatus" @click="changeCampaignStatus('draft', draft)" type="danger" plain size="small">
                                            {{ $t('Send to Draft') }}
                                        </el-button>
                                    </template>
                                    <el-button @click="$router.push({ name: 'recurring_email_report', params: { campaign_id: campaign.id, email_id: draft.id } })" type="primary" size="small">{{ $t('View Report') }}</el-button>
                                </div>
                            </el-col>
                        </el-row>
                    </div>
                    <pagination :pagination="pagination" @fetch="fetch"/>
                </div>
            </div>
        </template>

        <div v-else>
            <h3 style="text-align: center;">{{ $t('All_Email_Hist') }}</h3>
        </div>

        <div v-if="previewingCampaign">
            <email-preview :by_campaign_id="true" :auto_load="true" @modalClosed="() => { previewingCampaign = null }" :show_audit="true"
                           :campaign="previewingCampaign"/>
        </div>
    </div>
</template>

<script type="text/babel">
import EmailPreview from '@/Pieces/EmailElements/EmailPreview';
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'EmailHistory',
    components: {
        EmailPreview,
        Pagination
    },
    props: ['campaign'],
    data() {
        return {
            drafts: [],
            history: [],
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1
            },
            loading: false,
            previewingCampaign: false,
            updatingStatus: false
        }
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get('recurring-campaigns/' + this.campaign.id + '/emails', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            })
                .then(response => {
                    if (response.drafts) {
                        this.drafts = response.drafts;
                    }
                    this.history = response.emails.data;
                    this.pagination.total = response.emails.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        showPreview(campaignEmail) {
            this.previewingCampaign = campaignEmail;
        },
        changeCampaignStatus(status, campaignEmail) {
            this.updatingStatus = true;
            this.$put('recurring-campaigns/' + this.campaign.id + '/emails/' + campaignEmail.id, {
                status: status
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updatingStatus = false;
                });
        }
    },
    mounted() {
        this.fetch();
        this.changeTitle(this.$t('Email History') + ' - ' + this.campaign.title);
    }
}
</script>
