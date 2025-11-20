<template>
    <div class="fc_child_blocks">
        <div
            class="fluentcrm_block"
            v-for="(block,blockIndex) in blocks"
            :key="blockIndex"
            :class="'fluentcrm_block_'+block.action_name"
        >
            <div :style="{ backgroundImage: getBlockIcon(block) }" @click="setCurrentBlock(block, blockIndex)" class="fluentcrm_blockin">
                <div class="fc_action_abs_right">
                    <el-dropdown trigger="click">
                                            <span @click.stop="" class="el-dropdown-link">
                                                <i style="font-weight: bold; cursor: pointer;" class="el-icon-more icon-90degree el-icon--right"></i>
                                            </span>
                        <el-dropdown-menu class="fc_clickable_pop" slot="dropdown">
                            <el-dropdown-item>
                                <confirm placement="top-start" :message="$t('Delete_Block_Alert')" @yes="deleteChild(block, blockIndex)">
                                    <span slot="reference">{{ $t('Delete') }}</span>
                                </confirm>
                            </el-dropdown-item>
                            <el-dropdown-item>
                                <span @click="cloneBlock(block, blockIndex)" class="el-popover__reference">{{ $t('Clone') }}</span>
                            </el-dropdown-item>
                        </el-dropdown-menu>
                    </el-dropdown>
                </div>
                <div class="fluentcrm_block_title">
                    <i :class="getBlockIcon(block)"></i> {{ block.title }}
                </div>
                <div class="fluentcrm_block_desc" v-html="getBlockDescription(block)"></div>
                <report-widget v-if="show_inline_report" :stat="stats[block.id]"/>
            </div>
            <el-button-group class="fc_block_controls">
                <el-button :disabled="blockIndex == 0" @click="moveToPosition('up', blockIndex)"
                           size="mini" icon="el-icon-arrow-up"></el-button>
                <el-button :disabled="blockIndex + 1 == blocks.length"
                           @click="moveToPosition('down', blockIndex)" size="mini"
                           icon="el-icon-arrow-down"></el-button>
            </el-button-group>
        </div>
        <el-button
            @click="show_choice_modal = true"
            icon="el-icon-plus"
            style="width: 100%;"
            type="default">
            {{$t('Add Action')}}
        </el-button>

        <el-drawer
            class="fc_company_info_drawer fc_choice_action_drawer"
            :close-on-click-modal="true"
            :title="$t('Add Action')"
            :visible.sync="show_choice_modal"
            :append-to-body="true"
            :destroy-on-close="true"
            ref="choice_action_drawer"
            :size="globalDrawerSize">
            <block-choice choice_context="child" condition_type="action" @insert="addBlock" :blocks="all_blocks"/>
        </el-drawer>

        <el-drawer
            class="fc_company_info_drawer"
            :close-on-click-modal="false"
            :title="'Edit ' + (editing_block ? editing_block.title : '')"
            :visible.sync="editing_modal"
            :class="(editing_block) ? 'fc_drawer_for_' + editing_block.action_name + ((editing_block.id) ? ' fc_blocked_has_id' : ' fc_blocked_no_id') : ''"
            :append-to-body="true"
            :destroy-on-close="true"
            :size="globalDrawerSize"
            :with-header="false"
            @close="() => {editing_modal = false}"
        >
            <div class="fluentcrm_block_editor_body">
                <template v-if="editing_block && editing_modal">
                    <field-editor
                        :title_badge="editing_block.type"
                        @save="save()"
                        @save_reload="save()"
                        @deleteSequence="deleteBlock()"
                        :show_controls="true"
                        :data="editing_block.settings"
                        :options="options"
                        :action_name="editing_block.action_name"
                        :key="editing_index+'_'+editing_block.action_name"
                        :is_first="editing_index === 0"
                        :is_last="editing_index === (blocks.length - 1)"
                        @closeDrawer="editing_modal = false"
                        :settings="current_block_fields">
                        <template v-slot:after_header>
                            <el-form-item :label="$t('Internal Label')">
                                <el-input :placeholder="$t('Internal Label')" v-model="editing_block.title"/>
                            </el-form-item>
                        </template>
                    </field-editor>
                </template>
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import ReportWidget from './_report_widget';
import BlockChoice from './_BlockChoice';
import FieldEditor from './FieldEditor';
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'ChildBlocks',
    components: {
        ReportWidget,
        BlockChoice,
        FieldEditor,
        Confirm
    },
    props: [
        'blocks',
        'block_fields',
        'all_blocks',
        'show_inline_report',
        'getBlockDescription',
        'getBlockIcon',
        'stats',
        'options'
    ],
    data() {
        return {
            show_choice_modal: false,
            editing_block: false,
            editing_modal: false,
            editing_index: 0
        }
    },
    computed: {
        current_block_fields() {
            if (!this.editing_block) {
                return {}
            }
            const blockKey = this.editing_block.action_name;
            return this.block_fields[blockKey];
        }
    },
    methods: {
        addBlock(item) {
            const block = JSON.parse(JSON.stringify(this.all_blocks[item]));
            block.action_name = item;
            this.blocks.push(block);

            this.$refs.choice_action_drawer.closeDrawer()
            this.show_choice_modal = false;

            const editingIndex = this.blocks.length - 1;
            this.editing_index = editingIndex;
            this.editing_block = JSON.parse(JSON.stringify(this.blocks[editingIndex]));
            setTimeout(() => {
                this.editing_modal = true;
            }, 300);
        },
        save() {
            this.$set(this.blocks, this.editing_index, this.editing_block);
            this.editing_modal = false;
            this.editing_block = false;
            this.$emit('save');
        },
        deleteBlock() {
            this.blocks.splice(this.editing_index, 1);
            this.editing_modal = false;
            this.editing_block = false;
            this.$emit('save');
        },
        deleteChild(block, blockIndex) {
            this.blocks.splice(blockIndex, 1);
            this.$emit('save');
        },
        cloneBlock(block, blockIndex) {
            block = JSON.parse(JSON.stringify(block));
            delete block.id;
            this.removeBlockCampaign(block);

            this.blocks.splice(blockIndex, 0, block);
            this.$emit('save');
        },
        removeBlockCampaign(block) {
            if (block.settings && block.settings.campaign) {
                delete block.settings.campaign.id;
            }
        },
        setCurrentBlock(block, blockIndex) {
            this.editing_block = JSON.parse(JSON.stringify(block));
            this.editing_index = blockIndex;
            this.$nextTick(() => {
                this.editing_modal = true;
            });
        },
        moveToPosition(type, fromIndex) {
            let toIndex = fromIndex - 1;
            if (type === 'down') {
                toIndex = fromIndex + 1;
            }
            const funnelSequences = this.blocks;
            const element = funnelSequences[fromIndex];
            funnelSequences.splice(fromIndex, 1);
            funnelSequences.splice(toIndex, 0, element);
            this.$set(this, 'blocks', funnelSequences);
            this.$emit('save');
        }
    }
}
</script>
