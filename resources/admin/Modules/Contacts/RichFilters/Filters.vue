<template>
    <div class="fc_rich_filters">
        <table v-if="items.length && !working" style="width: 100%;" class="fc_table">
            <tbody>
            <filter-item v-for="(item,itemKey) in items" :view_only="view_only" @removeItem="removeItem(itemKey)"
                         :key="itemKey"
                         :filterLabels="filterLabels" :item="item"/>
            </tbody>
        </table>

        <div v-if="items.length == 0" class="fc_filter_intro fc_pad_around_5">
            <el-popover
                :placement="isRTL ? 'left' : 'right'"
                width="450"
                class="fc_contact_filter_pop"
                v-model="addVisible"
                trigger="manual">
                <el-cascader-panel @change="maybeSelected"
                                   style="width: 100%"
                                   :options="filterOptions"
                                   v-model="new_item"/>
                <el-button slot="reference" @click="addVisible = !addVisible" size="small" icon="el-icon-plus">
                    {{ $t('Add') }}
                </el-button>
            </el-popover>
            {{ $t(add_label) }}
            <el-button style="float: right;" @click="$emit('maybeRemove')" size="mini" type="danger"
                       icon="el-icon-delete"></el-button>
        </div>

        <div v-else-if="!view_only" class="fc_filter_intro fc_pad_around_5">
            <el-popover
                :placement="isRTL ? 'left' : 'right'"
                width="450"
                v-model="addVisible"
                trigger="manual">
                <el-cascader-panel @change="maybeSelected"
                                   style="width: 100%"
                                   :options="filterOptions"
                                   v-model="new_item"/>
                <el-button @click="addVisible = !addVisible" slot="reference" size="small" icon="el-icon-plus">
                    {{ $t('Add') }}
                </el-button>
            </el-popover>
            {{ $t(add_label) }}
        </div>
    </div>
</template>
<script type="text/babel">
import FilterItem from './_FilterItem';

export default {
    name: 'RichContactFilter',
    components: {
        FilterItem
    },
    props: {
        items: {
            type: Array,
            default: () => []
        },
        add_label: {
            type: String,
            default() {
                return this.$t('Filters.instruction');
            }
        },
        filterOptions: {
            type: Array,
            default() {
                return this.appVars.advanced_filter_options;
            }
        },
        view_only: {
            type: Boolean,
            default() {
                return false;
            }
        }
    },
    data() {
        return {
            addVisible: false,
            new_item: [],
            working: false,
            isRTL: window.fcAdmin.is_rtl
        }
    },
    computed: {
        filterLabels() {
            const options = {};
            this.each(this.filterOptions, (option) => {
                this.each(option.children, (item) => {
                    options[option.value + '-' + item.value] = {
                        provider: option.value,
                        ...item
                    }
                });
            });
            return options
        }
    },
    methods: {
        maybeSelected() {
            if (this.new_item.length == 2) {
                let operator = '';

                if (this.new_item[0] == 'subscriber' && this.new_item[1] != 'country') {
                    operator = 'contains';
                }

                this.items.push({
                    source: [...this.new_item],
                    operator: operator,
                    value: ''
                });
                this.addVisible = false;
                this.new_item = [];
                this.$emit('showClearFilterButton');
            }
        },
        removeItem(index) {
            this.working = true;
            this.$nextTick(() => {
                this.items.splice(index, 1);
                if (!this.items.length) {
                    this.$emit('maybeRemove');
                }
                this.working = false;
            });
        }
    }
}
</script>
