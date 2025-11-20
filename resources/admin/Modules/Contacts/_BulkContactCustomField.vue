<template>
    <div class="fc-bulk-contact-custom-fields">
        <el-select filterable
                   size="mini"
                   class="mt-5"
                   :placeholder="$t('Select Field')"
                   v-model="customFieldType">
            <el-option v-for="(field, index) in customFieldsList" :key="index" :value="field.slug"
                       :label="field.label"></el-option>
        </el-select>

        <!-- Render the Field Based on Selected Type -->
        <template v-if="selectedField">
            <template v-if="selectedField.type === 'select-one' || selectedField.type == 'select-multi'">
                <el-select
                    :placeholder="$t('Select') + ' ' + selectedField.label"
                    clearable
                    filterable
                    v-model="formattedCustomFields[selectedField.slug]"
                    :multiple="selectedField.type == 'select-multi'"
                    size="mini"
                    class="ml-5 mt-5"
                >
                    <el-option
                        v-for="option in selectedField.options"
                        :key="option"
                        :value="option"
                        :label="option"
                    ></el-option>
                </el-select>
            </template>

            <template v-else-if="selectedField.type == 'text' || selectedField.type == 'number' || selectedField.type == 'textarea'">
                <el-input :placeholder="selectedField.label"
                          size="mini"
                          class="ml-5 mt-5"
                          :type="selectedField.type"
                          v-model="formattedCustomFields[selectedField.slug]"></el-input>
            </template>

            <template v-else-if="selectedField.type == 'radio'">
                <el-radio-group v-model="formattedCustomFields[selectedField.slug]"
                                size="mini"
                                class="ml-5 mt-5">
                    <el-radio v-for="option in selectedField.options" :key="option" :value="option"
                              :label="option"></el-radio>
                </el-radio-group>
            </template>

            <template v-else-if="selectedField.type == 'checkbox'">
                <el-checkbox-group v-model="formattedCustomFields[selectedField.slug]" class="fc_checkbox_group ml-5 mr-5" size="mini">
                    <el-checkbox v-for="option in selectedField.options" :key="option" :value="option"
                                 :label="option" class="fc_checkbox"></el-checkbox>
                </el-checkbox-group>
            </template>
            <template v-else-if="selectedField.type == 'date'">
                <el-date-picker
                    value-format="yyyy-MM-dd"
                    v-model="formattedCustomFields[selectedField.slug]"
                    type="date"
                    size="mini"
                    class="ml-5 mt-5"
                    :placeholder="$t('Pick a date')">
                </el-date-picker>
            </template>
            <template v-else-if="selectedField.type == 'date_time'">
                <el-date-picker
                    value-format="yyyy-MM-dd HH:mm:ss"
                    v-model="formattedCustomFields[selectedField.slug]"
                    type="datetime"
                    size="mini"
                    class="ml-5 mt-5"
                    :placeholder="$t('Pick a date and time')">
                </el-date-picker>
            </template>
            <template v-else>
                {{ selectedField }}
            </template>
        </template>

        <el-button
            v-if="customFieldType"
            v-loading="updating"
            :disabled="updating"
            size="mini"
            type="success" class="ml-5 mt-5"
            @click="handleCustomFieldBulk"
        >
            {{ $t('Update Field') }}
        </el-button>
    </div>
</template>

<script>

export default {
    name: 'BulkContactCustomField',
    props: ['options'],
    data() {
        return {
            customFieldType: '',
            formattedCustomFields: {},
            updating: false
        }
    },
    methods: {
        formatFields() {
            this.customFieldsList.forEach((field) => {
                if (field.type === 'select-multi' || field.type == 'checkbox') {
                    this.$set(this.formattedCustomFields, field.slug, []);
                } else {
                    this.$set(this.formattedCustomFields, field.slug, '');
                }
            });
        },
        handleCustomFieldBulk() {
            const currentField = this.selectedField;
            const currentFieldValue = this.formattedCustomFields[currentField.slug];

            this.updating = true;
            this.$emit('updateCustomField', {
                key: currentField.slug,
                type: currentField.type,
                value: currentFieldValue
            });
        }
    },
    computed: {
        customFieldsList() {
            return this.options.custom_fields || [];
        },
        selectedField() {
            return this.customFieldsList.find(
                (field) => field.slug === this.customFieldType
            );
        }
    },
    mounted() {
        this.formatFields();
    }
}

</script>
