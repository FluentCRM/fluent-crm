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
                       style="width: 100%; margin-top: 10px"
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
import {automationFunnelColumns} from '@/Bits/data_config.js';
export default {
    name: 'ColumnToggler',
    components: {
        Filterer
    },
    data() {
        return {
            selection: [],
            automationFunnelColumns: automationFunnelColumns
        }
    },
    computed: {
        columnGroups() {
            const groups = [
                {
                    slug: 'automation',
                    label: this.$t('Primary Fields'),
                    fields: automationFunnelColumns
                }
            ];
            return groups;
        }
    },
    methods: {
        init() {
            const selection = this.storage.get('automationFunnelColumns');
            if (selection) {
                this.selection = selection;
                this.fire();
            } else {
                this.selection = ['trigger', 'action', 'status', 'pause/run'];
                this.save();
            }
        },
        save() {
            this.storage.set('automationFunnelColumns', this.selection);
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
