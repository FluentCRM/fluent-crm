<template>
    <el-form-item :label="label">
        <el-select
            v-model="model"
            multiple
            clearable
            :placeholder="placeholder"
            class="el-select-multiple"
        >
            <el-option
                v-for="item in option"
                :key="item.id"
                :value="item.id"
                :label="item.title"
            >
                {{ item.title }}
            </el-option>
        </el-select>
    </el-form-item>
</template>

<script>
    export default {
        name: 'TagListSelect',
        props: {
            label: {
                required: true
            },
            placeholder: {
                default: function () {
                    return 'Select ' + this.label;
                }
            },
            option: {
                required: true,
                type: Array
            },
            value: {
                type: Array
            }
        },
        data() {
            return {
                model: this.value
            }
        },
        watch: {
            model() {
                return this.$emit('input', this.model);
            }
        }
    };
</script>
