<template>
    <el-select
        v-model="model"
        :multiple="field.is_multiple"
        filterable
        :remote="!field.cacheable"
        :clearable="field.clearable"
        :disabled="field.disabled"
        reserve-keyword
        :size="field.size"
        :placeholder="field.placeholder || $t('Please enter a keyword')"
        :remote-method="fetchOptions"
        v-loading="loading">
        <el-option
            v-for="item in results"
            :key="item.id"
            :label="item.name"
            :value="item.id">
        </el-option>
    </el-select>
</template>

<script type="text/babel">
export default {
    name: 'CompanySelector',
    props: ['value', 'field'],
    data() {
        return {
            model: this.value,
            results: [],
            loading: false,
            doing_ajax: false
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    methods: {
        fetchOptions(query) {
            if (!this.hasPermission('fcrm_manage_contact_cats')) {
                return;
            }
            if (this.doing_ajax) {
                return false;
            }

            if (window.fc_all_company_cache) {
                this.results = window.fc_all_company_cache;
                return;
            }

            this.loading = true;
            this.doing_ajax = true;

            this.$get('companies/search', {
                search: query,
                values: this.model
            })
                .then(response => {
                    this.results = response.results;

                    if (!response.has_more) {
                        window.fc_all_company_cache = response.results;
                        this.field.cacheable = true;
                    }
                })
                .catch((errors) => {
                    this.handleError(errors)
                })
                .finally(() => {
                    this.loading = false;
                    this.doing_ajax = false;
                });
        }
    },
    mounted() {
        this.fetchOptions('');
    }
}
</script>
