<template>
    <div class="fluentcrm-filterer">
        <filterer
            :placement="placement"
            :filter_type="type"
            :class="{'fluentcrm-filtered': selection.length}"
        >
            <template slot="label">
                <template v-if="selection.length">
                    {{$t('Filtered by')}} {{ trans(type) | ucFirst }}
                </template>
                <template v-else>
                    {{$t('Filtered by')}} {{ trans(type) | ucFirst }}
                </template>
            </template>

            <!--tag search-->
            <el-dropdown-item slot="items" class="fluentcrm-filter-option fc-dropdown-search-item no-hover">
                <el-input size="small" v-model="query"
                          :placeholder="$t('Search...')"
                          @keyup.native="search"
                          class="fc_input"
                ></el-input>
            </el-dropdown-item>

            <!--choose a tag label-->
            <el-dropdown-item slot="items" class="fluentcrm-filter-option fc-dropdown-items-label">
                {{$t('Choose an option:')}}
            </el-dropdown-item>

            <!--tag options-->
            <el-checkbox-group v-model="selection"
                               slot="items"
                               class="fluentcrm-filter-options fc_checkbox_group"
            >
                <el-checkbox v-for="item in parsedOptions"
                             :key="item.id"
                             :label="item.id"
                             class="el-dropdown-menu__item fc_checkbox"
                >
                    {{ item.title }}
                </el-checkbox>
            </el-checkbox-group>

            <el-dropdown-item class="fluentcrm-filter-option"
                              v-if="noMatch"
                              slot="items"
            >
                <p>{{$t('No items found')}}</p>
            </el-dropdown-item>
        </filterer>

        <div v-if="selection.length" class="fluentcrm-meta">
            <el-tag v-for="item in parsedOptions"
                    v-if="selected.indexOf(item.id) != -1"
                    :key="item.id"
                    closable
                    @close="deselect(item)"
            >
                {{ item.title }}
            </el-tag>
        </div>
    </div>
</template>

<script type="text/babel">
    import Filterer from '@/Pieces/Filterer';

    export default {
        name: 'Filters',
        components: {
            Filterer
        },
        props: {
            type: {
                required: true
            },
            options: {
                required: true,
                type: Array
            },
            selected: {
                required: true
            },
            count: {
                required: true,
                type: Number
            },
            noMatch: {
                required: true,
                type: Boolean
            }
        },
        data() {
            return {
                query: null,
                placement: 'bottom-start'
            }
        },
        computed: {
            selection: {
                get() {
                    return this.selected;
                },
                set(value) {
                    this.$emit('filter', value);
                }
            },
            parsedOptions() {
                let options = [];
                if (this.type != 'statuses') {
                    this.each(this.options, (option) => {
                        options.push({
                            id: parseInt(option.id),
                            title: option.title,
                            slug: option.slug
                        });
                    });
                } else {
                    options = this.options;
                }
                return options;
            }
        },
        methods: {
            search() {
                this.$emit('search', this.query && this.query.toLowerCase());
            },
            deselect(tag) {
                const indexRemove = this.selection.indexOf(tag.slug);
                this.selection.splice(indexRemove, 1);

                this.$emit('filter', this.selection);
            }
        },
        mounted() {
            if (this.appVars.is_rtl) {
                this.placement = 'bottom-end';
            }
        }
    }
</script>
