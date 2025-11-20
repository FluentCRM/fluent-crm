<template>
    <div class="dc_radio_section">
        <div class="fc_section_heading">
            <h3>{{field.heading}}</h3>
            <p v-html="field.label"></p>
        </div>
        <el-radio-group v-model="model">
            <el-radio
                v-for="(option, optionKey) in field.options"
                :key="optionKey"
                :label="optionKey"
            >{{option}}</el-radio>
        </el-radio-group>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'RadioSectionField',
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
