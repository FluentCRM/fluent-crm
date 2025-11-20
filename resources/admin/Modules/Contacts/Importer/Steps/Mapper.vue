<template>
    <div class="dc_csv_mapper">
        <el-form v-if="!importing" label-width="100px" label-position="top">
            <!--map fields-->
            <el-form-item>
                <template slot="label">
                    {{$t('Mapper.title')}}
                    <el-tooltip class="item" placement="bottom-start" effect="light">
                        <div slot="content">
                            <p>
                                {{$t('Mapper.title_desc')}}
                            </p>
                        </div>

                        <i class="el-icon-info text-info"></i>
                    </el-tooltip>
                </template>
            </el-form-item>

            <!--mapper-->
            <el-form-item>
                <table class="fc_horizontal_table">
                    <thead>
                    <tr>
                        <th>{{$t('CSV Headers')}}</th>
                        <th>{{$t('Subscriber Fields')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in headers" :key="index">
                        <td>
                            <el-input :value="item" disabled/>
                        </td>
                        <td>
                            <el-select clearable v-model="form.map[index].table">
                                <el-option-group :label="$t('Main Contact Properties')">
                                    <el-option
                                        v-for="(column, field) in columns"
                                        :key="field"
                                        :label="column"
                                        :value="field">
                                        {{ column }}
                                    </el-option>
                                </el-option-group>
                                <el-option-group :label="$t('Custom Contact Properties')">
                                    <el-option
                                        v-for="custom_field in options.custom_fields"
                                        :key="custom_field.slug"
                                        :label="custom_field.label"
                                        :value="custom_field.slug">
                                        {{ custom_field.label }}
                                    </el-option>
                                </el-option-group>
                            </el-select>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </el-form-item>

            <el-row :gutter="20">
                <el-col v-if="!listId" :lg="12" :md="12" :sm="12" :xs="12">
                    <!--lists input-->
                    <tl-select
                        :label="$t('Lists')"
                        :option="options.lists"
                        v-model="form.lists"
                    />
                </el-col>

                <el-col :lg="12" :md="12" :sm="12" :xs="12">
                    <!--tags input-->
                    <tl-select
                        :label="$t('Tags')"
                        :option="options.tags"
                        v-model="form.tags"
                    />
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
                                        {{$t('update_subscribers_data_notice')}}
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
                        <el-select :placeholder="$t('Status')" v-model="form.new_status">
                            <el-option v-for="option in options.statuses" :key="option.slug" :label="option.title"
                                       :value="option.slug"></el-option>
                        </el-select>
                        <el-checkbox v-if="form.new_status == 'pending'" true-label="yes" v-model="form.double_optin_email">{{$t('Send Double Optin Email for new contacts')}}</el-checkbox>
                    </el-form-item>
                </el-col>
            </el-row>
            <el-checkbox style="margin-top: 20px;" true-label="yes" false-label="no" v-model="form.import_silently">
                {{$t('Do Not Trigger Automations (Tag & List related Events)')}}
            </el-checkbox>
            <el-checkbox v-if="form.update" style="margin-top: 20px;" true-label="yes" v-model="form.force_update_status">
                {{$t('force_update_contact_status')}}
            </el-checkbox>
        </el-form>
        <div v-else class="importing_stats">
            <div v-if="!import_status.total || import_status.has_more" class="text-align-center">
                <h4>{{$t('Map_Importing_CfyCPD')}}</h4>
                <el-progress :text-inside="true" :stroke-width="40" :percentage="completed_percent"
                             status="success"></el-progress>
                <h3>{{import_status.completed}} / {{import_status.total}}</h3>
            </div>

            <div v-if="import_status.total && !import_status.has_more">
                <h2>{{$t('Map_Completed_Ycctmn')}}</h2>
            </div>

            <div class="wfc_well">
                <ul>
                    <li>{{$t('Total Inserted:')}} {{inserted}}</li>
                    <li>{{$t('Total Updated:')}} {{updated}}</li>
                    <li>{{$t('Invalid Emails:')}} {{invalid_email_counts}}</li>
                    <li>{{$t('Map_Total_S_I')}} {{skipped}}</li>
                </ul>
            </div>

            <div v-if="errors">
                <h3>Errors</h3>
                <pre style="white-space: pre-wrap;">{{errors}}</pre>
            </div>

            <div class="fc_log_wrapper" style="margin-top: 20px;" v-if="invalid_contacts.length || skipped_contacts.length">
                <el-button-group>
                    <el-button size="small" type="info" v-if="invalid_contacts.length" @click="showing_contacts = 'invalid_contacts'">
                        {{$t('Show Invalid Contacts')}}
                    </el-button>
                    <el-button size="small" type="info" v-if="skipped_contacts.length" @click="showing_contacts = 'skipped_contacts'">
                        {{$t('Show Skipped Contacts')}}
                    </el-button>
                    <el-button size="small" type="danger" v-if="errors" @click="showing_contacts = 'errors'">
                        {{$t('Show Errors')}}
                    </el-button>
                </el-button-group>
                <div v-if="showing_contacts == 'invalid_contacts'">
                    <h3>{{$t('Contacts that are invalid')}}</h3>
                    <pre>{{invalid_contacts}}</pre>
                    <el-button size="mini" @click="showing_contacts = ''">{{$t('Close Log')}}</el-button>
                </div>
                <div v-else-if="showing_contacts == 'skipped_contacts'">
                    <h3>{{$t('Map_Contacts_tad')}}</h3>
                    <pre>{{skipped_contacts}}</pre>
                    <el-button size="mini" @click="showing_contacts = ''">{{$t('Close Log')}}</el-button>
                </div>
                <div v-else-if="showing_contacts == 'errors'" class="errors">
                    <p>{{$t('Errors')}}</p>
                    <pre>{{errors}}</pre>
                    <el-button size="mini" @click="showing_contacts = ''">{{$t('Close Log')}}</el-button>
                </div>
            </div>

        </div>

        <div v-if="!importing" slot="footer" class="dialog-footer">
            <el-button
                size="small"
                type="primary"
                @click="save">
                {{$t('Confirm Import')}}
            </el-button>
        </div>
    </div>

</template>

<script>
import TlSelect from '@/Pieces/TlSelect';

export default {
    name: 'Mapper',
    components: {
        TlSelect
    },
    props: ['map', 'headers', 'columns', 'options', 'csv', 'listId', 'tagId'],
    data() {
        return {
            form: {
                map: this.map,
                tags: [],
                lists: [],
                update: false,
                new_status: '',
                custom_values: {},
                delimiter: this.options.delimiter,
                import_silently: 'no'
            },
            importing_page: 1,
            total_page: 1,
            importing: false,
            import_status: {},
            skipped: 0,
            invalid_email_counts: 0,
            inserted: 0,
            updated: 0,
            errors: '',
            skipped_contacts: [],
            invalid_contacts: [],
            showing_contacts: ''
        }
    },
    computed: {
        completed_percent() {
            if (!this.import_status.total) {
                return 0;
            }
            return parseInt((this.import_status.completed / this.import_status.total) * 100);
        }
    },
    watch: {
        map() {
            this.form.map = this.map;
        }
    },
    beforeMount() {
        this.columns.lists = 'Lists';
        this.columns.tags = 'Tags';
        if (this.headers.includes('Lists')) {
            const item = this.map.find(obj => obj.csv === 'Lists');
            if (item) {
                item.table = 'lists';
            }
        }
        if (this.headers.includes('Tags')) {
            const item = this.map.find(obj => obj.csv === 'Tags');
            if (item) {
                item.table = 'tags';
            }
        }
    },
    methods: {
        save() {
            const mappings = this.form.map.filter(o => o.table);
            if (!mappings.length) {
                this.$notify({
                    title: this.$t('Warning'),
                    type: 'warning',
                    offset: 20,
                    message: this.$t('No mapping found.')
                });
                return;
            }

            if (this.listId) {
                this.form.lists.push(this.listId);
            }

            if (this.tagId) {
                this.form.tags.push(this.tagId);
            }
            this.importing = true;
            this.$post('import/csv-import', {
                ...this.form,
                file: this.csv,
                importing_page: this.importing_page
            }).then(response => {
                if (!response || typeof (response) != 'object' || !response.total) {
                    this.errors = response;
                    return false;
                }

                this.import_status = response;

                this.skipped += response.skipped;
                this.invalid_email_counts += response.invalid_email_counts;
                this.inserted += response.inserted;
                this.updated += response.updated;
                if (response.invalid_contacts && response.invalid_contacts.length) {
                    this.invalid_contacts.push(response.invalid_contacts);
                }

                if (response.skipped_contacts && response.skipped_contacts.length) {
                    this.skipped_contacts.push(response.skipped_contacts);
                }

                if (response.has_more) {
                    this.importing_page++;
                    this.save();
                } else {
                    this.$notify({
                        title: this.$t('Success'),
                        type: 'success',
                        offset: 20,
                        message: this.$t('Map_Subscribers_is')
                    });
                    this.$emit('fetch');
                }
                // this.$emit('fetch');
                // this.$emit('close');
            }).catch(r => {
                this.errors = r;
                const keys = Object.keys(r.data);
                this.$notify({
                    title: this.$t('Error'),
                    type: 'error',
                    offset: 20,
                    message: r.data[keys[0]]
                });
                this.importing = false;
            })
                .finally(() => {

                });
        }
    }
}
</script>
