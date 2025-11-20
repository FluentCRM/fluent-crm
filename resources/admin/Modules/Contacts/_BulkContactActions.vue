<template>
    <div style="flex: 1;">
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
                <el-select filterable
                           multiple
                           size="mini"
                           class="ml-5 mt-5"
                           :placeholder="$t('Select')"
                           v-model="select_job.selected_options">
                    <el-option v-for="item in currentAction.options" :key="item.id" :value="item.id"
                               :label="item.title"></el-option>
                </el-select>
                <el-button v-loading="doing_action" :disable="doing_action" @click="handleBulkActionRoute()" size="mini"
                           :disabled="!select_job.selected_options.length" type="success" class="ml-5 mt-5">
                    {{ $t('Confirm') }} ({{ currentAction.label }})
                </el-button>
            </template>

            <template v-else-if="select_job.action_name == 'change_contact_status'">
                <el-select filterable
                           size="mini"
                           class="ml-5 mt-5"
                           :placeholder="$t('Select')"
                           v-model="select_status">
                    <el-option v-for="item in currentAction.statuses" :key="item.id" :value="item.id"
                               :label="item.title"></el-option>
                </el-select>
                <el-button v-loading="doing_action" :disable="doing_action" @click="handleBulkActionRoute()" size="mini"
                           :disabled="!select_status" type="success" class="ml-5 mt-5">
                    {{ $t('Change Status') }}
                </el-button>
            </template>

            <template v-else-if="select_job.action_name == 'delete_contacts'">
                <confirm placement="top-start" :message="delete_confirm_message" @yes="handleBulkActionRoute()">
                    <el-button style="height: 30px;" v-loading="doing_action" :disabled="doing_action" slot="reference"
                               size="mini" type="danger"
                               class="mt-5 ml-5">{{ $t('Delete Contacts') }}
                    </el-button>
                </confirm>
            </template>

            <template v-else-if="select_job.action_name == 'send_double_optin'">
                <confirm placement="top-start" :message="double_optin_confirm_message" @yes="handleBulkActionRoute()">
                    <el-button style="height: 30px;" v-loading="doing_action" :disabled="doing_action" slot="reference"
                               size="mini" type="warning"
                               class="mt-5 ml-5">{{ $t('Send Double Optin') }}
                    </el-button>
                </confirm>
            </template>

            <template v-else-if="select_job.action_name == 'add_to_email_sequence'">
                <span style="padding: 10px;" v-if="!has_campaign_pro">{{ $t('Require FluentCRM Pro') }}</span>
                <template v-else>
                    <div class="fc_bulk_item">
                        <label>{{ $t('Select Sequence') }}</label>
                        <option-selector v-model="select_status"
                                         :field="{ option_key: 'email_sequences', clearable: true, size: 'mini' }"/>
                    </div>
                    <confirm placement="top-start"
                             :width="230"
                             :message="$t('Add_Contacts_To_Email_Sequence_Confirm_Message')"
                             @yes="handleBulkActionRoute()">
                        <el-button style="height: 30px;" v-loading="doing_action"
                                   :disabled="doing_action || !select_status"
                                   slot="reference"
                                   size="mini" type="primary"
                                   class="mt-5 ml-5">{{ $t('Add to Sequence') }}
                        </el-button>
                    </confirm>
                    <p class="help_msg">{{ $t('Already_Contact_in_Email_Sequence') }}</p>
                </template>
            </template>

            <template v-else-if="select_job.action_name == 'add_to_company'">
                <template>
                    <div class="fc_bulk_item">
                        <label>{{ $t('Select Company') }}</label>
                        <option-selector v-model="select_status"
                                         :field="{ option_key: 'companies', clearable: true, size: 'mini' }"/>
                    </div>
                    <confirm placement="top-start"
                             :width="230"
                             :message="$t('Add_Contacts_To_Company_Confirm_Message')"
                             @yes="handleBulkActionRoute()">
                        <el-button style="height: 30px;" v-loading="doing_action"
                                   :disabled="doing_action || !select_status"
                                   slot="reference"
                                   size="mini" type="primary"
                                   class="mt-5 ml-5">{{ $t('Add to Company') }}
                        </el-button>
                    </confirm>
                    <p class="help_msg">{{ $t('Already_Contact_in_Company') }}</p>
                </template>
            </template>

            <template v-else-if="select_job.action_name == 'remove_from_company'">
                <template>
                    <div class="fc_bulk_item">
                        <label>{{ $t('Select Company') }}</label>
                        <option-selector v-model="select_status"
                                         :field="{ option_key: 'companies', clearable: true, size: 'mini' }"/>
                    </div>
                    <confirm placement="top-start"
                             :width="230"
                             :message="$t('Remove_Contacts_From_Company_Confirm_Message')"
                             @yes="handleBulkActionRoute()">
                        <el-button style="height: 30px;" v-loading="doing_action"
                                   :disabled="doing_action || !select_status"
                                   slot="reference"
                                   size="mini" type="primary"
                                   class="mt-5 ml-5">{{ $t('Remove from Company') }}
                        </el-button>
                    </confirm>
                    <p class="help_msg">{{ $t('Contact_not_in_Company') }}</p>
                </template>
            </template>

            <template v-else-if="select_job.action_name == 'add_to_automation'">
                <span style="padding: 10px;" v-if="!has_campaign_pro">Require FluentCRM Pro</span>
                <template v-else>
                    <div class="fc_bulk_item">
                        <label>{{ $t('Select Automation Funnel') }}</label>
                        <option-selector v-model="select_status"
                                         :field="{ option_key: 'automation_funnels', clearable: true, size: 'mini' }"/>
                    </div>
                    <confirm placement="top-start"
                             :width="230"
                             :message="$t('Add_Contacts_To_Automation_Confirm_Message')"
                             @yes="handleBulkActionRoute()">
                        <el-button style="height: 30px;" v-loading="doing_action"
                                   :disabled="doing_action || !select_status"
                                   slot="reference"
                                   size="mini" type="primary"
                                   class="mt-5 ml-5">{{ $t('Add to Automation') }}
                        </el-button>
                    </confirm>
                    <p class="help_msg">{{ $t('Already_Contact_in_Automation') }}</p>
                </template>
            </template>

            <template v-else-if="select_job.action_name == 'update_custom_fields'">
                <BulkContactCustomField
                    :options="options"
                    @updateCustomField="updateCustomFieldValue" />
            </template>

            <template v-else-if="currentAction && currentAction.custom_options">
                <el-select filterable
                           size="mini"
                           :multiple="currentAction.is_multiple"
                           class="ml-5 mt-5"
                           :placeholder="$t('Select')"
                           v-model="select_status">
                    <el-option v-for="(item, itemKey) in currentAction.custom_options" :key="itemKey" :value="itemKey"
                               :label="item"></el-option>
                </el-select>

                <p class="help_msg" v-if="currentAction.help_message" v-html="currentAction.help_message"></p>

                <el-button v-loading="doing_action" :disable="doing_action" @click="handleBulkActionRoute()" size="mini"
                           type="success" class="ml-5 mt-5">
                    {{ currentAction.btn_text }}
                </el-button>
            </template>
        </div>
        <div v-if="canSelectAll" class="fc_bulk_navs">
            <p v-if="allSelected">All {{ pagination.total | formatMoney }} contacts selected.
                <el-button size="mini" type="text" @click="allSelected = false">Select only this page</el-button>
            </p>
            <p v-else>{{ this.selectedSubscribers.length | formatMoney }} contacts on this page selected.
                <el-button size="mini" type="text" @click="allSelected = true">Select all
                    {{ pagination.total | formatMoney }} contacts
                </el-button>
            </p>
        </div>

        <el-dialog :title="$t('Processing....')"
                   :visible="processingAllBulk"
                   width="50%"
                   :append-to-body="true"
                   :close-on-click-modal="false"
        >
            <div style="text-align: center;" v-if="processingAllBulk">
                <h3>Action: {{ currentAction.label }} <span v-loading="true"></span></h3>
                <el-progress :text-inside="true" :stroke-width="24"
                             :percentage="parseInt(processCount / pagination.total * 100)"
                             status="success"></el-progress>
                <h3>Processing {{ processCount | formatMoney }} of {{ pagination.total | formatMoney }} contacts</h3>
                <h4>Please do not close this window while processing this bulk action</h4>
            </div>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
import chunk from 'lodash/chunk';
import BulkContactCustomField from './_BulkContactCustomField.vue';

export default {
    name: 'BulkContactActions',
    props: ['selectedSubscribers', 'options', 'pagination'],
    components: {
        BulkContactCustomField,
        Confirm,
        OptionSelector
    },
    data() {
        return {
            delete_confirm_message: '<b>' + this.$t('Are you sure to delete?') + '</b><br />' + this.$t('delete_all_contacts_notice'),
            double_optin_confirm_message: '<b>' + this.$t('Are you sure to send double optin?') + '</b><br />' + this.$t('send_double_optin_contacts_notice'),
            actions: {},
            select_job: {
                action_name: '',
                selected_options: []
            },
            doing_action: false,
            select_status: '',
            allSelected: false,
            processCount: 0,
            processingAllBulk: false,
            currentAllActionLastContactId: 0,
            custom_field: {}
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
            return this.pagination && this.selectedSubscribers.length < this.pagination.total && this.selectedSubscribers.length == this.pagination.per_page && window.fcrm_sub_params && this.$route.name == 'subscribers';
        }
    },
    methods: {
        handleBulkActionRoute() {
            if (!this.canSelectAll || !this.allSelected) {
                this.doBulkAction();
                return false;
            }

            this.$confirm('You are about to apply the selected action to all the contacts (' + this.formatMoney(this.pagination.total) + ') of this filter. Please confirm.', 'Warning', {
                confirmButtonText: 'Yes, let\'s do it!',
                cancelButtonText: 'Cancel',
                type: 'warning'
            }).then(() => {
                // We have to do the bulk action for all contacts
                this.processingAllBulk = true;
                this.processAllBulk(() => {
                    alert('All Contacts has been processed!');
                });
            }).catch(() => {
                this.allSelected = false;
            });
        },
        async doBulkAction() {
            const contactIds = [];
            this.each(this.selectedSubscribers, (subscriber) => {
                contactIds.push(subscriber.id);
            });

            if (!contactIds.length) {
                this.$notify.error(this.$t('Please select subscribers first'));
                return false;
            }

            this.doing_action = true;

            const chunks = chunk(contactIds, 100);

            const data = {
                action_name: this.select_job.action_name,
                action_options: this.select_job.selected_options,
                new_status: this.select_status,
                custom_field: this.custom_field
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
            data.subscriber_ids = chunk;
            this.processCount += chunk.length;
            return this.$post('subscribers/do-bulk-action', data);
        },
        processAllBulk(completedCallBack) {
            const data = {
                is_all: 'yes',
                action_name: this.select_job.action_name,
                action_options: this.select_job.selected_options,
                new_status: this.select_status,
                last_id: this.currentAllActionLastContactId,
                per_page: 400,
                contact_query: window.fcrm_sub_params
            };

            this.$post('subscribers/do-bulk-action', data)
                .then(response => {
                    this.processCount += parseInt(response.completed_contacts);
                    if (response.is_completed) {
                        this.processingAllBulk = false;
                        this.$emit('refetch');
                        this.$notify.success(response.message);
                        if (completedCallBack) {
                            completedCallBack();
                        }
                    } else {
                        this.currentAllActionLastContactId = response.last_contact_id;
                        this.$nextTick(() => {
                            this.processAllBulk(completedCallBack);
                        });
                    }
                })
                .catch(errors => {
                    this.handleError(errors);
                });
        },
        updateCustomFieldValue(field) {
            this.custom_field = field;
            this.handleBulkActionRoute();
        }
    },
    mounted() {
        this.actions = {
            add_to_tags: {
                label: this.$t('Add To Tags'),
                options: this.options.tags
            },
            add_to_lists: {
                label: this.$t('Add To Lists'),
                options: this.options.lists
            },
            remove_from_tags: {
                label: this.$t('Remove From Tags'),
                options: this.options.tags
            },
            remove_from_lists: {
                label: this.$t('Remove From Lists'),
                options: this.options.lists
            },
            change_contact_status: {
                label: this.$t('Change Contact Status'),
                statuses: this.options.statuses
            },
            change_contact_type: {
                label: this.$t('Change Contact Type'),
                custom_options: this.appVars.contact_types,
                btn_text: this.$t('Change Contact Type'),
                is_multiple: false
            },
            add_to_email_sequence: {
                label: this.$t('Add To Email Sequence')
            },
            add_to_automation: {
                label: this.$t('Add To Automation Funnel')
            }
        }

        if (this.appVars.custom_contact_bulk_actions && this.appVars.custom_contact_bulk_actions.length) {
            this.each(this.appVars.custom_contact_bulk_actions, (action) => {
                if (!this.actions[action.action_name]) {
                    this.actions[action.action_name] = action;
                }
            });
        }

        if (this.has_company_module) {
            this.actions.add_to_company = {
                label: this.$t('Add To Company')
            }
            this.actions.remove_from_company = {
                label: this.$t('Remove From Company')
            }
        }

        this.actions.send_double_optin = {
            label: this.$t('Send Double Optin To Pending Contacts')
        }
        this.actions.update_custom_fields = {
            label: this.$t('Update Custom Fields')
        }
        this.actions.delete_contacts = {
            label: this.$t('Delete Contacts')
        }
    }
}
</script>

<style lang="scss" scoped>
.fc_bulk_navs {
    background: #f5f7fa;
    margin: 20px -15px -28px;
    padding: 0;
    border-top: 1px solid #e3e8ee;

    p {
        padding: 5px 15px 15px;
    }
}
</style>
