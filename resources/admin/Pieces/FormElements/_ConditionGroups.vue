<template>
    <div class="fc_condition_groups">
        <div class="fc_condition_wrapper" v-for="(group,groupIndex) in model" :key="groupIndex">
            <div class="fc_cond_and" v-if="groupIndex != 0">OR</div>
            <div class="fc_condition_group">
                <table class="wp-list-table widefat fixed striped table-view-list posts">
                    <thead>
                    <tr>
                        <th style="width: 180px;">{{ field.labels.data_key_label }}</th>
                        <th style="width: 180px;">{{ field.labels.condition_label }}</th>
                        <th>{{ field.labels.data_value_label }}</th>
                        <th style="width: 90px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(condition, conditionIndex) in group.conditions" :key="conditionIndex">
                        <td>
                            <el-select @change="condition.operator = '='; condition.data_value = ''" clearable
                                       :placeholder="$t('Select')" size="small" v-model="condition.data_key">
                                <el-option-group
                                    v-for="(group, groupKey) in field.condition_properties"
                                    :key="groupKey"
                                    :label="group.label">
                                    <el-option
                                        v-for="(prop, propkey) in group.options"
                                        :key="propkey"
                                        :value="propkey"
                                        :label="prop.label"
                                    ></el-option>
                                </el-option-group>
                            </el-select>
                        </td>
                        <td>
                            <el-select v-if="condition.data_key" clearable :placeholder="$t('Select Condition')"
                                       size="small" v-model="condition.operator">
                                <template v-if="flat_properties[condition.data_key].multiple">
                                    <el-option value="=" :label="$t('Match any Of')"></el-option>
                                    <el-option value="match_all" :label="$t('Match all of')"></el-option>
                                    <el-option value="match_none_of" :label="$t('Match none of')"></el-option>
                                </template>
                                <template v-else>
                                    <el-option value="=" :label="$t('Equal')"></el-option>
                                    <el-option value="!=" :label="$t('Not Equal')"></el-option>
                                    <template v-if="flat_properties[condition.data_key].type == 'text'">
                                        <el-option value="contains" :label="$t('Contains')"></el-option>
                                        <el-option value="doNotContains" :label="$t('Not Contains')"></el-option>
                                        <el-option value="startsWith" :label="$t('Starts With')"></el-option>
                                        <el-option value="endsWith" :label="$t('Ends With')"></el-option>
                                    </template>
                                    <template v-else-if="flat_properties[condition.data_key].type == 'number'">
                                        <el-option value=">" :label="$t('Greater Than')"></el-option>
                                        <el-option value="<" :label="$t('Less Than')"></el-option>
                                    </template>
                                </template>
                            </el-select>
                        </td>
                        <td>
                            <div v-if="condition.data_key && condition.operator">
                                <el-input
                                    v-if="flat_properties[condition.data_key].type == 'text'"
                                    size="small"
                                    type="text"
                                    v-model="condition.data_value"/>
                                <el-input
                                    v-else-if="flat_properties[condition.data_key].type == 'number'"
                                    size="small"
                                    type="number"
                                    v-model="condition.data_value"/>
                                <el-select
                                    v-else-if="flat_properties[condition.data_key].type == 'select'"
                                    size="small"
                                    v-model="condition.data_value"
                                    clearable
                                >
                                    <el-option
                                        v-for="option in flat_properties[condition.data_key].options"
                                        :key="option.id"
                                        :label="option.title"
                                        :value="option.id"
                                    ></el-option>
                                </el-select>
                                <template v-else-if="flat_properties[condition.data_key].type == 'option_selector'">
                                    <option-selector :field="{
                                    placeholder: 'Select',
                                    is_multiple: flat_properties[condition.data_key].multiple,
                                    option_key: flat_properties[condition.data_key].option_key
                                }" v-model="condition.data_value"></option-selector>
                                </template>
                                <template v-else-if="flat_properties[condition.data_key].type == 'rest_selector'">
                                    <ajax-selector :field="{
                                    placeholder: 'Select',
                                    is_multiple: flat_properties[condition.data_key].multiple,
                                    option_key: flat_properties[condition.data_key].option_key
                                }" v-model="condition.data_value"></ajax-selector>
                                </template>
                            </div>
                            <div v-else>{{$t('Select data source and operator first')}}</div>
                        </td>
                        <td style="text-align: right;">
                            <el-button @click="addCondition(groupIndex)" type="success" size="mini" icon="el-icon-plus"></el-button>
                            <el-button :disabled="group.conditions.length == 1"
                                       @click="deleteProp(groupIndex, conditionIndex)" size="mini" type="danger"
                                       icon="el-icon-delete"></el-button>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div v-if="!field.hide_match_type">
                    <p><b>{{$t('Match Type')}}</b></p>
                    <el-radio-group v-model="group.match_type">
                        <el-radio label="match_all">{{ field.labels.match_type_all_label }}</el-radio>
                        <el-radio label="match_any">{{ field.labels.match_type_any_label }}</el-radio>
                    </el-radio-group>
                </div>
                <p style="margin: 0; padding: 0" v-else>Inside group conditions are <b>match all</b></p>
                <el-button @click="removeGroup(groupIndex)" v-if="model.length > 1" type="danger" icon="el-icon-delete" size="mini">
                    {{$t('Delete this group')}}
                </el-button>
            </div>
        </div>
        <div class="text-align-right" v-if="field.is_multiple_grouping">
            <el-button @click="addConditionalGroup()" type="primary" size="mini" icon="el-icon-plus">
                {{$t('Add Another Conditional Group')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import OptionSelector from './_OptionSelector';
import AjaxSelector from './_AjaxSelector';

export default {
    name: 'ConditionGroup',
    props: ['field', 'value'],
    components: {
        OptionSelector,
        AjaxSelector
    },
    data() {
        return {
            model: this.value
        }
    },
    computed: {
        flat_properties() {
            let allOptions = {};
            this.each(this.field.condition_properties, (conditonGroup) => {
                allOptions = {
                    ...allOptions,
                    ...conditonGroup.options
                }
            });
            return allOptions;
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    methods: {
        addCondition(groupIndex) {
            this.model[groupIndex].conditions.push({
                data_key: '',
                operator: '=',
                data_value: ''
            });
        },
        deleteProp(groupIndex, index) {
            this.model[groupIndex].conditions.splice(index, 1);
        },
        removeGroup(groupIndex) {
            this.model.splice(groupIndex, 1);
        },
        addConditionalGroup() {
            this.model.push({
                conditions: [
                    {
                        data_key: '',
                        operator: '=',
                        data_value: ''
                    }
                ],
                match_type: 'match_all'
            });
        }
    }
}
</script>

<style lang="scss">
.fc_condition_group {
    background: #efefef;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 20px;

    h4 {
        margin: 0;
    }
}
</style>
