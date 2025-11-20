<template>
    <el-menu
        :router="true"
        mode="horizontal"
        class="fluentcrm-navigation"
        :default-active="active"
    >
        <el-menu-item index="dashboard" :route="{ name: 'dashboard' }" v-html="logo" />

        <el-submenu index="subscribers">
            <template slot="title">{{$t('Contacts')}}</template>
            <el-menu-item :route="{ name: 'subscribers' }" index="2-1">{{$t('View All')}}</el-menu-item>
            <el-menu-item :route="{ name: 'lists' }" index="2-2">{{$t('Lists')}}</el-menu-item>
            <el-menu-item :route="{ name: 'tags' }" index="2-3">{{$t('Tags')}}</el-menu-item>
        </el-submenu>

        <el-submenu index="campaigns">
            <template slot="title">{{$t('Campaigns')}}</template>
            <el-menu-item :route="{ name: 'campaigns' }" index="2-1">{{$t('All Campaigns')}}</el-menu-item>
            <el-menu-item :route="{ name: 'email-sequences' }" index="2-2">{{$t('Email Sequences')}}</el-menu-item>
            <el-menu-item :route="{ name: 'templates' }" index="2-3">{{$t('Email Templates')}}</el-menu-item>
        </el-submenu>

        <el-menu-item
            :key="item.route"
            :index="item.route"
            v-html="item.title"
            v-for="item in items"
            :route="{ name: item.route }"
        >
        </el-menu-item>
        <el-menu-item @click="toggleFullScreen()">
            <span v-if="goFull == 'yes'"><span class="dashicons dashicons-editor-contract"></span></span>
            <span v-else><span class="dashicons dashicons-editor-expand"></span></span>
        </el-menu-item>
    </el-menu>
</template>

<script>
    export default {
        name: 'Navigation',
        data() {
            return {
                active: null,
                items: [],
                goFull: window.localStorage.getItem('fluentcrm_full_screen'),
                logo: ''
            }
        },
        watch: {
            '$route'(to, from) {
                if (this.$route.name) {
                    this.setActive();
                }
            }
        },
        methods: {
            defaultRoutes() {
                return [
                    {
                        route: 'email_settings',
                        title: this.$t('Settings')
                    },
                    {
                        route: 'funnels',
                        title: this.$t('Automations')
                    }
                ]
            },
            setMenus() {
                this.items = this.applyFilters('fluentcrm_top_menus', this.defaultRoutes());
            },
            setActive() {
                this.active = this.$route.meta.parent || this.$route.name;
            },
            toggleFullScreen() {
                let status = 'yes';
                if (window.localStorage.getItem('fluentcrm_full_screen') === 'yes') {
                    status = 'no';
                }
                this.goFull = status;
                window.localStorage.setItem('fluentcrm_full_screen', status)
                jQuery('html').toggleClass('fluentcrm_go_full');
            }
        },
        mounted() {
            this.logo = `<div class="dashboard-link">
                                    <img src="${this.appVars.images_url + '/fluentcrm-logo.png'}"
                                         class="fluentcrm-logo"
                                    /> Dashboard
                                </div>`;
            this.setMenus();
            if (window.localStorage.getItem('fluentcrm_full_screen') === 'yes') {
                jQuery('html').addClass('fluentcrm_go_full');
            }
        }
    };
</script>
