<template>
    <el-dialog
        :title="$t('_Cr_Create_aAF')"
        :visible.sync="dialogVisible"
        :close-on-click-modal="false"
        :append-to-body="true"
        class="fc_trigger_dialog_wrap"
        width="60%"
    >
        <div v-if="showCreateNewAutomations">
            <div>
                <div class="fcrm_back_to_template">
                    <h3 @click="showCreateNewAutomations = false">
                        <span class="icon">
                            <i class="el-icon-back"></i>
                            <i class="el-icon-back"></i>
                        </span>
                        {{ $t('Back To Templates') }}
                    </h3>
                </div>
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
                                            <h3>
                                                <span v-if="trigger.custom_icon"  v-html="getFunnelIcon(trigger.custom_icon)"></span>
                                                <i v-else :class="trigger.icon ? trigger.icon : 'fc-icon-trigger'"></i>
                                                {{ trigger.label }}

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
        </div>

        <div v-else>
            <div class="fc_a_cat_selection_wrapper">
                <el-row :gutter="20">
                    <el-col :md="24" :xs="12" class="fc_trigger_right_col">
                        <div class="fc_choice_header">
                            <h2 class="fc_choice_title">
                                {{ $t('Popular pre-built funnel templates') }}</h2>
                        </div>
                        <el-row class="fc_choice_row" v-if="selected_category" :gutter="20">
                            <el-col class="fc_choice_block" :md="6" :sm="24" :xs="24">
                                <div class="fc_choice_card_create_from_scratch fc_choice_card" @click="showCreateNewAutomations = true">
                                    <h3><i class="el-icon-plus"></i>{{ $t('Create from Scratch') }}</h3>
                                </div>
                            </el-col>

                            <template v-if="templatesLoading" class="fc_choice_row">
                                <el-col v-for="loadingCol in 3" :key="loadingCol" class="fc_choice_block" :md="6" :sm="24" :xs="24">
                                    <el-skeleton animated :rows="3" />
                                </el-col>
                            </template>
                            <el-col v-else class="fc_choice_block" v-for="(template,templateKey) in templates"
                                    :key="templateKey" :md="6" :sm="24" :xs="24">
                                <div class="fc_choice_card">
                                    <h3>
                                        <i :class="getTriggerIcon(template?.funnel_data?.trigger_name)"></i>
                                        {{ template.title }}
                                    </h3>
                                    <p v-html="template.short_description"></p>

                                    <div class="fc_choice_card_overlay">
                                        <el-button size="small" type="primary" @click="createFromTemplate(template)">{{ $t('Import') }}</el-button>
                                        <a class="preview-btn" size="small" :href="template.link" target="_blank">{{ $t('Preview') }}</a>
                                    </div>
                                    
                                    <div class="fc_pro_ribbon" v-if="visibleProRibon(template)">{{ $t('Pro') }}</div>
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
        </div>
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
            templates: [],
            templatesLoading: false,
            showCreateNewAutomations: false,
            dialogVisible: this.visible,
            selected_category: this.$t('CRM'),
            allowed_categories: []
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
            if (icon && category == 'voxel') {
                return icon;
            }
            if (icon && category == 'fluentcart') {
                return icon;
            }
            if (icon) {
                return '<i class="' + icon + '"></i>'
            }
            return ' <i class="el-icon el-icon-finished"></i>'
        },
        getFunnelIcon(iconName) {
            const icon = this.appVars.funnel_cat_icons[iconName];
            return icon;
        },
        createFromTemplate(template) {
            if (!this.checkImportable(template)) {
                return; // Stop execution if template is not importable
            }

            this.$post('funnels/create-from-template', {
                template: template
            }).then(response => {
                this.$notify.success(response.message)
                this.$router.push({
                    name: 'edit_funnel',
                    params: {
                        funnel_id: response.funnel.id
                    },
                    query: {
                        is_template: 'yes'
                    }
                })
            }).catch((error) => {
                this.handleError(error)
            })
        },
        checkImportable(template) {
            if (this.visibleProRibon(template)) {
                this.$notify.error(this.$t('This template requires FluentCRM Pro version. Please upgrade to Pro.'));
                return false;
            }

            // Check if template dependencies are available in allowed_categories array
            if (template.dependencies && template.dependencies.length > 0) {
                const missingDependencies = template.dependencies.filter(dep => {
                    // Skip fluentcrm_pro as it's handled above
                    if (dep === 'fluentcrm_pro') {
                        return false;
                    }
                    // Check if the dependency is in allowed categories
                    return !this.allowed_categories.includes(dep);
                });

                if (missingDependencies.length > 0) {
                    const dependencyLabels = missingDependencies.map(dep => this.getDependencyLabel(dep));
                    this.$notify.error({
                        title: this.$t('Missing Dependencies'),
                        message: this.$t('This template requires the following dependencies to be activated: ') + dependencyLabels.join(', '),
                        duration: 6000
                    });
                    return false;
                }
            }

            return true;
        },
        getTemplates() {
            this.templatesLoading = true;
            this.$get('funnels/templates').then(response => {
                this.templates = response.all;
                this.allowed_categories = response.cats;
            }).catch((error) => {
                this.handleError(error)
            }).finally(() => {
                this.templatesLoading = false;
            });
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

        /**
         * Check if template requires Pro version
         */
        visibleProRibon(template) {
            const isProInstalled = window.fcAdmin.addons.fluentcampaign;

            // First check if dependencies include fluentcrm_pro
            const isProDependency = template.dependencies && template.dependencies.includes('fluentcrm_pro');

            // if  prodependency is present and plugin is not installed, show Pro ribbon
            if (isProInstalled) {
                return false; // If Fluent Campaign is installed, we do not show Pro ribbon
            } else {
                return isProDependency; // Show Pro ribbon if the template requires Pro version
            }
        },

        /**
         * Get human-readable label for dependency
         * I will refactor this one , this should be consistent with FunnelController -> allowedCategories() Method
         */
        getDependencyLabel(dependency) {
            const labels = {
               fluentforms: 'Fluent Forms',
               memberpress: 'MemberPress',
               'fluent-boards': 'Fluent Boards',
               'fluent-support': 'Fluent Support',
               'fluent-booking': 'Fluent Booking',
               woocommerce: 'WooCommerce',
               wcs: 'WooCommerce Subscriptions',
               edd: 'Easy Digital Downloads',
               lifterlms: 'LifterLMS',
               tutor: 'Tutor LMS',
               learndash: 'LearnDash',
               surecart: 'SureCart',
               woo_abandon_carts: 'WooCommerce Abandoned Cart',
               fluentcrm_pro: 'FluentCRM Pro'
            };
            
            return labels[dependency] || dependency.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    },
    mounted() {
        this.getTemplates();
    }
}
</script>
