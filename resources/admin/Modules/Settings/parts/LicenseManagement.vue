<template>
    <div v-if="has_campaign_pro" class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('License Management')}}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button
                    class="refresh-setting-icon"
                    type="text"
                    size="medium"
                    icon="el-icon-refresh"
                    @click="getSettings">
                </el-button>
            </div>
        </div>
        <div v-loading="verifying" class="fluentcrm_pad_around">
            <div v-if="fetching" v-loading="fetching" class="text-align-center">
                <h3>{{$t("Fetching License Information Please wait")}}</h3>
            </div>
            <div v-else class="fc_narrow_box fc_white_inverse text-align-center" :class="'fc_license_'+licenseData.status">
                <div v-if="licenseData.status == 'expired'">
                    <h3>{{$t("Looks like your license has been expired")}} {{licenseData.expires | nsHumanDiffTime}}</h3>
                    <a :href="licenseData.renew_url" target="_blank" class="el-button el-button--danger el-button--small">{{$t("Click Here to Renew your License")}}</a>

                    <hr style="margin: 20px 0px;" />
                    <p v-if="!showNewLicenseInput">{{$t('Have a new license Key?')}} <a @click.prevent="showNewLicenseInput = !showNewLicenseInput" href="#">{{$t('Click here')}}</a></p>
                    <div v-else>
                        <h3>{{$t('Your License Key')}}</h3>
                        <el-input v-model="licenseKey" placeholder="License Key">
                            <el-button @click="verifyLicense()" slot="append" icon="el-icon-lock">{{$t('Verify License')}}</el-button>
                        </el-input>
                    </div>
                </div>
                <div v-else-if="licenseData.status == 'valid'">
                    <div class="text-align-center"><span style="font-size: 50px;" class="el-icon el-icon-circle-check"></span></div>
                    <h2>{{$t('You license key is valid and activated')}}</h2>
                    <hr style="margin: 20px 0px;" />
                    <p>{{$t('Want to deactivate this license?')}} <a @click.prevent="deactivateLicense()" href="#">{{$t('Click here')}}</a></p>
                </div>
                <div v-else>
                    <h3>{{$t('FluentCRM_License.desc')}}</h3>
                    <el-input v-model="licenseKey" placeholder="License Key">
                        <el-button type="success" @click="verifyLicense()" slot="append" icon="el-icon-lock">{{$t('Verify License')}}</el-button>
                    </el-input>
                    <hr style="margin: 20px 0 30px;" />
                    <p v-if="!showNewLicenseInput">{{$t('dont_have_license_key')}} <a target="_blank" :href="licenseData.purchase_url">{{$t('Purchase one here')}}</a></p>
                </div>
            </div>

            <p class="text-align-center" style="color: red;" v-html="errorMessage"></p>
        </div>
    </div>
</template>

<script type="text/babel">

export default {
    name: 'LicenseSettings',
    components: {},
    data() {
        return {
            fetching: true,
            verifying: false,
            licenseData: {},
            licenseKey: '',
            showNewLicenseInput: false,
            errorMessage: ''
        }
    },
    methods: {
        getSettings() {
            this.errorMessage = '';
            this.fetching = true;
            this.$get('campaign-pro-settings/license', { verify: true })
                .then((response) => {
                    this.licenseData = response;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                });
        },
        verifyLicense() {
            if (!this.licenseKey) {
                this.$notify.error(this.$t('Please provide a license key'));
                this.errorMessage = this.$t('Please provide a license key');
                return;
            }

            this.verifying = true;

            this.errorMessage = '';
            this.$post('campaign-pro-settings/license', {
                license_key: this.licenseKey
            })
                .then(response => {
                    this.licenseData = response.license_data;
                    this.$notify.success(response.message);
                })
                .catch((errorResponse) => {
                    let errorMessage = '';
                    if (typeof errorResponse === 'string') {
                        errorMessage = errorResponse;
                    } else if (errorResponse && errorResponse.message) {
                        errorMessage = errorResponse.message;
                    } else {
                        errorMessage = window.FLUENTCRM.convertToText(errorResponse);
                    }
                    if (!errorMessage) {
                        errorMessage = this.$t('Something is wrong!');
                    }

                    this.errorMessage = errorMessage;

                    this.handleError(errorResponse);
                })
                .finally(() => {
                    this.verifying = false;
                });
        },
        deactivateLicense() {
            this.verifying = true;
            this.$del('campaign-pro-settings/license')
            .then(response => {
                this.licenseData = response.license_data;
                this.$notify.success(response.message);
            })
            .catch((errors) => {
                this.handleError(errors)
            })
            .finally(() => {
                this.verifying = false;
            });
        }
    },
    mounted() {
        if (this.has_campaign_pro) {
            this.getSettings();
        }
        this.changeTitle(this.$t('General Settings'));
    }
}
</script>
