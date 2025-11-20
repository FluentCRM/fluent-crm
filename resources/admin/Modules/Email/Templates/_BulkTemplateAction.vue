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

        <template v-if="select_job.action_name == 'delete_templates'">
            <confirm placement="top-start" :message="delete_confirm_message" @yes="doBulkAction()">
                <el-button style="height: 30px;" v-loading="doing_action" :disabled="doing_action" slot="reference"
                           size="mini" type="danger"
                           class="mt-5 ml-5">{{ $t('Delete Templates') }}
                </el-button>
            </confirm>
        </template>

    </div>
</template>

<script>
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'BulkTemplateAction',
    props: ['selectedTemplates'],
    components: {
        Confirm
    },
    data() {
        return {
            delete_confirm_message: '<b>' + this.$t('Are you sure to delete?') + '</b><br />' + this.$t('delete_all_templates_notice'),
            actions: {},
            select_job: {
                action_name: '',
                selected_options: []
            },
            doing_action: false,
            select_status: ''
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
            const templateIds = [];
            this.each(this.selectedTemplates, (template) => {
                templateIds.push(template.ID);
            });
            console.log(templateIds);

            this.doing_action = true;
            this.$post('templates/do-bulk-action', {
                action_name: this.select_job.action_name,
                status: this.select_status,
                template_ids: templateIds
            })
                .then(res => {
                    this.$notify.success(res.message);
                    this.$emit('refetch');
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
            delete_templates: {
                label: this.$t('Delete Templates')
            }
        }
    }
}
</script>
