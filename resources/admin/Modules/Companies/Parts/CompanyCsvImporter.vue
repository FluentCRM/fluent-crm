<template>
    <div class="fc_csv_import fc_company_unsaved fc_company_info_wrapper fc_company_in_drawer">
        <template v-if="has_campaign_pro">
            <div v-if="step == 'upload'">
                <csv-importer @success="handleUploadSuccess" :options="options" />
            </div>
            <div v-if="step == 'map'">
                <h3>Please map the fields</h3>
                <table v-loading="importing" class="fc_horizontal_table">
                    <thead>
                    <tr>
                        <th>{{$t('CSV Headers')}}</th>
                        <th>{{$t('Company Fields')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(item, index) in headers" :key="index">
                        <td>
                            <el-input :value="item" disabled/>
                        </td>
                        <td>
                            <el-select clearable v-model="form.map[index].table">
                                <el-option-group :label="$t('Main Properties')">
                                    <el-option
                                        v-for="(column, field) in columns"
                                        :key="field"
                                        :label="fields[column] || column"
                                        :value="column">
                                    </el-option>
                                </el-option-group>

                                <el-option-group v-if="appVars.company_custom_fields && appVars.company_custom_fields.length" :label="$t('Custom Properties')">
                                    <el-option
                                        v-for="customField in appVars.company_custom_fields"
                                        :key="customField.slug"
                                        :label="customField.label"
                                        :value="'_custom_' + customField.slug">
                                    </el-option>
                                </el-option-group>

                            </el-select>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <el-form v-loading="importing" v-model="form" label-position="top">
                    <el-form-item class="no-margin-bottom">
                        <template slot="label">
                            {{$t('Update Companies')}}
                            <el-tooltip class="item" placement="bottom-start" effect="light">
                                <div slot="content">
                                    <h3>{{$t('Update Companies')}}</h3>
                                    <p>
                                        {{ $t('Do you want to update the companies data') }}
                                        <br> {{ $t('if it\'s already exist?') }}
                                    </p>
                                </div>

                                <i class="el-icon-info text-info"></i>
                            </el-tooltip>
                        </template>
                        <el-radio-group v-model="form.update">
                            <el-radio label="yes">{{$t('Yes')}}</el-radio>
                            <el-radio label="no">{{$t('No')}}</el-radio>
                        </el-radio-group>
                    </el-form-item>
                    <el-form-item>
                        <el-checkbox true-label="yes" false-label="no" v-model="form.create_owner">{{ $t('Create owner as contact if not exist') }}</el-checkbox>
                    </el-form-item>
                </el-form>
                <div class="fc_company_save_wrap">
                    <el-button @click="importCompanies()" v-loading="importing" :disabled="importing" type="success">
                        {{ $t('Import Companies') }}
                        <span v-if="results.total">({{results.total}} / {{results.completed}})</span>
                    </el-button>
                </div>
            </div>
        </template>
        <div class="text-align-center" v-else>
            <generic-promo />
        </div>
    </div>
</template>

<script type="text/babel">
import CsvImporter from '@/Modules/Contacts/Importer/Steps/Csv.vue';
import GenericPromo from '@/Modules/Promos/GenericPromo';
export default {
    name: 'CompanyCsvImporter',
    components: {
        CsvImporter,
        GenericPromo
    },
    data() {
        return {
            step: 'upload',
            options: {
                delimiter: 'comma',
                type: 'company',
                sampleCsv: this.appVars.images_url + '/sample-companies.csv'
            },
            headers: {},
            fields: {},
            columns: [],
            form: {
                map: [],
                file: '',
                update: 'yes',
                create_owner: 'yes'
            },
            importing: false,
            importing_page: 1,
            results: {}
        }
    },
    methods: {
        handleUploadSuccess(response) {
            this.headers = response.headers;
            this.form.map = response.map;
            this.form.file = response.file;
            this.columns = response.columns;
            this.fields = response.fields;
            this.step = 'map'
        },
        importCompanies() {
            this.importing = true;
            this.$post('companies/csv-import', {
                ...this.form,
                importing_page: this.importing_page,
                delimiter: this.options.delimiter
            })
                .then(response => {
                    this.results = response;
                    if (response.has_more) {
                        this.importing_page++;
                        this.importCompanies();
                        return;
                    }

                    this.$notify.success(this.$t('Companies imported successfully'));
                    this.$emit('imported');
                    this.importing = false;
                })
                .catch((errors) => {
                    this.importing = false;
                    this.handleError(errors);
                });
        }
    }
}
</script>
