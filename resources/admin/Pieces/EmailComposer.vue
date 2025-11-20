<template>
    <div class="email_composer_wrapper">
        <el-form v-if="!disable_subject" :label-position="label_align" :model="campaign">
            <el-row :gutter="20" class="mb-10">
                <el-col :sm="24" :md="12">
                    <el-form-item :label="$t('Email Subject')">
                        <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" popper_extra="fc_with_c_fields" v-if="email_subject_status" :placeholder="$t('Email Subject')" :data="smartcodes" v-model="campaign.email_subject"/>
                    </el-form-item>
                </el-col>
                <el-col :sm="24" :md="12">
                    <el-form-item :label="$t('Email Pre-Header')">
                        <el-input type="textarea"
                                  :placeholder="$t('Email Pre-Header')"
                                  :rows="2"
                                  v-model="campaign.email_pre_header"
                        ></el-input>
                    </el-form-item>
                </el-col>
            </el-row>
        </el-form>
        <email-block-composer @save="triggerSave()" :show_audit="show_audit" :extra_tags="extra_tags" :show_merge="true" :enable_template_save="true" @template_inserted="resetSubject()" :disable_fixed="disable_fixed" :enable_templates="!disable_templates" :campaign="campaign">
            <template slot="fc_editor_actions">
                <slot name="actions"></slot>
            </template>
        </email-block-composer>

        <slot name="after_block_composer"></slot>

        <div v-if="enable_test">
            <send-test-email btn_type="danger" :campaign="campaign" />
        </div>
    </div>
</template>

<script type="text/babel">
import EmailBlockComposer from './EmailElements/BlockComposer';
import InputPopover from '@/Pieces/InputPopover';
import SendTestEmail from '@/Pieces/TestEmail';

export default {
    name: 'EmailComposer',
    props: ['campaign', 'label_align', 'disable_subject', 'disable_templates', 'disable_fixed', 'enable_test', 'show_merge', 'extra_tags', 'show_audit'],
    components: {
        EmailBlockComposer,
        InputPopover,
        SendTestEmail
    },
    data() {
        return {
            smartcodes: window.fcAdmin.globalSmartCodes,
            email_subject_status: true
        }
    },
    methods: {
        triggerSave() {
            this.$emit('save');
        },
        resetSubject() {
            this.email_subject_status = false;
            this.$nextTick(() => {
                this.email_subject_status = true;
            });
        }
    },
    mounted() {
        if (this.extra_tags) {
            this.smartcodes = [...this.smartcodes, ...this.extra_tags];
        }

        if (window.fcAdmin.extendedSmartCodes) {
            this.smartcodes = [...this.smartcodes, ...window.fcAdmin.extendedSmartCodes];
        }
    }
}
</script>
