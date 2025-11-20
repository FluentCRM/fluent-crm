<template>
    <div>
        <el-popover
            :ref="btn_ref"
            placement="right-end"
            offset="50"
            popper-class="fcrm-smartcodes-popover el-dropdown-list-wrapper"
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
                        <a :href="doc_url" target="_blank" rel="noopener">Learn More</a>
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

        <el-button-group>
            <el-button class="editor-add-shortcode"
                       size="mini"
                       v-popover="btn_ref"
                       :type="btnType"
                       v-html="buttonText"
            />
        </el-button-group>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'inputPopoverDropdownExtended',
    props: {
        data: Array,
        close_on_insert: {
            type: Boolean,
            default() {
                return true;
            }
        },
        buttonText: {
            type: String,
            default() {
                return 'Add SmartCodes <i class="el-icon-arrow-down el-icon--right"></i>';
            }
        },
        btnType: {
            type: String,
            default() {
                return 'success';
            }
        },
        btn_ref: {
            type: String,
            default() {
                return 'input-popover1';
            }
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
            activeIndex: 0,
            visible: false,
            searchQuery: ''
        }
    },
    methods: {
        selectEmoji(imoji) {
            this.insertShortcode(imoji.data);
        },
        insertShortcode(code) {
            this.$emit('command', code);
            if (this.close_on_insert) {
                this.visible = false;
            }
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
    },
    mounted() {
    }
}
</script>
