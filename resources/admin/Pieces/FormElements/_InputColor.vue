<template>
    <el-color-picker @active-change="(color) => { model = color; }"
                     :color-format="field.colorFormat" :show-alpha="field.showAlpha"
                     v-model="model"></el-color-picker>
</template>

<script type="text/babel">
export default {
    name: 'InputColor',
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
