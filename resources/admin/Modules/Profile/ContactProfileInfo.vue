<template>
    <div class="fluentcrm_profile_header">
        <div class="fluentcrm_profile-photo">
            <div
                :class="photo_holder"
                :style="{ backgroundImage: 'url(' + subscriber.photo + ')'}"
            ></div>
            <photo-widget
                class="fc_photo_changed"
                btn_type="default"
                :btn_text="$t('+ Photo')"
                :btn_mode="true"
                @changed="updateAvatar"
                v-model="subscriber.photo"
            />
            <el-button size="small" type="danger" v-if="subscriber.avatar" class="fc_photo_remove" @click="removeAvatar">
                {{ $t('Remove') }}
            </el-button>
        </div>
        <div class="profile-info">
            <div class="profile_title">
                <slot name="heading" />
                <h3>{{ name }}</h3>
                <div class="profile_action">
                    <el-popover
                        :placement="(!!appVars.is_rtl) ? 'left' : 'right'"
                        width="360"
                        v-model="lead_visible"
                        popper-class="fluentcrm_profile_action_popover">
                        <div style="padding: 10px;" class="fluentcrm_type_change_wrapper">
                            <el-select :placeholder="$t('Select Status')" size="mini"
                                       v-model="subscriber.contact_type">
                                <el-option v-for="contact_type in contact_types" :key="contact_type.id"
                                           :value="contact_type.id" :label="contact_type.title"></el-option>
                            </el-select>
                        </div>
                        <div style="margin: 0">
                            <el-button @click="saveLead()" type="success" size="mini">
                                {{$t('Change Contact Type')}}
                            </el-button>
                        </div>
                        <el-tag slot="reference" size="mini">{{ trans(subscriber.contact_type) | ucFirst }}<span
                            class="el-icon el-icon-caret-bottom"></span></el-tag>
                    </el-popover>
                </div>
                <div class="profile_action">
                    <el-popover
                        :placement="(!!appVars.is_rtl) ? 'left' : 'right'"
                        width="360"
                        v-model="status_visible"
                        popper-class="fluentcrm_profile_action_popover">
                        <div style="padding: 10px;" class="fluentcrm_status_change_wrapper">
                            <el-select :placeholder="$t('Select Status')" size="mini" v-model="subscriber.status">
                                <el-option v-for="status in subscriber_statuses" :key="status.id"
                                           :value="status.id" :label="status.title"></el-option>
                            </el-select>
                        </div>
                        <div style="margin: 0">
                            <el-button v-if="hasPermission('fcrm_manage_contacts')" @click="saveStatus()" type="success" size="mini">
                                {{$t('Pro_Change_SS')}}
                            </el-button>
                        </div>
                        <el-tag slot="reference" size="mini">{{ trans(subscriber.status) | ucFirst }}<span
                            class="el-icon el-icon-caret-bottom"></span></el-tag>
                    </el-popover>
                </div>
            </div>
            <p class="fc_profile_meta show_on_parent">
                {{ subscriber.email }}
                <span title="WordPress User ID" v-if="subscriber.user_id && subscriber.user_edit_url"><span class="fc_middot">·</span>
                    <a target="_blank" rel="noopener" :href="subscriber.user_edit_url">{{ subscriber.user_id }} <span class="dashicons dashicons-external"></span></a>
                </span>
            </p>
            <p>{{$t('Added')}} {{ subscriber.created_at | nsHumanDiffTime }} <span v-if="subscriber.last_activity"> & {{$t('Last Activity')}} {{
                    subscriber.last_activity | nsHumanDiffTime
                }}</span></p>
            <div class="stats_badges">
                            <span :title="$t('Total Emails')"><i
                                class="el-icon el-icon-message"></i> <span>{{ subscriber.stats.emails }}</span></span>
                <span :title="$t('Open Rate')"><i
                    class="el-icon el-icon-folder-opened"></i> <span>{{
                        percent(subscriber.stats.opens, subscriber.stats.emails)
                    }}</span></span>

                <ProfileStatURL :subscriber="subscriber" />
            </div>
            <div class="fc_t_10" v-if="subscriber.status == 'pending'">
                <el-button @click="sendDoubleOptinEmail()" type="danger" size="mini">
                    {{$t('Send Double Optin Email')}}
                </el-button>
            </div>
            <div class="fc_t_10" v-if="subscriber.unsubscribe_reason">
                <p>{{ucFirst(subscriber.status)}} {{$t('Reason:')}} {{subscriber.unsubscribe_reason}} <span v-if="subscriber.unsubscribe_date"> @ {{subscriber.unsubscribe_date}}</span></p>
            </div>
            <p style="margin-top: 7px;" v-if="subscriber.user_roles && subscriber.user_roles.length">
                {{$t('User Roles:')}} <span class="items">
                                <span class="fc_tag_prof_info" v-for="role in subscriber.user_roles" :key="role">{{role}} </span>
                            </span>
            </p>
        </div>
    </div>
</template>

<script>
import PhotoWidget from '@/Pieces/PhotoWidget';
import ProfileStatURL from './Parts/_ProfileStatURL.vue';

export default {
    name: 'ProfileInfo',
    props: {
        subscriber: {
            type: Object,
            default: () => {
                return null
            }
        },
        photo_holder: {
            type: String,
            default: 'fc_photo_holder'
        }
    },
    components: {
        ProfileStatURL,
        PhotoWidget
    },
    data() {
        return {
            status_visible: false,
            lead_visible: false,
            subscriber_statuses: window.fcAdmin.available_contact_statuses,
            contact_types: window.fcAdmin.available_contact_types
        }
    },
    computed: {
        name() {
            if (this.subscriber.first_name || this.subscriber.last_name) {
                if (this.subscriber.prefix) {
                    return `${this.subscriber.prefix || ''} ${this.subscriber.first_name || ''} ${this.subscriber.last_name || ''}`;
                }
                return `${this.subscriber.first_name || ''} ${this.subscriber.last_name || ''}`;
            }
            return this.subscriber.email;
        }
    },
    methods: {
        saveStatus() {
            this.updateProperty('status', this.subscriber.status, () => {
                this.status_visible = false;
            });
        },
        saveLead() {
            this.updateProperty('contact_type', this.subscriber.contact_type, () => {
                this.lead_visible = false;
            });
        },
        removeAvatar() {
            this.subscriber.avatar = '';
            this.updateAvatar('');
            this.$emit('fetch');
        },
        updateAvatar(url) {
            this.updateProperty('avatar', url);
        },
        updateProperty(prop, value, callback) {
            this.$put('subscribers/subscribers-property', {
                property: prop,
                subscribers: [this.subscriber.id],
                value: value
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    if (callback) {
                        callback(response);
                    }
                    this.$emit('fetch');
                })
                .catch((errors) => {
                    this.handleError(errors);
                });
        },
        sendDoubleOptinEmail() {
            this.$post(`subscribers/${this.subscriber.id}/send-double-optin`)
                .then((response) => {
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {

                });
        }
    }
}
</script>
