<template>
    <div>
        <div class="list-stat" v-if="list">
            <el-breadcrumb separator="/">
                <el-breadcrumb-item :to="{ name: 'lists' }">
                    {{$t('Lists')}}
                </el-breadcrumb-item>

                <el-breadcrumb-item>
                    {{ list.title }}
                    <el-link icon="el-icon-edit" :underline="false" @click="edit"/>
                </el-breadcrumb-item>
            </el-breadcrumb>
        </div>

        <subscribers/>

        <adder
            v-if="hasPermission('fcrm_manage_contact_cats')"
            :api="api"
            type="list"
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
        props: ['listId'],
        data() {
            return {
                list: null,
                adder: false,
                api: {
                    store: 'lists'
                }
            }
        },
        methods: {
            fetch() {
                this.$get(`lists/${this.listId}`)
                    .then(response => {
                        this.list = response;
                    })
                    .catch(() => {});
            },
            edit() {
                this.adder = true;
                this.$bus.$emit('edit-list', this.list);
            },
            update(list) {
                this.list = list;
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
