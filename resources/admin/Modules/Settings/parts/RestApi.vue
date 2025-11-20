<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('REST API Access Management')}}</h3>
                <p>{{$t('rest_api_sub_heading')}}</p>
            </div>
            <div v-if="has_campaign_pro" class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    type="primary"
                    size="medium"
                    @click="addKey()"
                >{{$t('Add New Key')}}
                </el-button>
                <a target="_blank" rel="noopener" href="https://rest-api.fluentcrm.com/" class="el-button el-button--info el-button--medium">
                    {{$t('Documentation')}}
                </a>
            </div>
        </div>
        <div class="" style="position: relative;">
            <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table border v-else :empty-text="$t('No Data Found')" stripe :data="rest_keys" style="width: 100%">

                <el-table-column type="expand">
                    <template #default="scope">
                        <div style="padding-left: 60px; padding-bottom: 6px">
                            <h2 style="margin: 8px 0 8px 0;">{{ $t('APIs') }}</h2>
                            <el-tag
                                type="info"
                                size="mini"
                                v-for="permission in scope.row.api_keys"
                                :key="permission.name"
                                closable
                                @close="deleteKeyConfirm(scope.row.id, permission.uuid)"
                            >{{ permission.name }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column :label="$t('ID')" prop="id" width="80"/>

                <el-table-column :label="$t('Name')">
                    <template slot-scope="scope">
                        {{ scope.row.first_name }} {{ scope.row.last_name }}
                    </template>
                </el-table-column>

                <el-table-column :label="$t('Email')">
                    <template slot-scope="scope">
                        {{ scope.row.email }}
                    </template>
                </el-table-column>

                <el-table-column fixed="right" :label="$t('Actions')" min-width="80">
                    <template #default="scope">
                        <a class="el-button el-button--info el-button--mini" :href="scope.row.manage_url">{{$t('Manage APIs')}}</a>
                    </template>
                </el-table-column>

            </el-table>
        </div>
        <el-dialog
            :title="$t('Delete API Key')"
            :visible.sync="dialogDeleteApiKeyVisible"
            :close-on-click-modal="false"
            :append-to-body="true"
            width="30%">
            <strong><span>{{$t('Api key delete confirmation')}}</span></strong>
            <br><span class="warning">{{$t('Api key delete warning')}}</span>
            <span slot="footer" class="dialog-footer">
                <el-button @click="cancelDeleteApiKey">Cancel</el-button>
                <el-button type="danger" @click="deleteApiKey">Confirm</el-button>
            </span>
        </el-dialog>

        <el-dialog
            :title="$t('Add New REST API Key')"
            :visible.sync="show_modal"
            :close-on-click-modal="false"
            :append-to-body="true"
            width="60%">
            <template v-if="!managers.length">
                <h3>{{$t('RestApi.Please_Create_A_Manager')}}</h3>
            </template>
            <el-form v-else-if="!created_key" :data="adding_key" label-position="top">
                <el-form-item :label="$t('Name of this key')">
                    <el-input
                        type="text"
                        :placeholder="$t('Friendly Name for identification')"
                        v-model="adding_key.api_name"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Associate FluentCRM Manager (Non-Admin Only)')">
                    <el-select v-model="adding_key.api_user_id">
                        <el-option v-for="manager in managers" :key="manager.id" :value="manager.id"
                                   :label="manager.full_name + ' ' + manager.email"></el-option>
                    </el-select>
                </el-form-item>
            </el-form>
            <div v-else class="fc_api_created_response">
                <h3>{{$t('REST API key has been created to selected user')}}</h3>
                <p style="color: red;">{{$t('Please save the details as you can not retrieve again')}}</p>
                <el-row :gutter="20">
                    <el-col :span="12">
                        {{$t('API Username:')}}
                        <item-copier :text="created_key.info.api_username" />
                    </el-col>
                    <el-col :span="12">
                        {{$t('API Password:')}}
                        <item-copier :text="created_key.info.api_password" />
                    </el-col>
                    <el-col :span="24">
                        <p style="margin-bottom: 50px;">{{$t('RestApi.Use_Basic_Auth_Info')}}</p>
                    </el-col>
                </el-row>
            </div>
            <span v-if="!created_key" slot="footer" class="dialog-footer">
                <el-button type="primary" @click="save()">{{ $t('Create') }}</el-button>
          </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import ItemCopier from '@/Pieces/ItemCopier';
export default {
    name: 'RestKeys',
    components: {
        ItemCopier
    },
    data() {
        return {
            rest_keys: [],
            managers: [],
            loading: false,
            adding_key: {},
            show_modal: false,
            saving: false,
            created_key: false,
            dialogDeleteApiKeyVisible: false,
            apiKeyToDelete: {
                userId: null,
                uuid: null
            }
        }
    },
    methods: {
        deleteKeyConfirm(userId, uuid) {
            this.dialogDeleteApiKeyVisible = true;
            this.apiKeyToDelete.userId = userId;
            this.apiKeyToDelete.uuid = uuid;
        },
        cancelDeleteApiKey() {
            this.apiKeyToDelete.userId = null;
            this.apiKeyToDelete.uuid = null;
            this.dialogDeleteApiKeyVisible = false;
        },
        deleteApiKey() {
            this.loading = true;
            this.$del('setting/rest-keys', {
                user_id: this.apiKeyToDelete.userId,
                uuid: this.apiKeyToDelete.uuid
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                    this.cancelDeleteApiKey();
                });
        },
        fetch() {
            this.loading = true;
            this.$get('setting/rest-keys')
                .then(response => {
                    this.rest_keys = response.rest_keys;
                    this.managers = response.managers;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        addKey() {
            this.adding_key = {
                api_user_id: '',
                api_name: ''
            }
            this.created_key = false;
            this.show_modal = true;
        },
        save() {
            this.saving = true;
            this.$post('setting/rest-keys', {
                ...this.adding_key
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.created_key = response.item;
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    },
    mounted() {
        if (this.has_campaign_pro) {
            this.fetch();
        }
    }
}
</script>
