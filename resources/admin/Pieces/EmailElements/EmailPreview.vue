<template>
     <span style="display: inline-block;" class="fc_email_preview">
        <el-button v-if="!auto_load" @click="fetchHtml()" size="mini" icon="el-icon-view" type="primary"></el-button>

         <el-drawer
             class="fc_company_info_drawer"
             :with-header="true"
             :size="globalDrawerSize"
             :title="$t('Email Preview (for better view, please send test email)')"
             :append-to-body="true"
             :before-close="fireClose"
             :visible.sync="showing_view">
             <div v-if="loading_preview" style="padding: 20px;">
                 <el-skeleton :rows="10" :animated="true" ></el-skeleton>
             </div>
             <div v-else-if="preview_html">
                <preview-iframe-builder :preview_html="preview_html" frame_height="85vh" :show_audit="true"></preview-iframe-builder>

                 <div style="background-color: #f7fafc;padding: 10px 20px !important;" class="fc_company_save_wrap">
                     <div>
                         <span v-if="selectedContact">Showing preview for {{selectedContact.full_name}} ({{selectedContact.email}})</span>
                         <span v-else>{{ $t('Showing preview for current contact') }}</span>
                         <el-popover
                             placement="top"
                             width="400"
                             v-model="showChanger"
                             trigger="manual">
                             <div v-if="showChanger" class="contact_selector">
                                 <p style="font-weight: bold;">{{ $t('Select Contact') }}</p>
                                 <contact-selector @contactSelected="(contact) => { selectedContact = contact }" v-model="selectedId" :field="{ clearable: true, size: 'small' }" />
                                 <div style="padding: 20px 0;">
                                     <el-button :disabled="!selectedId" @click="fetchHtml()" size="small" icon="el-icon-refresh" type="success">
                                            {{ $t('Refresh Email Preview') }}
                                     </el-button>
                                 </div>
                             </div>
                             <el-button slot="reference" size="small" @click="showChanger = !showChanger" type="default">{{ $t('Change preview contact') }}</el-button>
                         </el-popover>
                     </div>
                </div>
             </div>
         </el-drawer>
     </span>
</template>
<script type="text/babel">
import PreviewIframeBuilder from '@Pieces/PreviewIframeBuilder';
import ContactSelector from '@Pieces/FormElements/_ContactSelector';

export default {
    name: 'EmailPreview',
    props: ['campaign', 'show_audit', 'auto_load', 'by_campaign_id'],
    components: {
        PreviewIframeBuilder,
        ContactSelector
    },
    data() {
        return {
            showing_view: false,
            preview_html: '',
            loading_preview: false,
            showChanger: false,
            selectedId: false,
            selectedContact: false
        }
    },
    methods: {
        fetchHtml() {
            this.loading_preview = true;
            this.showing_view = true;

            const data = {
                campaign: this.campaign,
                contact_id: this.selectedId
            };

            if (this.by_campaign_id) {
                data.campaign = {
                    id: this.campaign.id
                }
                data.campaign_id = this.campaign.id;
            }

            this.$post('campaigns/email-preview-html', data)
            .then(response => {
                this.preview_html = response.preview_html;
                this.$emit('dataLoaded', response);
            })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {
                this.loading_preview = false;
                this.showChanger = false;
            });
        },
        fireClose() {
            this.$emit('modalClosed');
            this.showing_view = false;
        }
    },
    mounted() {
        if (this.auto_load) {
            this.fetchHtml();
        }
    }
}
</script>
