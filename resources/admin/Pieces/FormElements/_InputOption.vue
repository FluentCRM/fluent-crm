<template>
    <el-select clearable filterable :placeholder="field.placeholder" :multiple="field.multiple" :class="field.wrapper_class" v-model="model">
        <el-option v-for="item in field.options" :key="item.id" :value="item.id" :label="item.label">
        </el-option>
    </el-select>
</template>

<script type="text/babel">
export default {
    name: 'InputOption',
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
