<template>
    <div>
        <el-button @click="showContactAdd()" style="margin-left:10px; padding: 8px 15px;" icon="el-icon-plus" size="small" class="fc_columns_filter" plain>{{ $t('Contact') }}</el-button>

        <el-drawer
            class="fc_company_info_drawer"
            :with-header="true"
            :size="globalDrawerSize"
            :title="assignState == 'existing' ? $t('Add existing contacts') : $t('Create contact & Assign')"
            :append-to-body="true"
            :visible.sync="newContactDrawer">
            <div v-if="newContactDrawer" style="padding: 10px 15px;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <el-radio-group v-model="assignState">
                        <el-radio-button value="existing" label="existing">{{ $t('Add Existing') }}</el-radio-button>
                        <el-radio-button value="new" label="new">{{ $t('Create New') }}</el-radio-button>
                    </el-radio-group>
                </div>
                <div v-if="assignState == 'existing'">
                    <contact-assign-selector @attached="handleAttached()" :company="company" />
                </div>
                <div v-else>
                    <form-handler @created="handleCreated" :company_id="company.id" />
                </div>
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import ContactAssignSelector from './ContactAssignSelector.vue';
import FormHandler from '../../Contacts/Adder/FormHandler.vue';
export default {
    name: 'ContactAdded',
    props: ['company'],
    components: {
        ContactAssignSelector,
        FormHandler
    },
    data() {
        return {
            assignState: 'existing',
            newContactDrawer: false
        }
    },
    methods: {
        showContactAdd() {
            this.newContactDrawer = true;
        },
        handleCreated(contact) {
            this.newContactDrawer = false;
            this.$router.push({name: 'subscriber', params: {id: contact.id}});
        },
        handleAttached() {
            this.newContactDrawer = false;
            this.$emit('reloadContacts');
        }
    }
}
</script>
