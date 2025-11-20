<template>
    <div v-loading="loading">
        <h4>
            {{ $t('SourceSelector.title') }}
        </h4>

        <template v-if="import_sources">
            <el-radio-group class="sources fc_inline_image_radio" v-model="option">
                <el-radio
                    v-for="(source, sourceName) in import_sources"
                    :key="sourceName"
                    :label="sourceName"
                    class="option">
                    <img style="width: 80px; height: 80px;" :src="source.logo"/>
                    <span class="fc_text_label">{{ source.label }}</span>
                    <span v-if="source.disabled" class="fc_import_pro">{{ $t('Pro') }}</span>
                </el-radio>
            </el-radio-group>
            <router-link style="display: inline-block; margin-top: 20px;" :to="{ name: 'crm_migrations' }">
                <div class="fc_inline_image_radio">
                    <label>
                        <img style="max-height: 64px;" :src="appVars.images_url + '/migrators/crm_importers.png'" />
                        <span class="fc_text_label">{{$t('Import From Other Providers')}}</span>
                    </label>
                </div>
            </router-link>
        </template>

        <div class="fc_narrow_box fluentcrm_databox text-align-center" v-if="disabledText && buttonDisabled">
            <p v-html="disabledText"></p>
            <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank"
               rel="noopener">{{ $t('Get FluentCRM Pro') }}</a>
        </div>

        <div slot="footer" class="dialog-footer">
            <el-button
                :disabled="buttonDisabled"
                size="small"
                type="primary"
                @click="next">
                {{ $t('Next') }}
            </el-button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ImportSourceSelector',
    props: ['value'],
    data() {
        return {
            loading: false,
            option: this.value,
            import_sources: false
        }
    },
    computed: {
        buttonDisabled() {
            if (!this.value || !this.import_sources || !this.import_sources[this.value] || this.import_sources[this.value].disabled) {
                return true;
            }

            return false;
        },
        disabledText() {
            if (!this.value || !this.import_sources || !this.import_sources[this.value] || !this.import_sources[this.value].disabled) {
                return '';
            }

            return this.import_sources[this.value].disabled_message;
        }
    },
    watch: {
        option() {
            return this.$emit('input', this.option);
        }
    },
    methods: {
        next() {
            this.$emit('next');
        },
        getSources() {
            this.loading = true;
            this.$get('import/drivers')
                .then(response => {
                    this.import_sources = response.drivers;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.getSources();
    }
}
</script>
