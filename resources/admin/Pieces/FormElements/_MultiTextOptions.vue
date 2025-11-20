<template>
    <div class="fc_url_boxes">
        <div class="fc_each_text_option" v-for="(option,OptionIndex) in options"  :key="OptionIndex">
            <el-input :type="field.input_type" :placeholder="field.placeholder" v-model="option.value">
                <el-button @click="deleteUrl(OptionIndex)" :disabled="options.length == 1" slot="append" icon="el-icon-delete"></el-button>
            </el-input>
        </div>
        <el-button @click="addMoreUrl()" size="small" type="info">{{$t('Add More')}}</el-button>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'MultiTextOptions',
        props: {
            value: {
                type: Array,
                default() {
                    return [''];
                }
            },
            field: {
                type: Object
            }
        },
        data() {
            return {
                options: []
            }
        },
        watch: {
            options: {
                deep: true,
                handler() {
                    const urlArray = [];
                    this.options.forEach(url => {
                        if (url.value) {
                            urlArray.push(url.value);
                        }
                    });
                   this.$emit('input', urlArray);
                }
            }
        },
        methods: {
            addMoreUrl() {
                this.options.push({ value: '' })
            },
            deleteUrl(index) {
                this.options.splice(index, 1);
            }
        },
        mounted() {
            const value = JSON.parse(JSON.stringify(this.value));
            if (!value || !value.length) {
                this.options = [{ value: '' }];
            } else {
                this.options = [];
                value.forEach(url => {
                    this.options.push({ value: url });
                });
            }
        }
    }
</script>

<!--
Used IN:
/admin/Modules/Funnels/_Field.vue

-->
