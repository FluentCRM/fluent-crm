<template>
    <el-form @submit.native.prevent="doNothing" :data="data" label-position="top">
        <div v-show="settings.title" class="fluentcrm_funnel_header">
            <div class="fc_funnel_head">
                <div class="fc_funel_head_title">
                    <h3>
                        {{ settings.title }}
                        <span v-if="title_badge" class="ff_funnel_badge" :class="'ff_funnel_badge-' + title_badge">
                            {{ title_badge }}
                        </span>
                    </h3>
                    <p v-html="settings.sub_title"></p>
                </div>
                <div class="fc_funnel_head_action">
                    <merge-codes v-if="title_badge != 'trigger'" class="fc_header_merge_codes" />
                    <el-button v-if="!is_editable" style="font-size: 22px; color: black;" @click="closeDrawer" type="text" icon="el-icon-close"></el-button>
                </div>
            </div>
        </div>

        <template>
            <slot name="after_header"></slot>
        </template>

        <div class="fc_funnerl_editor fc_block_white" v-if="settings.fields">
            <div v-for="(field, fieldKey) in settings.fields" :class="field.wrapper_class" :key="fieldKey">
                <template v-if="dependancyPass(field)">
                    <form-field
                        @save_inline="saveEmailActionInline()"
                        @save_reload="saveAndReload()"
                        :options="options"
                        v-model="data[fieldKey]"
                        :field="field"/>
                </template>
            </div>
        </div>

        <template v-if="is_settings_missing">
            <h3>{{ $t('block_does_not_exist') }}</h3>
        </template>

        <div v-if="show_controls" class="fluentcrm-sequence_control">
            <div class="fluentcrm_pull_left">
                <el-button :loading="is_internal_loading" :disabled="is_internal_loading"
                           @click="saveFunnelSequences(false)" size="small" type="success">
                    {{ $t('Save Settings') }}
                </el-button>

                <el-button v-if="action_name === 'http_send_data'"
                           v-loading="sendingTestWebhook"
                           :disabled="sendingTestWebhook"
                           @click="sendTestWebhook"
                           type="danger"
                           size="small">
                    {{ $t('Send Test Webhook') }}
                </el-button>
            </div>
            <div class="fluentcrm_pull_right">
                <el-button @click="deleteFunnelSequences(false)" size="mini" icon="el-icon-delete"
                           type="danger"></el-button>
            </div>
        </div>
    </el-form>
</template>
<script type="text/babel">
import FormField from './_Field';
import MergeCodes from '@Pieces/EmailElements/_MergeCodes';

export default {
    name: 'FieldEditor',
    components: {
        FormField,
        MergeCodes
    },
    props: ['data', 'settings', 'options', 'show_controls', 'title_badge', 'action_name', 'is_editable', 'block_type'],
    data() {
        return {
            is_settings_missing: false,
            is_internal_loading: false,
            funnel_id: this.$route.params.funnel_id,
            sendingTestWebhook: false
        }
    },
    methods: {
        saveFunnelSequences() {
            if (this.action_name === 'send_custom_email') {
                this.saveEmailAction();
            } else {
                this.is_internal_loading = true;
                this.$emit('save', 1);
            }
        },

        saveEmailAction() {
            if (!this.data.campaign.email_subject) {
                this.$notify.error('Please provide email subject');
                return false;
            }
            this.is_internal_loading = true;

            this.$post('funnels/funnel/save-email-action-fallback', {
                action_data: JSON.stringify(this.data),
                funnel_id: this.funnel_id
            })
                .then((response) => {
                    this.data.campaign = response.campaign;
                    this.data.reference_campaign = response.reference_campaign;
                    this.$emit('save', 1);
                    this.is_internal_loading = false;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                });
        },
        saveEmailActionInline() {
            this.$post('funnels/funnel/save-email-action-fallback', {
                action_data: JSON.stringify(this.data),
                funnel_id: this.funnel_id
            });
        },

        deleteFunnelSequences() {
            this.$emit('deleteSequence', 1);
        },
        movePosition(type) {
            this.$emit('movePosition', type);
        },
        /**
         * Helper function for show/hide dependent elements
         & @return {Boolean}
         */
        compare(operand1, operator, operand2) {
            switch (operator) {
                case '=':
                    return operand1 === operand2
                case '!=':
                    return operand1 !== operand2
            }
        },

        /**
         * Checks if a prop is dependent on another
         * @param listItem
         * @return {boolean}
         */
        dependancyPass(listItem) {
            if (listItem.dependency) {
                const optionPaths = listItem.dependency.depends_on.split('/');

                const dependencyVal = optionPaths.reduce((obj, prop) => {
                    return obj[prop]
                }, this.data);

                if (this.compare(listItem.dependency.value, listItem.dependency.operator, dependencyVal)) {
                    return true;
                }
                return false;
            }
            return true;
        },
        saveAndReload() {
            this.$emit('save_reload');
        },
        doNothing() {
        },
        closeDrawer() {
            this.$emit('closeDrawer');
        },
        sendTestWebhook() {
            if (!this.data.remote_url) {
                this.$notify.error(this.$t('Please provide Remote URL'));
                return false;
            }
            this.sendingTestWebhook = true;
            this.$post('funnels/send-test-webhook', {
                data: this.data
            })
                .then((response) => {
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.sendingTestWebhook = false;
                });
        }
    },
    mounted() {
        this.is_internal_loading = false;
        if (!this.settings) {
            this.is_settings_missing = true;
            this.settings = {}
        } else {
            this.is_settings_missing = false;
        }
    }
}
</script>
