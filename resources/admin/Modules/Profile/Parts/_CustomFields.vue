<template>
    <div class="fc_custom_field_wrapper">
        <div v-if="custom_fields.length" class="fluentcrm_custom_fields">
            <h3>
                {{ $t('Custom Profile Data') }}
                <el-button v-if="hasPermission('fcrm_manage_settings')" type="default" plain size="mini" icon="el-icon-s-operation"
                           @click="showingCustomFieldsConfig = true"></el-button>
            </h3>
            <el-form v-if="app_ready" :data="subscriber.custom_values" label-position="top">
                <div v-for="(fields, groupName) in fieldGropups" :key="groupName" class="fc_custom_field_box" :class="groupName ? 'fc_custom_field_group_box' : ''">
                    <div v-if="groupName && editingGroupName !== groupName" class="fc_item_label" @click="clickGroupNameToEdit(groupName)">{{ groupName }}</div>
                    <div v-if="groupName && editingGroupName === groupName" class="fc_item_label">
                        <el-input
                            ref="groupNameInput"
                            type="text"
                            v-model="UpdatedGroupName"
                            @blur="handleGroupNameUpdate"
                            @keyup.enter.native="handleGroupNameUpdate"
                        />
                    </div>
                    <div class="fluentcrm_layout">
                        <el-form-item class="fluentcrm_layout_half" v-for="(field, fieldKey) in fields" :key="fieldKey"
                                      :label="field.label">
                            <el-input :placeholder="field.label"
                                      v-if="field.type == 'text' || field.type == 'number' || field.type == 'textarea'"
                                      :type="field.type"
                                      v-model="subscriber.custom_values[fieldKey]"></el-input>
                            <template v-else-if="field.type == 'radio'">
                                <el-radio-group v-model="subscriber.custom_values[fieldKey]">
                                    <el-radio v-for="option in field.options" :key="option" :value="option"
                                              :label="option"></el-radio>
                                </el-radio-group>
                            </template>
                            <template v-else-if="field.type == 'select-one' || field.type == 'select-multi'">
                                <el-select :placeholder="$t('Select')+' '+field.label" clearable filterable
                                           :multiple="field.type == 'select-multi'"
                                           v-model="subscriber.custom_values[fieldKey]">
                                    <el-option v-for="option in field.options" :key="option" :value="option"
                                               :label="option"></el-option>
                                </el-select>
                            </template>
                            <template v-else-if="field.type == 'checkbox'">
                                <el-checkbox-group v-model="subscriber.custom_values[fieldKey]" class="fc_checkbox_group">
                                    <el-checkbox v-for="option in field.options" :key="option" :value="option"
                                                 :label="option" class="fc_checkbox"></el-checkbox>
                                </el-checkbox-group>
                            </template>
                            <template v-else-if="field.type == 'date'">
                                <el-date-picker
                                    value-format="yyyy-MM-dd"
                                    v-model="subscriber.custom_values[fieldKey]"
                                    type="date"
                                    :placeholder="$t('Pick a date')">
                                </el-date-picker>
                            </template>
                            <template v-else-if="field.type == 'date_time'">
                                <el-date-picker
                                    value-format="yyyy-MM-dd HH:mm:ss"
                                    v-model="subscriber.custom_values[fieldKey]"
                                    type="datetime"
                                    :placeholder="$t('Pick a date and time')">
                                </el-date-picker>
                            </template>
                            <template v-else>
                                {{ field }}
                            </template>
                        </el-form-item>
                    </div>
                </div>
            </el-form>
        </div>
        <div class="fc_no_custom_fields_btn" v-else>
            <el-button plain size="mini" icon="el-icon-s-operation" @click="showingCustomFieldsConfig = true">
                {{ $t('Custom Fields') }}
            </el-button>
        </div>
        <el-drawer :append-to-body="true" :title="$t('Custom Fields Config')" :visible.sync="showingCustomFieldsConfig"
                   size='50%'>
            <custom-fields :is_pop="true" @fieldsUpdated="(fields) => { $bus.$emit('contact_custom_fields_updated', fields)}"
                           v-if="showingCustomFieldsConfig"></custom-fields>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import has from 'lodash/has';
import CustomFields from '@/Modules/Settings/parts/CustomContactFields.vue';

export default {
    name: 'ProfileCustomFields',
    props: ['subscriber', 'custom_fields'],
    components: {
        CustomFields
    },
    data() {
        return {
            app_ready: false,
            showingCustomFieldsConfig: false,
            UpdatedGroupName: '',
            editingGroupName: null
        }
    },
    computed: {
        fieldGropups() {
            const formattedFields = {};
            this.each(this.custom_fields, (field) => {
                if (has(this.subscriber.custom_values, field.slug)) {
                    field.is_disabled = true;
                }

                if (!field.group) {
                    field.group = '';
                }

                if (!formattedFields[field.group]) {
                    formattedFields[field.group] = {};
                }

                formattedFields[field.group][field.slug] = field;
            });
            return formattedFields;
        }
    },
    methods: {
        clickGroupNameToEdit(groupName) {
            this.UpdatedGroupName = groupName;
            this.editingGroupName = groupName;
            this.$nextTick(() => {
                this.$refs.groupNameInput[0].focus();
            });
        },
        handleGroupNameUpdate() {
            const oldGroupName = this.editingGroupName;
            const newGroupName = this.UpdatedGroupName.trim();

            if (!newGroupName || newGroupName === oldGroupName) {
                this.UpdatedGroupName = '';
                this.editingGroupName = null;
                return;
            }

            this.$put('custom-fields/contacts/update_group_name', {
                old_name: oldGroupName,
                new_name: newGroupName
            })
                .then((response) => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                    this.custom_fields.forEach(field => {
                        if (field.group === oldGroupName) {
                            field.group = newGroupName;
                        }
                    });
                })
                .catch((errors) => {
                    this.errors = errors;
                    this.handleError(errors);
                })
                .finally(() => {
                    this.UpdatedGroupName = '';
                    this.editingGroupName = null;
                });
        }
    },
    mounted() {
        this.each(this.custom_fields, (field) => {
            let defaultValue = '';
            if (['select-multi', 'checkbox'].indexOf(field.type) !== -1) {
                defaultValue = [];
            }
            if (!has(this.subscriber.custom_values, field.slug)) {
                this.$set(this.subscriber.custom_values, field.slug, defaultValue);
            }
        });
        this.app_ready = true;
    }
}
</script>

<style lang="scss">
.fluentcrm_custom_fields {
    > h3 {
        cursor: pointer;
    }
}

.fc_item_label {
    margin-bottom: 15px;
    font-size: 110%;
    font-weight: bold;
    color: #606266;
    cursor: pointer;
}

.fc_custom_field_group_box {

}

</style>
