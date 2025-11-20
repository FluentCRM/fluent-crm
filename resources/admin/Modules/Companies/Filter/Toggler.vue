<template>
    <filterer name="column-toggler">
        <el-button class="fc_columns_filter" slot="header" icon="el-icon-setting" plain size="small">
            {{ $t('Columns') }}
        </el-button>
        <el-checkbox-group v-model="selection"
                           slot="items"
                           class="fluentcrm-filter-options fc_column_toggler_checks fc_checkbox_group"
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
                <slot name="btn-label">{{ $t('Save') }}</slot>
            </el-button>
        </el-dropdown-item>
    </filterer>
</template>

<script type="text/babel">
import Filterer from '@/Pieces/Filterer';
import {companyColumns} from '@/Bits/data_config.js';

export default {
    name: 'ColumnToggler',
    components: {
        Filterer
    },
    data() {
        return {
            selection: [],
            companyColumns: companyColumns
        }
    },
    computed: {
        columnGroups() {
            const groups = [
                {
                    slug: 'company',
                    label: this.$t('Primary Fields'),
                    fields: companyColumns
                }
            ];

            if (this.appVars.company_custom_fields && this.appVars.company_custom_fields.length) {
                const fields = this.appVars.company_custom_fields.map(field => {
                    return {
                        label: field.label,
                        value: '_custom_' + field.slug
                    }
                });

                groups.push({
                    slug: 'custom_fields',
                    label: this.$t('Custom Fields'),
                    fields: fields
                });
            }

            return groups;
        }
    },
    methods: {
        init() {
            const selection = this.storage.get('companyColumns');
            if (selection) {
                this.selection = selection;
                this.fire();
            } else {
                this.save();
            }
        },
        save() {
            this.storage.set('companyColumns', this.selection);
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
