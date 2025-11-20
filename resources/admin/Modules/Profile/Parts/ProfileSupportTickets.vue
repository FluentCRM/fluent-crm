<template>
    <div v-if="app_ready" class="fluentcrm_databox">
        <support-tickets-block
            v-for="(provider, provider_key) in providersData"
            :key="provider_key"
            :provider="provider"
            :subscriber_id="subscriber_id"/>
    </div>
</template>

<script type="text/babel">
    import SupportTicketsBlock from './_SupportTicketsBlock'

    export default {
        name: 'ProfileSuportTickets',
        props: ['subscriber_id'],
        components: {
            SupportTicketsBlock
        },
        data() {
            return {
                providersData: {},
                app_ready: false
            }
        },
        computed: {
        },
        mounted() {
            this.each(window.fcAdmin.support_tickets_providers, (provider, providerKey) => {
                const data = {
                    title: provider.title,
                    name: provider.name,
                    provider_key: providerKey
                }
                this.providersData[providerKey] = data;
            });
            this.app_ready = true;
        }
    }
</script>
