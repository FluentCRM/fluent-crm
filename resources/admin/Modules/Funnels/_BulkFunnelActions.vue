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

        <template v-if="select_job.action_name == 'change_funnel_status'">
            <el-select filterable
                       size="mini"
                       class="ml-5 mt-5"
                       :placeholder="$t('Select')"
                       v-model="select_status">
                <el-option v-for="item in options.statuses" :key="item.id" :value="item.id"
                           :label="item.title"></el-option>
            </el-select>
            <el-button v-loading="doing_action" :disable="doing_action" @click="doBulkAction()" size="mini"
                       :disabled="!select_status" type="success" class="ml-5 mt-5">
                {{ $t('Change Status') }}
            </el-button>
        </template>
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

        <template v-else-if="select_job.action_name == 'delete_funnels'">
            <confirm placement="top-start" :message="delete_confirm_message" @yes="doBulkAction()">
                <el-button style="height: 30px;" v-loading="doing_action" :disabled="doing_action" slot="reference"
                           size="mini" type="danger"
                           class="mt-5 ml-5">{{ $t('Delete Funnels') }}
                </el-button>
            </confirm>
        </template>

    </div>

</template>

<script>
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'BulkFunnelActions',
    props: ['selectedFunnels', 'options'],
    components: {
        Confirm
    },
    data() {
        return {
            delete_confirm_message: '<b>' + this.$t('Are you sure to delete?') + '</b><br />' + this.$t('delete_all_funnels_notice'),
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
            const funnelIds = [];
            this.each(this.selectedFunnels, (funnel) => {
                funnelIds.push(funnel.id);
            });

            this.doing_action = true;
            this.$post('funnels/do-bulk-action', {
                action_name: this.select_job.action_name,
                status: this.select_status,
                labels: this.selectedLabels,
                funnel_ids: funnelIds
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
            change_funnel_status: {
                label: this.$t('Change Funnel Status'),
                options: this.options.statuses
            },
            apply_labels: {
                label: this.$t('Apply Labels'),
                options: this.options.labels
            },
            delete_funnels: {
                label: this.$t('Delete Funnels')
            }
        }
    }
}
</script>

<style scoped>

</style>
