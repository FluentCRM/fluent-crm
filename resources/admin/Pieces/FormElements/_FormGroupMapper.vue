<template>
    <table class="fc_horizontal_table">
        <thead>
        <tr>
            <th>{{field.local_label}}</th>
            <th>{{field.remote_label}}</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(fieldItem, fieldKey) in field.fields" :key="fieldKey">
            <td>{{fieldItem.label}}</td>
            <td>
                <el-select clearable filterable v-model="model[fieldKey]" :placeholder="$t('Select Value')">
                    <el-option
                        v-for="option in field.value_options"
                        :key="option.id"
                        :value="option.id"
                        :label="option.title"></el-option>
                </el-select>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<script type="text/babel">
export default {
    name: 'FormFieldsGroupMapper',
    props: ['field', 'model']
}
</script>

<!--
Used IN:
/admin/Modules/Funnels/_Field.vue

-->
