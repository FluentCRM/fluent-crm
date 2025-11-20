<template>
    <div style="border-top: 5px solid #e3e8ee;" class="template">
        <email-block-composer :disabled_templates="{ visual_builder: true }" @save="updateCampaign()" :show_audit="true"
                              :show_merge="true"
                              :enable_template_save="true" :enable_templates="true" :campaign="campaign">
            <template slot="fc_editor_actions">
                <el-button :disabled="saving" v-loading="saving" size="small" type="success" @click="updateCampaign()">
                    {{ $t('Save') }}
                </el-button>
                <el-button :disabled="saving" v-loading="saving" size="small" type="primary" icon="el-icon-right"
                           @click="nextStep()">{{ $t('Cam_Continue_S') }}
                </el-button>
            </template>
        </email-block-composer>
    </div>
</template>

<script type="text/babel">
import EmailBlockComposer from '@/Pieces/EmailElements/BlockComposer';

export default {
    name: 'RecurringMailEditor',
    props: ['campaign', 'saving'],
    components: {
        EmailBlockComposer
    },
    methods: {
        nextStep() {
            this.$emit('goToNext');
        },
        updateCampaign() {
            this.$emit('updateCampaign');
        }
    }
};
</script>
