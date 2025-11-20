<template>
    <div>
        <el-form v-if="!importing" label-position="top" class="manager">
            <el-form-item :label="$t('Use_Some_otumyc')"/>
            <!--sample matching users-->
            <el-table
                border
                stripe
                :data="users"
                style="width: 100%; margin-bottom: 30px;"
            >
                <el-table-column
                    prop="display_name"
                    :label="$t('Name')"
                />

                <el-table-column
                    prop="user_email"
                    :label="$t('Email')"
                />
            </el-table>

            <p v-if="total_count">{{$t('Total Found Result:')}} {{total_count}}</p>

            <el-row :gutter="20">
                <el-col v-if="!listId" :lg="12" :md="12" :sm="12" :xs="12">
                    <!--lists input-->
                    <tl-select :label="$t('Lists')" :option="options.lists" v-model="form.lists"/>
                </el-col>
                <el-col :lg="12" :md="12" :sm="12" :xs="12">
                    <!--tags input-->
                    <tl-select :label="$t('Tags')" :option="options.tags" v-model="form.tags"/>
                </el-col>
            </el-row>

            <el-row :gutter="20">
                <el-col :lg="12" :md="12" :sm="12" :xs="12">
                    <el-form-item class="no-margin-bottom">
                        <template slot="label">
                            {{$t('Update Subscribers')}}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <h3>{{$t('Update Subscribers')}}</h3>
                                    <p>
                                        {{ $t('update_subscribers_data_notice')}}
                                    </p>
                                </div>

                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </template>
                        <el-radio-group v-model="form.update">
                            <el-radio :label="true">{{$t('Yes')}}</el-radio>
                            <el-radio :label="false">{{$t('No')}}</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </el-col>
                <el-col :lg="12" :md="12" :sm="12" :xs="12">
                    <el-form-item class="no-margin-bottom">
                        <template slot="label">
                            {{$t('New Subscriber Status')}}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    {{$t('New Subscriber Status')}}

                                    <p>
                                        {{$t('Map_Status_ftns')}}
                                    </p>
                                </div>
                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </template>
                            <el-select v-model="form.new_status">
                                <el-option v-for="option in options.statuses" :key="option.slug" :value="option.slug" :label="option.title"></el-option>
                            </el-select>
                            <el-checkbox v-if="form.new_status == 'pending'" true-label="yes" v-model="form.double_optin_email">{{$t('Send Double Optin Email for new contacts')}}</el-checkbox>
                    </el-form-item>
                </el-col>
            </el-row>

            <el-checkbox style="margin-top: 20px;" true-label="yes" false-label="no" v-model="form.import_silently">
                {{$t('Do Not Trigger Automations (Tag & List related Events)')}}
            </el-checkbox>

        </el-form>
        <div class="text-align-center" v-else>
            <h3>{{$t('Importing now...')}}</h3>
            <h4>{{$t('Use_Please_dnctm')}}</h4>
            <h2 v-if="import_page_total">{{import_page}}/{{import_page_total}}</h2>
            <el-progress :text-inside="true" :stroke-width="24" :percentage="parseInt((import_page / import_page_total) * 100)" status="success"></el-progress>
        </div>
        <div v-if="!importing" slot="footer" class="dialog-footer">
            <el-button
                size="small"
                type="primary"
                @click="save">
                {{$t('Import Users Now')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
    import TlSelect from '@/Pieces/TlSelect';

    export default {
        name: 'UserImportManager',
        components: {
            TlSelect
        },
        props: ['roles', 'options', 'listId', 'tagId'],
        data() {
            return {
                users: [],
                form: {
                    tags: [],
                    lists: [],
                    update: false,
                    new_status: '',
                    import_silently: 'no'
                },
                import_page: 1,
                importing: false,
                import_page_total: 1,
                total_count: 0
            }
        },
        watch: {
            roles() {
                this.fetch();
            }
        },
        methods: {
            fetch() {
                this.$get('users', {roles: this.roles}).then(response => {
                    this.users = response.users;
                    this.total_count = response.total;
                });
            },
            save() {
                this.importing = true;
                const roles = {};
                for (let i = 0, l = this.roles.length; i < l; i++) {
                    const role = this.roles[i];
                    roles[role] = role;
                }

                if (this.listId) {
                    this.form.lists.push(this.listId);
                }

                if (this.tagId) {
                    this.form.tags.push(this.tagId);
                }

                this.$post('import/users', {
                    ...this.form,
                    roles: roles,
                    page: this.import_page
                }).then(response => {
                    if (response.has_more) {
                        this.import_page = response.next_page;
                        this.import_page_total = response.page_total;
                        this.$nextTick(() => {
                            this.save();
                        });
                    } else {
                        this.$notify.success(response.record_total + ' users have been successfully imported as CRM contacts');
                        this.$emit('fetch');
                        this.$emit('close');

                        if (response.reload_page) {
                            setTimeout(() => {
                                window.location.reload(true);
                            }, 500);
                        }
                    }
                }).catch((error) => {
                    console.log(error);
                });
            },
            listeners() {
                this.addAction('import', 'fluentcrm', type => {
                    if (type === 'users') {
                        this.save();
                    }
                });
            }
        },
        mounted() {
            this.fetch();
            this.listeners();
        }
    }
</script>
