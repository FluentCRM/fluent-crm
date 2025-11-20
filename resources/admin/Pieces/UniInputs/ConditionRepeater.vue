<template>
    <div class="fc_condition_repeater">
        <table class="fc_horizontal_table">
            <tbody>
            <tr v-for="(item,itemIndex) in model" :key="'repeat_item'+itemIndex">
                <td>
                    <div class="fc_inline_items fc_no_pad_child">
                        <el-select style="min-width: 93%" @change="resetItem(item)" v-model="item.field">
                            <el-option
                                v-for="(field,fieldKey) in fields"
                                :key="fieldKey"
                                :label="field.label"
                                :value="fieldKey"
                            >
                            </el-option>
                        </el-select>
                        <el-tooltip v-if="fields[item.field] && fields[item.field].description" class="item"
                                    effect="dark" :content="fields[item.field].description" placement="top">
                            <span class="el-icon el-icon-info"></span>
                        </el-tooltip>
                    </div>
                </td>
                <td>
                    <template v-if="item.field">
                        <el-select :placeholder="$t('condition')" v-model="item.operator">
                            <el-option
                                v-for="(label,itemValue) in fields[item.field].operators"
                                :key="itemValue"
                                :label="label"
                                :value="itemValue"
                            >
                            </el-option>
                        </el-select>
                    </template>
                    <template v-else>
                        <el-input :placeholder="$t('condition')" :disabled="true"/>
                    </template>
                </td>
                <td>
                    <template v-if="item.field">
                        <template v-if="fields[item.field].type == 'text'">
                            <el-input :placeholder="$t('Value')" v-model="item.value"/>
                        </template>
                        <template v-if="fields[item.field].type == 'select'">
                            <el-select :multiple="fields[item.field].is_multiple" :placeholder="$t('Choose Values')"
                                       v-model="item.value">
                                <el-option
                                    v-for="(label,itemValue) in fields[item.field].options"
                                    :key="itemValue"
                                    :label="label"
                                    :value="itemValue"
                                >
                                </el-option>
                            </el-select>
                        </template>
                        <template v-else-if="fields[item.field].type == 'days_ago'">
                            <el-input-number v-model="item.value"></el-input-number>
                            {{$t('Days Ago')}}
                        </template>
                        <template v-else-if="fields[item.field].type == 'option-selector'">
                            <option-selector :field="fields[item.field]" v-model="item.value"></option-selector>
                        </template>
                    </template>
                    <template v-else>
                        <el-input placeholder="value" :disabled="true"/>
                    </template>
                </td>
                <td>
                    <el-button @click="removeItem(itemIndex)" :disabled="model.length == 1" type="danger"
                               icon="el-icon-close" size="small"></el-button>
                </td>
            </tr>
            </tbody>
        </table>
        <div style="padding-bottom: 20px" class="text-align-right">
            <el-button icon="el-icon-plus" type="primary" @click="addRow()" size="small">{{$t('Add Condition')}}</el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import OptionSelector from '../FormElements/_OptionSelector';

export default {
    name: 'ConditionRepeaterField',
    props: ['value', 'fields'],
    components: {
        OptionSelector
    },
    data() {
        return {
            model: this.value
        }
    },
    watch: {
        model: {
            deep: true,
            handler() {
                this.$emit('input', this.model);
            }
        }
    },
    methods: {
        resetItem(item) {
            item.operator = '';
            if (!item.field) {
                item.value = '';
            } else {
                item.value = JSON.parse(JSON.stringify(this.fields[item.field].value))
            }
        },
        addRow() {
            this.model.push({
                field: '',
                operator: '',
                value: ''
            });
        },
        removeItem(itemIndex) {
            this.model.splice(itemIndex, 1);
        }
    }
}
</script>
