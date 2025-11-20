<template>
    <div class="fluentcrm_link_metrics">
        <div class="fluentcrm_inner_header">
            <h3>{{$t('Campaign Actions')}}</h3>
            <p>{{$t('Cam_Add_Remove_ToyCb')}}</p>
        </div>
        <hr/>
        <div v-if="has_campaign_pro" class="fc_campaign_action_wrapper">
            <el-form v-if="!processing" label-position="top" :data="action_details">
                <el-form-item :label="$t('Action Type')">
                    <el-radio-group v-model="action_details.action_type">
                        <el-radio label="add_tags">{{$t('Add Tags')}}</el-radio>
                        <el-radio label="remove_tags">{{$t('Remove Tags')}}</el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item :label="$t('Select Tags')">
                    <el-select :multiple="true" v-model="action_details.tags">
                        <el-option v-for="tag in available_tags" :key="tag.id" :value="tag.id" :label="tag.title"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item :label="$t('Filter Subscribers')">
                    <el-radio-group v-model="action_details.activity_type">
                        <el-radio v-for="(activity, activityName) in activity_types" :key="activityName"
                                  :label="activityName">{{ activity }}
                        </el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item :label="$t('Cam_Select_Utwc_MfaS')"
                              v-if="action_details.activity_type == 'email_clicked'">
                    <el-checkbox-group v-if="clicked_links.length" class="fc_new_line_items"
                                       v-model="action_details.link_ids">
                        <el-checkbox v-for="link in clicked_links" :key="link.id" :label="link.id">{{ link.url }} -
                            ({{ link.total }})
                        </el-checkbox>
                    </el-checkbox-group>
                    <p style="color: red" v-else>{{$t('Cam_Sorry_nlftcc')}}</p>
                </el-form-item>
                <el-form-item>
                    <el-button @click="process()" size="small" type="success">
                        <span v-if="action_details.action_type == 'add_tags'">{{$t('Add Tags to Subscribers')}}</span>
                        <span v-else>{{$t('Cam_Remove_TFS')}}</span>
                    </el-button>
                </el-form-item>
            </el-form>
            <div v-else>
                <div v-if="!is_completed" class="text-align-center">
                    <h3 v-loading="processing">{{$t('Processing now...')}}</h3>
                    <h4>{{$t('Rec_Please_dnctw')}}</h4>
                    <h2>{{ total_processed }}/{{ total_count }}</h2>
                    <p v-if="total_processed">{{ total_processed }} {{$t('Cam_Contacts_psf')}}</p>
                </div>
                <div v-else class="text-align-center">
                    <h3>{{$t('All Done')}}</h3>
                    <p>{{ total_processed }} {{$t('Cam_contacts_hbp')}}</p>
                    <el-button size="small" @click="resetAction()" type="info">{{$t('Do another Action')}}</el-button>
                </div>

                <div v-if="errors">
                    <h3>{{ $t('Errors Found') }}:</h3>
                    <pre>{{ errors }}</pre>
                </div>
            </div>
        </div>
        <div v-else>
            <generic-promo />
        </div>
    </div>
</template>

<script type="text/babel">
import GenericPromo from '../../../Promos/GenericPromo';
export default {
    name: 'CampaignActions',
    props: ['campaign'],
    components: {
        GenericPromo
    },
    data() {
        return {
            action_details: {
                action_type: 'add_tags',
                tags: [],
                activity_type: 'email_open',
                link_ids: []
            },
            activity_types: {
                email_open: this.$t('Cam_Select_Swote'),
                email_not_open: this.$t('Cam_Select_Swdnoe'),
                email_clicked: this.$t('Cam_Select_Swcsl')
            },
            available_tags: window.fcAdmin.available_tags,
            clicked_links: [],
            processing: false,
            processing_page: 1,
            total_count: 'calculating...',
            errors: null,
            total_processed: 0,
            is_completed: false
        }
    },
    methods: {
        getClickedLinks() {
            this.$get(`campaigns/${this.campaign.id}/link-report`)
                .then(response => {
                    this.clicked_links = response.links;
                });
        },
        process() {
            // validate
            if (!this.action_details.tags.length) {
                this.$notify.error(this.$t('Please Select Tags first'));
                return false;
            }

            if (this.action_details.activity_type === 'email_clicked' && !this.action_details.link_ids.length) {
                this.$notify.error(this.$t('Please Select Clicked URLS'));
                return false;
            }
            this.errors = null;
            this.processing = true;

            this.$post(`campaigns-pro/${this.campaign.id}/tag-actions`, {
                processing_page: this.processing_page,
                ...this.action_details
            })
                .then(response => {
                    if (response.total_count) {
                        this.total_count = response.total_count;
                    }

                    this.total_processed += response.processed_contacts;

                    if (response.has_more) {
                        this.processing_page = this.processing_page + 1;
                        this.$nextTick(() => {
                            this.process();
                        })
                    } else {
                        this.is_completed = true;
                    }
                })
                .catch((errors) => {
                    this.errors = errors;
                    this.handleError(errors);
                });
        },
        resetAction() {
            this.action_details = {
                action_type: 'add_tags',
                tags: [],
                activity_type: 'email_open',
                link_ids: []
            };
            this.is_completed = false;
            this.total_processed = 0;
            this.processing_page = 1;
            this.processing = false;
        }
    },
    mounted() {
        this.getClickedLinks();
    }
}
</script>
