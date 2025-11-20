<template>
    <div v-loading="!tags_ready" class="fc_tag_mappings">
        <table v-if="tags_ready" class="fc_horizontal_table">
            <thead>
                <tr>
                    <th>{{field.selector_label}}</th>
                    <th>{{field.add_tag_label}}</th>
                    <th>{{field.remove_tag_label}}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="option in field.selector_options" :key="option.id">
                    <td>{{option.title}}</td>
                    <td><option-selector v-model="model[option.id].add_tags" :field="{ option_key: 'tags', creatable: true, is_multiple: true }" /></td>
                    <td><option-selector v-model="model[option.id].remove_tags" :field="{ option_key: 'tags', creatable: true, is_multiple: true }" /></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script type="text/babel">
import OptionSelector from './_OptionSelector'
export default {
    name: 'TagAddRemoveElement',
    props: ['field', 'value'],
    components: {
        OptionSelector
    },
    data() {
        return {
            model: this.value,
            tags_ready: false
        }
    },
    watch: {
        model(value) {
            this.$emit('input', value);
        }
    },
    mounted() {
        this.renewOptionCache('tags', () => {
            this.tags_ready = true;
        });
    }
}
</script>
