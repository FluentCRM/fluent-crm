<template>
    <el-input size="mini" :readonly="true" v-model="url">
        <a slot="append" :href="url" target="_blank" rel="nofollow"><i class="el-icon-link"></i></a>
    </el-input>
</template>

<script type="text/babel">
export default {
    name: 'ItemCopier',
    props: ['url'],
    methods: {
        gotoUrl() {

        }
    }
}
</script>
