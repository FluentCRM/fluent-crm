<template>
    <tr>
        <td style="width: 210px; line-height: 110%;">
            {{ getProviderLabel(itemConfig.provider) }} <span class="fs_provider_separator">/</span>
            {{ itemConfig.label }}
            <span v-if="itemConfig.help">
                <el-tooltip class="item" effect="dark" placement="top-start">
                    <i class="el-icon el-icon-info"></i>
                    <span slot="content" v-html="itemConfig.help"></span>
                </el-tooltip>
            </span>
        </td>
        <td style="width: 190px" class="fc_filter_operator">
            <el-select :disabled="view_only" size="mini" :placeholder="$t('Select Operator')"
                       @visible-change="maybeOperatorSelected"
                       v-model="item.operator">
                <el-option v-for="(optionLabel,option) in operatorOptions" :key="option" :value="option"
                           :label="optionLabel"></el-option>
            </el-select>
        </td>
        <td class="fc_filter_value">
            <template v-if="item.operator == 'is_null' || item.operator == 'not_null' || item.operator == 'never'">
                --
            </template>
            <template v-else>
                <el-input :disabled="view_only" size="mini"
                          v-if="!itemConfig.type || itemConfig.type == 'text' || itemConfig.type == 'extended_text' || itemConfig.type == 'nullable_text'"
                          :placeholder="$t('Condition Value')"
                          type="text" v-model="item.value"/>
                <el-input :disabled="view_only" size="mini" v-else-if="itemConfig.type == 'numeric'" type="number"
                          :placeholder="$t('Condition Value')"
                          :min="itemConfig.min"
                          v-model="item.value"/>
                <template v-else-if="itemConfig.type == 'dates'">
                    <el-input :disabled="view_only" size="mini"
                              v-if="item.operator == 'days_before' || item.operator == 'days_within'"
                              type="number" :placeholder="$t('Days')" v-model="item.value"/>
                    <el-date-picker v-else-if="item.operator" :type="itemConfig.date_type || 'date'"
                                    :disabled="view_only" :value-format="itemConfig.value_format || 'yyyy-MM-dd'"
                                    size="mini"
                                    v-model="item.value"></el-date-picker>
                </template>
                <template v-if="itemConfig.type == 'selections'">
                    <template v-if="itemConfig.component == 'product_selector'">
                        <ajax-selector v-model="item.value" :field="{
                        is_multiple: itemConfig.is_multiple,
                        option_key: 'product_selector_' + itemConfig.provider,
                        extended_key: itemConfig.extended_key || '',
                        size: 'mini',
                        creatable: itemConfig.creatable,
                        disabled: view_only,
                        cacheable: itemConfig.cacheable,
                        experimental_cache: itemConfig.experimental_cache
                    }"/>
                    </template>
                    <template v-else-if="itemConfig.component == 'options_selector'">
                        <option-selector v-model="item.value" :field="{
                        is_multiple: itemConfig.is_multiple,
                        size: 'mini',
                        disabled: view_only,
                        option_key: itemConfig.option_key
                    }"></option-selector>
                    </template>
                    <template v-else-if="itemConfig.options">
                        <el-select :disabled="view_only" size="mini" :multiple="itemConfig.is_multiple"
                                   :placeholder="$t('Select Option')"
                                   v-model="item.value">
                            <el-option v-for="(optionLabel,option) in itemConfig.options" :key="option" :value="option"
                                       :label="optionLabel"></el-option>
                        </el-select>
                    </template>
                    <template v-else-if="itemConfig.component == 'ajax_selector'">
                        <ajax-selector v-model="item.value" :field="{
                        is_multiple: itemConfig.is_multiple,
                        option_key: itemConfig.option_key,
                        size: 'mini',
                        creatable: itemConfig.creatable,
                        disabled: view_only,
                        cacheable: itemConfig.cacheable,
                        experimental_cache: itemConfig.experimental_cache
                    }"/>
                    </template>
                    <template v-else-if="itemConfig.component == 'tax_selector'">
                        <taxonomy-terms-selector v-model="item.value" :field="{
                        is_multiple: itemConfig.is_multiple,
                        size: 'mini',
                        disabled: view_only,
                        taxonomy: itemConfig.taxonomy
                    }"/>
                    </template>
                    <template v-else-if="itemConfig.disable_values">
                        <p v-html="itemConfig.value_description"></p>
                    </template>
                    <pre v-else>{{ itemConfig }}</pre>
                </template>
                <template
                    v-else-if="itemConfig.type == 'single_assert_option' || itemConfig.type == 'straight_assert_option'">
                    <el-select size="mini" :placeholder="$t('Select Option')" :disabled="view_only"
                               v-model="item.value">
                        <el-option v-for="(optionLabel,option) in itemConfig.options" :key="option" :value="option"
                                   :label="optionLabel"></el-option>
                    </el-select>
                </template>
                <template v-else-if="itemConfig.type == 'times_numeric'">
                    <item-times-selection :disabled="view_only" v-model="item.value" :field="itemConfig"/>
                </template>
                <div class="fc_composite_filters" v-else-if="itemConfig.type == 'composite_optioned_compare'">
                    <div v-if="itemConfig.ajax_selector" class="fc_composite_filter">
                        <label>{{ itemConfig.ajax_selector.label }}</label>
                        <div class="fc_composite_input">
                            <ajax-selector v-model="item.extra_value" :field="{
                                is_multiple: itemConfig.ajax_selector.is_multiple,
                                option_key: itemConfig.ajax_selector.option_key,
                                size: 'mini',
                                creatable: itemConfig.ajax_selector.creatable,
                                cacheable: itemConfig.ajax_selector.cacheable,
                                experimental_cache: itemConfig.ajax_selector.experimental_cache
                            }"/>
                        </div>
                    </div>
                    <div class="fc_composite_filter">
                        <label>{{ itemConfig.value_config.label }}</label>
                        <div class="fc_composite_input">
                            <el-input size="mini" v-model="item.value" :type="itemConfig.value_config.data_type"
                                      :placeholder="itemConfig.value_config.placeholder"></el-input>
                        </div>
                    </div>
                </div>
                <div class="fc_cascade_selections" v-else-if="itemConfig.type == 'cascade_selections'">
                    <CascadeOptionSelector v-model="item.value" :field="itemConfig" />
                </div>
            </template>
        </td>
        <td v-if="!view_only" style="width: 50px; text-align: right;">
            <el-button
                icon="el-icon-delete"
                @click="removeItem()"
                size="mini"
                type="danger">
            </el-button>
        </td>
    </tr>
</template>

<script type="text/babel">
import isArray from 'lodash/isArray';
import AjaxSelector from '@/Pieces/FormElements/_AjaxSelector';
import TaxonomyTermsSelector from '@/Pieces/FormElements/_TaxonomyTermsSelector';
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
import ItemTimesSelection from './_ItemTimesSelection';
import CascadeOptionSelector from '@/Pieces/FormElements/_CascadeOptionSelector';

export default {
    name: 'RichFilterItem',
    props: ['item', 'filterLabels', 'view_only'],
    components: {
        OptionSelector,
        AjaxSelector,
        TaxonomyTermsSelector,
        ItemTimesSelection,
        CascadeOptionSelector
    },
    data() {
        return {}
    },
    computed: {
        operatorOptionsNative() {
            if (this.itemConfig.custom_operators) {
                return this.itemConfig.custom_operators;
            }

            const type = this.itemConfig.type;

            if (type == 'extended_text') {
                return {
                    '=': this.$t('equal'),
                    '!=': this.$t('does not equal'),
                    contains: this.$t('includes'),
                    not_contains: this.$t('does not includes'),
                    startsWith: this.$t('starts with'),
                    endsWith: this.$t('ends with')
                }
            }

            if (!type || type == 'text') {
                return {
                    '=': this.$t('equal'),
                    '!=': this.$t('does not equal'),
                    contains: this.$t('includes'),
                    not_contains: this.$t('does not includes')
                }
            }
            if (type == 'numeric' || type == 'times_numeric') {
                return {
                    '>': this.$t('Greater Than'),
                    '<': this.$t('Less Than'),
                    '=': this.$t('equal'),
                    '!=': this.$t('does not equal')
                }
            }
            if (type == 'selections') {
                if (this.itemConfig.custom_operators) {
                    return this.itemConfig.custom_operators;
                }

                if (this.itemConfig.option_key === 'countries') {
                    return {
                        in: this.$t('includes in'),
                        not_in: this.$t('not includes in'),
                        is_null: this.$t('Empty'),
                        not_null: this.$t('Not Empty')
                    }
                }

                if (this.itemConfig.is_multiple && this.itemConfig.provider != 'subscriber' && this.itemConfig.provider != 'custom_fields' && !this.itemConfig.is_singular_value) {
                    return {
                        in: this.$t('includes'),
                        not_in: this.$t('Does not include (in any)'),
                        in_all: this.$t('includes all of'),
                        not_in_all: this.$t('Includes none of (match all)')
                    };
                }

                if (this.itemConfig.provider == 'custom_fields' && this.itemConfig.type == 'selections') {
                    return {
                        in: this.$t('includes in'),
                        not_in: this.$t('not includes in'),
                        '=': this.$t('equal'),
                        '!=': this.$t('not equal')
                    };
                }

                if (this.itemConfig.is_only_in) {
                    return {
                        in: this.$t('includes in')
                    }
                }

                return {
                    in: this.$t('includes in'),
                    not_in: this.$t('not includes in')
                };
            }
            if (type == 'single_assert_option') {
                return {
                    '=': this.$t('equal')
                };
            }

            if (type == 'straight_assert_option') {
                return {
                    '=': this.$t('equal'),
                    '!=': this.$t('not equal')
                };
            }

            if (type == 'dates') {
                const oparetors = {
                    before: this.$t('before'),
                    after: this.$t('after'),
                    date_equal: this.$t('in the date'),
                    days_before: this.$t('before days'),
                    days_within: this.$t('within days')
                };
                if (this.itemConfig.provider == 'activities') {
                    let neverTitle = this.$t('Never Opened');
                    if (this.itemConfig.value == 'email_link_clicked') {
                        neverTitle = this.$t('Never Clicked');
                    }
                    oparetors['never'] = neverTitle;
                }
                return oparetors;
            }

            if (type == 'nullable_text') {
                return {
                    '=': this.$t('equal'),
                    '!=': this.$t('does not equal'),
                    contains: this.$t('includes'),
                    not_contains: this.$t('does not includes'),
                    is_null: this.$t('Empty'),
                    not_null: this.$t('Not Empty')
                }
            }

            return {}
        },
        operatorOptions() {
            const options = this.operatorOptionsNative;
            if (this.itemConfig.provider == 'custom_fields') {
                options.is_null = this.$t('Empty');
                options.not_null = this.$t('Not Empty');
            }

            return options;
        },
        itemConfig() {
            const key = this.item.source.join('-');
            return this.filterLabels[key] || {}
        }
    },
    methods: {
        closingSource(status) {
            if (!status) {
                setTimeout(() => {
                    jQuery(this.$el).find('.fc_filter_operator .el-select').trigger('click');
                }, 300);
            }
        },
        maybeOperatorSelected(status) {
            if (!status && this.item.operator) {
                if (this.itemConfig.type == 'dates') {
                    this.item.value = '';
                }
                setTimeout(() => {
                    jQuery(this.$el).find('.fc_filter_value input').focus();
                }, 200);
            }
        },
        removeItem() {
            this.$emit('removeItem');
        },
        getProviderLabel(provider) {
            const labels = {
                ab_cart_woo: this.$t('Abandoned Cart'),
                custom_fields: this.$t('Custom Fields')
            };

            if (labels[provider]) {
                return labels[provider];
            }

            // replace _ and - with space
            provider = provider.replace(/_/g, ' ').replace(/-/g, ' ');
            return this.ucWords(provider);
        }
    },
    mounted() {
        if (this.itemConfig.is_multiple && !isArray(this.item.value)) {
            this.item.value = [];
        }
        if (!this.item.operator) {
            const objectValues = Object.keys(this.operatorOptions);
            if (objectValues.length) {
                this.item.operator = objectValues[0];
                jQuery(this.$el).find('.fc_filter_operator .el-select').trigger('click');
            }
        } else {
            const itemOperator = this.item.operator;

            const objectValues = Object.keys(this.operatorOptions);

            if (objectValues.length && objectValues.indexOf(itemOperator) === -1) {
                this.item.operator = objectValues[0];
            }
        }
    }
}
</script>
