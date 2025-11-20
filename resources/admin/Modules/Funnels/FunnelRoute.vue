<template>
    <div class="fc_funnel_root">
        <router-view :options="options"></router-view>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'FunnelRoute',
    data() {
        return {
            app_ready: false,
            options: {}
        }
    },
    methods: {
        getOptions() {
            this.app_ready = false;
            const query = {
                fields: 'campaigns,email_sequences'
            };
            if (this.has_company_module) {
                query.fields = `${query.fields},companies`;
            }
            this.$get('reports/options', query).then(response => {
                this.options = response.options;
                this.options.tags = this.appVars.available_tags;
                this.options.lists = this.appVars.available_lists;
                this.options.editable_statuses = this.appVars.available_contact_editable_statuses;
            })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.app_ready = true;
                });
        }
    },
    mounted() {
        this.getOptions();
        this.changeTitle(this.$t('Automations'));
    }
}
</script>
