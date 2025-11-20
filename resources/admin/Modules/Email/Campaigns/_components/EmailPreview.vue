<template>
    <div>
        <el-dialog
            width="60%"
            :title="$t('Email Preview')"
            @open="onOpen"
            :append-to-body="true"
            :close-on-click-modal="false"
            :visible.sync="preview.isVisible"
        >
            <div v-if="!email" v-loading="loading"></div>

            <div v-if="email" style="min-height:300px;">
                <ul class="email-header">
                    <li>{{ $t('Status') }}: {{ info.status }}</li>
                    <li>{{ $t('Campaign') }}: {{ (info.campaign) ? info.campaign.title : 'n/a' }}</li>
                    <li>{{$t('Subject')}}: {{ email.subject ? email.subject : '' }}</li>
                    <li>{{$t('To')}}: {{ email.to.name }} &lt;{{ email.to.email }}&gt;</li>
                    <li>{{$t('Date')}}: {{info.scheduled_at | nsDateFormat}}</li>
                    <li class="stats_badges">
                        <span :title="$t('Open Count')"><i class="el-icon el-icon-folder-opened"></i> <span>{{info.is_open || 0}}</span></span>
                        <span :title="$t('Click')"><i
                            class="el-icon el-icon-position"></i> <span>{{info.click_counter || 0}}</span></span>
                    </li>
                    <li v-if="info.note">{{$t('Note')}}: {{info.note}}</li>
                </ul>

                <div v-if="email.clicks && email.clicks.length" class="fc_email_clicks">
                    <h4>{{$t('Email Clicks')}}</h4>
                    <ul>
                        <li v-for="click in email.clicks" :key="click.id">{{click.url}} ({{click.counter}})</li>
                    </ul>
                </div>

                <p v-if="info.status == 'sent'" style="color: #ff3434;">{{$t('preview_email_info')}}</p>
                <hr>

                <div class="email-body">
                    <iframe-builder frame_height="500px" :preview_html="email.body" />
                    <div style="width:0px;height:0px;clear:both;"></div>
                </div>
            </div>

            <span slot="footer" class="dialog-footer">
                <el-button type="danger" @click="preview.isVisible=false">{{$t('Close')}}</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import IframeBuilder from '@Pieces/IframeBuilder';

export default {
    name: 'EmailPreview',
    props: ['preview'],
    components: {
        IframeBuilder
    },
    data() {
        return {
            email: null,
            info: {},
            loading: false
        };
    },
    methods: {
        fetch() {
            this.email = null
            this.loading = true;
            this.$get(`campaigns/emails/${this.preview.id}/preview`)
                .then(response => {
                    this.email = response.email;
                    this.info = response.info;
                })
                .finally(() => {
                    this.loading = false;
                })
        },
        onOpen() {
            this.fetch();
        },
        onClosed() {
            this.preview.isVisible = false;
        }
    }
};
</script>
