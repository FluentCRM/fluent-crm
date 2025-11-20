<template>
    <div class="">
        <el-button-group>
            <el-button
                size="small"
                icon="el-icon-plus"
                type="primary"
                @click="toggle('new')">
                {{$t('Add Company')}}
            </el-button>
            <el-button
                size="small"
                type="info"
                icon="el-icon-upload"
                @click="toggle('csv')">
                {{$t('Import')}}
            </el-button>
            <el-button
                size="small"
                type="info"
                icon="el-icon-download"
                @click="exportCompanies()">
                {{$t('Export')}}
            </el-button>
        </el-button-group>
        <el-drawer
            class="fc_company_info_drawer"
            :with-header="true"
            :size="globalDrawerSize"
            :title="createState == 'new' ? $t('Create company') : $t('Import CSV')"
            :append-to-body="true"
            :visible.sync="newCompanyDrawer">
            <div v-if="newCompanyDrawer" style="padding: 10px 15px;">
                <company-info-side v-if="createState == 'new'" @companyCreated="companyCreated" :company="new_company" :is_drawer="true" />
                <div v-else>
                    <company-csv-importer @imported="handleImported()" />
                </div>
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
    import CompanyInfoSide from './Parts/CompanyInfoSide.vue';
    import CompanyCsvImporter from './Parts/CompanyCsvImporter.vue';

    export default {
        name: 'ActionMenu',
        components: {
            CompanyInfoSide,
            CompanyCsvImporter
        },
        props: ['options', 'query_data'],
        data() {
            return {
                newCompanyDrawer: false,
                new_company: {},
                createState: 'new'
            }
        },
        methods: {
            toggle(state) {
                this.createState = state;
                this.newCompanyDrawer = true;
            },
            fetch(data) {
                this.$emit('fetch', data);
            },
            companyCreated(company) {
                this.$router.push({ name: 'view_company', params: { company_id: company.id }});
            },
            handleImported() {
                this.fetch({});
                this.newCompanyDrawer = false;
            },
            exportCompanies() {
                location.href = window.ajaxurl + '?' + jQuery.param({
                    action: 'fluentcrm_export_companies',
                    ...this.query_data,
                    format: 'csv'
                });
            }
        }
    }
</script>
