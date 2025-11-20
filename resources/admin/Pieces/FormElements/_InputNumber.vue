<template>
    <el-input-number :min="field.min" :max="field.max" :step="field.step"
                     v-model="model"></el-input-number>
</template>

<script type="text/babel">
export default {
    name: 'InputNumber',
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
