<template>
    <div class="fc_flow_basic">
        <el-form-item :label="$t('Title of the Recurring Campaign')">
            <el-input type="text" :placeholder="$t('eg: Weekly Post Updated')" v-model="campaign.title"/>
        </el-form-item>
        <el-form-item :label="$t('How often you want to send this email?')">
            <el-radio-group @change="maybeResetDay()" size="small" v-model="campaign.settings.scheduling_settings.type">
                <el-radio-button label="daily">{{ $t('Daily') }}</el-radio-button>
                <el-radio-button label="weekly">{{ $t('Weekly') }}</el-radio-button>
                <el-radio-button label="monthly">{{ $t('Monthly') }}</el-radio-button>
            </el-radio-group>
        </el-form-item>
        <el-row :gutter="30">
            <el-col v-if="campaign.settings.scheduling_settings.type == 'weekly'" :md="12" :xs="24">
                <el-form-item :label="$t('Select which day you want to send email')">
                    <el-select :placeholder="$t('Select Day of the week')" v-model="campaign.settings.scheduling_settings.day">
                        <el-option value="mon" :label="$t('Every Monday')"></el-option>
                        <el-option value="tue" :label="$t('Every Tuesday')"></el-option>
                        <el-option value="wed" :label="$t('Every Wednesday')"></el-option>
                        <el-option value="thu" :label="$t('Every Thursday')"></el-option>
                        <el-option value="fri" :label="$t('Every Friday')"></el-option>
                        <el-option value="sat" :label="$t('Every Saturday')"></el-option>
                        <el-option value="sun" :label="$t('Every Sunday')"></el-option>
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col v-if="campaign.settings.scheduling_settings.type == 'monthly'" :md="12" :xs="24">
                <el-form-item :label="$t('Select which day of the month to send email')">
                    <el-select :placeholder="$t('Select Day of the month')"
                               v-model="campaign.settings.scheduling_settings.day">
                        <el-option v-for="day in 31" :key="day" :value="day" :label="$t('Day') + ' - ' + day"/>
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :md="12" :xs="24">
                <el-form-item :label="$t('Which time you would like to schedule')">
                    <el-time-select
                        v-model="campaign.settings.scheduling_settings.time"
                        :picker-options="{ step: '00:15', start: '00:00', end: '23:59' }"
                        :placeholder="$t('Select Schedule time')">
                    </el-time-select>
                    <p style="margin: 0; font-size: 90%;">{{ $t('Current Date & Time (server):') }} {{ currentDateTime() }}</p>
                </el-form-item>
            </el-col>
        </el-row>
        <el-form-item>
            <el-checkbox class="fc_inline_check" true-label="yes" false-label="no" v-model="campaign.settings.scheduling_settings.send_automatically">{{ $t('Send_Email_Auto_Info') }}</el-checkbox>
        </el-form-item>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'CampaignBasicSettings',
    props: ['campaign'],
    methods: {
        maybeResetDay() {
            if (this.campaign.settings.scheduling_settings.type == 'weekly') {
                this.campaign.settings.scheduling_settings.day = 'mon';
            } else if (this.campaign.settings.scheduling_settings.type == 'monthly') {
                this.campaign.settings.scheduling_settings.day = 1;
            }
        }
    }
}
</script>
