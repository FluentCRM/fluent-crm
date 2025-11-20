<template>
    <div class="fc_checkbox_group">
        <template v-if="field.has_all_selector">
            <el-checkbox
                v-model="checkAll"
                :indeterminate="isIndeterminate"
                @change="all">
                {{ field.all_selector_label }}
            </el-checkbox>
            <div style="margin: 15px 0;"></div>
        </template>
        <el-checkbox-group
            :class="field.input_class"
            v-model="model"
            @change="checked">
            <el-checkbox
                v-for="option in field.options"
                :label="option.id"
                :key="option.id">
                {{ option.label }}
            </el-checkbox>
        </el-checkbox-group>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'CheckboxGroup',
    props: ['field', 'value'],
    data() {
        return {
            model: this.value ?? [],
            isIndeterminate: false,
            checkAll: false
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    computed: {
        optionKeys() {
            if (!this.field.has_all_selector) {
                return [];
            }
            const options = [];
            this.each(this.field.options, (option) => {
                options.push(option.id);
            });
            return options;
        }
    },
    methods: {
        checked(value) {
            if (this.field.has_all_selector) {
                const checkedCount = this.optionKeys.length;
                this.checkAll = checkedCount === this.model.length;
                this.isIndeterminate = checkedCount > 0 && checkedCount < this.model.length;
            }
        },
        all(value) {
            if (this.checkAll) {
                this.model = this.optionKeys;
            } else {
                this.model = [];
            }
            this.isIndeterminate = false;
        }
    }
}
</script>
