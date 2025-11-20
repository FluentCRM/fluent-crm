<template>
    <input-popover doc_url="https://fluentcrm.com/docs/merge-codes-smart-codes-usage/" :field-type="field.field_type" :placeholder="field.placeholder" :popper_class="field.popper_class" :data="smartcodes" v-model="model"/>
</template>

<script type="text/babel">
import InputPopover from '@/Pieces/InputPopover';
export default {
    name: 'InputTextPopper',
    props: ['field', 'value'],
    components: {
        InputPopover
    },
    data() {
        return {
            model: this.value,
            smartcodes: window.fcAdmin.globalSmartCodes
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    created() {
        if (this.field.context_codes && window.fcrm_funnel_context_codes) {
            this.smartcodes = [...this.smartcodes, ...window.fcrm_funnel_context_codes];
        }

        if (window.fcAdmin.extendedSmartCodes) {
            this.smartcodes = [...this.smartcodes, ...window.fcAdmin.extendedSmartCodes];
        }
    }
}
</script>
