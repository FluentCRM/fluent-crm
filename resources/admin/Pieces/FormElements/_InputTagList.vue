<template>
    <div class="fc_tag_list_wrapper" :class="field.wrapper_class">
        <el-form-item :label="field.tag_label">
            <option-selector v-model="model.tags" :field="{ is_multiple: true, creatable: true, option_key: 'tags' }" />
            <p v-if="field.tag_help" class="fc_inline_help" v-html="field.tag_help"></p>
        </el-form-item>
        <el-form-item :label="field.list_label">
            <option-selector v-model="model.lists" :field="{ is_multiple: true, creatable: true, option_key: 'lists' }" />
            <p v-if="field.list_help" class="fc_inline_help" v-html="field.list_help"></p>
        </el-form-item>
    </div>
</template>

<script type="text/babel">
import OptionSelector from './_OptionSelector';

export default {
    name: 'InputText',
    props: ['field', 'value'],
    components: {
        OptionSelector
    },
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
