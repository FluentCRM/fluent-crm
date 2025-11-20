<template>
    <div class="fluentcrm_settings_wrapper fc_sequence_import">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'email-sequences' }">
                        {{ $t('Email Sequences') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ $t('Import') }}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
        </div>
        <div v-loading="importing" style="margin-top: 30px; max-width: 1160px;"
             class="fluentcrm_body fluentcrm_title_cards fc_narrow_box fc_white_inverse">
            <template v-if="has_campaign_pro">
                <div class="fc_funnel_upload text-align-center">
                    <h3>{{ $t('Email_Sequence_Import_Note') }}</h3>
                    <el-upload
                        drag
                        :limit="1"
                        :action="url"
                        ref="uploader"
                        :multiple="false"
                        :on-error="error"
                        :on-success="success">
                        <i class="el-icon-upload"/>
                        <div class="el-upload__text">
                            {{ $t('Drop JSON file here or') }} <em>{{ $t('click to upload') }}</em>
                        </div>
                    </el-upload>
                    <pre v-if="inline_errors">{{ inline_errors }}</pre>
                </div>
            </template>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ImportEmailSequence',
    components: {},
    data() {
        return {
            step: 'upload',
            importing: false,
            inline_errors: false
        }
    },
    computed: {
        url() {
            let url = window.ajaxurl;
            url += (url.match(/\?/) ? '&' : '?') + jQuery.param({action: 'fluentcrm_import_sequence'});
            return url;
        }
    },
    methods: {
        success(response) {
            this.$notify.success(response.message);
            this.$router.push({
                name: 'edit-sequence',
                params: {
                    id: response.sequence.id
                }
            });
        },
        error(error) {
            const errors = JSON.parse(error.message);
            this.$notify.error(errors.message);
            if (errors.requires) {
                this.inline_errors = errors.requires;
            }
        }
    }
}
</script>
