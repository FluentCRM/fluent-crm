<template>
    <table v-if="render_table" class="fc_horizontal_table">
        <thead>
        <tr>
            <th>{{field.local_label}}</th>
            <th>{{field.remote_label}}</th>
            <th width="40px"></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(item, itemIndex) in value" :key="itemIndex">
            <td>
                <ajax-selector v-model="item.field_key" v-if="field.field_ajax_selector" :field="{
                    placeholder: field.local_placeholder,
                    ...field.field_ajax_selector
                }"/>
                <option-selector v-else-if="field.field_option_selector" v-model="item.field_key" :field="{
                    placeholder: field.local_placeholder,
                    ...field.field_option_selector
                }">
                </option-selector>
                <el-select v-else clearable filterable v-model="item.field_key" :placeholder="field.local_placeholder">
                    <el-option
                        v-for="(option, optionKey) in field.fields"
                        :key="optionKey"
                        :value="optionKey"
                        :label="option.label"></el-option>
                </el-select>
            </td>
            <td>
                <option-selector v-if="field.value_option_selector" v-model="item.field_value" :field="{
                    placeholder: field.remote_placeholder,
                    ...field.value_option_selector
                }">
                </option-selector>
                <el-select v-else-if="field.value_options" clearable filterable v-model="item.field_value"
                           :placeholder="field.remote_placeholder">
                    <el-option
                        v-for="option in field.value_options"
                        :key="option.id"
                        :value="option.id"
                        :label="option.title"></el-option>
                </el-select>
                <input-text v-else-if="field.remote_field_type == 'input-text'" :field="field.remote_field" v-model="item.field_value" />
                <input-text-popper v-else-if="field.remote_field_type == 'input-text-popper'" :field="field.remote_field" v-model="item.field_value" />
            </td>
            <td>
                <div class="text-align-right">
                    <template v-if="field.manage_serial">
                        <el-button-group>
                            <el-button @click="movePosition(itemIndex, 'up')" :disabled="itemIndex == 0" size="mini" icon="el-icon-arrow-up"></el-button>
                            <el-button @click="movePosition(itemIndex, 'down')" :disabled="itemIndex == (value.length - 1)" size="mini" icon="el-icon-arrow-down"></el-button>
                        </el-button-group>
                    </template>
                    <el-button @click="deleteItem(itemIndex)" :disabled="value.length == 1" type="danger" size="small"
                               icon="el-icon-delete"></el-button>
                </div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
            </td>
            <td>
                <div class="text-align-right">
                    <el-button @click="addMore()" size="small" icon="el-icon-plus">
                        {{$t('Add More')}}
                    </el-button>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<script type="text/babel">
import OptionSelector from './_OptionSelector';
import AjaxSelector from './_AjaxSelector';
import InputText from './_InputText';
import InputTextPopper from './_InputTextPopper';

export default {
    name: 'FormManyDropdownMapper',
    props: ['field', 'value'],
    components: {
        OptionSelector,
        AjaxSelector,
        InputText,
        InputTextPopper
    },
    data() {
        return {
            render_table: true
        }
    },
    methods: {
        addMore() {
            this.value.push({
                field_key: '',
                field_value: ''
            })
        },
        deleteItem(index) {
            this.value.splice(index, 1);
        },
        movePosition(fromIndex, type) {
            let toIndex = fromIndex - 1;
            if (type === 'down') {
                toIndex = fromIndex + 1;
            }
            const fields = this.value;
            const element = fields[fromIndex];
            fields.splice(fromIndex, 1);
            fields.splice(toIndex, 0, element);
            this.$set(this, 'value', fields);
            this.render_table = false;
            this.$nextTick(() => {
                this.render_table = true;
            });
        }
    }
};
</script>

<!--
Used IN:
/admin/Modules/Funnels/_Field.vue

-->
