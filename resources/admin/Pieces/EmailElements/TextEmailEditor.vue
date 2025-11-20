<template>
    <div class="fc_text_body">
        <div class="fc_text_body_input">
            <el-input
                type="textarea"
                :rows="20"
                :placeholder="$t('Please Provide Text only (HTML will not support)')"
                v-model="content">
            </el-input>
        </div>
        <p>{{$t('no_html_support')}}</p>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'RawtextEditor',
    props: ['value', 'editor_design'],
    data() {
        return {
            content: this.value || ''
        }
    },
    watch: {
        content() {
            this.$emit('input', this.content);
        }
    },
    methods: {

    }
};
</script>

<style>
.fc_text_body {
    max-width: 600px;
    margin: 20px auto;
}
</style>
