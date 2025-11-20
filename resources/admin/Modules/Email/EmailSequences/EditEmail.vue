<template>
    <div v-loading="loading" class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'email-sequences' }">
                        {{ $t('Email Sequences') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item :to="{ name: 'edit-sequence', params: { id: sequence_id } }">
                        {{ sequence.title }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{ email.email_subject }}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button @click="backToSequence()" size="small" class="mr-5">
                    {{ $t('Back') }}
                </el-button>
                <send-test-email btn_type="danger" :campaign="email"/>
            </div>
        </div>
        <template v-if="app_loaded">
            <div class="fluentcrm_body fluentcrm_pad_30">
                <el-form label-position="top" :model="email">
                    <el-row :gutter="20">
                        <el-col :sm="24" :md="12">
                            <el-form-item :label="$t('Email Subject')">
                                <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" popper_extra="fc_with_c_fields" v-if="email_subject_status"
                                               :placeholder="$t('Email Subject')" :data="smartcodes"
                                               v-model="email.email_subject"/>
                            </el-form-item>
                        </el-col>
                        <el-col :sm="24" :md="12">
                            <el-form-item :label="$t('Email Pre-Header')">
                                <el-input type="textarea"
                                          :placeholder="$t('Email Pre-Header')"
                                          :rows="2"
                                          v-model="email.email_pre_header"
                                ></el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="20">
                        <el-col :md="8" :sm="24">
                            <el-form-item :label="$t('Delay')">
                                <el-row class="d-flex items-center">
                                    <el-col style="min-width: 200px;" :md="6" :sm="12">
                                        <el-input-number v-model="email.settings.timings.delay"></el-input-number>
                                    </el-col>
                                    <el-col :md="12" :sm="12">
                                        <el-select v-model="email.settings.timings.delay_unit">
                                            <el-option value="minutes" :label="$t('Minutes')"></el-option>
                                            <el-option value="hours" :label="$t('Hours')"></el-option>
                                            <el-option value="days" :label="$t('Days')"></el-option>
                                            <el-option value="weeks" :label="$t('Weeks')"></el-option>
                                            <el-option value="Months" :label="$t('Months')"></el-option>
                                        </el-select>
                                    </el-col>
                                </el-row>
                                <p>{{ $t('Set after how many') }} {{ email.settings.timings.delay_unit || $t('time unit') }}
                                    {{ $t('the email will be triggered from the starting date') }}</p>
                            </el-form-item>
                        </el-col>
                        <el-col :md="8" :sm="24">
                            <el-form-item class="fluentcrm_width_input" :label="$t('Sending Time Range')">
                                <el-time-picker
                                    is-range
                                    value-format="HH:mm"
                                    format="HH:mm"
                                    v-model="email.settings.timings.sending_time"
                                    range-separator="To"
                                    :start-placeholder="$t('Start Range')"
                                    :end-placeholder="$t('End Range')">
                                </el-time-picker>
                                <p>{{ $t('Edi_If_ysatrtFstettt') }}</p>
                            </el-form-item>
                        </el-col>
                        <el-col :md="8" :sm="24">
                            <el-checkbox @change="changingSpecificDaysStatus()" class="mt-20" true-label="yes"
                                         false-label="no" v-model="email.settings.timings.selected_days_only">
                                {{ $t('Enable Specific Days Only') }}
                            </el-checkbox>
                            <el-form-item v-if="email.settings.timings.selected_days_only == 'yes'"
                                          style="margin-top: 10px;"
                                          :label="$t('Please select allowed days to send emails')">
                                <el-checkbox-group v-model="email.settings.timings.allowed_days">
                                    <el-checkbox label="Mon">{{ $t('Monday') }}</el-checkbox>
                                    <el-checkbox label="Tue">{{ $t('Tuesday') }}</el-checkbox>
                                    <el-checkbox label="Wed">{{ $t('Wednesday') }}</el-checkbox>
                                    <el-checkbox label="Thu">{{ $t('Thursday') }}</el-checkbox>
                                    <el-checkbox label="Fri">{{ $t('Friday') }}</el-checkbox>
                                    <el-checkbox label="Sat">{{ $t('Saturday') }}</el-checkbox>
                                    <el-checkbox label="Sun">{{ $t('Sunday') }}</el-checkbox>
                                </el-checkbox-group>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <div :class="{ fc_is_highlighted: email.utm_status == 1 || email.utm_status == '1' }"
                         class="fc_form_items_inline">
                        <el-form-item>
                            <el-checkbox true-label="1" false-label="0" v-model="email.utm_status">
                                {{ $t('Ema_Add_UPFU') }}
                            </el-checkbox>
                        </el-form-item>
                        <template v-if="email.utm_status == 1 || email.utm_status == '1'">
                            <el-form-item :label="$t('Campaign Source (required)')">
                                <el-input :placeholder="$t('The referrer: (e.g. google, newsletter)')"
                                          v-model="email.utm_source"></el-input>
                            </el-form-item>
                            <el-form-item :label="$t('Campaign Medium (required)')">
                                <el-input :placeholder="$t('Marketing medium: (e.g. cpc, banner, email)')"
                                          v-model="email.utm_medium"></el-input>
                            </el-form-item>
                            <el-form-item :label="$t('Campaign Name (required)')">
                                <el-input :placeholder="$t('Product, promo code, or slogan (e.g. spring_sale)')"
                                          v-model="email.utm_campaign"></el-input>
                            </el-form-item>
                            <el-form-item :label="$t('Campaign Term')">
                                <el-input :placeholder="$t('Identify the paid keywords')"
                                          v-model="email.utm_term"></el-input>
                            </el-form-item>
                            <el-form-item :label="$t('Campaign Content')">
                                <el-input :placeholder="$t('Use to differentiate ads')"
                                          v-model="email.utm_content"></el-input>
                            </el-form-item>
                        </template>
                    </div>
                </el-form>
            </div>
            <email-block-composer :show_audit="true" @save="save()" @changed="handleChangeContent()" :show_merge="true"
                                  :enable_template_save="true"
                                  @template_inserted="resetSubject()" :enable_templates="true" :campaign="email">
                <template slot="fc_editor_actions">
                    <el-button @click="maybeSave()" size="small" type="success">{{ $t('Save') }}</el-button>
                </template>
            </email-block-composer>
        </template>
        <div class="fluentcrm_body fluentcrm_pad_30 text-align-center" v-else>
            <h3>{{ $t('Loading') }}</h3>
        </div>
    </div>
</template>

<script type="text/babel">
import EmailBlockComposer from '@/Pieces/EmailElements/BlockComposer';
import InputPopover from '@/Pieces/InputPopover';
import SendTestEmail from '@Pieces/TestEmail.vue';

export default {
    name: 'SequenceEmailEdit',
    props: ['sequence_id', 'email_id'],
    components: {
        EmailBlockComposer,
        InputPopover,
        SendTestEmail
    },
    data() {
        return {
            sequence: {},
            email: {},
            loading: false,
            saving: false,
            app_loaded: false,
            smartcodes: window.fcAdmin.globalSmartCodes,
            email_subject_status: true,
            is_dirty: ''
        }
    },
    watch: {
        email_id() {
            this.fetchSequenceEmail();
        }
    },
    methods: {
        backToSequence() {
            this.$router.push({
                name: 'edit-sequence',
                params: {
                    id: this.sequence_id
                },
                query: {t: (new Date()).getTime()}
            });
        },
        fetchSequenceEmail() {
            this.loading = true;
            this.$get(`sequences/${this.sequence_id}/email/${this.email_id}`, {with: ['sequence']})
                .then(response => {
                    this.sequence = response.sequence;
                    this.email = response.email;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                    this.app_loaded = true;
                });
        },
        save() {
            if (!this.email.email_body) {
                return this.$notify.error({
                    title: this.$t('Oops!'),
                    message: this.$t('Cam_Please_peb'),
                    offset: 19
                });
            }

            if (!this.email.email_subject) {
                return this.$notify.error({
                    title: this.$t('Oops!'),
                    message: this.$t('Cam_Please_peS'),
                    offset: 19
                });
            }
            this.saving = true;
            this.is_dirty = false;

            const data = {
                route_method: 'create',
                email: JSON.stringify(this.email),
                sequence_id: this.sequence_id
            }

            if (parseInt(this.email_id)) {
                data.route_method = 'update';
                data.mail_id = this.email_id;
            }

            this.$post('sequences/sequence-email-update-create', data).then(response => {
                this.$notify.success(response.message);
                if (!parseInt(this.email_id)) {
                    this.$router.push({
                        name: 'edit-sequence-email',
                        params: {
                            sequence_id: response.email.parent_id,
                            email_id: response.email.id
                        }
                    });
                }
            })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                    this.is_dirty = false;
                });
        },
        maybeSave() {
            if (this.email.design_template == 'visual_builder') {
                this.$bus.$emit('getVisualData', {});
            } else {
                this.save();
            }
        },
        resetSubject() {
            this.email_subject_status = false;
            this.$nextTick(() => {
                this.email_subject_status = true;
            });
        },
        changingSpecificDaysStatus() {
            if (this.email.settings.timings.selected_days_only == 'yes' && !this.email.settings.timings.allowed_days) {
                this.$set(this.email.settings.timings, 'allowed_days', [
                    'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'
                ]);
            }
        },
        handleChangeContent() {
            if (this.is_dirty === '') {
                this.is_dirty = false;
                return;
            }
            this.is_dirty = true;
        },
        initKeyboardSave(e) {
            if ((window.navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.maybeSave();
            }
        }
    },
    mounted() {
        this.fetchSequenceEmail();
        document.addEventListener('keydown', this.initKeyboardSave);
    },
    beforeRouteLeave(to, from, next) {
        if (this.is_dirty) {
            const answer = window.confirm(this.$t('Unsaved_Confirm_Msg'))
            if (!answer) return false;
        }
        this.unmountBlockEditor();
        document.removeEventListener('keydown', this.initKeyboardSave);
        next();
    }
}
</script>
