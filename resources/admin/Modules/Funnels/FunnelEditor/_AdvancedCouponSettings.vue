<template>
    <div class="fc_coupon_settings">
        <div>
            <p v-if="settings.smart_code"
               style="font-size: 16px;margin-bottom: 20px;padding: 20px;background: #f5f7fa;border-radius: 10px;text-align: center;">
                {{ $t('Dynamic_Coupon_Usage') }}
                <item-copier style="max-width: 220px;" :text="settings.smart_code"/>
            </p>

            <el-form-item label="Coupon Code Configuration Type">
                <el-radio-group v-model="settings.template_type">
                    <el-radio label="new">{{ $t('Configure from scratch') }}</el-radio>
                    <el-radio label="templated">{{ $t('Use Existing Coupon as Template') }}</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item v-if="settings.template_type == 'templated'" :label="$t('Select your existing Coupon Code')">
                <ajax-selector v-model="settings.base_coupon_id"
                               :field="{ option_key: 'woo_coupons', sub_option_key: 'main_only', is_multiple: false }"/>
                <p>{{$t('Dynamic_Coupon_Configuration')}}</p>
            </el-form-item>

            <el-form-item v-if="!settings.smart_code">
                <el-button :disabled="saving" v-loading="saving" @click="save" type="primary">{{ $t('Continue') }}</el-button>
            </el-form-item>
        </div>
        <el-tabs v-if="settings.smart_code" type="border-card">
            <el-tab-pane label="General">
                <el-form-item>
                    <template slot="label">
                        {{ $t('Coupon Code Prefix') }}
                        <el-tooltip class="item" effect="dark"
                                    :content="$t('Coupon_Code_Prefix_help')"
                                    placement="top-start">
                            <i class="el-icon el-icon-info"></i>
                        </el-tooltip>
                    </template>
                    <el-input v-model="settings.code_prefix" :placeholder="'eg: WELCOME_{{contact.first_name}}'">
                        <template slot="append">
                            -RANDOM_SUFFIX
                        </template>
                    </el-input>
                </el-form-item>
                <template v-if="settings.template_type == 'new'">
                    <el-row :gutter="30">
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('Discount Type')">
                                <el-select v-model="settings.discount_type" :placeholder="$t('Discount Type')">
                                    <el-option :label="$t('Percentage Discount')" value="percent"></el-option>
                                    <el-option :label="$t('Fixed Cart Discount')" value="fixed_cart"></el-option>
                                    <el-option :label="$t('Fixed Product Discount')" value="fixed_product"></el-option>
                                </el-select>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('Amount')">
                                <el-input type="number" v-model="settings.amount" :placeholder="$t('Amount')">
                                    <template v-if="settings.discount_type == 'percent'" slot="append">%</template>
                                    <template v-else slot="prepend">
                                        <span>{{ appVars.woo_currency_sign }}</span>
                                    </template>
                                </el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="30">
                        <el-col :md="18" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Coupon Expiry') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Choose when the coupon will expire')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <el-radio-group v-model="settings.expiry_type">
                                    <el-radio label="never">{{ $t('Never Expires') }}</el-radio>
                                    <el-radio label="fixed">{{ $t('Fixed Date') }}</el-radio>
                                    <el-radio label="relative_days">{{ $t('Expire after x days of creation') }}</el-radio>
                                </el-radio-group>
                            </el-form-item>
                        </el-col>
                        <el-col :md="6" :xs="24">
                            <el-form-item ::label="$t('Expiry Date')" v-if="settings.expiry_type == 'fixed'">
                                <el-date-picker
                                    v-model="settings.date_expires"
                                    type="date"
                                    date-format="yyyy-MM-dd"
                                    :placeholder="$t('Pick a date')">
                                </el-date-picker>
                            </el-form-item>
                            <el-form-item :label="$t('Days')" v-if="settings.expiry_type == 'relative_days'">
                                <el-input type="number" :min="1" v-model="settings.expiry_days"
                                          :placeholder="$t('Expire after x days')">
                                    <template slot="append">{{$t('days')}}</template>
                                </el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-form-item>
                        <template slot="label">
                            {{ $t('Allow Free Shipping') }}
                            <el-tooltip class="item" effect="dark"
                                        :content="$t('Free_Shipping_Info')"
                                        placement="top-start">
                                <i class="el-icon el-icon-info"></i>
                            </el-tooltip>
                        </template>
                        <el-radio-group v-model="settings.free_shipping">
                            <el-radio label="yes">{{ $t('Yes') }}</el-radio>
                            <el-radio label="no">{{ $t('No') }}</el-radio>
                        </el-radio-group>
                    </el-form-item>
                </template>
                <div class="fc_info" v-else>
                    <p>{{ $t('Coupon settings will be inherited from the selected base coupon') }}</p>
                </div>
                <el-form-item>
                    <el-checkbox v-model="settings.contact_email_only" true-label="yes" false-label="no">
                        {{ $t('Restrict the generated coupon to Contact Email Only') }}
                    </el-checkbox>
                </el-form-item>
            </el-tab-pane>
            <el-tab-pane :label="$t('Restrictions & Limits')">
                <template v-if="settings.template_type == 'new'">
                    <el-row :gutter="30">
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Minimum Spend') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Minimum_Spend_Requirement')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <el-input type="number" v-model="settings.minimum_amount" :placeholder="$t('Minimum Spend')">
                                    <template slot="prepend">
                                        <span>{{ appVars.woo_currency_sign }}</span>
                                    </template>
                                </el-input>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Maximum Spend') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Maximum_Spend_Limit')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <el-input type="number" v-model="settings.maximum_amount" :placeholder="$t('Maximum Spend')">
                                    <template slot="prepend">
                                        <span>{{ appVars.woo_currency_sign }}</span>
                                    </template>
                                </el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>

                    <el-row :gutter="30">
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Products') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Eligible_Products_For_Discount')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <ajax-selector v-model="settings.product_ids"
                                               :field="{ option_key: 'woo_products', is_multiple: true }"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Exclude Products') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Excluded_Products_For_Discount')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <ajax-selector v-model="settings.exclude_product_ids"
                                               :field="{ option_key: 'woo_products', is_multiple: true }"/>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="30">
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Product categories') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Eligible_Product_Categories_For_Discount')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <ajax-selector v-model="settings.product_categories"
                                               :field="{ option_key: 'woo_categories', is_multiple: true }"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Exclude Product categories') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Excluded_Product_Categories_For_Discount')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <ajax-selector v-model="settings.exclude_product_categories"
                                               :field="{ option_key: 'woo_categories', is_multiple: true }"/>
                            </el-form-item>
                        </el-col>
                    </el-row>

                    <hr/>
                    <el-form-item>
                        <el-checkbox style="margin-bottom: 10px;" v-model="settings.individual_use" true-label="yes"
                                     false-label="no">
                            {{ $t('Individual_Coupon_Use_info') }}
                        </el-checkbox>
                        <el-checkbox v-model="settings.exclude_sale_items" true-label="yes" false-label="no">
                            {{ $t('Exclude_Sale_Items') }}
                        </el-checkbox>
                    </el-form-item>
                    <hr/>
                    <h4>{{ $t('Limits') }}</h4>
                    <el-row :gutter="30">
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Usage limit per coupon') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('How many times this coupon can be used before it is void.')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <el-input type="number" v-model="settings.usage_limit" :placeholder="$t('Unlimited Usage')"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Limit usage to X items') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Coupon_Max_items_For_Discount')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <el-input type="number" v-model="settings.limit_usage_to_x_items"
                                          :placeholder="$t('Apply to all qualifying items in cart')"/>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    <el-row :gutter="30">
                        <el-col :md="12" :xs="24">
                            <el-form-item>
                                <template slot="label">
                                    {{ $t('Usage limit per user') }}
                                    <el-tooltip class="item" effect="dark"
                                                :content="$t('Coupon_Usage_Limit_Per_User')"
                                                placement="top-start">
                                        <i class="el-icon el-icon-info"></i>
                                    </el-tooltip>
                                </template>
                                <el-input type="number" v-model="settings.usage_limit_per_user"
                                          :placeholder="$t('Unlimited Usage')"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                        </el-col>
                    </el-row>
                </template>
                <div class="fc_info" v-else>
                    <p>{{ $t('Coupon_Inherited_Restrictions_And_Limits') }}</p>
                </div>
            </el-tab-pane>
        </el-tabs>
    </div>
</template>

<script type="text/babel">
import AjaxSelector from '@/Pieces/FormElements/_AjaxSelector';
import ItemCopier from '../../../Pieces/ItemCopier.vue';

export default {
    name: 'AdvancedCouponSettings',
    props: ['settings'],
    components: {
        AjaxSelector,
        ItemCopier
    },
    data() {
        return {
            saving: false
        }
    },
    methods: {
        save() {
            this.saving = true;
            this.$emit('saveAndReload');
        }
    }
}
</script>
