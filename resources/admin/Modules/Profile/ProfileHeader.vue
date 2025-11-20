<template>
    <div class="fluentcrm_profile_header_warpper">
        <el-row :gutter="30">
            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                <profile-info :subscriber="subscriber" @fetch="fetch" />
            </el-col>
            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                <profile-list-tags @updateSubscriber="emitUpdate" :subscriber="subscriber"/>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import ProfileListTags from './Parts/ProfileListTags'
import ProfileInfo from './ContactProfileInfo';

export default {
    name: 'ProfileHeader',
    components: {
        ProfileInfo,
        ProfileListTags
    },
    props: ['subscriber'],
    data() {
        return {

        }
    },
    methods: {
        fetch() {
            this.$emit('fetch');
        },
        emitUpdate(subscriber) {
            this.$emit('updateSubscriber', subscriber);
        }
    }
};
</script>
