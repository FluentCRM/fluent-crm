<template>
    <el-dialog
        :title="$t('_Cr_Create_aAF')"
        :visible.sync="dialogVisible"
        :close-on-click-modal="false"
        :append-to-body="true"
        class="fc_trigger_dialog_wrap"
        width="60%"
    >
        <div>
            <el-form @submit.prevent.native="saveFunnel" :data="funnel" label-position="top">
                <el-form-item :label="$t('Internal Label')">
                    <el-input :placeholder="$t('Internal Label')" v-model="funnel.title"></el-input>
                </el-form-item>

                <div class="fc_a_cat_selection_wrapper">
                    <el-row :gutter="20">
                        <el-col :md="6" :xs="12" class="fc_trigger_left_col">
                            <el-menu
                                background-color="#f2f2f2"
                                text-color="#1e1f21"
                                active-text-color="#ffd04b"
                                @select="(item) => { selected_category = item; funnel.trigger_name = '' }"
                                class="fc_trigger_selectors"
                                :default-active="selected_category"
                            >
                                <el-menu-item v-for="category in funnel_categories" :key="category"
                                              :index="category">
                                    <span class="fc_trigger_icon" v-html="getCatIcon(category)"></span>
                                    <span>{{ category }}</span>
                                </el-menu-item>
                            </el-menu>
                        </el-col>
                        <el-col :md="18" :xs="12" class="fc_trigger_right_col">
                            <div class="fc_choice_header">
                                <h2 class="fc_choice_title">
                                    {{ $t('Select the trigger for this automation') }}</h2>
                            </div>
                            <el-row class="fc_choice_row" v-if="selected_category" :gutter="20">
                                <el-col class="fc_choice_block" v-for="(trigger,triggerKey) in funnelTriggers"
                                        :key="triggerKey" :md="8" :sm="24" :xs="24">
                                    <div @click="funnelActiveHandle(triggerKey)"
                                         :class="{fc_trigger_selected: funnel.trigger_name == triggerKey}"
                                         class="fc_choice_card">
                                        <div class="fc_pro_ribbon" v-if="trigger.disabled">{{ $t('Pro') }}</div>
                                        <div class="fc_pro_ribbon" v-else-if="trigger.ribbon">{{
                                                trigger.ribbon
                                            }}
                                        </div>
                                        <h3><i
                                            :class="trigger.icon ? trigger.icon : 'fc-icon-trigger'"></i>{{ trigger.label }}
                                        </h3>
                                        <p v-html="trigger.description"></p>
                                    </div>
                                </el-col>
                            </el-row>
                            <div v-if="funnel.trigger_name && triggers[funnel.trigger_name].disabled"
                                 style="background: whitesmoke;padding: 20px 10px; margin: 0;text-align: center;display: block;overflow: hidden;"
                                 class="promo_block">
                                <p>{{ $t('install_fluentcrm_pro') }}</p>
                                <a class="el-button el-button--danger" :href="appVars.crm_pro_url"
                                   target="_blank" rel="noopener">
                                    {{ $t('Get FluentCRM Pro') }}
                                </a>
                            </div>
                        </el-col>
                    </el-row>
                </div>
            </el-form>
        </div>
        <span v-if="funnel.trigger_name && !triggers[funnel.trigger_name].disabled" slot="footer"
              class="dialog-footer">
            <el-button type="primary" @click="saveFunnel()">
                {{ $t('Continue') }}
            </el-button>
        </span>
    </el-dialog>
</template>
<script type="text/babel">
export default {
    name: 'CreateFunnelModal',
    props: ['visible', 'triggers'],
    data() {
        return {
            saving: false,
            funnel: {
                title: '',
                trigger_name: ''
            },
            activeName: 'templates',
            dialogVisible: this.visible,
            selected_category: this.$t('CRM')
        }
    },
    watch: {
        dialogVisible() {
            this.$emit('close')
        }
    },
    computed: {
        funnel_categories() {
            const categories = []
            this.each(this.triggers, (trigger) => {
                if (categories.indexOf(trigger.category) === -1) {
                    categories.push(trigger.category)
                }
            })
            return categories.sort()
        },
        funnelTriggers() {
            if (!this.selected_category) {
                return []
            }
            const triggers = {}
            this.each(this.triggers, (trigger, triggerKey) => {
                if (trigger.category === this.selected_category) {
                    triggers[triggerKey] = trigger
                }
            })
            return triggers
        }
    },
    methods: {
        funnelActiveHandle(triggerKey) {
            if (this.funnel.trigger_name && this.funnel.trigger_name == triggerKey) {
                this.funnel.trigger_name = ''
                return
            }
            this.funnel.trigger_name = triggerKey
        },
        saveFunnel() {
            this.saving = true
            this.$post('funnels', {
                funnel: this.funnel
            }).then(response => {
                this.$notify.success(response.message)
                this.$router.push({
                    name: 'edit_funnel',
                    params: {
                        funnel_id: response.funnel.id
                    },
                    query: {
                        is_new: 'yes'
                    }
                })
            }).catch((error) => {
                this.handleError(error)
            }).finally(() => {
                this.saving = false
            })
        },
        getCatIcon(category) {
            category = category.replaceAll(' ', '').toLowerCase()
            const icon = this.appVars.funnel_cat_icons[category]
            if (icon) {
                return '<i class="' + icon + '"></i>'
            }
            return ' <i class="el-icon el-icon-finished"></i>'
        }
    }
}
</script>
