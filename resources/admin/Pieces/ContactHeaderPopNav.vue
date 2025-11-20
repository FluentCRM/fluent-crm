<template>
    <el-dropdown @command="handleCommand" placement="bottom-start" trigger="click">
        <span style="cursor: pointer; font-size: 18px;">
            <i class="el-icon-arrow-down fluentcrm_clickable el-icon--right"></i> {{ head_title }}
        </span>
        <el-dropdown-menu slot="dropdown">
            <el-dropdown-item :class="{ sub_item_active: 'subscribers' == routeName }" command="subscribers">{{ $t('Contacts') }}</el-dropdown-item>
            <el-dropdown-item v-if="appVars.company_types" :class="{ sub_item_active: 'companies' == routeName }" command="companies">{{ $t('Companies') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'lists' == routeName }" command="lists">{{ $t('Lists') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'tags' == routeName }" command="tags">{{ $t('Tags') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'dynamic_segments' == routeName }" command="dynamic_segments">{{ $t('Dynamic Segments') }}</el-dropdown-item>
        </el-dropdown-menu>
    </el-dropdown>
</template>

<script type="text/babel">
export default {
    name: 'ContactHeaderPopNav',
    props: ['head_title'],
    data() {
        return {
            routeName: this.$route.name
        }
    },
    methods: {
        handleCommand(command) {
            if (command == this.routeName) {
                return;
            }
            this.$router.push({name: command});
        }
    }
}
</script>
