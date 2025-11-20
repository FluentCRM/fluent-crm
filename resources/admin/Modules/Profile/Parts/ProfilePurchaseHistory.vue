<template>
    <div class="fluentcrm_databox">
        <div v-if="app_ready" class="fluentcrm_purchase_history_wrapper">
            <template v-if="!is_empty_item">
                <purchase-history-block
                    v-for="(provider, provider_key) in providersData"
                    :key="provider_key"
                    :provider="provider"
                    :subscriber_id="subscriber_id"/>
            </template>
            <div v-else>
                <h3 class="text-align-center">
                    {{$t('Pro_Purchase_hfEwbsh')}}
                </h3>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
    import PurchaseHistoryBlock from './_PurchaseHistoryBlock';

    export default {
        name: 'ProfilePurchaseHistory',
        props: ['subscriber_id'],
        components: {
            PurchaseHistoryBlock
        },
        data() {
            return {
                providersData: {},
                app_ready: false
            }
        },
        computed: {
            is_empty_item() {
                return this.isEmptyValue(this.providersData);
            }
        },
        mounted() {
            this.each(window.fcAdmin.purchase_providers, (provider, providerKey) => {
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
