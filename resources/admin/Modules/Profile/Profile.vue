<template>
    <div :class="{ fc_side_closed: hasSidebar && !showSidebar, fc_side_opened: showSidebar  }" style="position: relative">
        <el-row :gutter="30">
            <el-col class="fc_contact_main_col" :xs="24" :sm="showSidebar ? 18 : 24" :md="showSidebar ? 18 : 24">
                <div class="fluentcrm-contact-view fluentcrm-view-wrapper fluentcrm_view">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                                <el-breadcrumb-item :to="{ name: 'subscribers' }">
                                    {{ $t('Contacts') }}
                                </el-breadcrumb-item>
                                <el-breadcrumb-item>
                                    {{ name }}
                                </el-breadcrumb-item>
                            </el-breadcrumb>
                        </div>
                        <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                            <el-dropdown trigger="click">
                                <span class="el-dropdown-link">
                                    <i style="font-weight: bold; cursor: pointer;" class="el-icon-more icon-90degree el-icon--right"></i>
                                </span>
                                <el-dropdown-menu slot="dropdown">
                                    <el-dropdown-item class="fc_dropdown_action">
                                        <confirm placement="top-start" :message="$t('Contact_Delete_Alert')"
                                                 @yes="deleteContact()">
                                            <span slot="reference">{{ $t('Delete Contact') }}</span>
                                        </confirm>
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </el-dropdown>
                        </div>
                    </div>
                    <div style="padding: 20px;" class="fluentcrm_body fluentcrm-profile">
                        <el-skeleton v-if="loading" class="" animated>
                            <template slot="template">
                                <el-row :gutter="30">
                                    <el-col :span="3">
                                        <el-skeleton-item variant="circle" style="width: 145px; height: 145px;"/>
                                    </el-col>
                                    <el-col :span="20">
                                        <el-skeleton/>
                                    </el-col>
                                </el-row>
                            </template>
                        </el-skeleton>
                        <profile-header v-else-if="subscriber" @updateSubscriber="setup" @fetch="fetch" :subscriber="subscriber"/>
                    </div>
                    <div class="fluentcrm-profile">
                        <ul class="fluentcrm_profile_nav">
                            <router-link @click.native="maybeCustomHandler(item)" v-for="(item,itemIdex) in profile_parts"
                                         :key="item.name + itemIdex"
                                         custom v-slot="{ navigate }"
                                         :to="{ name: item.name, hash: '#fluentcrm_sub_info_body', params: { id: id }, query: item.query }">
                                <li :class="{item_active: $route.name == item.name && (!item.query || $route.query.handler == item.query.handler) }" @click="navigate" v-html="item.title"></li>
                            </router-link>
                        </ul>
                        <div v-if="subscriber && show_profile" class="fluentcrm_sub_info_body">
                            <transition>
                                <router-view
                                    @updateSubscriber="setup"
                                    key="user_profile_route"
                                    :custom_fields="custom_fields"
                                    :subscriber="subscriber"
                                    :subscriber_id="id"></router-view>
                            </transition>
                        </div>
                        <el-skeleton style="margin-top: 20px;" v-else class="fc_skeleton_loader" animated>
                            <template slot="template">
                                <el-row :gutter="30">
                                    <el-col :span="12">
                                        <el-skeleton :rows="10"/>
                                    </el-col>
                                    <el-col :span="12">
                                        <el-skeleton :rows="3"/>
                                    </el-col>
                                </el-row>
                            </template>
                        </el-skeleton>
                    </div>
                </div>
            </el-col>
            <el-col class="fc_sidebar_col" v-show="showSidebar" :xs="24" :sm="6" :md="6">
                <div class="fc_abs_sidebar_opened">
                    <el-button @click="toggleSidebar()" type="info" size="small" class="fc_sidebar_open_btn">
                        <i class="el-icon-arrow-right"></i>
                    </el-button>
                </div>
                <over-view-sidebar @widgetsFetched="sidebarLoaded" :subscriber="subscriber" :subscriber_id="id" v-show="show_profile"/>
                <div v-if="loading">
                    <el-skeleton class="fc_skeleton_loader" style="margin-bottom: 20px;" animated></el-skeleton>
                    <el-skeleton class="fc_skeleton_loader" animated></el-skeleton>
                </div>
            </el-col>
        </el-row>

        <div class="fc_abs_sidebar" v-if="hasSidebar && !showSidebar">
            <el-badge :value="widgetTickerCount">
                <el-button @click="toggleSidebar()"  type="info" size="small" class="fc_sidebar_open_btn">
                    <i class="el-icon-arrow-left"></i>
                </el-button>
            </el-badge>
        </div>
    </div>

</template>

<script type="text/babel">
import ProfileHeader from './ProfileHeader';
import Confirm from '@/Pieces/Confirm';
import OverViewSidebar from './Parts/_OverviewSidebar';

export default {
    name: 'Profile',
    components: {
        ProfileHeader,
        Confirm,
        OverViewSidebar
    },
    props: ['id'],
    data() {
        return {
            subscriber: false,
            loading: false,
            profile_parts: window.fcAdmin.profile_sections,
            custom_fields: [],
            subscriber_meta: {},
            show_profile: true,
            doing_action: false,
            sidebarOpen: 'yes',
            widgetsCount: 0,
            hasSidebar: true
        }
    },
    watch: {
        id() {
            this.fetch();
        }
    },
    computed: {
        lists() {
            return this.subscriber.lists.map(list => list.title);
        },
        name() {
            if (this.subscriber.first_name || this.subscriber.last_name) {
                return `${this.subscriber.first_name || ''} ${this.subscriber.last_name || ''}`;
            }
            return this.subscriber.email;
        },
        showSidebar() {
            return this.hasSidebar && this.sidebarOpen == 'yes';
        },
        widgetTickerCount() {
            if (this.has_company_module && this.subscriber && this.subscriber.companies && this.subscriber.companies.length) {
                return this.widgetsCount + 1;
            }
            return this.widgetsCount;
        }
    },
    methods: {
        setup(item) {
            const customValues = item?.custom_values;
            if (customValues) {
                Object.keys(customValues).forEach(key => {
                    const option = customValues[key];
                    if (option) {
                        const fieldType = this.custom_fields.find(field => field.slug === key);
                        if (fieldType && (fieldType.type === 'select-multi' || fieldType.type === 'checkbox') && typeof option == 'string') {
                            customValues[key] = option.split(', ');
                        }
                    }
                });

                item.custom_values = customValues;
            }

            this.subscriber = item;
            if (!this.show_profile) {
                this.show_profile = true;
            }

            const revisedParts = this.applyFilters('fluentcrm_profile_sections', window.fcAdmin.profile_sections, item);
            if (Object.keys(revisedParts).length !== Object.keys(this.profile_parts).length) {
                this.profile_parts = revisedParts;
            }
            this.changeTitle(item.full_name + ' - Contact');

            this.doAction('fluent_crm_subscriber_loaded', this);
        },
        fetch() {
            this.loading = true;
            this.show_profile = false;
            this.$get(`subscribers/${this.id}`, {
                with: ['stats', 'custom_fields', 'subscriber.custom_values']
            }).then(response => {
                this.custom_fields = response.custom_fields;
                this.setup(response.subscriber);
            })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        sidebarLoaded(count) {
            this.widgetsCount = count;
        },
        maybeCustomHandler(item) {
            if (item.name != 'fluentcrm_profile_extended') {
                return;
            }
            this.show_profile = false;
            setTimeout(() => {
                this.show_profile = true;
            }, 100);
        },
        deleteContact() {
            this.loading = true;
            this.$del(`subscribers/${this.id}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({name: 'subscribers'})
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        toggleSidebar() {
            this.sidebarOpen = this.sidebarOpen == 'yes' ? 'no' : 'yes';
            this.storage.set('fc_profile_sidebar', this.sidebarOpen);
        }
    },

    mounted() {
        this.fetch();
        this.changeTitle(this.$t('Profile'));
        this.sidebarOpen = this.storage.get('fc_profile_sidebar', 'yes') == 'no' ? 'no' : 'yes';

        this.$bus.$on('contact_custom_fields_updated', (fields) => {
            this.custom_fields = fields;
        });
    },
    beforeDestroy() {
        this.doAction('fluent_crm_leaving_profile', this.subscriber);
    }
};
</script>
