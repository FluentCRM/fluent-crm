<template>
    <div v-if="stat" class="fluentcrm_block_stats mt-5">
        <el-tag size="mini" effect="plain">
            <i class="el-icon el-icon-user"></i>
            {{stat.count}}
        </el-tag>
        <el-tag size="mini" effect="plain">
            {{stat.percent}}%
        </el-tag>
        <el-tag v-if="stat.drop_percent" size="mini" type="danger" effect="plain">
            <i class="el-icon el-icon-bottom"></i>
            {{stat.drop_percent}}%
        </el-tag>
        <el-tag size="mini" effect="plain" v-if="stat.revenues">
            <i class="el-icon el-icon-money"></i>
            <span v-html="stat.revenues.join(' | ')"></span>
        </el-tag>
        <el-tag title="Email Open (estimated)" size="mini" effect="plain" v-if="stat.email_opens">
            <i class="el-icon el-icon-folder-opened"></i>
            {{stat.email_opens}}
        </el-tag>
        <el-tag title="Clicks Count" size="mini" effect="plain" v-if="stat.link_clicks">
            <i class="el-icon el-icon-position"></i>
            {{stat.link_clicks}}
        </el-tag>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'reportWidget',
        props: ['stat']
    }
</script>
