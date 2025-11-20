<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{ $t('Abandoned Cart Settings') }}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">

            </div>
        </div>
        <div class="fluentcrm_pad_around">

            <template v-if="!has_campaign_pro">
                <div class="fluentcrm_no_campaign_pro">
                    <generic-promo
                        :heading="$t('Recover Carts & Pending Orders')"/>
                </div>
            </template>

            <template v-else-if="!isAvailable">
                <h3>{{ $t('Abandoned_Cart_requires_WooCommerce') }}</h3>
            </template>

            <template v-else>
                <!-- Campaign Settings -->
                <div v-if="settings_loaded" class="settings-section">
                    <el-checkbox v-model="settings.enabled" true-label="yes" false-label="no">
                        {{ $t('Enable Abandoned Cart Tracking for WooCommerce') }}
                    </el-checkbox>
                    <template v-if="settings.enabled == 'yes'">
                        <el-form style="margin-top: 20px;" v-model="settings" label-position="top">
                            <el-row :gutter="30">
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Cart Abandoned Cut-off Time')">
                                        <el-input type="number" :min="10" v-model="settings.capture_after_minutes"
                                                  placeholder="e.g. 30">
                                            <template slot="append">{{ $t('Minutes') }}</template>
                                        </el-input>
                                        <p class="fc_inline_help">{{ $t('Minutes_before_cart_is_marked_recoverable') }}</p>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Mark as Lost after')">
                                        <el-input type="number" :min="1" v-model="settings.lost_cart_days"
                                                  placeholder="e.g. 20">
                                            <template slot="append">{{ $t('Days') }}</template>
                                        </el-input>
                                        <p class="fc_inline_help">{{ $t('AB_Cart_Mark_cart_as_lost_after_days') }}</p>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <el-row :gutter="30">
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Cool-Off Period')">
                                        <el-input type="number" :min="0" v-model="settings.cool_off_period_days"
                                                  placeholder="e.g. 10">
                                            <template slot="append">{{ $t('Days') }}</template>
                                        </el-input>
                                        <p class="fc_inline_help">{{ $t('AB_Cart_Exclude_customers_from_tracking_for_days_after_order') }}</p>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Status for New Contacts')">
                                        <el-select v-model="settings.new_contact_status" :placeholder="$t('Select Status')">
                                            <el-option label="Subscribed" value="subscribed"></el-option>
                                            <el-option label="Transactional" value="transactional"></el-option>
                                        </el-select>
                                        <p class="fc_inline_help">{{ $t('Status for your new contacts who are not exist in FluentCRM Database') }}</p>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                            <div style="margin-bottom: 30px;" v-if="wooOptions">
                                <el-form-item :label="$t('Mark Cart as Recovered when WooCommerce Order Status Changes to:')">
                                    <el-checkbox-group @change="checkWooPaidStatuses"
                                                       v-model="settings.wc_recovered_statuses">
                                        <el-checkbox v-for="(option, optionKey) in wooOptions.all_statuses"
                                                     :key="optionKey" :label="optionKey">
                                            {{ option|ucFirst }}
                                        </el-checkbox>
                                    </el-checkbox-group>
                                </el-form-item>
                            </div>
                            <h3>{{ $t('GDPR Consent') }}</h3>
                            <el-form-item>
                                <el-checkbox v-model="settings.gdpr_consent" true-label="yes" false-label="no">
                                    {{
                                        $t('Inform customers that their email and cart data are saved to send abandonment reminders')
                                    }}
                                </el-checkbox>
                            </el-form-item>

                            <el-form-item v-if="settings.gdpr_consent == 'yes'" :label="$t('GDPR Message')">
                                <el-input v-model="settings.gdpr_consent_text" type="textarea"
                                          :placeholder="$t('GDPR Message')"></el-input>
                                <p class="fc_inline_help">Use smartcode <span
                                    v-html="'{{opt_out label=\'No Thanks\'}}'"></span> to let users opt out of cart
                                    tracking.</p>
                            </el-form-item>

                            <h3>{{ $t('User') }}</h3>
                            <el-row :gutter="30">
                                <el-col v-if="false" :md="12" :xs="24">
                                    <el-form-item>
                                        <el-checkbox v-model="settings.track_add_to_cart" true-label="yes"
                                                     false-label="no">
                                            {{
                                                $t('Track carts when a product is added to the cart for logged-in users')
                                            }}
                                        </el-checkbox>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Disable Tracking For the User Roles')">
                                        <option-selector
                                            v-model="settings.disabled_user_roles"
                                            :field="{
                                                option_key: 'user_roles_options',
                                                size: 'small',
                                                is_multiple: true,
                                                placeholder: $t('Select Roles')
                                            }"></option-selector>
                                        <p class="fc_inline_help">{{ $t('Disable cart tracking for selected user roles') }}</p>
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <h3>{{ $t('Contact Tagging - Cart Abandoned') }}</h3>
                            <el-row :gutter="30">
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Add Lists on Cart Abandoned')">
                                        <option-selector
                                            v-model="settings.lists_on_cart_abandoned"
                                            :field="{
                                                option_key: 'lists',
                                                creatable: true,
                                                is_multiple: true,
                                                placeholder: $t('Select Lists (Optional)')
                                            }"></option-selector>
                                        <p class="fc_inline_help">{{ $t('Selected list(s) added when cart is abandoned. Removed on successful order.') }}</p>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Add Tags on Cart Abandoned')">
                                        <option-selector
                                            v-model="settings.tags_on_cart_abandoned"
                                            :field="{
                                                option_key: 'tags',
                                                creatable: true,
                                                is_multiple: true,
                                                placeholder: $t('Select Tags (Optional)')
                                            }"></option-selector>
                                        <p class="fc_inline_help">{{ $t('Selected tag(s) added when cart is abandoned. Removed on successful order.') }}</p>
                                    </el-form-item>
                                </el-col>
                            </el-row>

                            <h3>{{ $t('Contact Tagging - Cart Lost') }}</h3>
                            <el-row :gutter="30">
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Add Lists on Cart Lost')">
                                        <option-selector
                                            v-model="settings.lists_on_cart_lost"
                                            :field="{
                                                option_key: 'lists',
                                                creatable: true,
                                                is_multiple: true,
                                                placeholder: $t('Select Lists (Optional)')
                                            }"></option-selector>
                                        <p class="fc_inline_help">{{ $t('Selected list(s) added when cart is lost. Removed on successful order.') }}</p>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :xs="24">
                                    <el-form-item :label="$t('Add Tags on Cart Lost')">
                                        <option-selector
                                            v-model="settings.tags_on_cart_lost"
                                            :field="{
                                                option_key: 'tags',
                                                creatable: true,
                                                is_multiple: true,
                                                placeholder: $t('Select Tags (Optional)')
                                            }"></option-selector>
                                        <p class="fc_inline_help">{{ $t('Selected tag(s) added when cart is lost. Removed on successful order.') }}</p>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                        </el-form>
                    </template>
                    <div style="margin-top: 20px;">
                        <el-button :loading="btnFromLoading" @click="save" type="success">{{
                                $t('Save Settings')
                            }}
                        </el-button>
                    </div>
                </div>
                <el-skeleton :rows="8" :animated="true" v-else></el-skeleton>
            </template>
        </div>
    </div>
</template>

<script type="text/babel">
// import FormBuilder from '@/Pieces/FormElements/_FormBuilder';
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
import GenericPromo from '../../Promos/GenericPromo.vue';

export default {
    name: 'BusinessSettings',
    components: {
        GenericPromo,
        OptionSelector
    },
    data() {
        return {
            btnFromLoading: false,
            loading: false,
            settings: {},
            settings_loaded: false,
            isAvailable: false,
            wooOptions: null
        };
    },
    methods: {
        save() {
            this.btnFromLoading = true;
            this.$post('campaign-pro-settings/abandon-cart', {settings: this.settings})
                .then(r => {
                    this.$notify.success(r.message);
                    if (r.reload) {
                        location.reload();
                    }
                })
                .catch(error => {
                    this.$handleError(error);
                })
                .finally(() => {
                    this.btnFromLoading = false;
                });
        },
        fetchSettings() {
            this.loading = true;
            this.$get('campaign-pro-settings/abandon-cart')
                .then(response => {
                    this.settings = response.settings;
                    this.wooOptions = response.wooOptions;
                    this.settings_loaded = true;
                })
                .catch((error) => {
                    this.$handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        checkWooPaidStatuses() {
            this.each(this.wooOptions.paid_statuses, (status) => {
                if (!this.settings.wc_recovered_statuses.includes(status)) {
                    this.settings.wc_recovered_statuses.push(status);
                    this.$notify.warning('You must select woo defined paid statuses to mark the cart as recovered');
                }
            });
        }
    },
    mounted() {
        if (this.has_campaign_pro) {
            this.fetchSettings();
        }

        this.isAvailable = this.has_campaign_pro && this.appVars.has_woo;

        this.changeTitle(this.$t('Abandon Cart Settings'));
    }
};
</script>
