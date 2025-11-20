<template>
    <div v-if="app_ready" class="fluentcrm_databox">
        <template v-if="!is_empty_item">
            <form-submission-block
                v-for="(provider, provider_key) in providersData"
                :key="provider_key"
                :provider="provider"
                :subscriber_id="subscriber_id"/>
        </template>
        <div v-else>
            <h3 class="text-align-center">
                {{$t('Pro_Form_SfFFwbshCFF')}}
            </h3>
        </div>
    </div>
</template>

<script type="text/babel">
    import FormSubmissionBlock from './_FormSubmissionsBlock'

    export default {
        name: 'ProfileFormSubmissions',
        props: ['subscriber_id'],
        components: {
            FormSubmissionBlock
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
            this.each(window.fcAdmin.form_submission_providers, (provider, providerKey) => {
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
