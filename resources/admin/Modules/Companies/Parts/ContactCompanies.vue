<template>
    <div class="fc_sidebar_card fc_sidebar_card_customer">
        <div class="fc_card_header">
            <h3>
                {{$t('Companies')}} <span v-if="subscriber.companies">({{subscriber.companies.length}})</span>
            </h3>
            <div class="fluentcrm-actions">
                <el-button @click="showAddCompanyDrawer()" size="small" type="text">{{$t('+ Add')}}</el-button>
            </div>
        </div>
        <div class="fc_sidebar_card_content">
            <div v-if="subscriber.companies && subscriber.companies.length" class="fc_companies">
                <company-card @showDetails="showCompanyDetails" :company='company' :subscriber="subscriber"  v-for="(company, companyIndex) in subscriber.companies" @removed="companyRemoved(companyIndex)" :key="company.id" />
            </div>
            <div class="fc_full_box" v-else>
                <div style="padding: 10px 10px 20px;" class="text-align-center">
                    <p>{{ $t('Contact is not associated with any companies') }}</p>
                    <el-button size="small" @click="showAddCompanyDrawer()" type="default">{{ $t('Assign Company') }}</el-button>
                </div>
            </div>
        </div>
        <el-drawer
            class="fc_company_info_drawer"
            :with-header="true"
            :size="globalDrawerSize"
            :title="showingCompany ? showingCompany.name : ''"
            :append-to-body="true"
            :visible.sync="drawerVisible">
            <div style="padding: 10px 15px;">
                <company-info-side v-if="drawerVisible" :company="showingCompany" :is_drawer="true" />
            </div>
        </el-drawer>

        <el-drawer
            class="fc_company_info_drawer"
            :with-header="true"
            :size="globalDrawerSize"
            :title="assignState == 'existing' ? $t('Add existing company') : $t('Create company & Assign')"
            :append-to-body="true"
            :visible.sync="newCompanyDrawer">
            <div v-if="newCompanyDrawer" style="padding: 10px 15px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <el-radio-group v-model="assignState">
                        <el-radio-button value="existing" label="existing">{{ $t('Add Existing') }}</el-radio-button>
                        <el-radio-button value="new" label="new">{{ $t('Create New') }}</el-radio-button>
                    </el-radio-group>
                </div>
                <div v-if="assignState == 'existing'">
                    <company-assign-selector @companiesAttached="() => { newCompanyDrawer = false; }" :subscriber="subscriber" />
                </div>
                <div v-else>
                    <company-info-side @companyCreated="newCompanyAssigned" :intended_contact_id="subscriber.id" :company="new_company" :is_drawer="true" />
                </div>
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import CompanyCard from '@/Modules/Companies/Parts/CompanyCard.vue';
import CompanyInfoSide from '@/Modules/Companies/Parts/CompanyInfoSide.vue';
import CompanyAssignSelector from '@/Modules/Companies/Parts/CompanyAssignSelector.vue';
export default {
    name: 'ContactCompanies',
    props: ['subscriber'],
    components: {
        CompanyCard,
        CompanyInfoSide,
        CompanyAssignSelector
    },
    data() {
        return {
            showingCompany: null,
            drawerVisible: false,
            new_company: {
                owner_id: '' + this.subscriber.id
            },
            newCompanyDrawer: false,
            assignState: 'existing'
        }
    },
    methods: {
        showCompanyDetails(company) {
            this.showingCompany = company;
            this.drawerVisible = true
        },
        showAddCompanyDrawer() {
            this.new_company = {
                owner_id: '' + this.subscriber.id
            }
            this.assignState = 'existing'
            this.newCompanyDrawer = true
        },
        newCompanyAssigned(company) {
            this.newCompanyDrawer = false;

            this.new_company = {
                owner_id: '' + this.subscriber.id
            }

            this.subscriber.companies.push(company);
        },
        companyRemoved(index) {
            this.subscriber.companies.splice(index, 1);
        }
    }
}
</script>
