<template>
    <div style="flex: 1">
        <div class="fc_bulk_wrap">
            <div class="fc_bulk_item">
                <label>{{ $t('Select Action') }}</label>
                <el-select clearable filterable size="mini" class="mt-5" :placeholder="$t('Select Bulk Action')"
                    v-model="select_job.action_name">
                    <el-option v-for="(actionName, action) in actions" :key="action" :value="action"
                        :label="actionName.label"></el-option>
                </el-select>
            </div>

            <template v-if="currentAction && currentAction.options">
                <el-select filterable multiple size="mini" class="ml-5 mt-5" :placeholder="$t('Select')"
                    v-model="select_job.selected_options">
                    <el-option v-for="item in currentAction.options" :key="item.id" :value="item.id"
                        :label="item.title"></el-option>
                </el-select>
                <el-button v-loading="doing_action" :disable="doing_action" @click="handleBulkActionRoute()" size="mini"
                    :disabled="!select_job.selected_options.length" type="success" class="ml-5 mt-5">
                    {{ $t('Confirm') }} ({{ currentAction.label }})
                </el-button>
            </template>

            <template v-else-if="select_job.action_name == 'change_company_category'">
                <el-select filterable size="mini" class="ml-5 mt-5" :placeholder="$t('Select')" v-model="select_status">
                    <el-option v-for="item in currentAction.custom_options" :key="item" :value="item"
                        :label="item"></el-option>
                </el-select>
                <el-button v-loading="doing_action" :disable="doing_action" @click="handleBulkActionRoute()" size="mini"
                    :disabled="!select_status" type="success" class="ml-5 mt-5">
                    {{ $t('Change Category') }}
                </el-button>
            </template>

            <template v-else-if="select_job.action_name == 'change_company_type'">
                <el-select filterable size="mini" class="ml-5 mt-5" :placeholder="$t('Select')" v-model="select_status">
                    <el-option v-for="item in currentAction.custom_options" :key="item" :value="item"
                        :label="item|ucFirst"></el-option>
                </el-select>
                <el-button v-loading="doing_action" :disable="doing_action" @click="handleBulkActionRoute()" size="mini"
                    :disabled="!select_status" type="success" class="ml-5 mt-5">
                    {{ $t('Change Type') }}
                </el-button>
            </template>

            <template v-else-if="select_job.action_name == 'delete_companies'">
                <confirm placement="top-start" :message="delete_confirm_message" @yes="handleBulkActionRoute()">
                    <el-button style="height: 30px;" v-loading="doing_action" :disabled="doing_action" slot="reference"
                        size="mini" type="danger" class="mt-5 ml-5">{{ $t('Delete Companies') }}
                    </el-button>
                </confirm>
            </template>
        </div>
        <div v-if="canSelectAll" class="fc_bulk_navs">
            <p v-if="allSelected">{{ $t('All') }} {{ pagination.total }} {{ $t('companies selected') }}.
                <el-button size="mini" type="text" @click="allSelected = false">{{ $t('Select only this page') }}</el-button>
            </p>
            <p v-else>{{ this.selectedCompanies.length }} {{ $t('companies on this page selected') }}.
                <el-button size="mini" type="text" @click="allSelected = true">{{ $t('Select all') }}
                    {{ pagination.total }} {{ $t('companies') }}
                </el-button>
            </p>
        </div>

        <el-dialog :title="$t('Processing....')" :visible="processingAllBulk" width="50%" :append-to-body="true"
            :close-on-click-modal="false">
            <div style="text-align: center;" v-if="processingAllBulk">
                <h3>{{ $t('Action') }}: {{ currentAction.label }} <span v-loading="true"></span></h3>
                <el-progress :text-inside="true" :stroke-width="24"
                    :percentage="parseInt(processCount / pagination.total * 100)" status="success"></el-progress>
                <h3>{{ $t('Processing') }} {{ processCount }} {{ $t('of') }} {{ pagination.total }} {{ $t('companies') }}</h3>
                <h4>{{ $t('Please do not close this window while processing this bulk action') }}</h4>
            </div>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import chunk from 'lodash/chunk';

export default {
    name: 'BulkCompanyActions',
    props: ['selectedCompanies', 'options', 'pagination'],
    components: {
        Confirm
    },
    data() {
        return {
            delete_confirm_message: '<b>' + this.$t('Are you sure to delete?') + '</b><br />' + this.$t('delete_all_companies_notice'),
            actions: {},
            select_job: {
                action_name: '',
                selected_options: []
            },
            doing_action: false,
            select_status: '',
            processCount: 0,
            allSelected: false,
            processingAllBulk: false,
            currentAllActionLastCompanyId: 0
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
    computed: {
        currentAction() {
            if (!this.select_job.action_name) {
                return false;
            }
            return this.actions[this.select_job.action_name];
        },
        canSelectAll() {
            return this.pagination && this.selectedCompanies.length < this.pagination.total && this.selectedCompanies.length == this.pagination.per_page && window.fcrm_company_query_params && this.$route.name == 'companies';
        }
    },
    methods: {
        handleBulkActionRoute() {
            if (!this.canSelectAll || !this.allSelected) {
                this.doBulkAction();
                return false;
            }

            this.$confirm(this.$t('You are about to apply the selected action to all the companies (') + this.pagination.total + this.$t(') of this filter. Please confirm.'), 'Warning', {
                confirmButtonText: 'Yes, let\'s do it!',
                cancelButtonText: 'Cancel',
                type: 'warning'
            }).then(() => {
                // We have to do the bulk action for all company
                this.processingAllBulk = true;
                this.processAllBulk(() => {
                    alert(this.$t('All companies have been processed successfully!'));
                });
            }).catch(() => {
                this.allSelected = false;
            });
        },
        async doBulkAction() {
            const companyIds = [];
            this.each(this.selectedCompanies, (company) => {
                companyIds.push(company.id);
            });

            if (!companyIds.length) {
                this.$notify.error(this.$t('Please select companies first'));
                return false;
            }
            this.doing_action = true;

            const chunks = chunk(companyIds, 100);

            const data = {
                action_name: this.select_job.action_name,
                action_options: this.select_job.selected_options,
                new_status: this.select_status
            };

            for (let i = 0; i < chunks.length; i++) {
                await this.repeatBulkAction(chunks[i], data)
                    .then(response => {
                        if (i === (chunks.length - 1)) {
                            this.$emit('refetch');
                            this.$notify.success(response.message);
                        }
                    })
                    .catch(errors => {
                        this.handleError(errors);
                    });
            }
            this.doing_action = false;
        },
        repeatBulkAction(chunk, data) {
            data.company_ids = chunk;
            return this.$post('companies/do-bulk-action', data);
        },
        processAllBulk(completedCallBack) {
            const data = {
                is_all: 'yes',
                action_name: this.select_job.action_name,
                action_options: this.select_job.selected_options,
                new_status: this.select_status,
                last_id: this.currentAllActionLastCompanyId,
                per_page: 50,
                company_query: window.fcrm_company_query_params
            };

            this.$post('companies/do-bulk-action', data)
                .then(response => {
                    this.processCount += parseInt(response.completed_companies);
                    if (response.is_completed) {
                        this.processingAllBulk = false;
                        this.$emit('refetch');
                        this.$notify.success(response.message);
                        if (completedCallBack) {
                            completedCallBack();
                        }
                    } else {
                        this.currentAllActionLastCompanyId = response.last_company_id;
                        this.$nextTick(() => {
                            this.processAllBulk(completedCallBack);
                        });
                    }
                })
                .catch(errors => {
                    this.handleError(errors);
                });
        }
    },
    mounted() {
        let manageActions = {};
        let deleteAction = {};
        if (this.hasPermission('fcrm_manage_contact_cats')) {
            manageActions = {
                change_company_type: {
                    label: this.$t('Change Company Type'),
                    custom_options: this.appVars.company_types,
                    btn_text: this.$t('Change Company Type'),
                    is_multiple: false
                },
                change_company_category: {
                    label: this.$t('Change Company Category'),
                    custom_options: this.appVars.company_categories,
                    btn_text: this.$t('Change Company Type'),
                    is_multiple: false
                }
            };
        }
        if (this.hasPermission('fcrm_manage_contact_cats_delete')) {
            deleteAction = {
                delete_companies: {
                    label: this.$t('Delete Companies')
                }
            };
        }

        this.actions = {...manageActions, ...deleteAction};
    }
}
</script>
