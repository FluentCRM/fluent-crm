<template>
    <el-form-item :class="field.wrapper_class">
        <div v-if="field.label" slot="label">
        {{ field.label }}
            <el-tooltip v-if="field.help" popper-class="sidebar-popper" effect="dark" placement="top">
                <div slot="content" v-html="field.help"></div>
                <i class="tooltip-icon el-icon-info"></i>
            </el-tooltip>
        </div>
        <slot></slot>
        <p v-if="field.inline_help" class="fc_inline_help" v-html="field.inline_help"></p>
    </el-form-item>
</template>

<script type="text/babel">
export default {
    name: 'withLabelField',
    props: ['field']
}
</script>
