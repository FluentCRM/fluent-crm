<template>
    <div class="">
        <el-button-group>
            <el-button
                v-if="hasPermission('fcrm_manage_contacts')"
                size="small"
                icon="el-icon-plus"
                type="primary"
                @click="toggle('adder')">
                {{$t('Add Contact')}}
            </el-button>

            <el-button
                v-if="hasPermission('fcrm_manage_contacts')"
                size="small"
                type="info"
                icon="el-icon-upload"
                @click="toggle('importer')">
                {{$t('Import')}}
            </el-button>

            <el-button
                v-if="hasPermission('fcrm_manage_contacts_export')"
                size="small"
                type="info"
                icon="el-icon-download"
                @click="toggle('exporter')">
                {{$t('Export')}}
            </el-button>
        </el-button-group>

        <adder :visible="adder"
               :listId="listId"
               :tagId="tagId"
               :options="options"
               @fetch="fetch"
               @close="close('adder')"
        />

        <importer
            :list-id="listId"
            :tag-id="tagId"
            :visible="importer"
            v-if="importer"
            :options="options"
            @fetch="fetch"
            @close="close('importer')"
        />

        <exporter :search_query="search_query" :visible="exporter" @close="close('exporter')" />
    </div>
</template>

<script type="text/babel">
    import Adder from './Adder/Adder';
    import Importer from './Importer/Importer';
    import Exporter from './Exporter/Exporter';

    export default {
        name: 'ActionMenu',
        components: {
            Adder,
            Importer,
            Exporter
        },
        props: ['options', 'listId', 'tagId', 'search_query'],
        data() {
            return {
                adder: false,
                importer: false,
                exporter: false
            }
        },
        methods: {
            toggle(name) {
                this[name] = !this[name];
            },
            close(name) {
                this[name] = false;
            },
            fetch(data) {
                this.$emit('fetch', data);
            }
        }
    }
</script>
