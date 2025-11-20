<template>
    <el-drawer
        class="fc_company_info_drawer"
        :with-header="true"
        :size="globalDrawerSize"
        :title="$t('Add New Contact')"
        :append-to-body="true"
        :visible.sync="showing">
        <div style="padding: 0 20px;">
            <form-handler
                @created="handleCreated"
                :listId="listId"
                :tagId="tagId"
            />
        </div>
    </el-drawer>
</template>

<script type="text/babel">
import FormHandler from './FormHandler.vue';

export default {
    name: 'Adder',
    components: {
        FormHandler
    },
    props: ['visible', 'listId', 'tagId'],
    data() {
        return {
            showing: this.visible
        }
    },
    watch: {
        visible(newVal) {
            this.showing = newVal;
        }
    },
    methods: {
        handleCreated(contact, addMore) {
            if (addMore === true) {
                this.showing = true;
                this.$emit('fetch');
            } else {
                this.showing = false;
                this.$router.push({name: 'subscriber', params: {id: contact.id}});
                this.$emit('close');
            }
        }
    }
}
</script>
