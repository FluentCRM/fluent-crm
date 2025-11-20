<template>
    <div class="fc_value_property_group">
        <table class="wp-list-table widefat fixed striped table-view-list posts">
            <thead>
            <tr>
                <th style="width: 180px;">{{ field.data_key_label }}</th>
                <th>{{ field.data_value_label }}</th>
                <th style="width: 50px;"></th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="(condition, conditionIndex) in model" :key="conditionIndex">
                <td>
                    <el-select clearable @change="condition.data_value == ''; delete condition.data_operation" :placeholder="$t('Select')" size="small" v-model="condition.data_key" filterable>
                        <el-option
                            v-for="(prop, propkey) in field.property_options"
                            :key="propkey"
                            :value="propkey"
                            :label="prop.label"
                        ></el-option>
                    </el-select>
                </td>
                <td>
                    <div v-if="condition.data_key">
                        <el-input
                            v-if="field.property_options[condition.data_key].type == 'text'"
                            size="small"
                            type="text"
                            v-model="condition.data_value"/>
                        <el-input
                            v-if="field.property_options[condition.data_key].type == 'textarea'"
                            size="small"
                            type="textarea"
                            v-model="condition.data_value"/>
                        <el-date-picker
                            v-if="field.property_options[condition.data_key].type == 'date'"
                            value-format="yyyy-MM-dd"
                            size="small"
                            v-model="condition.data_value"
                            type="date"
                            :placeholder="$t('Pick a date')">
                        </el-date-picker>
                        <el-date-picker
                            v-if="field.property_options[condition.data_key].type == 'date_time'"
                            value-format="yyyy-MM-dd HH:mm:ss"
                            v-model="condition.data_value"
                            type="datetime"
                            size="small"
                            :placeholder="$t('Pick a date and time')">
                        </el-date-picker>
                        <p v-if="field.property_options[condition.data_key].type == 'date' || field.property_options[condition.data_key].type == 'date_time' && field.property_options[condition.data_key].info" class="info" style="margin: 2px 0 0 0;line-height: 1;font-size:12px;">{{ field.property_options[condition.data_key].info }}</p>
                        <template v-else-if="field.property_options[condition.data_key].type == 'number'">
                            <template v-if="field.support_operations == 'yes'">
                                <el-row :gutter="10">
                                    <el-col :span="18">
                                        <el-input
                                            size="small"
                                            type="number"
                                            class="input-with-select"
                                            v-model="condition.data_value">
                                        </el-input>
                                    </el-col>
                                    <el-col :span="6">
                                        <el-select size="mini" v-model="condition.data_operation" :placeholder="$t('Replace Value')">
                                            <el-option value="" :label="$t('Replace Value')"></el-option>
                                            <el-option value="subtract" :label="$t('Subtract Value')"></el-option>
                                            <el-option value="add" :label="$t('Add Value')"></el-option>
                                        </el-select>
                                    </el-col>
                                </el-row>
                            </template>
                            <el-input
                                v-else
                                size="small"
                                type="number"
                                class="input-with-select"
                                v-model="condition.data_value">
                            </el-input>
                        </template>

                        <template v-else-if="field.property_options[condition.data_key].type == 'select'">
                            <template v-if="field.support_operations == 'yes' && field.property_options[condition.data_key].multiple">
                                <el-row :gutter="10">
                                    <el-col :span="18">
                                        <el-select
                                            size="small"
                                            v-model="condition.data_value"
                                            clearable
                                            :multiple="field.property_options[condition.data_key].multiple"
                                            filterable
                                        >
                                            <el-option
                                                v-for="option in field.property_options[condition.data_key].options"
                                                :key="option.id"
                                                :label="option.title"
                                                :value="option.id"
                                            ></el-option>
                                        </el-select>
                                    </el-col>
                                    <el-col :span="6">
                                        <el-select size="mini" v-model="condition.data_operation" :placeholder="$t('Replace Value')">
                                            <el-option value="" :label="$t('Replace Options')"></el-option>
                                            <el-option value="subtract" :label="$t('Subtract Options')"></el-option>
                                            <el-option value="add" :label="$t('Add Options')"></el-option>
                                        </el-select>
                                    </el-col>
                                </el-row>
                            </template>
                            <el-select
                                v-else
                                size="small"
                                v-model="condition.data_value"
                                clearable
                                :multiple="field.property_options[condition.data_key].multiple"
                                filterable
                            >
                                <el-option
                                    v-for="option in field.property_options[condition.data_key].options"
                                    :key="option.id"
                                    :label="option.title"
                                    :value="option.id"
                                ></el-option>
                            </el-select>
                        </template>
                        <template v-else-if="field.property_options[condition.data_key].type == 'option_selector'">
                            <option-selector :field="{
                                    placeholder: 'Select',
                                    is_multiple: field.property_options[condition.data_key].multiple,
                                    option_key: field.property_options[condition.data_key].option_key
                                }" v-model="condition.data_value"></option-selector>
                        </template>
                    </div>
                </td>
                <td style="text-align: right;">
                    <el-button :disabled="model.length == 1" @click="deleteProp(conditionIndex)" size="mini" type="danger" icon="el-icon-delete"></el-button>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="text-align-right">
            <el-button @click="addProperty()" type="success" size="mini" icon="el-icon-plus">
                {{ $t('Add More') }}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import OptionSelector from './_OptionSelector';

export default {
    name: 'ConditionGroup',
    props: ['field', 'value'],
    components: {
        OptionSelector
    },
    data() {
        return {
            model: this.value
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    methods: {
        addProperty() {
            this.model.push({
                data_key: '',
                data_value: ''
            });
        },
        deleteProp(index) {
            this.model.splice(index, 1);
        }
    }
}
</script>
