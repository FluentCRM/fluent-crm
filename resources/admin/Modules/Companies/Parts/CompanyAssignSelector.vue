<template>
    <div>
        <el-input style="margin-bottom: 20px;" v-model="company_search" :placeholder="$t('Search Companies')">
            <el-button slot="append" icon="el-icon-search" @click="fetchCompanies"></el-button>
        </el-input>

        <div v-if="loading">
            <el-skeleton :animated="true" :rows="3"/>
        </div>
        <div v-else-if="searched && !companies.length">
            <p>{{ $t('No companies found based on your search') }}</p>
        </div>
        <div v-else class="fc_companies">
            <el-checkbox-group class="fc_rich_checkboxes" v-model="selectedIds">
                <el-checkbox v-for="company in companies" :key="company.id" :label="company.id">
                    <div class="fc_company_card">
                        <div class="fc_company_body">
                            <div class="company_name">
                                {{ company.name }}
                            </div>
                            <div class="company_domain">
                                {{ company.website }}
                            </div>
                            <div v-if="company.email || company.phone" class="company_email">
                                <span v-if="company.email">{{ company.email }} <span v-show="company.phone"
                                                                                     class="fc_middot">·</span></span>
                                <span>{{ company.phone }}</span>
                            </div>
                        </div>
                        <div class="fc_company_logo fc_company_photo">
                            <img :src="company.logo" alt="">
                        </div>
                    </div>
                </el-checkbox>
            </el-checkbox-group>

            <div v-if="selectedIds.length">
                <div style="padding: 10px 10px 20px;">
                    <el-button :disabled="attaching" v-loading="attaching" @click="attachCompanies()" size="small"
                               type="success">{{ $t('Assign selected companies') }}
                        ({{ selectedIds.length }})
                    </el-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'CompanyAssignSelector',
    props: ['subscriber'],
    components: {},
    data() {
        return {
            company_search: '',
            companies: [],
            searched: false,
            loading: false,
            selectedIds: [],
            attaching: false
        }
    },
    mounted() {
        this.fetchCompanies();
    },
    watch: {
        company_search: {
            handler: function(newVal, oldVal) {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.fetchCompanies();
                }, 500);
            },
            immediate: false
        }
    },
    methods: {
        fetchCompanies() {
            this.loading = true;
            this.selectedIds = [];
            this.$get('companies/search', {
                limit: 10,
                search: this.company_search,
                subscriber_id: this.subscriber.id
            })
                .then(response => {
                    this.companies = response.results;
                    this.searched = true;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        attachCompanies() {
            this.attaching = true;
            this.$post('companies/attach-subscribers', {
                subscriber_ids: [this.subscriber.id],
                company_ids: this.selectedIds
            })
                .then(response => {
                    this.$notify.success(response.message);

                    if (response.companies) {
                        this.each(response.companies, (company) => {
                            this.subscriber.companies.push(company);
                        });
                        this.$emit('companiesAttached', response.companies);
                    }
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.attaching = false;
                });
        }
    },
    beforeDestroy() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
    }
}
</script>
