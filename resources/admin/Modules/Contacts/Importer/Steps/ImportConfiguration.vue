<template>
    <div v-loading="loading" style="min-height: 300px;" class="fc_import_configuration">
        <template v-if="step == 'config'">
            <div v-if="config" class="fc_importer_form">
                <form-builder :formData="config" :fields="fields"/>
            </div>
            <div v-if="config" slot="footer" class="dialog-footer">
                <el-button
                    size="small"
                    @click="validateConfig()"
                    type="primary">
                    {{ labels.step_2 || $t('Next') }}
                </el-button>
            </div>
        </template>
        <template v-else-if="step == 'general_config'">
            <review-configurator :labels="labels" @fetch="emitFetch()" :config="config" :import_info="import_info"
                                 :driver="option"/>
        </template>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';
import ReviewConfigurator from './ReviewConfigurator';

export default {
    name: 'ImportConfiguration',
    props: ['option', 'options'],
    components: {
        FormBuilder,
        ReviewConfigurator
    },
    data() {
        return {
            fields: {},
            config: false,
            labels: {},
            loading: false,
            step: 'config',
            import_info: false,
            general_settings: {
                tags: [],
                lists: [],
                status: '',
                update_if_exist: 'no',
                disable_triggers: 'no'
            }
        }
    },
    methods: {
        fetchDriver() {
            this.loading = true;
            this.$get('import/drivers/' + this.option)
                .then(response => {
                    this.fields = response.fields;
                    this.config = response.config;
                    this.labels = response.labels;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        validateConfig() {
            this.loading = true;
            this.$get('import/drivers/' + this.option, {
                config: this.config,
                summary: 'yes'
            })
                .then(response => {
                    this.import_info = response.import_info;
                    this.step = 'general_config';
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        emitFetch() {
            this.$emit('fetch');
        }
    },
    mounted() {
        this.fetchDriver();
    }
}
</script>
