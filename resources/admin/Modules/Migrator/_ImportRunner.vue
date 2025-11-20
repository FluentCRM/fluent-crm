<template>
    <div v-loading="loading">
        <div class="fc_step_header">
            <h3>{{$t('Review & Import')}}</h3>
            <p></p>
        </div>
        <div class="text-align-center" v-if="import_completed">
            <h3>{{$t('All contacts from')}} {{driver}} {{$t('has been completed.')}}</h3>
            <el-button @click="$router.push({ name: 'subscribers' })" type="primary">{{$t('View Contacts')}}</el-button>
        </div>
        <div class="text-align-center" v-else-if="importing">
            <h3>{{ $t('Importing now...') }}</h3>
            <h4>{{ $t('Use_Please_dnctm') }}</h4>
            <template>
                <el-progress v-if="import_info.total && !import_info.hide_progress" :text-inside="true" :stroke-width="24"
                             :percentage="parseInt((import_info.completed / import_info.total) * 100)"
                             status="success"></el-progress>
                <p v-loading="importing">{{$t('Migrating')}}</p>

                <p v-html="import_info.message"></p>

                <div class="text-align-left" v-if="errors">
                    <h4>{{$t('Importing_Error_message')}}</h4>
                    <pre>{{errors}}</pre>
                </div>

            </template>
        </div>
        <div v-else class="text-align-center">
            <h2 v-html="import_summary.message"></h2>
            <el-button @click="startImport()" type="success">{{$t('Confirm Import')}}</el-button>
        </div>

    </div>

</template>

<script type="text/babel">
export default {
    name: 'ImportRunner',
    props: ['driver', 'credential', 'map_settings', 'segment_options'],
    data() {
        return {
            loading: false,
            import_summary: {},
            importing: false,
            import_completed: false,
            import_info: {
                completed: 0,
                total: 0,
                import_tracker: {}
            },
            errors: false
        }
    },
    methods: {
        fetchImportSummary() {
            this.loading = true;
            this.$post('migrators/summary', {
                driver: this.driver,
                credential: this.credential,
                map_settings: this.map_settings,
                ...this.segment_options
            })
                .then((response) => {
                    this.import_summary = response.import_summary;
                })
                .catch((errors) => {
                    this.handleError(errors);
                    this.$emit('prev');
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        startImport() {
            this.importing = true;
            this.$post('migrators/import', {
                driver: this.driver,
                credential: this.credential,
                map_settings: this.map_settings,
                completed: this.import_info.completed,
                import_tracker: this.import_info.import_tracker,
                ...this.segment_options
            })
                .then((response) => {
                    this.$set(this, 'import_info', response.import_info);
                    if (response.import_info.has_more) {
                        this.$nextTick(() => {
                            this.startImport();
                        })
                    } else {
                        this.import_completed = true;
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                    this.errors = errors;
                })
                .finally(() => {
                });
        }
    },
    mounted() {
        this.fetchImportSummary();
    }
}
</script>
