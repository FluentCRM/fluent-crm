<template>
    <div class="fc_bulk_wrap">

        <div class="fc_bulk_item">
            <label>{{ $t('Select Action') }}</label>
            <el-select clearable filterable size="mini" class="mt-5" :placeholder="$t('Select Bulk Action')"
                       v-model="select_job.action_name">
                <el-option v-for="(actionName, action) in actions" :key="action" :value="action"
                           :label="actionName.label"></el-option>
            </el-select>
        </div>

        <template v-if="select_job.action_name === 'apply_labels'">
            <el-select filterable
                       size="mini"
                       :placeholder="$t('Select Labels')"
                       v-model="selectedLabels"
                       multiple>
                <el-option
                    v-for="label in options.labels"
                    :key="label.id"
                    :value="label.id"
                >
                    <span
                        :style="'background:'+label.settings.color+';padding: 2px 5px 4px 5px;border-radius: 4px;'"
                    >
                        {{ label.title }}
                    </span>
                </el-option>
            </el-select>
            <el-button v-loading="doing_action" :disable="doing_action" @click="doBulkAction()"
                       class="ml-5" size="mini"
                       :disabled="!selectedLabels.length" type="success">
                {{ $t('Apply Label') }}
            </el-button>
        </template>

        <template v-else-if="select_job.action_name == 'delete_campaigns'">
            <confirm placement="top-start" @yes="doBulkAction()">
                <el-button style="height: 30px;" v-loading="doing_action" :disabled="doing_action" slot="reference"
                           size="mini" type="danger"
                           class="mt-5 ml-5">{{ $t('Delete Campaigns') }}
                </el-button>
            </confirm>
        </template>

    </div>

</template>

<script>
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'BulkCampaignActions',
    props: ['selectedCampaigns', 'options'],
    components: {
        Confirm
    },
    data() {
        return {
            actions: {},
            select_job: {
                action_name: '',
                selected_options: []
            },
            doing_action: false,
            select_status: '',
            selectedLabels: []
        }
    },
    watch: {
        'select_job.action_name': {
            handler() {
                this.select_job.selected_options = [];
                this.select_status = '';
            },
            deep: true
        }
    },
    methods: {
        doBulkAction() {
            const campaignIds = [];
            this.each(this.selectedCampaigns, (campaign) => {
                campaignIds.push(campaign.id);
            });

            this.doing_action = true;
            this.$post('recurring-campaigns/do-bulk-action', {
                action_name: this.select_job.action_name,
                labels: this.selectedLabels,
                campaign_ids: campaignIds
            })
                .then(res => {
                    this.$notify.success(res.message);
                    this.$emit('refetch');
                    this.selectedLabels = [];
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.doing_action = false;
                });
        }
    },
    mounted() {
        this.actions = {
            apply_labels: {
                label: this.$t('Apply Labels'),
                options: this.options.labels
            },
            delete_campaigns: {
                label: this.$t('Delete Recurring Campaigns')
            }
        }
    }
}
</script>

<style scoped>

</style>
