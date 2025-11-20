<template>
    <div class="fc_recurring_settings">
        <div class="fc_flow_items">
            <div class="step-container">
                <el-form label-position="top">
                    <h3>{{ $t('Scheduling Settings') }}</h3>
                    <div class="fc_flow_intro fc_flow_settings">
                        <basic-settings :campaign="campaign"/>
                    </div>
                    <h3>{{ $t('Sending Conditions') }}</h3>
                    <div class="fc_flow_intro fc_flow_settings">
                        <conditions-settings :sending_conditions="campaign.settings.sending_conditions"/>
                    </div>
                    <h3>{{ $t('Recipients') }}</h3>
                    <div class="fc_flow_intro fc_flow_settings">
                        <recipient-tagger-form v-model="campaign.settings.subscribers_settings"/>
                    </div>
                    <div style="text-align: right;" class="flow_nav">
                        <el-button v-loading="saving" :disabled="saving" @click="updateCampaign()" type="success">
                            {{ $t('Save Settings') }}
                        </el-button>
                    </div>
                </el-form>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import RecipientTaggerForm from '@/Pieces/RecipientTaggerForm';
import BasicSettings from '../parts/_basic.vue';
import ConditionsSettings from '../parts/_conditions.vue';

export default {
    name: 'RecurringEmailSettings',
    props: ['campaign'],
    components: {
        RecipientTaggerForm,
        BasicSettings,
        ConditionsSettings
    },
    data() {
        return {
            saving: false
        }
    },
    methods: {
        updateCampaign() {
            this.saving = true;
            const data = {
                campaign: JSON.stringify({
                    settings: this.campaign.settings,
                    title: this.campaign.title
                })
            }
            this.$post('recurring-campaigns/' + this.campaign.id + '/update-settings', data).then(response => {
                this.$notify.success(response.message);
            })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    },
    mounted() {
        this.changeTitle(this.$t('Settings') + ' - ' + this.campaign.title);
    }
}
</script>
