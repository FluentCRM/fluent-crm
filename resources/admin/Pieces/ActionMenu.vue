<template>
    <div class="">
        <el-button-group>
            <el-button
                size="small"
                icon="el-icon-plus"
                type="primary"
                @click="toggle('adder')">
                {{$t('Create')}} {{ trans(type) | ucFirst }}
            </el-button>
        </el-button-group>

        <adder
            :api="api"
            :type="type"
            :visible="adder"
            @fetch="fetch"
            @close="close('adder')"
        />
    </div>
</template>

<script>
    import Adder from '@/Pieces/Adder/Adder';

    export default {
        name: 'ActionMenu',
        props: {
            type: {
                type: String,
                required: true
            },
            api: {
                type: Object,
                required: true
            }
        },
        components: {
            Adder
        },
        data() {
            return {
                adder: false
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
            },
            listeners() {
                const event = 'edit-' + this.type;

                this.$bus.$on(event, () => {
                    this.adder = true;
                })
            }
        },
        mounted() {
            this.listeners();
        }
    }
</script>
