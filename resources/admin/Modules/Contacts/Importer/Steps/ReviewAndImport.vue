<template>
    <mapper
        v-if="isCsv"
        :csv="csv"
        :map="map"
        :list-id="listId"
        :tag-id="tagId"
        :headers="headers"
        :columns="columns"
        :options="options"
        @fetch="fetch"
        @close="close"
    />

    <manager
        v-else
        :roles="roles"
        :list-id="listId"
        :tag-id="tagId"
        :options="options"
        @fetch="fetch"
        @close="close"
    />
</template>

<script type="text/babel">
    import Mapper from './Mapper';
    import Manager from './UserImportManager';

    export default {
        name: 'ImportReviewer',
        components: {
            Mapper,
            Manager
        },
        props: {
            csv: {},
            map: {
                type: Array
            },
            headers: {
                type: Array
            },
            columns: {
                type: Object
            },
            roles: {
                type: Array
            },
            options: Object,
            option: String,
            listId: {
                default: null
            },
            tagId: {
                default: null
            }
        },
        data() {
            return {}
        },
        computed: {
            isCsv() {
                return this.option === 'csv';
            }
        },
        methods: {
            fetch() {
                this.$emit('fetch');
            },
            close() {
                this.$emit('close');
            }
        }
    }
</script>
