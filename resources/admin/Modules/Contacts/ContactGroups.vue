<template>
    <div class="fluentcrm_settings_wrapper">
        <el-menu
            style="margin-bottom: 10px;"
            mode="horizontal"
            :router="true"
            :default-active="router_name"
            class="fc_segment_menu"
        >
            <el-menu-item
                v-for="item in menu_items"
                :class="'fc_item_' + item.route + ' ' + (item.item_class || '')"
                :route="{ name: item.route }" :key="item.route" :index="item.route">
                <i :class="item.icon"></i>
                <span>{{ item.title }}</span>
            </el-menu-item>
        </el-menu>
        <div class="fc_setting_wrap">
            <router-view @changeMenu="changeMenu"></router-view>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ContactGroups',
    data() {
        return {
            router_name: this.$route.name,
            menu_items: [
                {
                    title: this.$t('Contacts'),
                    route: 'subscribers',
                    icon: 'el-icon-user'
                },
                {
                    title: this.$t('Companies'),
                    route: 'companies',
                    icon: 'el-icon-office-building',
                    item_class: this.appVars.addons.company_module ? '' : 'fc_item_hidden'
                },
                {
                    title: this.$t('Lists'),
                    route: 'lists',
                    icon: 'el-icon-files'
                },
                {
                    title: this.$t('Tags'),
                    route: 'tags',
                    icon: 'el-icon-price-tag'
                },
                {
                    title: this.$t('Dynamic Segments'),
                    route: 'dynamic_segments',
                    icon: 'el-icon-cpu'
                }
            ]
        }
    },
    watch: {
        '$route.name'() {
            this.router_name = this.$route.name;
        }
    },
    methods: {
        changeMenu(itemName) {
            jQuery('.fc_segment_menu li').removeClass('is-active');
            jQuery('.fc_segment_menu li.fc_item_' + itemName).addClass('is-active');
        }
    }
}
</script>
