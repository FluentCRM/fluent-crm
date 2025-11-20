<template>
    <div class="fc_days_ago_operator_field">
        <label>{{field.label}}</label>
        <div class="fc_inline_items">
            <el-select :placeholder="$t('Operator')" v-model="model.operator">
                <el-option
                    v-for="(name, itemValue) in field.options"
                    :key="itemValue"
                    :label="name"
                    :value="itemValue"
                ></el-option>
            </el-select>
            <el-input-number v-model="model.value"></el-input-number> {{$t('Days')}}
        </div>
        <p style="margin: 0" v-html="field.inline_help"></p>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'daysAgoInputField',
        props: ['field', 'value'],
        data() {
            return {
                model: this.value
            }
        },
        watch: {
            model() {
                this.$emit('input', this.model);
            }
        }
    }
</script>
