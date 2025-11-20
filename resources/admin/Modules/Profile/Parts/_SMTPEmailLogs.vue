<template>
    <div class="fc_smtp_email_logs_table">
        <el-table
            stripe
            :data="emails"
            v-loading="loading"
            style="width:100%"
            :row-class-name="tableRowClassName"
        >
            <el-table-column :label="$t('Subject')">
                <template slot-scope="scope">
                    <span style="cursor: pointer" @click="handleView(scope.row)">{{ scope.row.subject }}</span>
                    <span v-if="scope.row.extra && scope.row.extra.provider == 'Simulator'"
                          style="color: #ff0000;"> - Simulated</span>
                </template>
            </el-table-column>

            <el-table-column :label="$t('Status')" width="120" align="center">
                <template slot-scope="scope">
                    {{ scope.row.status }}
                </template>
            </el-table-column>

            <el-table-column prop="created_at" :label="$t('Date-Time')" width="200px">
                <template slot-scope="scope">
                    {{ scope.row.created_at | nsHumanDiffTime }}
                </template>
            </el-table-column>

            <el-table-column :label="$t('Actions')" width="190px" align="right">
                <template slot-scope="scope">
                    <el-button
                        size="mini"
                        type="primary"
                        icon="el-icon-view"
                        @click="handleView(scope.row)"
                    />
                </template>
            </el-table-column>
        </el-table>

        <log-viewer :logViewerProps="logViewerProps" @closeLogViewer="closeLogViewer" />
    </div>
</template>

<script>
import LogViewer from './LogViewer';
export default {
    name: 'SMTPEmailLogs',
    components: {LogViewer},
    props: ['emails'],
    data() {
        return {
            loading: false,
            logViewerProps: {
                log: null,
                dialogVisible: false
            }
        }
    },
    methods: {
        tableRowClassName({row}) {
            return 'row_type_' + row.status;
        },
        handleView(row) {
            this.logViewerProps.log = row;
            this.logViewerProps.dialogVisible = true;
        },
        closeLogViewer() {
            this.logViewerProps.log = null;
            this.logViewerProps.dialogVisible = false;
        }
    }
}
</script>

<style scoped>

</style>
