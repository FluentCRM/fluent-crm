<template>
    <div class="fc_custom_field_wrapper">
        <div v-if="custom_fields.length" class="fluentcrm_custom_fields">
            <h3> {{ $t('Custom Data') }}
                <el-button type="default" plain size="mini" icon="el-icon-s-operation"
                           @click="showingCustomFieldsConfig = true"></el-button>
            </h3>
            <el-form v-if="app_ready" :data="custom_values" label-position="top">
                <div :class="groupName ? 'fluentcrm_custom_fields' : ''" v-for="(fields, groupName) in fieldGropups" :key="groupName">
                    <div :class="groupName ? 'fc_custom_field_group_box' : ''">
                        <div v-if="groupName" class="fc_item_label">{{ groupName }}</div>
                        <el-form-item v-for="(field, fieldKey) in fields" :key="fieldKey"
                                      :label="field.label">
                            <el-input :placeholder="field.label"
                                      v-if="field.type == 'text' || field.type == 'number' || field.type == 'textarea'"
                                      :type="field.type"
                                      v-model="custom_values[fieldKey]"></el-input>
                            <template v-else-if="field.type == 'radio'">
                                <el-radio-group v-model="custom_values[fieldKey]">
                                    <el-radio v-for="option in field.options" :key="option" :value="option"
                                              :label="option"></el-radio>
                                </el-radio-group>
                            </template>
                            <template v-else-if="field.type == 'select-one' || field.type == 'select-multi'">
                                <el-select :placeholder="$t('Select')+' '+field.label" clearable filterable
                                           :multiple="field.type == 'select-multi'"
                                           v-model="custom_values[fieldKey]">
                                    <el-option v-for="option in field.options" :key="option" :value="option"
                                               :label="option"></el-option>
                                </el-select>
                            </template>
                            <template v-else-if="field.type == 'checkbox'">
                                <el-checkbox-group v-model="custom_values[fieldKey]">
                                    <el-checkbox v-for="option in field.options" :key="option" :value="option"
                                                 :label="option"></el-checkbox>
                                </el-checkbox-group>
                            </template>
                            <template v-else-if="field.type == 'date'">
                                <el-date-picker
                                    value-format="yyyy-MM-dd"
                                    v-model="custom_values[fieldKey]"
                                    type="date"
                                    :placeholder="$t('Pick a date')">
                                </el-date-picker>
                            </template>
                            <template v-else-if="field.type == 'date_time'">
                                <el-date-picker
                                    value-format="yyyy-MM-dd HH:mm:ss"
                                    v-model="custom_values[fieldKey]"
                                    type="datetime"
                                    :placeholder="$t('Pick a date and time')">
                                </el-date-picker>
                            </template>
                            <template v-else>
                                {{ field }}
                            </template>
                        </el-form-item>
                    </div>
                </div>
            </el-form>
        </div>
        <div style="margin-bottom: 15px; text-align: right;" class="fc_no_custom_fields_btn" v-else>
            <el-button plain size="mini" icon="el-icon-s-operation" @click="showingCustomFieldsConfig = true">
                {{ $t('Custom Fields') }}
            </el-button>
        </div>
        <el-drawer :append-to-body="true" :title="$t('Custom Fields Config')" :visible.sync="showingCustomFieldsConfig"
                   size='50%'>
            <custom-fields :is_pop="true" route="companies/custom-fields"
                           section_title="Custom Company Fields"
                           @fieldsUpdated="(fields) => { handleUpdateFields(fields) }"
                           v-if="showingCustomFieldsConfig"></custom-fields>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import has from 'lodash/has';
import CustomFields from '@/Modules/Settings/parts/CustomContactFields.vue';

export default {
    name: 'ProfileCustomFields',
    props: ['custom_values'],
    components: {
        CustomFields
    },
    data() {
        return {
            app_ready: false,
            showingCustomFieldsConfig: false,
            custom_fields: this.appVars.company_custom_fields
        }
    },
    computed: {
        fieldGropups() {
            const formattedFields = {};
            this.each(this.custom_fields, (field) => {
                if (has(this.custom_values, field.slug)) {
                    field.is_disabled = true;
                }

                if (!field.group) {
                    field.group = '';
                }

                if (!formattedFields[field.group]) {
                    formattedFields[field.group] = {};
                }

                formattedFields[field.group][field.slug] = field;
            });
            return formattedFields;
        }
    },
    methods: {
        handleUpdateFields(fields) {
            this.custom_fields = fields;
            this.appVars.company_custom_fields = fields;
        }
    },
    mounted() {
        this.each(this.custom_fields, (field) => {
            let defaultValue = '';
            if (['select-multi', 'checkbox'].indexOf(field.type) !== -1) {
                defaultValue = [];
            }
            if (!has(this.custom_values, field.slug)) {
                this.$set(this.custom_values, field.slug, defaultValue);
            }
        });
        this.app_ready = true;
    }
}
</script>
