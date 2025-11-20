<template>
    <div class="fc_iframe_wrap">
        <h3 v-if="loading_preview">{{$t('Loading Preview. Please wait...')}}</h3>
        <div class="el-alert el-alert--error is-light" style="display: block;overflow: hidden;padding: 0 20px; margin-bottom: 0px;" v-else-if="show_audit && invalidDoms && invalidDoms.length">
            <h3 style="font-size: 16px;">{{ $t('Invalid_Link_Detected') }} ({{invalidDoms.length}})</h3>
            <ul class="inline_disc_lists">
                <li v-for="(link, linkId) in invalidDoms" :key="linkId">{{link.text}}</li>
            </ul>
        </div>
        <div
            v-show="!loading_preview"
            ref="fc_ifr"
            style="width:100%;height: 500px; overflow: auto;"
            :style="{ height: frame_height }"
        ></div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'PreviewIframeBuilder',
    props: {
        preview_html: {
            type: String,
            default() {
                return '';
            }
        },
        campaign: {
            type: Object,
            default() {
                return {}
            }
        },
        campaign_id: {
            default() {
                return 0;
            }
        },
        frame_height: {
            type: String,
            default() {
                return '500px';
            }
        },
        show_audit: {
            type: Boolean,
            default() {
                return false;
            }
        }
    },
    data() {
        return {
            loading_preview: true,
            invalidDoms: [],
            preview_full_html: this.preview_html
        }
    },
    methods: {
        loadFrame() {
            const host = this.$refs.fc_ifr;
            const shadow = host.attachShadow({ mode: 'closed' });
            const div = document.createElement('div');
            div.innerHTML = this.preview_full_html;
            shadow.appendChild(div);
            this.loading_preview = false;
            this.checkDoms(this.preview_full_html);
        },
        fetchHtml() {
            this.loading_preview = true;
            this.showing_view = true;
            this.$post('campaigns/email-preview-html', {
                campaign_id: this.campaign_id,
                disable_subscriber: 'yes'
            })
                .then(response => {
                    this.preview_full_html = response.preview_html;
                    this.loadFrame();
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading_preview = false;
                });
        },
        checkDoms(html) {
            if (!this.show_audit) {
                return;
            }
            this.invalidDoms = [];
            const invalidDoms = jQuery('a[href=""], a:not([href]), a[href="#"]', html);
            this.each(invalidDoms, (domItem) => {
                console.log(domItem);
                this.invalidDoms.push({
                    text: domItem.text || 'Empty Text / Image'
                })
            });
        }
    },
    mounted() {
        if (this.campaign_id) {
            this.fetchHtml();
        } else {
            this.loadFrame();
        }
    }
}
</script>
