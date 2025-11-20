<template>
    <div>
        <table class="fc_table fc_horizontal_table">
            <thead>
            <tr>
                <th>{{ driver | ucFirst }} {{$t('Field')}}</th>
                <th>{{$t('FluentCRM Field')}}</th>
                <th>{{$t('Skip')}}</th>
            </tr>
            </thead>
            <tbody>
                <tr v-for="(field, fieldIndex) in contact_fields" :key="fieldIndex">
                    <td>
                        {{field.remote_label}}
                    </td>
                    <td>
                        <span v-if="field.will_skip == 'yes'">
                            {{$t('this value will be skipped')}}
                        </span>
                        <el-select v-else v-model="field.fluentcrm_field" filterable clearable>
                            <template v-if="field.options">
                                <el-option
                                    v-for="(fillableName, fillableValue) in field.options"
                                    :key="fillableValue"
                                    :value="fillableValue"
                                    :label="fillableName"></el-option>
                            </template>
                            <template v-else>
                                <el-option
                                    v-for="(fillableName, fillableValue) in contact_fillables"
                                    :key="fillableValue"
                                    :value="fillableValue"
                                    :label="fillableName"></el-option>
                            </template>
                        </el-select>
                    </td>
                    <td>
                        <el-switch active-value="yes" inactive-value="no" v-model="field.will_skip" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ContactFieldMapper',
    props: ['contact_fields', 'contact_fillables', 'driver'],
    data() {
        return {

        }
    }
}
</script>
