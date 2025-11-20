<template>
    <div class="fc_block_choice_wrapper">
        <el-row :gutter="0">
            <el-col v-if="choice_context != 'child'" :span="24">
                <el-menu
                    mode="horizontal"
                    @select="(item) => { selectType = item; }"
                    :default-active="selectType"
                >
                    <el-menu-item index="action">
                        <i class="fc-icon-action"></i>
                        <span>{{ $t('Actions') }}</span>
                    </el-menu-item>
                    <template v-if="!condition_type">
                        <el-menu-item index="benchmark">
                            <i class="fc-icon-benchmark"></i>
                            <span>{{ $t('Goals') }}</span>
                        </el-menu-item>
                        <el-menu-item index="conditional">
                            <i class="fc-icon-conditions"></i>
                            <span>{{ $t('Condition') }}</span>
                        </el-menu-item>
                        <el-menu-item index="all">
                            <i class="el-icon el-icon-menu" />
                            <span>{{ $t('View All') }}</span>
                        </el-menu-item>
                    </template>
                </el-menu>
                <i @click="() => { $emit('close'); }" v-if="show_close" class="el-dialog__close el-icon el-icon-close"></i>
            </el-col>
            <el-col :span="24">
                <div class="fc_funnel_block_search_wrap">
                    <el-input v-model="searchBlock" :placeholder="$t('Search blocks, e.g., email, apply tags, etc.')" clearable />
                </div>
            </el-col>
            <el-col :span="24">
                <div class="fc_choice_blocks">
                    <div v-if="selectType == 'action' && !searchBlock" class="fc_choice_header">
                        <h2 class="fc_choice_title">{{ $t('Action Blocks') }}</h2>
                        <p>{{ $t('_Bl_Actions_battywtf') }}</p>
                    </div>
                    <div v-else-if="selectType == 'benchmark' && !searchBlock" class="fc_choice_header">
                        <h2 class="fc_choice_title">{{ $t('Goals') }}</h2>
                        <p>{{ $t('_Bl_These_aygityuwda') }}</p>
                    </div>
                    <div v-else-if="selectType == 'conditional' && !searchBlock" class="fc_choice_header">
                        <h2 class="fc_choice_title">{{ $t('Condition Blocks') }}</h2>
                        <p>{{ $t('_Bl_Use_tbtcspfysc') }}</p>
                    </div>
                    <div v-if="Object.keys(item.categories).length" v-for="(item,itemType) in current_items" :key="itemType" :class="['fc_choice_wrap_'+itemType, 'fc_choice_selected_'+selectType]">
                        <h2 class="fc_choice_title" v-if="item.title">{{item.title}}</h2>
                        <div class="fc_choice_category" v-for="(category,categoryIndex) in item.categories" :key="categoryIndex">
                            <h3 v-if="category.title && (selectType == 'action' || selectType == 'all')">{{category.title}}</h3>
                            <el-row class="fc_choice_row" :class="'fc_items_'+selectType" :gutter="20">
                                <el-col class="fc_choice_block" v-for="(block,blockIndex) in category.items" :key="blockIndex"
                                        :sm="24" :xs="24" :md="8" :lg="8">
                                    <div @click="insert(blockIndex, block.is_pro)" class="fc_choice_card">
                                        <div class="fc_pro_ribbon" v-if="block.is_pro">{{ $t('Pro') }}</div>
                                        <h3><i :class="block.icon ? block.icon : 'fc-icon-trigger'"></i> {{ block.title }}</h3>
                                        <p v-html="block.description"></p>
                                    </div>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div v-if="selectType == 'all' && !Object.keys(current_items.action.categories).length && !Object.keys(current_items.benchmark.categories).length && !Object.keys(current_items.conditional.categories).length" class="fc_empty_block_wrap">
                        <el-empty :description="$t('No Blocks Found')"></el-empty>
                    </div>
                </div>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'blockChoice',
    props: ['blocks', 'condition_type', 'choice_context', 'show_close'],
    data() {
        return {
            selectType: 'action',
            searchBlock: ''
        }
    },
    watch: {
        condition_type() {
            this.selectType = 'action';
        },
        searchBlock(newValue) {
            if (newValue) {
                this.selectType = 'all';
            }
        }
    },
    computed: {
        current_items() {
            if (this.selectType == 'all') {
                const items = {
                    action: {
                        title: this.$t('Actions'),
                        categories: {}
                    },
                    benchmark: {
                        title: this.$t('Goals'),
                        categories: {}
                    },
                    conditional: {
                        title: this.$t('Conditionals'),
                        categories: {}
                    }
                };
                this.each(this.blocks, (block, blockIndex) => {
                    let blockType = block.type;

                    if (blockType == 'conditional' && blockIndex == 'funnel_ab_testing') {
                        blockType = 'action';
                    }

                    if (items[blockType]) {
                        const category = block.category || 'Other';
                        if (!items[blockType].categories[category]) {
                            items[blockType].categories[category] = {
                                title: category,
                                items: {}
                            }
                        }
                        items[blockType].categories[category].items[blockIndex] = block;
                    }
                });

                // Apply search filter if searchBlock is defined
                if (this.searchBlock) {
                    const search = this.searchBlock.toLowerCase();

                    Object.keys(items).forEach(blockType => {
                        items[blockType].categories = Object.entries(items[blockType].categories).reduce((acc, [categoryKey, category]) => {
                            const filteredItems = Object.entries(category.items).reduce((itemAcc, [itemKey, item]) => {
                                if (item.title.toLowerCase().includes(search)) {
                                    itemAcc[itemKey] = item;
                                }
                                return itemAcc;
                            }, {});

                            if (Object.keys(filteredItems).length > 0) {
                                acc[categoryKey] = {
                                    title: category.title,
                                    items: filteredItems
                                };
                            }
                            return acc;
                        }, {});
                    });
                }
                return items;
            }

            const items = {
                title: '',
                categories: {}
            };
            this.each(this.blocks, (block, blockIndex) => {
                let blockType = block.type;
                if (blockType == 'conditional' && blockIndex == 'funnel_ab_testing' && this.choice_context != 'child') {
                    blockType = 'action';
                }

                if (blockType === this.selectType) {
                    const category = block.category || 'Other';

                    if (!items.categories[category]) {
                        items.categories[category] = {
                            title: category,
                            items: {}
                        }
                    }
                    items.categories[category].items[blockIndex] = block;
                }
            });

            return {
                items: items
            };
        }
    },
    methods: {
        insert(index, isPro) {
            if (isPro) {
                this.$alert('<p><strong>This block require pro version of FluentCRM</strong></p><p style="line-height: 22px; margin-bottom: 15px !important;">Please download and install FluentCRM Pro to activate this block</p><p><a class="el-button el-button--danger" :href="' + this.appVars.crm_pro_url + '" target="_blank" rel="noopener">Get FluentCRM Pro</a></p>', 'Require FluentCRM Pro', {
                    dangerouslyUseHTMLString: true,
                    showConfirmButton: false
                });
                return false;
            }
            this.$emit('insert', index);
        }
    }
}
</script>
