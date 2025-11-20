<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{section_title}}</h3>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button @click="addFieldVisible = true" type="primary" size="small">{{$t('Add Field')}}</el-button>
            </div>
        </div>

        <div class="fluentcrm_pad_around" style="position: relative;">
            <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
            </div>
            <div class="fc-reset-custom-fields-sort" v-if="query_data.sort_by">
                <el-tag size="mini" closable @close="resetFilter">
                    {{ query_data.sort_by ? $t('Sorting By') + ' ' + $t(query_data.sort_by) + ': ' + $t(query_data.sort_order) : '' }}
                </el-tag>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table v-else :empty-text="$t('No Data Found')" border @sort-change="handleSortable" stripe :data="fields">
                <el-table-column :label="$t('Label')" prop="label" sortable></el-table-column>
                <el-table-column :label="$t('Slug')" prop="slug" sortable></el-table-column>
                <el-table-column :label="$t('Group')" prop="group" sortable></el-table-column>
                <el-table-column :label="$t('Type')" prop="type" sortable></el-table-column>
                <el-table-column width="160" :label="$t('Actions')">
                    <template slot-scope="scope">
                        <el-button type="primary" @click="updateFieldModal(scope.$index)" size="mini" icon="el-icon-edit"></el-button>
                        <confirm @yes="deleteField(scope.$index)">
                            <el-button
                                size="mini"
                                type="danger"
                                slot="reference"
                                icon="el-icon-delete"
                            />
                        </confirm>
                        <el-button-group>
                            <el-button @click="movePosition(scope.$index, 'up')" :disabled="scope.$index == 0" size="mini" icon="el-icon-arrow-up"></el-button>
                            <el-button @click="movePosition(scope.$index, 'down')" :disabled="scope.$index == (fields.length - 1)" size="mini" icon="el-icon-arrow-down"></el-button>
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>
        </div>

        <el-dialog
            :title="$t('Add New Custom Field')"
            :visible.sync="addFieldVisible"
            :close-on-click-modal="false"
            :append-to-body="true"
            width="60%">
            <custom-field-form :existing_fields="fields" form_type="new" :field_types="field_types" :item="new_item"></custom-field-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="createField()" type="primary" size="small">
                    {{$t('Add')}}
                </el-button>
            </div>
        </el-dialog>

        <el-dialog
            :title="$t('Update Custom Field')"
            :visible.sync="updateFieldVisible"
            :close-on-click-modal="false"
            :append-to-body="true"
            class="fcrm-update-dialog"
            width="60%">
            <custom-field-form :existing_fields="fields" form_type="update" :field_types="field_types" :item="update_field"></custom-field-form>
            <div slot="footer" class="dialog-footer">
                <el-button @click="updateField()" type="success" size="small">{{$t('Update Field')}}</el-button>
            </div>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
    import Confirm from '@/Pieces/Confirm';
    import CustomFieldForm from './_CustomFieldForm';

    export default {
        name: 'custom_contact_fields',
        components: {
            Confirm,
            CustomFieldForm
        },
        props: {
            is_pop: {
                type: Boolean,
                default: () => {
                    return false
                }
            },
            section_title: {
                type: String,
                default: () => {
                    return 'Custom Contact Fields'
                }
            },
            route: {
                type: String,
                default: () => {
                    return 'custom-fields/contacts'
                }
            }
        },
        data() {
            return {
                loading: false,
                fields: [],
                originalFields: [],
                new_item: {},
                field_types: {},
                addFieldVisible: false,
                updateFieldVisible: false,
                update_field: {},
                query_data: {
                    sort_by: '',
                    sort_order: ''
                }
            }
        },
        methods: {
            fetchFields() {
                this.loading = true;
                this.$get(this.route, {
                    with: ['field_types', 'field_groups'],
                    sort_by: this.query_data.sort_by,
                    sort_order: this.query_data.sort_order
                })
                    .then((response) => {
                        this.fields = response.fields;
                        this.originalFields = [...this.fields];
                        this.field_types = response.field_types;
                    })
                    .catch((error) => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            saveFields() {
                this.loading = true;
                this.$put(this.route, {
                    fields: JSON.stringify(this.fields)
                })
                    .then((response) => {
                        this.appVars.available_custom_fields = response.fields;
                        this.fields = response.fields;
                        this.$emit('fieldsUpdated', response.fields);
                        this.$notify.success(response.message);
                    })
                    .catch((error) => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            createField() {
                if (!this.validateField(this.new_item)) {
                    return false;
                }
                this.fields.push(this.new_item);
                this.addFieldVisible = false;
                this.new_item = {};
                this.saveFields();
            },
            validateField(item) {
                if (!item.label) {
                    this.$notify.error(this.$t('Please Provide label'));
                    return false;
                }
                if (item.options && !item.options.length) {
                    this.$notify.error(this.$t('Please Field Option Values'));
                    return false;
                }
                return true;
            },
            updateFieldModal(fieldIndex) {
                this.update_field = this.fields[fieldIndex];
                this.updateFieldVisible = true;
            },
            updateField() {
                if (!this.validateField(this.update_field)) {
                    return false;
                }
                this.updateFieldVisible = false;
                this.update_field = {};
                this.saveFields();
            },
            deleteField(fieldIndex) {
                this.fields.splice(fieldIndex, 1);
                this.saveFields();
            },
            movePosition(fromIndex, type) {
                let toIndex = fromIndex - 1;
                if (type === 'down') {
                    toIndex = fromIndex + 1;
                }
                const fields = this.fields;
                const element = fields[fromIndex];
                fields.splice(fromIndex, 1);
                fields.splice(toIndex, 0, element);
                this.$set(this, 'fields', fields);
                this.saveFields();
            },
            handleSortable(sorting) {
                // Check if the same column is clicked for sorting
                if (this.query_data.sort_by === sorting.prop) {
                    // Toggle the sort order
                    this.query_data.sort_order = this.query_data.sort_order === 'ascending' ? 'descending' : 'ascending';
                } else {
                    // Update query data for a new column
                    this.query_data.sort_by = sorting.prop;
                    this.query_data.sort_order = sorting.order;
                }

                const sortBy = this.query_data.sort_by;
                const isDesc = this.query_data.sort_order === 'descending';

                if (sortBy) {
                    // Sort the fields array
                    const sortedFields = [...this.fields].sort((a, b) => {
                        const aValue = (a[sortBy] || '').toString().toLowerCase();
                        const bValue = (b[sortBy] || '').toString().toLowerCase();

                        // Special handling for "group" sorting
                        if (sortBy === 'group') {
                            const aIsEmpty = !aValue;
                            const bIsEmpty = !bValue;

                            if (aIsEmpty && !bIsEmpty) return 1;
                            if (!aIsEmpty && bIsEmpty) return -1;
                        }

                        // Simple alphabetical comparison
                        if (aValue < bValue) return isDesc ? 1 : -1;
                        if (aValue > bValue) return isDesc ? -1 : 1;
                        return 0;
                    });

                    this.fields = sortedFields;
                    this.loading = true;

                    this.$nextTick(() => {
                        this.loading = false;
                    });
                }
            },
            resetFilter() {
                this.query_data.sort_by = '';
                this.query_data.sort_order = '';

                // Restore original fields
                this.fields = [...this.originalFields];
                
                this.loading = true;
                this.$nextTick(() => {
                    this.loading = false;
                });
            }
        },
        mounted() {
            this.fetchFields();
            if (!this.is_pop) {
                this.changeTitle(this.$t('Custom Fields'));
            }
        }
    };
</script>
