<template>
    <div class="fluentcrm_subject_metrics">
        <div class="fluentcrm_inner_header">
            <h3 class="fluentcrm_inner_title">{{$t('Subject Analytics')}}</h3>
            <div class="fluentcrm_inner_actions">

            </div>
        </div>
        <el-table border :empty-text="$t('No Data Found')" class="fc_el_border_table" stripe :data="metrics.subjects">
            <el-table-column type="expand">
                <template slot-scope="props">
                    <ul class="fc_list">
                        <li v-for="(click, clickIndex) in props.row.metric.clicks" :key="clickIndex"><a target="_blank" rel="noopener" :href="click.url">{{click.url}}</a> - <el-tag size="mini">{{click.total}}</el-tag></li>
                    </ul>
                </template>
            </el-table-column>
            <el-table-column :label="$t('Subject')">
                <template slot-scope="scope">{{scope.row.value}}</template>
            </el-table-column>
            <el-table-column width="140" :label="$t('Email Sent %')">
                <template slot-scope="scope">
                    {{percent(scope.row.total,campaign.recipients_count)}} ({{scope.row.total}})
                </template>
            </el-table-column>
            <el-table-column width="140" :label="$t('Open Rate')">
                <template slot-scope="scope">
                    {{percent(scope.row.metric.total_opens,scope.row.total)}}
                    <span v-show="scope.row.metric.total_opens">({{scope.row.metric.total_opens}})</span>
                </template>
            </el-table-column>
            <el-table-column width="140" :label="$t('Click Rate')">
                <template slot-scope="scope">
                    {{percent(scope.row.metric.total_clicks,scope.row.total)}}
                    <span v-show="scope.row.metric.total_clicks">({{scope.row.metric.total_clicks}})</span>
                </template>
            </el-table-column>
        </el-table>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'CampaignSubjectAnalytics',
        props: ['metrics', 'campaign']
    }
</script>
