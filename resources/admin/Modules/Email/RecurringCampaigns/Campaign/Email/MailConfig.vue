<template>
    <div style="margin: 30px 20px;" class="template">
        <div class="fc_narrow_box fc_white_inverse">
            <el-form label-position="top" :model="campaign">
                <email-subjects :mailer_settings="true" :multi_subject="false" label_align="top" :campaign="campaign"/>

                <el-form-item :label="$t('Set_Date_Time_Label')">
                        <el-date-picker
                            value-format="yyyy-MM-dd HH:mm:ss"
                            v-model="campaign.scheduled_at"
                            type="datetime"
                            :picker-options="pickerOptions"
                            :placeholder="$t('Select date and time')">
                        </el-date-picker>
                </el-form-item>

                <el-form-item>
                    <test-email :campaign="campaign" />
                </el-form-item>
            </el-form>
        </div>

        <el-row style="max-width: 860px; margin: 0 auto" :gutter="20">
            <el-col :span="12">
                <el-button
                    size="small"
                    type="text"
                    :disabled="saving"
                    @click="goToPrev()"
                > {{$t('Back')}}
                </el-button>
            </el-col>
            <el-col :span="12" class="text-align-right">
                <el-button :disabled="saving" v-loading="saving" @click="updateCampaign()" size="small" type="primary">
                    {{ $t('Update') }}
                </el-button>
                <el-button :disabled="saving" v-loading="saving" @click="nextStep()" size="small" type="success">
                    {{ $t('Schedule Campaign') }}
                </el-button>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import EmailSubjects from '@Pieces/EmailSubjects';
import TestEmail from '@Pieces/TestEmail.vue';
import {emailDateConfig} from '@/Bits/data_config';

export default {
    name: 'RecurringMailConfig',
    props: ['campaign', 'saving'],
    components: {
        EmailSubjects,
        TestEmail
    },
    data() {
        return {
            fetchingTemplate: false,
            editor_status: true,
            loading: false,
            smart_codes: [],
            inline_errors: '',
            pickerOptions: emailDateConfig
        };
    },
    methods: {
        nextStep() {
            this.$emit('goToNext');
        },
        updateCampaign() {
            this.$emit('updateCampaign');
        },
        goToPrev() {
            this.$emit('goToPrev');
        }
    }
};
</script>
