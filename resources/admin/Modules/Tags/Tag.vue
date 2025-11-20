<template>
    <div>
        <div class="list-stat" v-if="tag">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item :to="{ name: 'lists' }">
                    {{$t('Tags')}}
                </el-breadcrumb-item>

                <el-breadcrumb-item>
                    {{ tag.title }}

                    <el-link icon="el-icon-edit" :underline="false" @click="edit"/>
                </el-breadcrumb-item>
            </el-breadcrumb>
        </div>

        <subscribers/>

        <adder
            v-if="hasPermission('fcrm_manage_contact_cats')"
            :api="api"
            type="tag"
            @fetch="update"
            :visible="adder"
            @close="close"
        />
    </div>
</template>

<script>
    import Adder from '@/Pieces/Adder/Adder';
    import Subscribers from '@/Modules/Contacts/Contacts';

    export default {
        name: 'List',
        components: {
            Adder,
            Subscribers
        },
        props: ['tagId'],
        data() {
            return {
                tag: null,
                adder: false,
                api: {
                    store: 'tags'
                }
            }
        },
        methods: {
            fetch() {
                this.$get(`tags/${this.tagId}`)
                    .then(response => {
                        this.tag = response.tag;
                    });
            },
            edit() {
                this.adder = true;
                this.$bus.$emit('edit-tag', this.tag);
            },
            update(tag) {
                this.tag = tag;
            },
            close() {
                this.adder = false;
            }
        },
        mounted() {
            this.fetch();
        }
    };
</script>

<style lang="scss">
    .list-stat {
        padding: 24px 15px;
        background: white;
    }

    .el-link.el-link--default {
        &:hover {
            color: #409EFF !important;
            cursor: pointer !important;
        }
    }
</style>
