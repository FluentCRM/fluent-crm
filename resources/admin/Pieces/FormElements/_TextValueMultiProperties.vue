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
                    <el-input
                        :placeholder="field.data_key_placeholder"
                        type="text"
                        v-model="condition.data_key"/>
                </td>
                <td>
                    <template v-if="field.value_input_type == 'text-popper'">
                        <input-text-popper :field="{ placeholder: field.data_value_placeholder, popper_class: 'fc_limit_height' }" v-model="condition.data_value" />
                    </template>
                    <el-input
                        v-else
                        type="text"
                        :placeholder="field.data_value_placeholder"
                        v-model="condition.data_value"/>
                </td>
                <td style="text-align: right;">
                    <el-button :disabled="model.length == 1" @click="deleteProp(conditionIndex)" size="mini"
                               type="danger" icon="el-icon-delete"></el-button>
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
import InputTextPopper from './_InputTextPopper';
export default {
    name: 'TextValueMultiProperties',
    props: ['field', 'value'],
    components: {
        InputTextPopper
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
