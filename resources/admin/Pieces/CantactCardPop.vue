<template>
    <el-popover
        :placement="placement"
        width="400"
        class="fc_dark"
        :trigger="trigger_type">
        <div class="fc_profile_card fluentcrm_profile_header fc_profile_pop">
            <div class="fluentcrm_profile-photo">
                <img :src="subscriber.photo"/>
            </div>
            <div class="profile-info">
                <div class="profile_title">
                    <h3>{{ subscriber.full_name }}</h3>
                    <div class="profile_action">
                        <el-tag slot="reference" size="mini">{{subscriber.status | ucFirst}}</el-tag>
                    </div>
                </div>
                <p>{{ subscriber.email }} <span v-if="subscriber.user_id && subscriber.user_edit_url"> | <a target="_blank"
                                                                                :href="subscriber.user_edit_url">{{subscriber.user_id}} <span
                    class="dashicons dashicons-external"></span></a></span></p>
                <p>{{$t('Added')}} {{ subscriber.created_at | nsHumanDiffTime }}</p>
                <router-link :to="{ name: 'subscriber', params: { id: subscriber.id } }">
                    {{$t('View Full Profile')}}
                </router-link>
            </div>
        </div>
        <span :class="'fc_trigger_'+trigger_type" slot="reference">
            <template v-if="display_key == 'photo'">
                <img :title="$t('Contact ID:') + ' ' + subscriber.id" class="fc_contact_photo" :src="subscriber.photo" />
            </template>
            <div class="fc_photo_name" v-else-if="display_key == 'full'">
                <img :title="$t('Contact ID:') + ' ' + subscriber.id" class="fc_contact_photo" :src="subscriber.photo" />
                <span>{{subscriber.full_name}}</span>
            </div>
            <template v-else>
                 {{subscriber[display_key]}}
            </template>
        </span>
    </el-popover>
</template>

<script type="text/babel">
    export default {
        name: 'ContactCardPop',
        props: {
            subscriber: {
                type: Object,
                default() {
                    return {}
                }
            },
            display_key: {
                type: String,
                default() {
                    return 'email';
                }
            },
            placement: {
                type: String,
                default() {
                    return 'top-start';
                }
            },
            trigger_type: {
                type: String,
                default() {
                    return 'click';
                }
            }
        },
        methods: {
            viewProfile() {

            }
        }
    }
</script>

<style lang="scss">
.fc_photo_name {
    display: flex;
    align-items: flex-start;
    gap: 7px;
    line-height: 1.2;
}
</style>
