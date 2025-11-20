<template>
    <div class="fluentcrm_settings_wrapper fc_sequence_import">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'templates' }">
                        {{ $t('Email Templates') }}
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
                    <h3>{{ $t('Import_Email_Template') }}</h3>
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
                    <h4>{{ $t('Not_Import_Templates_Alert') }}</h4>
                    <pre v-if="inline_errors">{{ inline_errors }}</pre>
                </div>
            </template>
            <div v-else>
                <generic-promo />
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import GenericPromo from '../../Promos/GenericPromo';
export default {
    name: 'ImportEmailTemplate',
    components: {GenericPromo},
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
            url += (url.match(/\?/) ? '&' : '?') + jQuery.param({ action: 'fluentcrm_import_template' });
            return url;
        }
    },
    methods: {
        success(response) {
            this.$notify.success(response.message);
            this.$router.push({
                name: 'edit_template',
                params: {
                    template_id: response.template_id
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
