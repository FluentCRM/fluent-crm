<template>
    <el-date-picker
        :value-format="field.value_format"
        v-model="model"
        :placeholder="field.placeholder"
        :type="field.data_type"></el-date-picker>
</template>

<script type="text/babel">
export default {
    name: 'InputDate',
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
