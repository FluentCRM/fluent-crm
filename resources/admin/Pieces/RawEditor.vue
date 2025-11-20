<template>
    <div class="fc_raw_body">
        <el-input
            type="textarea"
            :rows="30"
            :placeholder="$t('Raw_Please_PHoyE')"
            v-model="content">
        </el-input>
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
