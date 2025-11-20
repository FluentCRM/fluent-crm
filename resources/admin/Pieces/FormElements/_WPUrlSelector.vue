<template>
    <el-input type="url" :placeholder="field.placeholder" v-model="model"></el-input>
</template>

<script type="text/babel">
export default {
    name: 'WPUrlSelector',
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
