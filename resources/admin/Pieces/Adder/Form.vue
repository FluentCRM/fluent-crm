<template>
    <el-form label-position="top" label-width="100px">
        <el-row :gutter="20">
            <el-col :md="12">
                <el-form-item :label="$t('Title')" class="is-required">
                    <el-input v-model="item.title"/>
                    <error :error="errors.get('title')"/>
                </el-form-item>
            </el-col>
            <el-col :md="12">
                <el-form-item :label="$t('Slug')">
                    <el-input v-model="item.slug"/>
                    <error :error="errors.get('slug')"/>
                </el-form-item>
            </el-col>
        </el-row>
        <el-form-item :label="$t('For_Internal_S_')">
            <el-input v-model="item.description" :placeholder="$t('Internal Subtitle')"/>
        </el-form-item>
    </el-form>
</template>

<script>
    import Error from '@/Pieces/Error';

    export default {
        name: 'Form',
        components: {
            Error
        },
        props: {
            item: {
                required: true,
                type: Object
            },
            errors: {
                required: true,
                type: Object
            }
        },
        watch: {
            'item.title'(newValue) {
                if (!this.item.id) {
                    this.item.slug = newValue
                        ? this.generateSlug(newValue)
                        : '';
                }
            }
        },
        methods: {
            generateSlug(title) {
                return title.toString().toLowerCase()
                    .normalize('NFD') // Normalize the string into decomposed form
                    .replace(/[\u0300-\u036f]/g, '') // Remove diacritical marks
                    .replace(/\s+/g, '-') // Replace spaces with -
                    .replace(/\\-\\-+/g, '-') // Replace multiple - with single -
                    .replace(/^-+/, '') // Trim - from start of text
                    .replace(/-+$/, ''); // Trim - from end of text
            }
        }
    };
</script>
