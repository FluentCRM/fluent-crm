<template>
    <div>
        <el-tabs type="border-card" @tab-click="tabClicked" :value="activeTab">
            <el-tab-pane :label="$t('CSV')" name="Csv">
                <el-steps :active="activeCsv" finish-status="success" align-center>
                    <el-step :title="$t('Step 1')"></el-step>
                    <el-step :title="$t('Step 2')"></el-step>
                </el-steps>

                <div v-if="activeCsv===1" style="text-align:center;">
                    <h3>{{$t('Upload Your CSV file')}}: </h3>
                    <el-row>
                        <el-col>
                            <el-upload
                                drag
                                accept=".csv"
                                :limit="1"
                                :action="uploadUrl"
                                :on-success="fileUploaded"
                                :on-remove="fileRemoved"
                            >
                                <i class="el-icon-upload"></i>
                                <div class="el-upload__text">{{$t('Drop file here or')}} <em>{{$t('click to upload')}}</em></div>
                            </el-upload>
                        </el-col>
                    </el-row>
                </div>

                <div v-if="activeCsv===2">
                    <el-row :gutter="20" v-for="(column, key) in csvMapping" :key="key">
                        <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
                            <el-input readonly v-model="csvMapping[key].csv"></el-input>
                        </el-col>
                        <el-col :xs="12" :sm="12" :md="12" :lg="12" :xl="12">
                            <el-select v-model="csvMapping[key].table" :placeholder="$t('Select')">
                                <el-option
                                    v-for="tableColumn in tableColumns"
                                    :key="tableColumn"
                                    :label="tableColumn"
                                    :value="tableColumn">
                                </el-option>
                            </el-select>
                        </el-col>
                    </el-row>

                    <el-row style="margin-top:10px;">
                        <div style="color:#606266;">{{$t('Select Tags')}}</div>
                        <div>
                            <el-select
                                v-model="selectedTags"
                                multiple
                                :placeholder="$t('Select')">
                                <el-option
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    :label="tag.title"
                                    :value="tag.id">
                                </el-option>
                            </el-select>
                        </div>
                    </el-row>

                    <el-row>
                        <div style="color:#606266;">{{$t('Select Lists')}}</div>
                        <div>
                            <el-select
                                v-model="selectedLists"
                                multiple
                                :placeholder="$t('Select')"
                            >
                                <el-option
                                    v-for="list in lists"
                                    :key="list.id"
                                    :label="list.title"
                                    :value="list.id">
                                </el-option>
                            </el-select>
                        </div>
                    </el-row>
                </div>

                <div style="margin-top:10px;">
                    <el-button @click="prev" v-if="activeCsv>1" size="small">{{$t('Prev step')}}</el-button>
                    <el-button @click="next" v-if="activeCsv<2" size="small">{{$t('Next step')}}</el-button>

                    <el-button
                        @click="confirmCsv"
                        :disabled="activeCsv !== 2"
                        size="small"
                        type="primary"
                        style="float:right;"
                    >{{ $t('Confirm') }}
                    </el-button>
                </div>
            </el-tab-pane>

            <el-tab-pane :label="$t('WP Users')" name="User">
                <el-steps :active="activeUser" finish-status="success" align-center>
                    <el-step :title="$t('Step 1')"></el-step>
                    <el-step :title="$t('Step 2')"></el-step>
                </el-steps>

                <div v-if="activeUser===1" style="text-align:center;">
                    <h3>{{$t('Select by Roles')}}: </h3>
                    <el-row>
                        <el-checkbox
                            v-model="checkAll"
                            :indeterminate="isIndeterminate"
                            @change="handleCheckAllChange"
                        >{{ $t('All') }}
                        </el-checkbox>

                        <div style="margin: 15px 0;"></div>

                        <el-checkbox-group
                            class="fluentcrm-subscribers-import-check"
                            v-model="selectedRoles"
                            @change="fluentcrmCheckedRolesChange"
                        >
                            <el-checkbox
                                v-for="(role, key) in roles"
                                :label="key"
                                :key="key"
                            >{{ role.name }}
                            </el-checkbox>
                        </el-checkbox-group>

                    </el-row>
                </div>

                <div v-if="activeUser===2">
                    <el-row>
                        <h4>{{$t('User data from database')}}</h4>
                        <el-table
                            :empty-text="$t('No Data Available')"
                            :data="tableData"
                            style="width: 100%">
                            <el-table-column
                                v-for="(column, key) in tableColumn"
                                :prop="column"
                                :key="key"
                                :label="column"
                                sortable
                                width="auto">
                            </el-table-column>
                        </el-table>
                        <el-form>
                            <el-form-item :label="$t('Tags')">
                                <el-select
                                    v-model="selectedTags"
                                    multiple
                                    :placeholder="$t('Select')">
                                    <el-option
                                        v-for="tag in tags"
                                        :key="tag.slug"
                                        :label="tag.title"
                                        :value="tag.slug">
                                    </el-option>
                                </el-select>
                            </el-form-item>
                            <el-form-item :label="$t('Lists')">
                                <el-select
                                    v-model="selectedLists"
                                    multiple
                                    :placeholder="$t('Select')">
                                    <el-option
                                        v-for="list in lists"
                                        :key="list.slug"
                                        :label="list.title"
                                        :value="list.slug">
                                    </el-option>
                                </el-select>
                            </el-form-item>
                        </el-form>
                        <el-radio-group class="fluentcrm-subscribers-import-radio" v-model="IsDataInDatabase">
                            <ul>
                                <li>
                                    <el-radio :label="'skip'">{{$t('Skip if already in DB')}}</el-radio>
                                </li>
                                <li>
                                    <el-radio :label="'update'">{{$t('Update if already in DB')}}</el-radio>
                                </li>
                            </ul>

                        </el-radio-group>
                    </el-row>
                </div>

                <div style="margin-top:10px;">
                    <el-button @click="prev" v-if="activeUser>1" size="small">{{$t('Prev step')}}</el-button>
                    <el-button @click="next" v-if="activeUser<2" size="small">{{$t('Next step')}}</el-button>

                    <el-button
                        @click="confirmCsv"
                        :disabled="activeUser!==2"
                        size="small"
                        type="primary"
                        style="float:right;"
                    >
                        {{$t('Confirm')}}
                    </el-button>
                </div>
            </el-tab-pane>
        </el-tabs>
    </div>
</template>

<script>
export default {
    name: 'Importer',
    data() {
        return {
            activeTab: 'Csv',
            activeCsv: 1,
            activeUser: 1,
            uploadedFile: null,
            uploadUrl: window.ajaxurl + '?action=fluentcrm-post-csv-upload',
            tags: [],
            selectedTags: [],
            lists: [],
            selectedLists: [],
            csvColumns: [],
            tableColumns: [],
            csvMapping: [],

            // WP Users
            roles: [],
            tableData: [],
            checkAll: false,
            isIndeterminate: false,
            selectedRoles: [],
            tableColumn: ['date', 'name', 'address'],
            IsDataInDatabase: ''
        };
    },
    methods: {
        tabClicked(tab, event) {
            this.activeTab = tab.name;
        },
        next() {
            const active = 'active' + this.activeTab;

            if (this.activeTab === 'Csv') {
                if (this.active === 1 && !this.uploadedFile) {
                    return;
                }
            }

            if (this.activeTab === 'User') {
                if (this[active] === 1 && !this.selectedRoles.length) {
                    return;
                }
            }

            if (this.active++ > 2) {
                this[active] = 0;
            }
        },
        prev() {
            const active = 'active' + this.activeTab;
            if (this[active] !== 0) this[active]--;
        },
        fileUploaded(response, file, fileList) {
            this.csvColumns = response.headers;
            this.tableColumns = response.columns;
            this.uploadedFile = response.file;

            const mappings = [];
            for (const key in this.csvColumns) {
                mappings.push({csv: this.csvColumns[key], table: null});
            }
            this.csvMapping = mappings;
        },
        fileRemoved(file, fileList) {
            this.uploadedFile = null;
        },
        confirmCsv() {
            const mappings = this.csvMapping.filter(o => o.table);

            if (!mappings.length) {
                this.$notify({
                    title: this.$t('Warning'),
                    type: 'warning',
                    offset: 20,
                    message: this.$t('No mapping found.')
                });
                return;
            }

            this.$post('import/csv-import', {
                mappings: mappings,
                tags: this.selectedTags,
                lists: this.selectedLists,
                file: this.uploadedFile
            }).then(r => {
                this.$notify({
                    title: this.$t('Success'),
                    type: 'success',
                    offset: 20,
                    message: this.$t('Map_Subscribers_is')
                });
            }).catch(r => console.log(r));
        },
        handleCheckAllChange(val) {
            this.selectedRoles = val ? Object.keys(this.roles) : [];
            this.isIndeterminate = false;
        },
        fluentcrmCheckedRolesChange(value) {
            const roles = Object.keys(this.roles);
            const checkedCount = value.length;
            this.checkAll = checkedCount === roles.length;
            this.isIndeterminate = checkedCount > 0 && checkedCount < roles.length;
        }
    },
    created() {
        this.$get('tags')
            .then(response => {
                this.tags = response;
            })
            .catch(r => this.handleError(r));

        this.$get('lists').then(response => {
            this.lists = response;
        })
            .catch(r => this.handleError(r));

        this.$get('reports/roles')
            .then(r => {
                this.roles = r.roles;
            })
            .catch(errors => this.handleError(errors));
    }
};
</script>
