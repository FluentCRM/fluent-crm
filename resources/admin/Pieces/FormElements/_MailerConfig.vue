<template>
    <div>
        <el-form-item>
            <el-checkbox true-label="yes" false-label="no" v-model="mailer_settings.is_custom">{{ $t('Vie_Set_CFNaE') }}</el-checkbox>
        </el-form-item>
        <div class="fc_highlight_gray" v-if="mailer_settings.is_custom == 'yes'">
            <el-row :gutter="20">
                <el-col :md="12" :sm="24">
                    <el-form-item :label="$t('From Name')">
                        <el-input :placeholder="$t('From Name')"
                                  v-model="mailer_settings.from_name"
                        ></el-input>
                    </el-form-item>
                </el-col>
                <el-col :md="12" :sm="24">
                    <el-form-item :label="$t('From Email')">
                        <verified-email-input
                            v-model="mailer_settings.from_email"
                            :field="{ placeholder: $t('From Email'), 'data-type': 'email' }"
                        />
                        <p style="margin: 0; padding:0; font-size: 10px;">{{ $t('Vie_Please_msteisbyS') }}</p>
                    </el-form-item>
                </el-col>
            </el-row>
            <el-row :gutter="20">
                <el-col :md="12" :sm="24">
                    <el-form-item :label="$t('Reply To Name')">
                        <el-input :placeholder="$t('Reply To Name')"
                                  v-model="mailer_settings.reply_to_name"
                        ></el-input>
                    </el-form-item>
                </el-col>
                <el-col :md="12" :sm="24">
                    <el-form-item :label="$t('Reply To Email')">
                        <el-input :placeholder="$t('Reply To Email')" type="email"
                                  v-model="mailer_settings.reply_to_email"
                        ></el-input>
                    </el-form-item>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script type="text/babel">
import VerifiedEmailInput from '@/Pieces/FormElements/_VerifiedEmailInput';

export default {
    name: 'MailerSettings',
    components: {
        VerifiedEmailInput
    },
    props: {
        mailer_settings: {
            type: Object,
            default() {
                return {
                    from_name: '',
                    from_email: '',
                    reply_to_name: '',
                    reply_to_email: '',
                    is_custom: 'no'
                }
            }
        }
    },
    mounted() {

    }
}
</script>
