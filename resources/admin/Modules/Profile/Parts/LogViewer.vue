<template>
    <div class="fc_smtp_log_viewer">
        <el-dialog
            v-if="log"
            :append-to-body="true"
            :title="$t('Email Log')"
            :visible.sync="logViewerProps.dialogVisible"
            class="fc_smtp_email_dialog"
            @close="handleCloseLogViewer"
        >
            <div class="fc_smtp_email_dialog_view">
                <ul class="fc_smtp_email_log_items">
                    <li>
                        <div class="item_header">{{ $t('Status') }}:</div>
                        <div class="item_content">
                            <span :class="{
                                success: log.status == 'sent',
                                resent: log.status == 'resent',
                                fail: log.status == 'failed'
                            }">
                                <span
                                    style="text-transform:capitalize;margin-right:10px;"
                                >{{ log.status }}</span>
                            </span>
                        </div>
                    </li>
                    <li>
                        <div class="item_header">{{ $t('Date-Time') }}:</div>
                        <div class="item_content">{{ log.created_at }}</div>
                    </li>
                    <li>
                        <div class="item_header">{{ $t('From') }}:</div>
                        <div class="item_content"><span v-html="log.from"></span></div>
                    </li>
                    <li>
                        <div class="item_header">{{ $t('To') }}:</div>
                        <div class="item_content">
                            <span v-html="log.to"></span>
                        </div>
                    </li>
                    <li v-if="log.resent_count > 0">
                        <div class="item_header">{{ $t('Resent Count') }}:</div>
                        <div class="item_content">
                            <span v-html="log.resent_count"></span>
                        </div>
                    </li>
                    <li>
                        <div class="item_header">{{ $t('Subject') }}:</div>
                        <div class="item_content">
                            <span>{{ log.subject }}</span>
                        </div>
                    </li>
                    <li v-if="log.extra && log.extra.provider">
                        <div class="item_header">{{ $t('Mailer') }}:</div>
                        <div class="item_content">
                            <span>{{ log.extra.provider }}</span>
                        </div>
                    </li>
                </ul>

                <el-collapse v-model="activeName" style="margin-top:10px;">
                    <el-collapse-item name="email_body">
                        <template slot="title">
                            <strong style="color:#606266">{{ $t('Email Body') }} (sanitized)</strong>
                        </template>
                        <hr class="log-border">
                        <SMTPEmailbodyContainer :content="sanitize(log.body)"></SMTPEmailbodyContainer>
                    </el-collapse-item>
                    <p><strong>{{ $t('Server Response') }}</strong></p>
                    <el-row>
                        <el-col>
                            <pre style="white-space: break-spaces;overflow-wrap: anywhere;">{{ log.response }}</pre>
                        </el-col>
                    </el-row>
                    <hr/>
                    <el-collapse-item name="tech_info">
                        <template slot="title">
                            <strong style="color:#606266">{{ $t('Email Headers') }}</strong>
                        </template>
                        <div>
                            <pre>{{ log.headers }}</pre>
                            <pre v-if="log.extra.custom_headers">{{ log.extra.custom_headers }}</pre>
                        </div>
                    </el-collapse-item>

                    <el-collapse-item name="attachments">
                        <template slot="title">
                            <strong style="color:#606266">
                                {{ $t('Attachments') }} ({{ getAttachments(log).length }})
                            </strong>
                        </template>
                        <hr class="log-border">
                        <div
                            v-for="(attachment, key) in getAttachments(log)"
                            :key="key"
                            style="margin:5px 0 10px 0;"
                        >
                            ({{ key + 1 }}) {{ getAttachmentName(attachment) }}
                        </div>
                    </el-collapse-item>
                </el-collapse>
            </div>
        </el-dialog>
    </div>

</template>

<script>
import SMTPEmailbodyContainer from './_SMTPEmailbodyContainer';

export default {
    name: 'LogViewer',
    components: {
        SMTPEmailbodyContainer
    },
    props: ['logViewerProps'],
    data() {
        return {
            activeName: 'email_body',
            loading: false,
            next: false,
            prev: false,
            retrying: false
        };
    },
    computed: {
        log: {
            get() {
                let log;
                if (this.logViewerProps.log) {
                    log = {...this.logViewerProps.log};
                    if (!log.headers) {
                        log.headers = {};
                    }
                    if (!log.response) {
                        log.response = {};
                    }
                    if (!log.extra) {
                        log.extra = {};
                    }
                }
                return log;
            },
            set(log) {
                this.logViewerProps.log = log;
            }
        }
    },
    methods: {
        getAttachments(log) {
            if (!log) return [];
            if (!log.attachments) return [];
            if (!Array.isArray(log.attachments)) {
                return [log.attachments];
            }
            const attachments = [];

            log.attachments.forEach((attachment, key) => {
                attachments[key] = attachment;
            });
            return attachments;
        },
        getAttachmentName(name) {
            if (!name || !name[0]) return;
            name = name[0].replace(/\\/g, '/');
            return name.split('/').pop();
        },
        sanitize(html) {
            return window.DOMPurify.sanitize(html);
        },
        handleCloseLogViewer() {
            this.$emit('closeLogViewer');
        }
    }
}
</script>

<style scoped>

</style>
