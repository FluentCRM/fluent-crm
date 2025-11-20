<template>
    <el-radio-group class="fc_image_radio_tooltips" v-model="model">
        <el-radio v-for="(item,itemIndex) in field.options" :key="itemIndex" :label="item.id">
            <el-tooltip :content="tooltip_prefix + item.label" placement="top">
                <div :style="{backgroundImage: 'url('+item.image+')', width: width + 'px', height: height + 'px'}" :class="(model == item.id) ? 'fc_image_active' : ''" class="fc_image_box"></div>
            </el-tooltip>
        </el-radio>
    </el-radio-group>
</template>

<script type="text/babel">
export default {
    name: 'InputRadioImage',
    props: ['field', 'value', 'boxWidth', 'boxHeight', 'tooltip_prefix'],
    data() {
        return {
            model: this.value,
            width: this.boxWidth || 120,
            height: this.boxHeight || 120
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
            this.$emit('change', value);
        }
    }
}
</script>
