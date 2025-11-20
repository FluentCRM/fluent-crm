<template>
    <div>
        <el-form v-loading="loading" label-position="top" :model="form">
            <el-form-item :label="$t('CONDITION TYPE')">
                <el-radio-group v-model="form.display_type">
                    <el-radio label="show_if_tag_exist">{{ $t('Show IF in Selected Tag') }}</el-radio>
                    <el-radio label="show_if_tag_not_exist">{{ $t('Show IF not in selected tag') }}</el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item :label="$t('Select Targeted Tags')">
                <el-checkbox-group v-model="form.selected_tags">
                    <el-checkbox v-for="tag in tags" :key="tag.slug" :label="tag.id">{{ tag.title }}</el-checkbox>
                </el-checkbox-group>
            </el-form-item>
        </el-form>
        <el-button @click="fireCondition()" type="primary">{{ $t('Apply Condition') }}</el-button>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'DisplayCondition',
    props: ['existing_tag', 'editing_condition'],
    data() {
        return {
            form: {
                selected_tags: [],
                display_type: 'show_if_tag_exist'
            },
            loading: false
        }
    },
    computed: {
        tags() {
            const tags = {};
            this.each(this.appVars.available_tags, (tag) => {
                tags[tag.id] = tag;
            });
            return tags;
        }
    },
    methods: {
        fireCondition() {
            const displayTags = [];
            this.each(this.form.selected_tags, (id) => {
                if (this.tags[id]) {
                    displayTags.push(this.tags[id].title);
                } else {
                    delete this.form.selected_tags[id];
                }
            });

            if (!this.form.selected_tags || !this.form.selected_tags.length) {
                this.$notify.error(this.$t('Please select at least one tag'));
                return;
            }

            let title = this.$t('Show if in tags:');

            if (this.form.display_type == 'show_if_tag_not_exist') {
                title = this.$t('Show if not in tags:')
            }

            const description = displayTags.join(', ');

            const data = {
                type: 'check_contact_tag',
                label: title,
                description: description,
                before: '<p style="opacity: 0;height: 0;margin: 0;">[fc_vis_cond type=\'' + this.form.display_type + '\' values=\'' + this.form.selected_tags.join('|') + '\']</p>',
                after: '<p style="opacity: 0;height: 0;margin: 0;">[/fc_vis_cond]</p>'
            }

            this.$emit('insertTag', data);
        },
        parseExitingCondition() {
            if (!this.editing_condition || !this.editing_condition.before) {
                return;
            }
            this.loading = true;

            const tag = this.editing_condition.before;
            // Extract type and values as attributes from tag variable using regex
            let attributes = tag.match(/\[fc_vis_cond ([^\]]*)\]/)[1];
            // Split attributes into array
            attributes = attributes.split(' ');
            // Create object for attributes
            var obj = {};
            // Loop through array
            for (const i in attributes) {
                // Get attribute name and value
                const parts = attributes[i].split("='");
                // Set object key and value
                obj[parts[0]] = parts[1].replace(/'/g, '');
            }

            if (obj.type) {
                this.form.display_type = obj.type;
            }

            if (obj.values) {
                this.form.selected_tags = obj.values.split('|');
            }

            this.loading = false;
        }
    },
    mounted() {
        this.parseExitingCondition();
    }
}
</script>
