<template>
    <div v-if="has_campaign_pro" class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header d-flex items-center justify-between">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('CRM Managers') }}</h3>
                <p>{{ $t('all_admin_managers_vue') }}</p>
            </div>
            <div v-if="has_campaign_pro" class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    type="primary"
                    size="medium"
                    @click="addManager()"
                >
                    {{ $t('Add New Manager') }}
                </el-button>
            </div>
        </div>
        <div v-if="has_campaign_pro" class="fluentcrm_pad_b_20" style="position: relative;">
            <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table border v-else :empty-text="$t('No Data Found')" stripe :data="managers" style="width: 100%">
                <el-table-column :label="$t('ID')" prop="id" width="80"/>
                <el-table-column :label="$t('Name')" width="150">
                    <template slot-scope="scope">
                        {{ scope.row.first_name }} {{ scope.row.last_name }}
                    </template>
                </el-table-column>
                <el-table-column :label="$t('Email')" width="250">
                    <template slot-scope="scope">
                        {{ scope.row.email }}
                    </template>
                </el-table-column>
                <el-table-column :min-width="300" :label="$t('Permissions')">
                    <template #default="scope">
                        <el-tag
                            type="info"
                            size="mini"
                            v-for="permission in scope.row.permissions"
                            :key="permission">
                            {{ readable(permission) }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column fixed="right" :label="$t('Actions')" min-width="100">
                    <template #default="scope">
                        <el-button @click="initEdit(scope.row)" size="mini" type="primary" icon="el-icon-edit"/>
                        <confirm @yes="removeManager(scope.row)">
                            <el-button
                                size="mini"
                                type="danger"
                                slot="reference"
                                icon="el-icon-delete"
                            />
                        </confirm>
                    </template>
                </el-table-column>
            </el-table>
            <pagination :pagination="pagination" @fetch="fetch"/>
        </div>

        <el-dialog
            :title="(editing_manager.id) ? $t('Edit Manager') : $t('Add new Manager')"
            :visible.sync="show_modal"
            :append-to-body="true"
            :close-on-click-modal="false"
            @submit.native.prevent="doNothing"
            :destroy-on-close="true"
            width="60%">
            <el-form :data="editing_manager" label-position="top">
                <el-form-item :label="$t('User Email')">
                    <el-popover
                        trigger="manual"
                        v-model="showSearchResults"
                        placement="bottom"
                        width="400"
                        popper-class="fc-user-search-popover"
                    >
                        <div slot="reference" class="fc_board_member_add">
                            <el-input
                                type="email"
                                :disabled="editing_manager.id ? true : false"
                                :placeholder="$t('Type User Email Address')"
                                v-model="editing_manager.email"
                                @focus="onFocusSearch"
                                @blur="onBlurSearch('input')"
                            >
                            </el-input>
                            <p v-show="!editing_manager.id">{{ $t('Managers.user_email.info') }}</p>
                        </div>
                        <div ref="searchResultsDiv" @mouseout="onBlurSearch('results')">
                            <el-skeleton animated v-if="wpUsersLoading"  :rows="3" :throttle="300"/>
                            <ul class="fc-user-search-list-group" v-else-if="wordpressUser.length > 0">
                                <li
                                    v-for="user in wordpressUser"
                                    :key="user.id"
                                    class="fc-search-user-list"
                                    @click="onClickEachResults(user)"
                                >
                                    <span v-if="user.user_email"> {{ user.title }} </span>
                                </li>
                            </ul>

                            <div v-else-if="!wpUsersLoading && editing_manager?.email?.length > 2" class="fc-user-not-found">
                                <span>
                                    <span>{{ $t('No user found with your query.') }}</span>
                                </span>
                            </div>
                        </div>
                    </el-popover>

                </el-form-item>
                <el-form-item :label="$t('Permissions')">
                    <hr />
                    <div>
                        <el-checkbox style="min-width: 250px" v-model="check_all" @change="handleAllPermission">
                            {{ $t('Check All') }}
                        </el-checkbox>
                    </div>
                    <div v-for="permissionSet in group_permissions" :key="permissionSet.title">
                        <p style="margin: 15px 0 10px; font-size: 16px; line-height: 100%; color: #828282;">{{ permissionSet.title | ucFirst }} Permissions</p>
                        <el-checkbox-group v-model="editing_manager.permissions">
                            <el-checkbox style="min-width: 250px" v-for="(permission, permissionKey) in permissionSet.permissions"
                                         @change="handleChangePermission(permission, permissionKey)"
                                         :label="permissionKey"
                                         :key="permissionKey"
                            >
                                {{ permission.title }}
                            </el-checkbox>
                        </el-checkbox-group>
                    </div>
                </el-form-item>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button :type="editing_manager.id ? 'success':'primary'" @click="save()">
                    {{ editing_manager.id ? $t('Update') : $t('Create') }}
                </el-button>
          </span>
        </el-dialog>
    </div>
    <div v-else>
        <div class="fc_narrow_box fluentcrm_databox text-align-center">
            <h2 class="">{{ $t('CRM Managers - Roles and Permissions') }}</h2>
            <p class="text-align-center">{{ $t('crm_managers_roles_and_permissions') }}</p>
            <hr/>
            <p>{{ $t('Upgrade_To_Pro') }}</p>
            <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank"
               rel="noopener">{{ $t('Get FluentCRM Pro') }}</a>
        </div>
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'Managers',
    components: {
        Confirm,
        Pagination
    },
    data() {
        return {
            managers: [],
            pagination: {
                total: 0,
                current_page: 1,
                per_page: 10
            },
            loading: false,
            permissions: {},
            editing_manager: {},
            check_all: false,
            show_modal: false,
            saving: false,
            showSearchResults: false,
            wordpressUser: [],
            wpUsersLoading: false
        }
    },
    computed: {
        group_permissions() {
            const permissionGroups = {};
            this.each(this.permissions, (permission, permissionKey) => {
                if (!permissionGroups[permission.group]) {
                    permissionGroups[permission.group] = {
                        title: permission.group,
                        permissions: {}
                    }
                }
                permissionGroups[permission.group].permissions[permissionKey] = permission;
            });

            return permissionGroups;
        }
    },
    watch: {
        'editing_manager.email'(newValue, oldValue) {
            if (newValue.length > 2) {
                if (!this.editing_manager.id) {
                    this.showSearchResults = true;
                    this.getWordpressUser(newValue);
                }
            } else {
                this.wordpressUser = [];
                this.showSearchResults = false;
                this.wpUsersLoading = false;
            }
        },
        'editing_manager.permissions'(newValue) {
            if (newValue.length === Object.keys(this.permissions).length) {
                this.check_all = true;
            } else {
                this.check_all = false;
            }
        }
    },
    methods: {
        onFocusSearch() {
            if (this.editing_manager.email.length > 2) {
                this.showSearchResults = true;
                this.getWordpressUser(this.editing_manager.email);
            }
        },
        onBlurSearch(source) {
            // if the cursor/mouse is on the search results dive then don't hide the search results
            if (source == 'results') {
                return;
            }
            setTimeout(() => {
                this.showSearchResults = false;
            }, 200);
        },
        onClickEachResults (user) {
            this.editing_manager.email = user.user_email;
        },
        getWordpressUser(searchInput) {
            if (this.editing_manager.email.length < 4) {
                this.wpUsersLoading = true;
            }
            this.$get('reports/ajax-options', {
                option_key: 'users',
                search: searchInput
            })
                .then((res) => {
                    this.wordpressUser = res.options;
                })
                .catch((error) => {
                    this.$handleError(error);
                }).finally(() => {
                    this.wpUsersLoading = false;
                });
        },
        handleAllPermission() {
            if (this.check_all) {
                const permissions = [];
                this.each(this.permissions, (permission, permissionKey) => {
                    permissions.push(permissionKey);
                });
                this.editing_manager.permissions = permissions;
            } else {
                this.editing_manager.permissions = [];
            }
        },
        // Auto Check Dependent Permission
        handleChangePermission(permission, permissionKey) {
            if (this.editing_manager.permissions.includes(permissionKey)) {
                // Forcely Assign Dependent Permissions
                permission.depends.forEach(dependentPermission => {
                    if (!this.editing_manager.permissions.includes(dependentPermission)) {
                        this.editing_manager.permissions.push(dependentPermission);
                    }
                })
            } else {
                const permissionKeys = Object.keys(this.permissions);
                // Forcely Remove Permissions that Depends on the Current Permission
                permissionKeys.forEach(globalPermissionKey => {
                    if (this.permissions[globalPermissionKey].depends.includes(permissionKey)) {
                        this.editing_manager.permissions = this.editing_manager.permissions.filter(p => p != globalPermissionKey);
                    }
                })
            }
        },
        doNothing() {

        },
        fetch() {
            this.loading = true;
            this.$get('campaign-pro-settings/managers', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            })
                .then(response => {
                    this.permissions = response.permissions;
                    this.managers = response.managers.data;
                    this.pagination.total = response.managers.total;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        readable(permission) {
            if (this.permissions[permission]) {
                return this.permissions[permission].title;
            }
            return permission;
        },
        addManager() {
            this.editing_manager = {
                email: '',
                permissions: []
            }
            this.show_modal = true;
            this.check_all = false;
        },
        removeManager(manager) {
            this.$del(`campaign-pro-settings/managers/${manager.id}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetch();
                })
                .catch((errors) => {
                    this.handleError(errors);
                });
        },
        initEdit(manager) {
            this.editing_manager = manager;
            this.show_modal = true;
            this.check_all = false;
        },
        save() {
            this.saving = true;
            let $request = this.$post('campaign-pro-settings/managers', {
                manager: this.editing_manager
            });

            if (this.editing_manager.id) {
                $request = this.$put(`campaign-pro-settings/managers/${this.editing_manager.id}`, {
                    manager: this.editing_manager
                });
            }

            $request.then(response => {
                this.$notify.success(response.message);
                this.fetch();
                this.show_modal = false;
            })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                    this.check_all = false;
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
