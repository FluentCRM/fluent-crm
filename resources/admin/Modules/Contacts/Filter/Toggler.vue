<template>
    <filterer name="column-toggler">
        <el-button class="fc_columns_filter" slot="header" icon="el-icon-setting" plain size="small">
            {{$t('Columns')}}
        </el-button>
        <el-checkbox-group v-model="selection"
                           slot="items"
                           class="fluentcrm-filter-options fc_checkbox_group fc_column_toggler_checks"
        >
            <template v-for="(group, groupSlug) in columnGroups">
                <el-checkbox :key="groupSlug" :disabled="true">{{ group.label }}</el-checkbox>
                <el-checkbox v-for="(item, index) in group.fields"
                             :key="groupSlug+' '+index"
                             :label="item.value"
                             class="el-dropdown-menu__item fc_checkbox"
                >
                    {{ item.label }}
                </el-checkbox>
            </template>
        </el-checkbox-group>

        <el-dropdown-item slot="footer" class="no-hover">
            <el-button type="success"
                       size="mini"
                       style="width: 100%"
                       @click="save"
                       class="fc_primary_button"
            >
                <slot name="btn-label">{{$t('Save')}}</slot>
            </el-button>
        </el-dropdown-item>
    </filterer>
</template>

<script type="text/babel">
import Filterer from '@/Pieces/Filterer';
import {subscriberColumns} from '@/Bits/data_config.js';

export default {
    name: 'ColumnToggler',
    components: {
        Filterer
    },
    data() {
        return {
            selection: [],
            subscriberColumns: subscriberColumns
        }
    },
    computed: {
        columnGroups() {
            if (this.has_company_module) {
                if (!window.fc_primary_company_pushed) {
                    subscriberColumns.push({
                        label: this.$t('Primary Company'),
                        value: 'primary_company',
                        position: 4
                    });
                    window.fc_primary_company_pushed = true;
                }

                if (!window.fc_companies_pushed) {
                    subscriberColumns.push({
                        label: this.$t('Companies'),
                        value: 'companies',
                        position: 4
                    });
                    window.fc_companies_pushed = true;
                }
            }

            const groups = [{
                slug: 'subscriber',
                label: this.$t('Primary Fields'),
                fields: subscriberColumns
            }];

            if (this.appVars.commerce_provider) {
                groups.push({
                    slug: 'commerce',
                    label: this.$t('Commerce Fields'),
                    fields: [
                        {
                            label: this.$t('Lifetime Value'),
                            value: 'commerce.total_order_value',
                            position: 1
                        },
                        {
                            label: this.$t('Purchase Count'),
                            value: 'commerce.total_order_count',
                            position: 2
                        },
                        {
                            label: this.$t('Customer Since'),
                            value: 'commerce.first_order_date',
                            position: 3
                        },
                        {
                            label: this.$t('Last Purchase Date'),
                            value: 'commerce.last_order_date',
                            position: 3
                        }
                    ]
                });
            }

            const customFieldsGroup = [];
            this.each(this.appVars.contact_custom_fields, (field, index) => {
                customFieldsGroup.push({
                    label: field.label,
                    value: field.slug,
                    position: index + 10
                });
            });

            if (customFieldsGroup.length) {
                groups.push({
                    slug: 'custom_fields',
                    label: this.$t('Custom Fields'),
                    fields: customFieldsGroup
                });
            }

            return groups;
        }
    },
    methods: {
        init() {
            const selection = this.storage.get('columns');
            if (selection) {
                this.selection = selection;
                this.fire();
            } else {
                this.selection = ['tags', 'lists', 'status'];
                this.save();
            }
        },
        save() {
            this.storage.set('columns', this.selection);
            this.fire();
        },
        fire() {
            this.$emit('input', this.selection);
            setTimeout(() => {
                this.$emit('dataChanged', this.selection);
            }, 400);
        }
    },
    mounted() {
        this.init();
    }
}
</script>
