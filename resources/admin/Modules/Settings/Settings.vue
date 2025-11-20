<template>
    <div class="fluentcrm_settings_wrapper">
        <el-row>
            <el-col :span="5">
                <el-menu
                    :router="true"
                    :default-active="router_name"
                    class="el-menu-vertical-demo"
                >
                    <el-menu-item v-for="item in menuItems" :route="{ name: item.route }" :key="item.route"
                                  :index="item.route">
                        <i :class="item.icon"></i>
                        <span>{{ item.title }}</span>
                    </el-menu-item>
                </el-menu>
            </el-col>
            <el-col class="fc_settings_wrapper" :span="19">
                <router-view></router-view>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'GlobalSettings',
    data() {
        return {
            router_name: this.$route.name,
            menuItems: [
                {
                    icon: 'el-icon-document',
                    title: this.$t('Business Settings'),
                    route: 'business_settings'
                },
                {
                    route: 'email_settings',
                    icon: 'el-icon-message',
                    title: this.$t('Email Settings')
                },
                {
                    icon: 'el-icon-set-up',
                    route: 'other_settings',
                    title: this.$t('General Settings')
                },
                {
                    route: 'custom_contact_fields',
                    icon: 'el-icon-s-custom',
                    title: this.$t('Custom Contact Fields')
                },
                {
                    route: 'smart_links',
                    icon: 'el-icon-connection',
                    title: this.$t('Smart Links')
                },
                {
                    icon: 'el-icon-s-check',
                    route: 'double-optin-settings',
                    title: this.$t('Double Opt-in Settings')
                },
                {
                    route: 'integration_settings',
                    icon: 'el-icon-setting',
                    title: this.$t('Integration Settings')
                },
                {
                    route: 'abandon_cart_settings',
                    icon: 'el-icon-shopping-cart-2',
                    title: this.$t('Abandoned Cart Settings')
                },
                {
                    icon: 'el-icon-connection',
                    route: 'webhook-settings',
                    title: this.$t('Incoming Webhooks')
                },
                {
                    icon: 'el-icon-connection',
                    route: 'managers',
                    title: this.$t('Managers')
                },
                {
                    icon: 'el-icon-attract',
                    route: 'rest-api',
                    title: this.$t('REST API')
                },
                {
                    icon: 'el-icon-bangzhu',
                    route: 'settings_tools',
                    title: this.$t('Tools')
                },
                {
                    icon: 'el-icon-s-claim',
                    route: 'settings_compliance',
                    title: this.$t('Compliance')
                },
                {
                    icon: 'el-icon-guide',
                    route: 'smtp_settings',
                    title: this.$t('Set_SMTP_Email_SS')
                },
                {
                    icon: 'el-icon-ship',
                    route: 'experimental_features',
                    title: this.$t('Advanced Features Config')
                }
            ]
        }
    },
    mounted() {
        this.changeTitle('Settings');

        if (this.appVars.experimentals.system_logs == 'yes') {
            this.menuItems.push({
                icon: 'el-icon-info',
                route: 'system_logs',
                title: this.$t('System Logs')
            });
        }

        if (this.appVars.experimentals.activity_log == 'yes') {
            this.menuItems.push({
                icon: 'el-icon-s-order',
                route: 'activity_logs',
                title: this.$t('Activity Logs')
            });
        }

        if (this.has_campaign_pro) {
            this.menuItems.push({
                icon: 'el-icon-lock',
                route: 'license_settings',
                title: this.$t('License Management')
            });
        }
    }
};
</script>
