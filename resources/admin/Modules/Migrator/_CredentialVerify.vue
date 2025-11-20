<template>
    <div class="fc_credential">
        <div class="fc_step_header">
            <h3>{{$t('Connect with')}} {{ driver }}</h3>
            <p>{{$t('Please configure')}} {{ driver }} {{$t('with API key')}}</p>
        </div>

        <form-builder :formData="cred" :fields="current_driver.credential_fields"/>

        <p v-if="current_driver.doc_url"><a style="text-decoration: underline;" target="_blank" rel="noopener" :href="current_driver.doc_url">{{$t('Check the documentation')}}</a> {{$t('for migrating from')}} <b>{{current_driver.title}}</b></p>

        <div style="margin-top: 20px;" class="text-align-right">
            <el-button @click="back()" type="info">{{$t('Back')}}</el-button>
            <el-button v-loading="verifying" @click="verifyConnection()" type="primary">
                {{$t('Continue [Map Data]')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'CredentialVerify',
    components: {
        FormBuilder
    },
    props: ['driver', 'cred', 'current_driver'],
    data() {
        return {
            verifying: false
        }
    },
    methods: {
        verifyConnection() {
            this.verifying = true;
            this.$post('migrators/verify-cred', {
                driver: this.driver,
                credential: this.cred
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    this.$emit('verified');
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.verifying = false
                });
        },
        back() {
            this.$emit('back');
        }
    }
}
</script>
