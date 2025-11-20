<template>
    <el-form label-position="top" :data="item" class="fcrm-update-custom-fields-form">
        <el-form-item :label="$t('Field Type')">
            <el-select @change="changeFieldType()" :placeholder="$t('Select Field Type')" v-model="item.field_key">
                <el-option
                    v-for="(fieldType, fieldKey) in field_types"
                    :key="fieldKey"
                    :label="fieldType.label"
                    :value="fieldKey"
                ></el-option>
            </el-select>

            <template v-if="item.type">
                <el-form-item :label="$t('Label')">
                    <el-input @keyup.native="maybeSetSlug()" :placeholder="$t('Custom Field Label')" v-model="item.label"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Slug (Optional)')">
                    <el-input maxlength="25" :placeholder="$t('Custom Field Slug')" :disabled="form_type == 'update'" v-model="item.slug"></el-input>
                    <p v-if="form_type == 'new'">{{$t('CustomFieldForm.slug.desc')}}</p>
                </el-form-item>
                <el-form-item v-if="hasOptions(item.type)" :label="$t('Field Value Options')">
                    <ul class="fluentcrm_option_lists">
                        <li v-for="(optionName, optionIndex) in item.options" :key="optionIndex">
                            <el-button-group>
                                <el-button @click="moveOptionsPosition(optionIndex, 'up')" :disabled="optionIndex == 0" size="mini" icon="el-icon-arrow-up"></el-button>
                                <el-button @click="moveOptionsPosition(optionIndex, 'down')" :disabled="optionIndex == (item.options.length - 1)" size="mini" icon="el-icon-arrow-down"></el-button>
                            </el-button-group>
                            <el-tooltip
                                v-if="editingOptionIndex !== optionIndex"
                                class="item" effect="dark" :content="$t('Click to edit')"
                                placement="top"
                                :enterable="false">
                                <span
                                    @click="startEditing(optionIndex)"
                                    class="option-text">
                                      {{optionName}}
                                </span>
                            </el-tooltip>
                            <input
                                v-else
                                type="text"
                                v-model="item.options[optionIndex]"
                                @blur="stopEditing"
                                ref="optionInput"
                                class="option-edit-input" />
                            <i @click="removeOptionItem(optionIndex)" class="fluentcrm_clickable el-icon-close"></i>
                        </li>
                    </ul>
                    <div class="fcrm-add-new-option-input-wrap">
                        <el-input v-model="optionInputValue" @keyup.enter.native="handleOptionInputConfirm" :placeholder="$t('Type new option')">
                            <template slot="append">
                                <el-button @click="handleOptionInputConfirm">
                                    {{ $t('Add') }}
                                </el-button>
                            </template>
                        </el-input>
                    </div>
                </el-form-item>

                <el-form-item label="Field Group (Optional)">
                    <el-select no-data-text="Type new group name to create" clearable filterable allow-create :placeholder="$t('Select or Create New Field Group')" v-model="item.group">
                        <el-option
                            v-for="group in groupOptions"
                            :key="group"
                            :label="group"
                            :value="group"
                        ></el-option>
                    </el-select>
                </el-form-item>

            </template>
        </el-form-item>
    </el-form>
</template>

<script type="text/babel">
    export default {
        name: 'FieldForm',
        props: ['field_types', 'item', 'form_type', 'existing_fields'],
        data() {
            return {
                optionInputVisible: false,
                optionInputValue: '',
                editingOptionIndex: ''
            }
        },
        computed: {
            groupOptions() {
                const groups = {};
                this.each(this.existing_fields, (field) => {
                    if (field.group) {
                        groups[field.group] = field.group;
                    }
                });
                return groups;
            }
        },
        methods: {
            maybeSetSlug() {
                if (this.form_type != 'new') {
                    return false;
                }
                const slug = this.item.label.toLowerCase().replace(/đ/gi, 'd').replace(/\s*$/g, '').replace(/\s+/g, '_').substring(0, 25);
                this.$set(this.item, 'slug', slug);
            },
            changeFieldType() {
                const selectedType = this.item.field_key;
                const field = this.field_types[selectedType];

                this.$set(this.item, 'type', field.type);
                this.$set(this.item, 'label', '');

                if (this.hasOptions(field.type)) {
                    if (!this.item.options) {
                        this.$set(this.item, 'options', [
                            'Value Option 1'
                        ]);
                    }
                } else {
                    this.$delete(this.item, 'options')
                }
            },
            hasOptions(type) {
                const optionTypeFields = ['select-one', 'select-multi', 'radio', 'checkbox'];
                return optionTypeFields.indexOf(type) !== -1
            },
            handleOptionInputConfirm() {
                const inputValue = this.optionInputValue;
                if (inputValue) {
                    this.item.options.push(inputValue);
                }
                this.optionInputVisible = false;
                this.optionInputValue = '';
            },
            removeOptionItem(fieldIndex) {
                this.item.options.splice(fieldIndex, 1);
            },
            moveOptionsPosition(fromIndex, type) {
                let toIndex = fromIndex - 1;
                if (type === 'down') {
                    toIndex = fromIndex + 1;
                }

                // Make sure the target index is valid
                if (toIndex < 0 || toIndex >= this.item.options.length) {
                    return;
                }

                // Remove the element from its current position
                const element = this.item.options.splice(fromIndex, 1)[0];

                // Insert the element at the new position
                this.item.options.splice(toIndex, 0, element);
            },
            startEditing(index) {
                this.editingOptionIndex = index;
                // Focus the input field after the DOM updates
                this.$nextTick(() => {
                    if (this.$refs.optionInput) {
                        // If multiple inputs exist, this will be an array
                        if (Array.isArray(this.$refs.optionInput)) {
                            this.$refs.optionInput[0].focus();
                        } else {
                            this.$refs.optionInput.focus();
                        }
                    }
                });
            },
            stopEditing() {
                this.editingOptionIndex = null;
            }
        },
        mounted() {

        }
    }
</script>
