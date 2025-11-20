<template>
    <div class="csv-uploader">
        <div style="min-height: 300px;" class="csv_upload_container">
            <label style="margin: 10px 0px; display: block;">{{ $t('Select Your CSV Delimiter') }}</label>
            <el-select v-model="options.delimiter">
                <el-option v-for="(option,OptionLabel) in delimiter_options" :key="OptionLabel" :value="OptionLabel"
                           :label="option"></el-option>
            </el-select>

            <template v-if="options.delimiter">
                <h3>{{ $t('Upload CSV') }}</h3>

                <el-upload
                    drag
                    :limit="1"
                    :action="url"
                    ref="uploader"
                    :multiple="false"
                    :on-error="error"
                    :on-remove="remove"
                    :on-exceed="exceed"
                    :on-success="success"
                    :class="{'is-error': errors.has('file')}">
                    <i class="el-icon-upload"/>
                    <div class="el-upload__text">
                        {{ $t('Drop file here or') }} <em>{{ $t('click to upload') }}</em>
                    </div>
                </el-upload>

                <error :error="errors.get('file')"/>

                <div class="sample">
                    <a @click="sample">{{ $t('Download sample file') }}</a>
                </div>
                <p>{{ $t('csv.download_desc') }}</p>
            </template>
        </div>

        <div slot="footer" class="dialog-footer">
            <el-button
                size="small"
                type="primary"
                @click="next">
                {{ $t('Next [Map Columns]') }}
            </el-button>
        </div>
    </div>
</template>

<script>
import Errors from '@/Bits/Errors';
import Error from '@/Pieces/Error';

export default {
    name: 'Csv',
    components: {
        Error
    },
    props: ['options'],
    data() {
        return {
            errors: new Errors(),
            delimiter_options: {
                comma: this.$t('Comma Separated (,)'),
                semicolon: this.$t('Semicolon Separated (;)')
            }
        }
    },
    computed: {
        url() {
            if (window.FLUENTCRM.appVars.rest.url.indexOf('?') != -1) {
                return window.FLUENTCRM.appVars.rest.url + '/import/csv-upload&_wpnonce=' + window.FLUENTCRM.appVars.rest.nonce + '&delimiter=' + this.options.delimiter + '&type=' + this.options.type;
            }
            return window.FLUENTCRM.appVars.rest.url + '/import/csv-upload?_wpnonce=' + window.FLUENTCRM.appVars.rest.nonce + '&delimiter=' + this.options.delimiter + '&type=' + this.options.type;
        }
    },
    methods: {
        success(response) {
            this.errors.clear();
            if (!response.map) {
                response.map = response.headers.map(header => (
                    {csv: header, table: null}
                ));
            }

            this.$emit('success', response);
        },
        remove() {
            this.errors.clear();
        },
        exceed() {
            this.errors.record({
                file: {
                    exceed: this.$t('You cannot upload more than one file.')
                }
            });
        },
        error(error) {
            const message = JSON.parse(error.message);
            this.handleError(message);
            this.errors.record({
                file: {
                    invalid: message.message || this.$t('unknown error. Please check your csv first')
                }
            });
        },
        sample() {
            location.href = this.options.sampleCsv;
        },
        clear() {
            this.$refs.uploader.clearFiles();
        },
        next() {
            this.$notify.error(this.$t('Please Upload a CSV first'));
        }
    }
}
</script>
<style lang="scss">
.sample {
    a {
        cursor: pointer;
    }
}
</style>
