<template>
    <div class="fc_iframe_wrap">
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
    name: 'IframeBuilder',
    props: {
        preview_html: {
            type: String,
            default() {
                return '';
            }
        },
        frame_height: {
            type: String,
            default() {
                return '500px';
            }
        }
    },
    data() {
        return {
            loading_preview: true
        }
    },
    methods: {
        loadFrame() {
            const host = this.$refs.fc_ifr;
            const shadow = host.attachShadow({ mode: 'closed' });
            const div = document.createElement('div');
            div.innerHTML = this.preview_html;
            shadow.appendChild(div);
            this.loading_preview = false;
            this.loading_preview = false;
        }
    },
    mounted() {
        this.loadFrame();
    }
}
</script>
