<template>
    <el-checkbox :true-label="field.true_label" :disabled="field.disabled" :false-label="field.false_label" v-model="model">{{field.checkbox_label}}</el-checkbox>
</template>

<script type="text/babel">
export default {
    name: 'InlineCheckbox',
    props: ['field', 'value'],
    data() {
        return {
            model: this.value
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    }
}
</script>
