<template>
    <div class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <el-row v-if="company" :gutter="30">
            <el-col :lg="6" :md="8" :sm="24" :xs="24">
                <div class="fluentcrm_header">
                    <div class="fluentcrm_header_title">
                        <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                            <el-breadcrumb-item :to="{ name: 'companies' }">
                                {{ $t('Companies') }}
                            </el-breadcrumb-item>
                            <el-breadcrumb-item>{{company.name}}</el-breadcrumb-item>
                        </el-breadcrumb>
                    </div>
                </div>
                <div class="fc_company_sidebar">
                    <company-info-side :company="company"/>
                </div>
            </el-col>
            <el-col :lg="18" :md="16" :sm="24" :xs="24">
                <el-menu
                    style="margin-bottom: 10px;"
                    mode="horizontal"
                    :router="true"
                    :default-active="activeTab"
                    class="fc_segment_menu"
                >
                    <el-menu-item
                        v-for="(item, i) in menu_items"
                        :class="'fc_item_' + item.name + ' ' + (item.item_class || '')"
                        :route="{ name: item.name, query: item.query }" :key="i" :index="item.query ? item.query.handler : item.name">
                        <span>{{ item.title }}</span>
                    </el-menu-item>
                </el-menu>
                <router-view :company_id="company_id" :company="company"/>
            </el-col>
        </el-row>
        <div v-else>
            <el-row :gutter="30">
                <el-col :lg="6" :md="8" :sm="24" :xs="24">
                    <el-skeleton class="fc_skeleton_loader" :animated="true" :rows="25"></el-skeleton>
                </el-col>
                <el-col :lg="18" :md="16" :sm="24" :xs="24">
                    <el-skeleton class="fc_skeleton_loader" :animated="true" :rows="10"></el-skeleton>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script type="text/babel">
import CompanyInfoSide from './Parts/CompanyInfoSide.vue';

export default {
    name: 'ViewCompany',
    props: ['company_id'],
    components: {
        CompanyInfoSide
    },
    data() {
        return {
            company: null,
            loading: false,
            updating: false,
            show_profile: true,
            menu_items: window.fcAdmin.company_profile_sections,
            activeTab: this.$route.name
        }
    },
    watch: {
        company_id() {
            this.company = null;
            this.fetchCompany();
        }
    },
    methods: {
        setup(item) {
            this.company = item;
            this.changeTitle(item.name + ' - Company');
        },
        fetchCompany() {
            this.loading = true;
            this.show_profile = false;
            this.$get('companies/' + this.company_id)
                .then(response => {
                    this.setup(response.company);
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        deleteCompany() {
            this.loading = true;
            this.$del(`companies/${this.id}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({name: 'companies'})
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        maybeCustomHandler(item) {
            if (item.name != 'fluentcrm_company_profile_extended') {
                return;
            }
            this.show_profile = false;
            setTimeout(() => {
                this.show_profile = true;
            }, 100);
        }
    },
    mounted() {
        this.fetchCompany();
        this.changeTitle(this.$t('View Company'));
    }
}
</script>
