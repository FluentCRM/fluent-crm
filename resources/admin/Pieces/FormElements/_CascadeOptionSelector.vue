<template>
    <el-select :remote-method="remoteMethod" filterable remote reserve-keyword v-loading="loading" value-key="value" v-model="model" size="small"
               :multiple="field.is_multiple" placeholder="Select">
        <el-option-group
            v-for="group in options"
            :key="group.label"
            :label="group.label">
            <el-option
                v-for="item in group.children"
                :key="item.value"
                :label="item.label"
                :value="item.value"
            >
            </el-option>
        </el-option-group>
    </el-select>
</template>

<script type="text/babel">
import isArray from 'lodash/isArray';

export default {
    name: 'CascadeOptionSelector',
    props: ['field', 'value'],
    data() {
        return {
            model: this.value,
            loading: false,
            options: [],
            appReady: true
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    methods: {
        fetchOptions(query) {
            this.loading = true;
            this.$get('reports/cascade_selections', {
                search: query,
                values: this.model,
                provider: this.field.provider,
                is_multiple: !!this.field.is_multiple
            })
                .then(response => {
                    this.options = response.options;
                })
                .catch((errors) => {
                    this.handleError(errors)
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        remoteMethod(query) {
            if (this.loading) {
                return false;
            }
            this.fetchOptions(query);
        }
    },
    mounted() {
        this.fetchOptions('');
        if (!this.value || !isArray(this.value)) {
            this.model = [];
        }
    }
}
</script>
