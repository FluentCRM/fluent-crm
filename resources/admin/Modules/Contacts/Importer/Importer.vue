<template>
    <el-dialog
        :title="$t('Import Subscribers')"
        :visible="visible"
        :append-to-body="true"
        :close-on-click-modal="false"
        @close="hide()"
        width="900px"
        class="fluentcrm-importer"
    >
        <div v-if="visible">

            <template v-if="active === 0">
                <SourceSelector  @fetch="fetch()" @next="next()" class="step" v-model="option"/>
            </template>

            <template v-else-if="active === 1">
                <source-configuration
                    class="step"
                    :option="option"
                    :options="options"
                    @next="next()"
                    @fetch="fetch()"
                    @success="success"
                />
            </template>

            <template v-else-if="active === 2">
                <review-and-import
                    :csv="csv"
                    :map="map"
                    class="step"
                    :roles="roles"
                    :option="option"
                    :headers="headers"
                    :columns="columns"
                    :options="options"
                    :list-id="listId"
                    :tag-id="tagId"
                    @close="close"
                    @fetch="fetch"
                />
            </template>
        </div>
    </el-dialog>
</template>

<script type="text/babel">
    import SourceSelector from './Steps/SourceSelector';
    import SourceConfiguration from './Steps/SourceConfiguration';
    import ReviewAndImport from './Steps/ReviewAndImport';

    export default {
        name: 'Importer',
        components: {
            SourceSelector,
            SourceConfiguration,
            ReviewAndImport
        },
        props: ['visible', 'options', 'listId', 'tagId'],
        data() {
            return {
                map: [],
                tags: [],
                active: 0,
                csv: null,
                lists: [],
                roles: [],
                headers: [],
                columns: {},
                statuses: [],
                option: 'csv',
                countries: [],
                store: false,
                button_loading: false
            }
        },
        methods: {
            hide() {
                this.reset();
                this.$emit('close');
                this.doAction('cancel', 'importer');
            },
            next() {
                if (this.active++ > 2) {
                    this.active = 0;
                }
            },
            stop() {
                return this.active === 1 && (this.option === 'csv' ? !this.csv : !this.roles.length);
            },
            prev() {
                if (this.active !== 0) {
                    this.active--;
                }
            },
            success(payload) {
                if (payload.type === 'csv') {
                    this.map = payload.map;
                    this.csv = payload.file;
                    this.headers = payload.headers;
                    this.columns = payload.fields;
                    this.next();
                } else {
                    this.roles = payload.roles;
                }
            },
            confirm() {
                this.doAction('import', this.option);
            },
            fetch() {
                this.$emit('fetch');
            },
            close() {
                this.hide();
                this.reset();
            },
            reset() {
                this.active = 0;
                this.removeAllActions('import');
            }
        }
    }
</script>
