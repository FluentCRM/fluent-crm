<template>
    <div class="fluentcrm_settings_wrapper">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom fc_breadcrumb_inline_edit" separator="/">
                    <el-breadcrumb-item :to="{ name: 'funnels' }">
                        {{ $t('Automation Funnel') }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item class="fc_breadcrumb_item" v-if="funnel && funnel.trigger">
                        <el-popover
                            v-if="!showInlineEditFunnelTitle"
                            placement="right"
                            :title="funnel.trigger.label"
                            width="300"
                            trigger="hover"
                            :content="funnel.description || funnel.trigger.description"
                        >
                            <div slot="reference" class="fc_funnel_breadcrumb_title">
                                <span v-html="funnelTitle"></span>
                            </div>
                        </el-popover>
                        <div class="fc_inline_editable">
                            <el-input
                                v-if="showInlineEditFunnelTitle"
                                :placeholder="$t('Automation Name')"
                                v-model="funnel.title"></el-input>
                            <el-button
                                v-if="showInlineEditFunnelTitle"
                                class="fc_primary_btn"
                                size="small"
                                type="success"
                                @click="updateAutomationSettings()"
                                v-loading="updatingFunnelSettings">{{ $t('Save') }}</el-button>
                            <el-button
                                v-if="showInlineEditFunnelTitle"
                                type="info" size="small"
                                @click="showInlineEditFunnelTitle = false">
                                {{ $t('Cancel') }}
                            </el-button>
                            <i v-if="!showInlineEditFunnelTitle" @click="showInlineEditFunnelTitle = true" class="el-icon-edit"></i>
                        </div>
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div v-if="funnel && funnel.trigger" class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button style="padding: 6px 15px 7px;" @click="showEditTriggerModal()" size="small"
                           icon="el-icon-setting" class="mr-5"></el-button>
                <el-checkbox size="mini" border v-model="show_inline_report">{{ $t('Stats') }}</el-checkbox>
                <span style="vertical-align: middle;" class="mr-5 ml-5">{{ $t('Status') }}: {{ funnel.status }}</span>
                <el-switch class="mr-5"
                           @change="saveFunnelSequences(true)"
                           v-model="funnel.status"
                           active-value="published"
                           inactive-value="draft">
                </el-switch>
                <el-button @click="gotoReports()" v-if="funnel.status == 'published'" size="small">
                    {{ $t('View Reports') }}
                </el-button>
                <inline-doc :doc_id="13791"/>
            </div>
        </div>
        <div v-if="funnel && funnel.trigger" v-loading="working" class="fluentcrm_body fluentcrm_tile_bg">
            <div class="fluentcrm_blocks_container">
                <div class="fluentcrm_blocks_wrapper">
                    <div class="fluentcrm_blocks">
                        <div class="block_item_holder ">
                            <div :class="(is_editing_root) ? 'fluentcrm_block_active' : ''"
                                 class="fluentcrm_block fluentcrm_block_trigger">
                                <div v-if="appVars.icons.trigger_icon" @click="showRootSettings()"
                                     class="fluentcrm_blockin">
                                    <div class="fluentcrm_block_title">
                                        <i :class="getTriggerIcon(funnel.trigger_name)"></i> {{ funnel.title }}
                                    </div>
                                    <div class="fluentcrm_block_desc" v-html="getTriggerDescription(funnel)">
                                    </div>
                                    <report-widget class="fluentcrm_block_stats mt-5" v-if="show_inline_report"
                                                   :stat="stats['0']"/>
                                </div>
                            </div>
                            <div class="block_item_add">
                                <el-popover
                                    placement="right"
                                    width="400"
                                    trigger="hover">
                                    <div class="fc_action_selector">
                                        <ul>
                                            <li @click="handleBlockAdd(-1)">
                                                <h4>{{ $t('Add Action / Goal') }}</h4>
                                                <p>{{ $t('Run Action Task to do particular task on the contact') }}</p>
                                            </li>
                                            <li @click="handleConditionAdd(-1)">
                                                <h4>{{ $t('Conditional Action') }}</h4>
                                                <p>{{ $t('Funnel_Conditional_Action_desc') }}</p>
                                            </li>
                                        </ul>
                                    </div>
                                    <div slot="reference" class="fc_show_plus" @click="handleBlockAdd(-1)">
                                        <i class="el-icon el-icon-circle-plus-outline"></i>
                                    </div>
                                </el-popover>
                            </div>
                        </div>

                        <div :class="'block_item_holder_' + block.type" class="block_item_holder"
                             v-for="(block,blockIndex) in funnel_sequences" :key="blockIndex">
                            <div class="fluentcrm_block"
                                 :class="getBlockClasses(block, blockIndex)"
                            >
                                <div @click="setCurrentBlock(block, blockIndex)" class="fluentcrm_blockin">
                                    <div class="fc_action_abs_right">
                                        <el-dropdown trigger="click">
                                            <span @click.stop="" class="el-dropdown-link">
                                                <i style="font-weight: bold; cursor: pointer;"
                                                   class="el-icon-more icon-90degree el-icon--right"></i>
                                            </span>
                                            <el-dropdown-menu class="fc_clickable_pop" slot="dropdown">
                                                <el-dropdown-item>
                                                    <confirm placement="top-start"
                                                             :message="$t('Delete_Block_Alert')"
                                                             @yes="deleteBlock(block, blockIndex)">
                                                        <span slot="reference">{{ $t('Delete') }}</span>
                                                    </confirm>
                                                </el-dropdown-item>
                                                <el-dropdown-item>
                                                    <span @click="cloneBlock(block, blockIndex)"
                                                          class="el-popover__reference">{{ $t('Clone') }}</span>
                                                </el-dropdown-item>
                                            </el-dropdown-menu>
                                        </el-dropdown>
                                    </div>

                                    <div class="fluentcrm_block_title">
                                        <i :class="getBlockIcon(block)"></i> {{ block.title }}
                                    </div>
                                    <div class="fluentcrm_block_desc" v-html="getBlockDescription(block)"></div>
                                    <report-widget class="fluentcrm_block_stats mt-5" v-if="show_inline_report"
                                                   :stat="stats[block.id]"/>
                                </div>
                                <el-button-group class="fc_block_controls">
                                    <el-button :disabled="blockIndex == 0" @click="moveToPosition('up', blockIndex)"
                                               size="mini" icon="el-icon-arrow-up"></el-button>
                                    <el-button :disabled="blockIndex+1 == funnel_sequences.length"
                                               @click="moveToPosition('down', blockIndex)" size="mini"
                                               icon="el-icon-arrow-down"></el-button>
                                </el-button-group>
                                <template v-if="block.type === 'conditional'">
                                    <span class="fc_b_yes_node"></span>
                                    <span class="fc_b_no_node"></span>
                                </template>
                                <span></span>
                            </div>
                            <template v-if="block.type == 'conditional'">
                                <template v-if="block.action_name == 'funnel_ab_testing'">
                                    <dom-path class="fc_ab_test fc_ab_test_a" side="left" from="fc_b_no_node"
                                              to="fc_b_no_node_point"><span>A - <span>{{
                                            block.settings.path_a
                                        }}%</span></span></dom-path>
                                    <dom-path class="fc_ab_test fc_ab_test_b" side="right" from="fc_b_yes_node"
                                              to="fc_b_yes_node_point"><span>B - {{ block.settings.path_b }}%</span>
                                    </dom-path>
                                </template>
                                <template v-else>
                                    <dom-path side="left" from="fc_b_no_node" to="fc_b_no_node_point"
                                              class="fc_condition_node_point">
                                        <span>{{ $t('No') }}</span>
                                    </dom-path>
                                    <dom-path side="right" from="fc_b_yes_node" to="fc_b_yes_node_point"
                                              class="fc_condition_node_point">
                                        <span>{{ $t('Yes') }}</span>
                                    </dom-path>
                                </template>
                                <div class="block_conditional_wrapper">
                                    <div class="block_cond_holder block_cond_no">
                                        <span class="fc_b_no_node_point"></span>
                                        <div class="block_cond_inner">
                                            <child-blocks
                                                :all_blocks="blocks"
                                                :blocks="block.children['no']"
                                                :show_inline_report="show_inline_report"
                                                :getBlockDescription="getBlockDescription"
                                                :getBlockIcon="getBlockIcon"
                                                :stats="stats"
                                                :options="options"
                                                @save="saveFunnelSequences()"
                                                :block_fields="block_fields"
                                            ></child-blocks>
                                        </div>
                                    </div>
                                    <div class="block_cond_holder block_cond_yes">
                                        <span class="fc_b_yes_node_point"></span>
                                        <div class="block_cond_inner">
                                            <child-blocks
                                                :all_blocks="blocks"
                                                :blocks="block.children['yes']"
                                                :show_inline_report="show_inline_report"
                                                :getBlockDescription="getBlockDescription"
                                                :getBlockIcon="getBlockIcon"
                                                :stats="stats"
                                                :options="options"
                                                @save="saveFunnelSequences()"
                                                :block_fields="block_fields"
                                            ></child-blocks>
                                        </div>
                                    </div>
                                </div>
                                <div class="fc_cond_border_no_top"></div>
                            </template>

                            <div class="block_item_add">
                                <el-popover
                                    placement="right"
                                    width="400"
                                    trigger="hover">
                                    <div class="fc_action_selector">
                                        <ul>
                                            <li @click="handleBlockAdd(blockIndex)">
                                                <h4>{{ $t('Add Action / Goal') }}</h4>
                                                <p>{{ $t('Run Action Task to do particular task on the contact') }}</p>
                                            </li>
                                            <li @click="handleConditionAdd(blockIndex)">
                                                <h4>{{ $t('Conditional Action') }}</h4>
                                                <p>{{ $t('Funnel_Conditional_Action_desc') }}</p>
                                            </li>
                                        </ul>
                                    </div>
                                    <div slot="reference" class="fc_show_plus" @click="handleBlockAdd(blockIndex)">
                                        <i class="el-icon el-icon-circle-plus-outline"></i>
                                    </div>
                                </el-popover>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="loading" class="fluentcrm_body fluentcrm_tile_bg" style="position: relative;">
            <div class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>
        </div>

        <el-drawer
            class="fc_company_info_drawer fc_drawer_edit_block fc_funnel_block_modal"
            :close-on-click-modal="false"
            :class="(current_block) ? 'fc_drawer_for_' + current_block.action_name + ((current_block.id) ? ' fc_blocked_has_id' : ' fc_blocked_no_id') : ''"
            :visible.sync="show_blocK_editor"
            :append-to-body="true"
            @close="fireBeforeClose"
            :with-header="false"
            :size="globalDrawerSize"
            :destroy-on-close="true"
            :wrapperClosable="false"
        >
            <div class="fluentcrm_block_editor_body">
                <template v-if="current_block">
                    <field-editor
                        :title_badge="trans(current_block.type)"
                        :block_type="current_block.type"
                        @save="saveFunnelBlockSequence()"
                        @deleteSequence="deleteFunnelSequence()"
                        :show_controls="true"
                        @save_reload="saveAndFetchSettings()"
                        :data="current_block.settings"
                        :options="options"
                        :action_name="current_block.action_name"
                        :funnel_id="funnel_id"
                        :key="current_block_index+'_'+current_block.action_name"
                        :is_first="current_block_index === 0"
                        :is_last="current_block_index === (funnel_sequences.length - 1)"
                        :settings="current_block_fields"
                        @closeDrawer="show_blocK_editor = false">
                        <template v-slot:after_header>
                            <el-row :gutter="20">
                                <el-col :md="12" :sm="24">
                                    <el-form-item :label="$t('Internal Label')">
                                        <el-input :placeholder="$t('Internal Label')" v-model="current_block.title"/>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :sm="24">
                                    <el-form-item :label="$t('Internal Description')">
                                        <el-input :rows="2" type="textarea" :placeholder="$t('Internal Description')"
                                                  v-model="current_block.description"/>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                        </template>
                    </field-editor>
                </template>
                <template v-else-if="is_editing_root">
                    <field-editor
                        key="is_editing_root"
                        title_badge="trigger"
                        @save_reload="saveAndFetchSettings()"
                        :show_controls="false"
                        :options="options"
                        :data="funnel.settings"
                        @closeDrawer="show_blocK_editor = false"
                        :settings="funnel.settingsFields">
                        <template v-slot:after_header>
                            <el-row :gutter="20">
                                <el-col :md="12" :sm="24">
                                    <el-form-item :label="$t('Automation Name')">
                                        <el-input :placeholder="$t('Automation Name')" v-model="funnel.title"/>
                                    </el-form-item>
                                </el-col>
                                <el-col :md="12" :sm="24">
                                    <el-form-item :label="$t('Internal Description')">
                                        <el-input :rows="2" type="textarea" :placeholder="$t('Internal Description')"
                                                  v-model="funnel.description"/>
                                    </el-form-item>
                                </el-col>
                            </el-row>
                        </template>
                    </field-editor>

                    <el-form class="fc_funnel_conditions fc_block_white" v-if="!isEmptyValue(funnel.conditions)"
                             label-position="top"
                             :data="funnel.conditions">
                        <h3>{{ $t('Conditions') }}</h3>
                        <div v-for="(conditionField, conditionKey) in funnel.conditionFields"
                             :key="conditionKey">
                            <form-field
                                @save_reload="saveAndFetchSettings()"
                                :key="conditionKey" v-model="funnel.conditions[conditionKey]"
                                :field="conditionField"
                                :options="options"></form-field>
                        </div>
                    </el-form>

                    <div class="fluentcrm-text-right">
                        <el-button @click="saveFunnelSequences(false); funnelRootSaved()" size="small" type="success">
                            {{ $t('Save Settings') }}
                        </el-button>
                    </div>
                </template>
            </div>
        </el-drawer>

        <el-drawer
            class="fc_company_info_drawer"
            :close-on-click-modal="false"
            :title="$t('Add Action / Goal')"
            :visible.sync="show_choice_modal"
            :append-to-body="true"
            :with-header="false"
            ref="choice_action_goal_drawer"
            :size="globalDrawerSize"
            :destroy-on-close="true"
        >
            <block-choice v-if="show_choice_modal" @close="() => { show_choice_modal = false }" :show_close="true"
                          @insert="addBlock" :blocks="blocks"/>
        </el-drawer>

        <el-dialog
            :close-on-click-modal="false"
            :title="$t('Edit Primary Automation Trigger')"
            :visible.sync="show_trigger_changer"
            :append-to-body="true"
            width="60%">
            <trigger-changer
                :funnel="funnel"
                @refreshTrigger="handleTriggerChanged()"
                v-if="show_trigger_changer"/>
        </el-dialog>
    </div>
</template>
<script type="text/babel">
import FieldEditor from './FieldEditor';
import FormField from './_Field';
import BlockChoice from './_BlockChoice';
import ReportWidget from './_report_widget';
import DomPath from './_DomPath';
import ChildBlocks from './_ChildBlocks';
import TriggerChanger from './_TriggerChanger';
import Confirm from '@/Pieces/Confirm';
import InlineDoc from '@/Modules/Documentation/InlineDoc';

export default {
    name: 'FunnelEditor',
    props: ['funnel_id', 'options'],
    components: {
        FieldEditor,
        FormField,
        BlockChoice,
        ReportWidget,
        DomPath,
        ChildBlocks,
        TriggerChanger,
        Confirm,
        InlineDoc
    },
    data() {
        return {
            showPopTest: false,
            funnel: false,
            working: false,
            blocks: {},
            actions: [],
            funnel_sequences: [],
            block_fields: {},
            loading: false,
            current_block: false,
            current_block_index: false,
            is_editing_root: true,
            show_choice_modal: false,
            choice_modal_index: 'last',
            show_blocK_editor: false,
            is_new_funnel: this.$route.query.is_new === 'yes',
            stats: {},
            show_inline_report: false,
            show_trigger_changer: false,
            showInlineEditFunnelTitle: false,
            updatingFunnelSettings: false
        }
    },
    computed: {
        funnelTitle() {
            let status = ' (' + this.funnel.status + ')';
            if (this.funnel.status == 'draft') {
                status = '<span class="funnel_trigger_inst">' + status + '</span>';
            }
            return this.funnel.title + status;
        },
        action() {
            if (!this.actions) {
                return {};
            }

            const funnelKey = this.funnel.key;
            return this.actions[funnelKey] || {};
        },
        current_block_fields() {
            if (!this.current_block) {
                return {}
            }
            const blockKey = this.current_block.action_name;
            return this.block_fields[blockKey];
        }
    },
    methods: {
        fetchFunnel() {
            this.loading = true;
            this.$get(`funnels/${this.funnel_id}`, {
                with: ['blocks', 'block_fields', 'funnel_sequences']
            })
                .then(response => {
                    if (!response.funnel.trigger) {
                        this.$notify.error(this.$t('Attached Trigger could not be found'));
                        this.show_trigger_changer = true;
                    }

                    this.funnel = response.funnel;
                    this.block_fields = response.block_fields || {};
                    this.blocks = response.blocks;
                    this.actions = response.actions;
                    window.fcrm_funnel_context_codes = response.composer_context_codes;
                    this.funnel_sequences = response.funnel_sequences;

                    if (this.current_block_index !== false) {
                        this.current_block = false;
                        this.$nextTick(() => {
                            this.current_block = this.funnel_sequences[this.current_block_index];
                        });
                    } else if (this.is_new_funnel) {
                        this.showRootSettings();
                    }
                    this.is_new_funnel = false;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                    this.working = false;
                });
        },
        addBlock(item) {
            const block = JSON.parse(JSON.stringify(this.blocks[item]));
            let targetIndex = this.choice_modal_index;

            if (targetIndex === 'last') {
                targetIndex = this.funnel_sequences.length;
            } else {
                targetIndex = targetIndex + 1;
            }

            if (!block.settings) {
                block.settings = {};
            }

            if (block.type == 'conditional') {
                block.children = {
                    yes: [],
                    no: []
                }
            }

            block.action_name = item;
            this.funnel_sequences.splice(targetIndex, 0, block);

            this.current_block = false;
            this.current_block_index = false;

            this.$nextTick(() => {
                this.current_block = this.funnel_sequences[targetIndex];
                this.current_block_index = targetIndex;
            });

            if (block.reload_on_insert) {
                this.saveAndFetchSettings();
            }

            this.$refs.choice_action_goal_drawer.closeDrawer();

            setTimeout(() => {
                this.show_blocK_editor = true;
                this.show_choice_modal = false;
            }, 300);
        },
        handleBlockAdd(index) {
            this.choice_modal_index = index;
            this.show_choice_modal = true;
        },
        setCurrentBlock(block, blockIndex) {
            this.is_editing_root = false;
            this.current_block = block;
            this.current_block_index = blockIndex;
            this.show_blocK_editor = true;
        },
        deleteFunnelSequence() {
            this.fireBeforeClose();
            const intendedIndex = this.current_block_index;
            this.is_editing_root = false;
            this.current_block = false;
            this.current_block_index = false;
            this.show_blocK_editor = false;
            this.funnel_sequences.splice(intendedIndex, 1);
            this.saveFunnelSequences();
        },
        deleteBlock(block, index) {
            this.funnel_sequences.splice(index, 1);
            this.saveFunnelSequences();
        },
        cloneBlock(block, index) {
            block = JSON.parse(JSON.stringify(block));
            delete block.id;
            this.removeBlockCampaign(block);
            this.prepareConditionalBlock(block);

            this.funnel_sequences.splice(index, 0, block);
            this.saveFunnelSequences();
        },
        removeBlockCampaign(block) {
            if (block.settings && block.settings.campaign) {
                delete block.settings.campaign.id;
            }
        },
        prepareConditionalBlock(block) {
            if (block.action_name === 'funnel_condition') {
                block.children.no.forEach((item) => {
                    delete item.id;
                    this.removeBlockCampaign(item);
                });
                block.children.yes.forEach((item) => {
                    delete item.id;
                    this.removeBlockCampaign(item);
                });
            }
        },
        showRootSettings() {
            this.current_block = false;
            this.current_block_index = false;
            this.is_editing_root = true;
            this.show_blocK_editor = true;
        },
        saveFunnelSequences(isPublished, callback = false) {
            if (window.fluencrm_fallback_funnel_ajax) {
                return this.fallbackSaveFunnelSequence(isPublished, callback);
            }
            this.working = true;
            this.$post('funnels/funnel/save-funnel-sequences', {
                funnel_settings: JSON.stringify(this.funnel.settings),
                conditions: JSON.stringify(this.funnel.conditions),
                funnel_title: this.funnel.title,
                funnel_description: this.funnel.description,
                status: this.funnel.status,
                sequences: this.getStripedSequences(),
                funnel_id: this.funnel_id
            })
                .then(response => {
                    if (!callback) {
                        this.$set(this, 'funnel_sequences', response.sequences);
                        this.$notify.success(response.message);
                        this.fireBeforeClose();
                        this.show_blocK_editor = false;
                        this.current_block = false;
                        this.current_block_index = false;
                    } else {
                        callback(response);
                    }
                })
                .catch(error => {
                    if (error.code == 'rest_no_route') {
                        this.$notify.info(this.$t('Trying fallback save. Please Wait...'));
                        this.fallbackSaveFunnelSequence(isPublished, callback);
                    } else {
                        this.handleError(error);
                    }
                })
                .finally(() => {
                    if (!callback) {
                        this.working = false;
                    }
                });
        },
        fallbackSaveFunnelSequence(isPublished, callback = false) {
            window.fluencrm_fallback_funnel_ajax = true;
            this.working = true;
            window.jQuery.post(window.ajaxurl, {
                action: 'fluentcrm_save_funnel_sequence_ajax',
                funnel_id: this.funnel.id,
                funnel_settings: JSON.stringify(this.funnel.settings),
                conditions: JSON.stringify(this.funnel.conditions),
                funnel_title: this.funnel.title,
                status: this.funnel.status,
                sequences: JSON.stringify(this.funnel_sequences),
                is_fluentcrm: 'yes'
            })
                .then(response => {
                    if (!callback) {
                        this.$set(this, 'funnel_sequences', response.sequences);
                        this.$notify.success(response.message);
                        this.show_blocK_editor = false;
                        this.current_block = false;
                        this.current_block_index = false;
                    } else {
                        callback(response);
                    }
                })
                .catch(error => {
                    console.log(error);
                    this.handleError(error);
                })
                .always(() => {
                    if (!callback) {
                        this.working = false;
                    }
                });
        },
        getStripedSequences() {
            const sequences = JSON.parse(JSON.stringify(this.funnel_sequences));

            const strippedSequences = this.stripSequenceSets(sequences);

            return JSON.stringify(strippedSequences);
        },
        stripSequenceSets(sequences) {
            this.each(sequences, (sequence) => {
                if (sequence.action_name == 'send_custom_email') {
                    sequence = this.stripEmailSequence(sequence);
                } else if (sequence.action_name == 'funnel_condition') {
                    sequence.children.no = this.stripSequenceSets(sequence.children.no);
                    sequence.children.yes = this.stripSequenceSets(sequence.children.yes);
                }
            });

            return sequences;
        },
        stripEmailSequence(sequence) {
            if (sequence.settings.reference_campaign && sequence.settings.reference_campaign == sequence.settings.campaign.id) {
                sequence.settings.campaign = {
                    id: sequence.settings.campaign.id
                };
                sequence.is_stripped = true;
            }
            return sequence;
        },
        saveFunnelBlockSequence() {
            this.$set(this.funnel_sequences, this.current_block_index, this.current_block);
            this.$nextTick(() => {
                this.saveFunnelSequences(false);
            });
        },
        moveToPosition(type, fromIndex) {
            let toIndex = fromIndex - 1;
            if (type === 'down') {
                toIndex = fromIndex + 1;
            }
            const funnelSequences = this.funnel_sequences;
            const element = funnelSequences[fromIndex];
            funnelSequences.splice(fromIndex, 1);
            funnelSequences.splice(toIndex, 0, element);
            this.$set(this, 'funnel_sequences', funnelSequences);
            this.saveFunnelSequences();
        },
        getBlockDescription(block) {
            let description = '';
            switch (block.action_name) {
                case 'send_custom_email':
                    if (!this.isEmptyValue(block.settings.campaign.email_subject)) {
                        return block.settings.campaign.email_subject;
                    }
                    return '<span class="funnel_trigger_inst">' + this.$t('Set Email Subject & Body') + '</span>';
                case 'fluentcrm_wait_times':
                    if (block.settings.wait_type == 'timestamp_wait') {
                        description = this.$t('Wait until ') + block.settings.wait_date_time;
                    } else if (block.settings.wait_type == 'to_day') {
                        description = this.$t('Wait until next ') + block.settings.to_day.join(' / ') + ' - ' + block.settings.to_day_time;
                    } else if (block.settings.wait_type == 'by_custom_field') {
                        if (!block.settings.by_custom_field) {
                            return this.$t('Set Custom Field');
                        }
                        description = this.$t('Wait by custom field ') + block.settings.by_custom_field;
                    } else {
                        description = this.$t('Wait ') + block.settings.wait_time_amount + ' ' + block.settings.wait_time_unit;
                    }
                    break;
                case 'add_contact_to_company':
                case 'detach_contact_from_company':
                case 'fluentcrm_contact_added_to_companies':
                case 'fluentcrm_contact_removed_from_companies':
                case 'fcrm_has_contact_company':
                    description = '<span class="funnel_trigger_inst">Set Companies</span>';
                    if (block.settings.company) {
                        this.each(this.options.companies, (company) => {
                            if (company.id == block.settings.company) {
                                description = company.title;
                            }
                        });
                    }
                    break;
                case 'add_contact_to_list':
                case 'detach_contact_from_list':
                case 'fluentcrm_contact_added_to_lists':
                case 'fluentcrm_contact_removed_from_lists':
                case 'fcrm_has_contact_list':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set Lists') + '</span>';
                    if (!this.isEmptyValue(block.settings.lists)) {
                        const targetLists = [];
                        this.each(this.options.lists, (list) => {
                            if (block.settings.lists.indexOf(list.id.toString()) !== -1) {
                                targetLists.push(list.title);
                            }
                        });
                        description = targetLists.join(', ');
                    }
                    break;
                case 'add_contact_to_tag':
                case 'detach_contact_from_tag':
                case 'fluentcrm_contact_added_to_tags':
                case 'fluentcrm_contact_removed_from_tags':
                case 'fcrm_has_contact_tag':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set Tags') + '</span>';
                    if (!this.isEmptyValue(block.settings.tags)) {
                        description = this.getTagNames(block.settings.tags);
                    }
                    break;
                case 'send_campaign_email':
                    description = '<span class="funnel_trigger_inst">Set Campaign</span>';
                    if (block.settings.campaign_id) {
                        this.each(this.options.campaigns, (campaign) => {
                            if (campaign.id == block.settings.campaign_id) {
                                description = campaign.title;
                            }
                        });
                    }
                    break;
                case 'add_to_email_sequence':
                    if (!this.isEmptyValue(block.settings.sequence_id)) {
                        description = this.$t('Add To Sequence: ');
                        const sequenceEmails = [];
                        this.each(this.options.email_sequences, (tag) => {
                            if (block.settings.sequence_id === tag.id) {
                                sequenceEmails.push(tag.title);
                            }
                        });
                        description = sequenceEmails.join(', ');
                    } else {
                        description = '<span class="funnel_trigger_inst">' + this.$t('Set Email Sequence') + '</span>';
                    }
                    break;
                case 'fluentcrm_email_sequence_completed':
                    if (!this.isEmptyValue(block.settings.sequence_ids)) {
                        description = block.description;
                    } else {
                        description = '<span class="funnel_trigger_inst">' + this.$t('Set Email Sequence') + '</span>';
                    }
                    break;
                case 'funnel_condition':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set Condition') + '</span>';
                    if (!this.isEmptyValue(block.settings.conditions) && !this.isEmptyValue(block.settings.conditions[0])) {
                        description = this.$t('Matching ') + block.settings.conditions.length + ' condition sets';
                    }
                    break;
                case 'add_contact_activity':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set Note Title') + '</span>';
                    if (!this.isEmptyValue(block.settings.title)) {
                        description = block.settings.title;
                    }
                    break;
                case 'update_contact_property':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set Property') + '</span>';
                    if (!this.isEmptyValue(block.settings.contact_properties) && !this.isEmptyValue(block.settings.contact_properties[0].data_key)) {
                        description = this.$t('Updating ') + block.settings.contact_properties.length + ' properties';
                    }
                    break;
                case 'http_send_data':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set Webhook URL') + '</span>';
                    if (!this.isEmptyValue(block.settings.remote_url)) {
                        description = this.$t('Send HTTP ') + block.settings.sending_method + ' webhook';
                    }
                    break;
                case 'fcrm_change_user_role':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Set User Role') + '</span>';
                    if (!this.isEmptyValue(block.settings.user_role)) {
                        description = this.$t('Change User Role to ') + block.settings.user_role;
                    }
                    break;
                case 'remove_user_role':
                    description = '<span class="funnel_trigger_inst">' + this.$t('Remove User Role') + '</span>';
                    if (!this.isEmptyValue(block.settings.role)) {
                        description = this.$t('Remove User Role: ') + block.settings.role;
                    }
                    break;
                default:
                    description = block.description
            }

            return description || block.description;
        },
        getTriggerDescription(funnel) {
            let description = '';
            switch (funnel.trigger_name) {
                case 'fluentcrm_contact_added_to_companies':
                case 'fluentcrm_contact_removed_from_companies':
                    if (!this.isEmptyValue(funnel.settings.companies)) {
                        const targetCompanies = [];
                        this.each(this.options.companies, (company) => {
                            if (funnel.settings.companies.indexOf(company.id.toString()) != -1) {
                                targetCompanies.push(company.title);
                            }
                        });
                        description = targetCompanies.join(', ');
                    } else {
                        return '<span class="funnel_trigger_inst">Set Company</span>';
                    }
                    break;
                case 'fluentcrm_contact_added_to_tags':
                case 'fluentcrm_contact_removed_from_tags':
                    if (!this.isEmptyValue(funnel.settings.tags)) {
                        const targetTags = [];
                        this.each(this.options.tags, (tag) => {
                            if (funnel.settings.tags.indexOf(tag.id.toString()) != -1) {
                                targetTags.push(tag.title);
                            }
                        });
                        description = targetTags.join(', ');
                    } else {
                        return '<span class="funnel_trigger_inst">' + this.$t('Set Tag') + '</span>';
                    }
                    break;
                case 'fluentcrm_contact_added_to_lists':
                case 'fluentcrm_contact_removed_from_lists':
                    if (!this.isEmptyValue(funnel.settings.lists)) {
                        const targetLists = [];
                        this.each(this.options.lists, (list) => {
                            if (funnel.settings.lists.indexOf(list.id.toString()) !== -1) {
                                targetLists.push(list.title);
                            }
                        });
                        description = targetLists.join(', ');
                    } else {
                        return '<span class="funnel_trigger_inst">' + this.$t('Set Lists') + '</span>';
                    }
                    break;
                default:
                    description = funnel.trigger.label
            }

            return description || funnel.trigger.label;
        },
        getTagNames(tagIds) {
            const addedTags = [];
            this.each(this.options.tags, (tag) => {
                if (tagIds.indexOf(tag.id.toString()) != -1) {
                    addedTags.push(tag.title);
                }
            });
            return addedTags.join(', ');
        },
        saveAndFetchSettings() {
            this.$nextTick(() => {
                this.saveFunnelSequences(false, (response) => {
                    this.fetchFunnel();
                });
            });
        },
        gotoReports() {
            this.$router.push({
                name: 'funnel_subscribers',
                params: {
                    funnel_id: this.funnel_id
                }
            });
        },
        getBlockIcon(block) {
            const actionName = block.action_name;
            if (this.blocks[actionName] && this.blocks[actionName].icon) {
                return this.blocks[actionName].icon;
            }
            return '';
        },
        getStats() {
            this.$get(`funnels/${this.funnel_id}/report`)
                .then(response => {
                    const stats = response.stats.metrics;
                    const formattedStats = {};
                    this.each(stats, (stat) => {
                        formattedStats[stat.sequence_id] = stat;
                    });
                    this.stats = formattedStats;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                });
        },
        getBlockClasses(block, blockIndex) {
            const blockClasses = [
                'fc_block_type_' + block?.type,
                'fluentcrm_block_' + block?.action_name
            ];
            if (block?.settings?.type && block?.settings?.type == 'required') {
                blockClasses.push('fc_funnel_benchmark_required');
            }
            if (this.current_block_index === blockIndex) {
                blockClasses.push('fluentcrm_block_active');
            }

            return blockClasses;
        },
        showEditTriggerModal() {
            this.show_trigger_changer = true;
        },
        handleTriggerChanged() {
            this.show_trigger_changer = false;
            this.is_new_funnel = true;
            this.fetchFunnel();
        },
        handleConditionAdd(index) {
            if (!this.has_campaign_pro) {
                this.$alert('<p><strong>This block require pro version of FluentCRM</strong></p><p style="line-height: 22px; margin-bottom: 15px !important;">Please download and install FluentCRM Pro to activate this block</p><p><a class="el-button el-button--danger" :href="' + this.appVars.crm_pro_url + '" target="_blank" rel="noopener">Get FluentCRM Pro</a></p>', 'Require FluentCRM Pro', {
                    dangerouslyUseHTMLString: true,
                    showConfirmButton: false
                });
                return false;
            }

            if (!this.blocks.funnel_condition) {
                this.$notify.error(this.$t('Condition block is not available'));
                return false;
            }

            this.choice_modal_index = index;
            this.addBlock('funnel_condition');
        },
        fireBeforeClose(done = false) {
            if (this.current_block && this.current_block.action_name == 'send_custom_email') {
                this.unmountBlockEditor();
            }
            if (done) {
                done();
            }
        },
        funnelRootSaved() {
            if (this.$route.query.is_new == 'yes') {
                this.$router.replace({query: null});
            }
        },
        getTriggerIcon(triggerName) {
            const triggerIcons = {
                woocommerce_order_status_completed: 'fc-icon-woo_order_complete',
                woocommerce_order_status_processing: 'fc-icon-woo_new_order',
                woocommerce_order_status_refunded: 'fc-icon-woo_refund',
                woocommerce_order_status_changed: 'fc-icon-woo',
                woocommerce_subscription_status_active: 'fc-icon-woo_order_complete',
                woocommerce_subscription_renewal_payment_complete: 'fc-icon-woo_order_complete',
                woocommerce_subscription_renewal_payment_failed: 'fc-icon-woo_refund',
                wishlistmember_add_user_levels: 'fc-icon-wishlist',
                tutor_after_enrolled: 'fc-icon-tutor_lms_enrollment_course',
                tutor_course_complete_after: 'fc-icon-tutor_lms_complete_course',
                tutor_lesson_completed_after: 'fc-icon-tutor_lms_complete_course',
                rcp_membership_post_activate: 'fc-icon-rcp_membership_level',
                rcp_transition_membership_status_expired: 'fc-icon-rcp_membership_cancle',
                rcp_membership_post_cancel: 'fc-icon-rcp_membership_cancle',
                pmpro_after_change_membership_level: 'fc-icon-paid_membership_pro_user_level',
                pmpro_membership_post_membership_expiry: 'fc-icon-membership_level_ex_pmp',
                'mepr-account-is-active': 'fc-icon-memberpress_membership',
                'mepr-event-transaction-expired': 'el-icon-circle-close',
                llms_user_enrolled_in_course: 'fc-icon-lifter_lms_course_enrollment',
                lifterlms_course_completed: 'fc-icon-lifter_lms_complete_course',
                llms_user_added_to_membership_level: 'fc-icon-lifter_lms_membership',
                lifterlms_lesson_completed: 'fc-icon-lifter_lms_complete_lession-t2',
                learndash_update_course_access: 'fc-icon-learndash_enroll_course',
                learndash_lesson_completed: 'fc-icon-learndash_complete_lesson',
                learndash_topic_completed: 'fc-icon-learndash_complete_topic',
                learndash_course_completed: 'fc-icon-learndash_complete_course',
                ld_added_group_access: 'fc-icon-learndash_course_group',
                simulated_learndash_update_course_removed: 'fc-icon-learndash_enroll_course',
                fc_ab_cart_simulation_woo: 'fc-icon-woo',
                fluentcrm_contact_birthday: 'el-icon-present',
                user_register: 'fc-icon-wp_new_user_signup',
                fluentform_submission_inserted: 'fc-icon-fluentforms',
                fluentcrm_contact_added_to_lists: 'fc-icon-list_applied_2',
                edd_update_payment_status: 'fc-icon-edd_new_order_success',
                edd_recurring_add_subscription_payment: 'fc-icon-edd_new_order_success',
                edd_subscription_status_change: 'el-icon-circle-close',
                affwp_set_affiliate_status: 'fc-icon-trigger',
                fluent_surecart_purchase_created_wrap: 'el-icon-shopping-cart-full',
                fluent_surecart_purchase_refund_wrap: 'el-icon-sold-out'
            };

            return triggerIcons[triggerName] || 'fc-icon-trigger';
        },
        initKeyboardSave(e) {
            if ((window.navigator.platform.match('Mac') ? e.metaKey : e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.saveFunnelSequences(false, (response) => {
                    this.working = false;
                    this.$set(this, 'funnel_sequences', response.sequences);
                    this.$notify.success(response.message);
                });
                this.funnelRootSaved();
            }
        },
        updateAutomationSettings() {
            this.updatingFunnelSettings = true;
            this.$put(`funnels/funnel/${this.funnel.id}/title`, {
                title: this.funnel.title
            })
                .then(response => {
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updatingFunnelSettings = false;
                    this.showInlineEditFunnelTitle = false;
                });
        }
    },
    mounted() {
        this.fetchFunnel();
        this.getStats();
        this.changeTitle(this.$t('Edit Funnel'));
        document.addEventListener('keydown', this.initKeyboardSave);
    },
    beforeDestroy() {
        window.fcrm_funnel_context_codes = undefined;
        document.removeEventListener('keydown', this.initKeyboardSave);
    }
}
</script>
