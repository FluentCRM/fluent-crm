<template>
    <div v-if="isCsv">
        <csv :options="options" @success="success"/>
    </div>

    <div v-else>
        <import-configuration @fetch="emitFetch()" :option="option" :options="options" />
    </div>
</template>

<script type="text/babel">
    import Csv from './Csv';
    import ImportConfiguration from './ImportConfiguration';

    export default {
        name: 'ImportSourceConfiguration',
        components: {
            Csv,
            ImportConfiguration
        },
        props: {
            option: {
                required: true
            },
            options: Object
        },
        computed: {
            isCsv() {
                return this.option === 'csv';
            }
        },
        methods: {
            success(data) {
                let payload;

                if (this.isCsv) {
                    payload = {
                        type: 'csv',
                        ...data
                    }
                } else {
                    payload = {
                        type: 'users',
                        roles: data
                    }
                }

                this.$emit('success', payload);
            },
            next() {
                this.$emit('next');
            },
            emitFetch() {
                this.$emit('fetch');
            }
        }
    }
</script>
