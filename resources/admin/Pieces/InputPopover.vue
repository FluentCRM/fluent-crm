<template>
    <div>
        <el-popover
            ref="input-popover"
            placement="right-end"
            :popper-class="'fcrm-smartcodes-popover el-dropdown-list-wrapper ' + popper_extra"
            v-model="visible"
            trigger="click">
            <div class="el_pop_data_group">
                <div class="el_pop_data_headings">
                    <ul>
                        <li
                            v-for="(item,item_index) in data"
                            :data-item_index="item_index"
                            :key="item_index"
                            :class="(activeIndex == item_index) ? 'active_item_selected' : ''"
                            @click="activeIndex = item_index">
                            {{ item.title }}
                        </li>
                    </ul>
                    <div v-if="doc_url" class="pop_doc">
                        <a :href="doc_url" target="_blank" rel="noopener">{{ $t('Learn More') }}</a>
                    </div>
                </div>
                <div class="el_pop_data_body">
                    <div class="el_pop_search">
                        <el-input
                            v-model="searchQuery"
                            :placeholder="$t('Search shortcodes...')"
                            size="small"
                            clearable
                        />
                    </div>
                    <div v-for="(item,current_index) in data" :key="current_index">
                        <ul v-show="activeIndex == current_index"
                            :class="'el_pop_body_item_'+current_index">
                            <li @click="insertShortcode(code)" v-for="(label,code) in filteredShortcodes(item.shortcodes)" :key="code">
                                {{ label }}<span>{{ code }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </el-popover>

        <div v-if="fieldType == 'textarea'" class="input-textarea-value">
            <i class="icon el-icon-tickets" v-popover:input-popover></i>
            <el-input :placeholder="placeholder" :rows="4" type="textarea" v-model="model" @input="$emit('update', $event)"></el-input>
        </div>
        <el-input class="fc_pop_append" :placeholder="placeholder" v-else v-model="model" @input="$emit('update', $event)" :type="fieldType">
            <template slot="append">
                <span class="fluentcrm_url fluentcrm_clickable" :class="icon" v-popover:input-popover></span>
            </template>
        </el-input>
    </div>
</template>

<script type="text/babel">
    export default {
        model: {
            prop: 'value',
            event: 'update'
        },
        name: 'inputPopover',
        watch: {
            value (newVal, oldVal) {
                this.model = newVal
            },
            model (newVal, oldVal) {
                this.model = newVal
            }
        },
        props: {
            value: String,
            placeholder: {
                type: String,
                default: ''
            },
            placement: {
                type: String,
                default: 'bottom'
            },
            icon: {
                type: String,
                default: 'el-icon-more'
            },
            fieldType: {
                type: String,
                default: 'text'
            },
            popper_class: {
              type: String,
              default: ''
            },
            data: Array,
            attrName: {
                type: String,
                default: 'attribute_name'
            },
            popper_extra: {
                type: String,
                default: ''
            },
            doc_url: {
                type: String,
                default() {
                    return '';
                }
            }
        },
        data() {
            return {
                model: this.value,
                activeIndex: '0',
                visible: false,
                searchQuery: ''
            }
        },
        methods: {
            selectEmoji(imoji) {
                this.insertShortcode(imoji.data);
            },
            insertShortcode(codeString) {
                if (!this.model) {
                    this.model = '';
                }

                if (this.model) {
                    this.model = this.model.trim() + ' ' + codeString.replace(/param_name/, this.attrName);
                } else {
                    this.model += codeString.replace(/param_name/, this.attrName);
                }

                this.visible = false;
                this.$emit('update', this.model);
            },
            filteredShortcodes(shortcodes) {
                if (!this.searchQuery) return shortcodes;
                
                const query = this.searchQuery.toLowerCase();
                const filtered = {};
                
                Object.entries(shortcodes).forEach(([code, label]) => {
                    if (code.toLowerCase().includes(query) || label.toLowerCase().includes(query)) {
                        filtered[code] = label;
                    }
                });
                
                return filtered;
            }
        }
    }
</script>
