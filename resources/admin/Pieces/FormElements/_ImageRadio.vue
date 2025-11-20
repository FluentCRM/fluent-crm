<template>
    <el-radio-group class="fc_image_radios" v-model="model">
        <el-radio v-for="(item,itemIndex) in field.options" :key="itemIndex" :label="item.id">
            <div :style="{backgroundImage: 'url('+item.image+')', width: boxSize + 'px', height: boxSize + 'px'}" :class="(model == item.id) ? 'fc_image_active' : ''" class="fc_image_box">
                <span>{{ item.label }}</span>
            </div>
        </el-radio>
    </el-radio-group>
</template>

<script type="text/babel">
export default {
    name: 'InputRadioImage',
    props: ['field', 'value', 'size'],
    data() {
        return {
            model: this.value,
            boxSize: this.size || 120
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    }
}
</script>
