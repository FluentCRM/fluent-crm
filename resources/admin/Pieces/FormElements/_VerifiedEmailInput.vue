<template>
    <div>
        <el-select :placeholder="field.placeholder" filterable allow-create v-if="appVars.verified_senders.length" v-model="model">
            <el-option v-for="email in appVars.verified_senders" :key="email" :value="email"></el-option>
        </el-select>
        <el-input v-else :type="field.data_type" :placeholder="field.placeholder" v-model="model"></el-input>
        <div v-if="field.show_warning">
            <el-dialog
                title="Confirm"
                :visible.sync="dialogWarningVisible"
                :close-on-click-modal="false"
                :append-to-body="true"
                width="30%">
                <span>{{warningMessage + ' '}}<strong>{{model + '.'}}</strong> </span>
                <span slot="footer" class="dialog-footer">
                <el-button @click="cancelChangeDefaultEmail">Cancel</el-button>
                <el-button type="warning" @click="confirmChangeDefaultEmail">Continue</el-button>
            </span>
            </el-dialog>
        </div>

    </div>
</template>

<script type="text/babel">
export default {
    name: 'InputText',
    props: ['field', 'value'],
    data() {
        return {
            selectedMail: this.value,
            model: this.value,
            dialogWarningVisible: false,
            warningMessage: this.$t('Warning default email change')
        }
    },
    watch: {
        model(val) {
            this.$emit('input', val);

            if (val != null && val != this.selectedMail) {
                this.dialogWarningVisible = true;
            }

            if (val != null && val == this.selectedMail) {
                this.dialogWarningVisible = false;
            }
        }
    },
    methods: {
        cancelChangeDefaultEmail() {
            this.model = this.selectedMail;
            this.dialogWarningVisible = false;
        },

        confirmChangeDefaultEmail() {
            this.dialogWarningVisible = false;
            this.selectedMail = this.model;
        }
    }
}
</script>
