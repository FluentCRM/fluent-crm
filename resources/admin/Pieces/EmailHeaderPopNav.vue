<template>
    <el-dropdown @command="handleCommand" placement="bottom-start" trigger="click">
        <span style="cursor: pointer; font-size: 18px;">
            <i class="el-icon-arrow-down fluentcrm_clickable el-icon--right"></i> {{ head_title }}
        </span>
        <el-dropdown-menu slot="dropdown">
            <el-dropdown-item :class="{ sub_item_active: 'campaigns' == routeName }" command="campaigns">{{ $t('Email Campaigns') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'recurring_campaigns' == routeName }" command="recurring_campaigns">{{ $t('Recurring Email Campaigns') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'email-sequences' == routeName }" command="email-sequences">{{ $t('Email Sequences') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'templates' == routeName }" command="templates">{{ $t('Email Templates') }}</el-dropdown-item>
            <el-dropdown-item :class="{ sub_item_active: 'all_emails' == routeName }" command="all_emails">{{ $t('All Email Activities') }}</el-dropdown-item>
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
