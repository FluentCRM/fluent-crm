<template>
    <div class="fluentcrm-app">
        <div class="fluentcrm-body">
            <router-view key="main_route"></router-view>
        </div>
        <el-dialog :visible="showErrorModal"
                   title="Server Response (Error)"
                   :append-to-body="true"
                   :close-on-click-modal="false" width="50%"
                   :show-close="false"
        >
            <div>
                <el-input type="textarea" :rows="15" :value="errorMessage" readonly></el-input>
                <p>FluentCRM is expecting JSON data but HTML returned</p>
            </div>
            <div slot="footer" class="dialog-footer">
                <el-button @click="showErrorModal = false">Close</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'Application',
    data() {
        return {
            last_path: '',
            errorMessage: '',
            showErrorModal: false
        }
    },
    methods: {
        verifyLicense() {
            this.$get('campaign-pro-settings/license', {verify: true})
                .then((response) => {
                })
                .catch(errors => {
                })
                .finally(() => {
                })
        },
        pingToServer() {
            this.$get('reports/ping');
        }
    },
    mounted() {
        jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').not('.fc_notice').remove();

        if (window.fcAdmin.require_verify_request) {
            this.verifyLicense();
        }

        this.$bus.$on('renew_options', (option) => {
            this.renewOptions(option);
        });

        // run this every minute
        setInterval(() => {
            this.pingToServer();
        }, 50000);

        this.$bus.$on('show-error-modal', (errors) => {
            if (!errors.responseText) {
                return;
            }

            this.errorMessage = errors.responseText;
            this.showErrorModal = true;
        });
    }
};
</script>
