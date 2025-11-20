<template>
    <el-form-item>
        <template v-if="field.label" slot="label">
            {{ field.label }}
            <el-tooltip class="item" effect="dark" v-if="field.help" :content="field.help" placement="top-start">
                <i class="el-icon el-icon-info"></i>
            </el-tooltip>
        </template>
        <template v-if="field.type == 'option_selectors'">
            <option-selector v-model="model" :field="field"></option-selector>
        </template>
        <template v-else-if="field.type == 'multi-select' || field.type == 'select'">
            <el-select v-model="model" :multiple="field.type == 'multi-select'" :placeholder="field.placeholder"
                       clearable filterable>
                <el-option v-for="option in field.options" :key="option.id" :value="option.id"
                           :label="option.title"></el-option>
            </el-select>
        </template>
        <template v-else-if="field.type == 'radio'">
            <el-radio-group v-model="model">
                <el-radio v-for="option in field.options" :key="option.id" :label="option.id">{{ option.title }}
                </el-radio>
            </el-radio-group>
        </template>
        <template v-else-if="field.type=='input-number'">
            <el-input-number v-model="model"></el-input-number>
        </template>
        <template v-else-if="field.type=='input-text'">
            <el-input :readonly="field.readonly" :placeholder="field.placeholder" v-model="model"></el-input>
        </template>
        <template v-else-if="field.type=='input-text-area'">
            <el-input type="textarea" :rows="field.rows" :placeholder="field.placeholder" v-model="model"></el-input>
        </template>
        <template v-else-if="field.type=='input-text-popper'">
            <input-text-popper :field="field" :placeholder="field.placeholder" v-model="model"></input-text-popper>
        </template>
        <template v-else-if="field.type=='yes_no_check'">
            <el-checkbox true-label="yes" false-label="no" v-model="model">{{ field.check_label }}</el-checkbox>
        </template>
        <template v-else-if="field.type == 'grouped-select'">
            <el-select v-model="model" :multiple="field.is_multiple" :placeholder="field.placeholder" clearable
                       filterable>
                <el-option-group
                    v-for="group in field.options"
                    :key="group.slug"
                    :label="group.title">
                    <el-option
                        v-for="option in group.options"
                        :key="option.id"
                        :value="option.id"
                        :label="option.title"></el-option>
                </el-option-group>
            </el-select>
        </template>
        <template v-else-if="field.type == 'multi_text_options'">
            <multi-text-options :field="field" v-model="model"/>
        </template>
        <template v-else-if="field.type == 'email_campaign_composer'">
            <email-composer @save="saveInline()" :extra_tags="context_codes" :show_audit="true" :show_merge="true"
                            :enable_test="true" :disable_fixed="true"
                            class="fc_into_modal" :campaign="model"
                            label_align="top"/>
        </template>
        <template v-else-if="field.type == 'reload_field_selection'">
            <el-select @change="saveAndReload()" v-model="model" :multiple="field.type == 'multi-select'"
                       :placeholder="field.placeholder"
                       clearable filterable>
                <el-option v-for="option in field.options" :key="option.id" :value="option.id"
                           :label="option.title"></el-option>
            </el-select>
        </template>
        <template v-else-if="field.type == 'form-group-mapper'">
            <form-group-mapper :field="field" :model="model"/>
        </template>
        <template v-else-if="field.type == 'form-many-drop-down-mapper'">
            <form-many-drop-down-mapper :field="field" v-model="model"/>
        </template>
        <template v-else-if="field.type == 'html'">
            <div class="fc_html_content" v-html="field.info"></div>
        </template>
        <template v-else-if="field.type == 'url_selector'">
            <wp-url-selector v-model="model" :field="field"/>
        </template>
        <template v-else-if="field.type == 'date_time'">
            <el-date-picker
                value-format="yyyy-MM-dd HH:mm:ss"
                v-model="model"
                :placeholder="field.placeholder"
                type="datetime"></el-date-picker>
        </template>
        <template v-else-if="field.type == 'condition_groups'">
            <condition-groups
                v-model="model"
                :field="field"></condition-groups>
        </template>
        <template v-else-if="field.type == 'input_value_pair_properties'">
            <input-value-properties v-model="model"
                                    :field="field"></input-value-properties>
        </template>
        <template v-else-if="field.type == 'text-value-multi-properties'">
            <text-value-multi-properties v-model="model" :field="field"></text-value-multi-properties>
        </template>
        <template v-else-if="field.type == 'html_editor'">
            <wp-base-editor :editorShortcodes="editorCodes" v-model="model"></wp-base-editor>
        </template>
        <template v-else-if="field.type == 'rest_selector'">
            <ajax-selector v-model="model" :field="field"></ajax-selector>
        </template>
        <template v-else-if="field.type == 'reload_rest_selector'">
            <ajax-selector @change="saveAndReload() && logConsole" v-model="model" :field="field"></ajax-selector>
        </template>
        <template v-else-if="field.type == 'condition_block_groups'">
            <rich-filter-container :add_label="field.add_label" :advanced_filters="model"
                                   :filterOptions="field.groups"/>
        </template>
        <template v-else-if="field.type == 'custom_sender_config'">
            <mailer-config :mailer_settings="model"/>
        </template>
        <template v-else-if="field.type == 'radio_buttons'">
            <el-radio-group v-model="model">
                <el-radio-button v-for="option in field.options" :key="option.id" :label="option.id">
                    {{ option.title }}
                </el-radio-button>
            </el-radio-group>
        </template>
        <template v-else-if="field.type == 'checkboxes'">
            <el-checkbox-group v-model="model">
                <el-checkbox v-for="option in field.options" :key="option.id" :label="option.id">
                    {{ option.title }}
                </el-checkbox>
            </el-checkbox-group>
        </template>
        <template v-else-if="field.type == 'time_selector'">
            <el-time-select
                v-model="model"
                :picker-options="field.picker_options"
                :placeholder="field.placeholder">
            </el-time-select>
        </template>
        <template v-else-if="field.type == 'tax_selector'">
            <taxonomy-terms-selector v-model="model" :field="{
                        is_multiple: field.is_multiple,
                        size: 'mini',
                        taxonomy: field.taxonomy
                    }"/>
        </template>
        <template v-else-if="field.type == 'advanced_coupon_settings'">
            <AdvancedCouponSettings @saveAndReload="saveAndReload" :settings="model" :field="field"/>
        </template>
        <template v-else>
            <pre>{{ field }}</pre>
        </template>
        <p v-if="field.inline_help" v-html="field.inline_help"></p>
    </el-form-item>
</template>

<script type="text/babel">
import MultiTextOptions from '@/Pieces/FormElements/_MultiTextOptions';
import FormGroupMapper from '@/Pieces/FormElements/_FormGroupMapper';
import FormManyDropDownMapper from '@/Pieces/FormElements/_FormManyDropdownMapper';
import WpUrlSelector from '@/Pieces/FormElements/_WPUrlSelector';
import EmailComposer from '@/Pieces/EmailComposer';
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
import AjaxSelector from '@/Pieces/FormElements/_AjaxSelector';
import TaxonomyTermsSelector from '@/Pieces/FormElements/_TaxonomyTermsSelector';
import InputTextPopper from '@/Pieces/FormElements/_InputTextPopper';
import ConditionGroups from '@/Pieces/FormElements/_ConditionGroups.vue';
import InputValueProperties from '@/Pieces/FormElements/_InputValuePairProperties.vue';
import TextValueMultiProperties from '@/Pieces/FormElements/_TextValueMultiProperties.vue';
import WpBaseEditor from '@/Pieces/_wp_editor';
import RichFilterContainer from '@/Modules/Contacts/RichFilters/_RichFilterContainer';
import MailerConfig from '@/Pieces/FormElements/_MailerConfig';
import AdvancedCouponSettings from './_AdvancedCouponSettings';

export default {
    name: 'FormField',
    props: ['value', 'field', 'options'],
    components: {
        MultiTextOptions,
        EmailComposer,
        FormGroupMapper,
        FormManyDropDownMapper,
        WpUrlSelector,
        OptionSelector,
        ConditionGroups,
        InputValueProperties,
        WpBaseEditor,
        TextValueMultiProperties,
        InputTextPopper,
        AjaxSelector,
        RichFilterContainer,
        MailerConfig,
        TaxonomyTermsSelector,
        AdvancedCouponSettings
    },
    data() {
        return {
            model: this.value,
            context_codes: false,
            editorCodes: []
        }
    },
    watch: {
        model: {
            deep: true,
            handler(newValue) {
                this.$emit('input', newValue);
            }
        }
    },
    methods: {
        saveAndReload() {
            this.$nextTick(() => {
                this.$emit('save_reload');
            });
        },
        saveInline() {
            this.$emit('save_inline');
        }
    },
    created() {
        if (this.field.smart_codes) {
            this.editorCodes = window.fcAdmin.globalSmartCodes;
            if (this.field.context_codes && window.fcrm_funnel_context_codes) {
                this.editorCodes = [...this.editorCodes, ...window.fcrm_funnel_context_codes];
            }

            if (window.fcAdmin.extendedSmartCodes) {
                this.editorCodes.push(...window.fcAdmin.extendedSmartCodes);
            }
        }

        if (this.field.type === 'email_campaign_composer') {
            this.context_codes = window.fcrm_funnel_context_codes;
        }
    }
}
</script>
