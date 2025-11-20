<template>
    <div>
        <span style="display: none !important; visibility: hidden !important;">
            <option-selector @element_ready="initOptions()" v-model="simulated_tag_id"
                             :field="{ is_multiple: false, creatable: true, option_key: 'tags' }"/>
        </span>

        <table v-if="app_ready" class="fc_table fc_horizontal_table">
            <thead>
            <tr>
                <th>{{ driver | ucFirst }} {{ item_label }}</th>
                <th>FluentCRM {{ item_label }}</th>
                <th>
                    {{$t('Auto Create')}} {{ item_label }}?
                    <div style="line-height: 0">
                        <el-switch active-value="yes" inactive-value="no" v-model="autoCreateAll"/>
                        <span style="font-size: 14px; font-weight: 300">{{$t('Select All')}}</span>
                    </div>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="tag in tag_options" :key="tag.remote_id">
                <td>{{ tag.remote_name }}</td>
                <td>
                    <span style="font-style: italic;" v-if="tag.will_create == 'yes'">
                        {{ item_label }} {{$t('will be created automatically in FluentCRM')}}
                    </span>
                    <option-selector v-else-if="element_ready" v-model="tag.fluentcrm_id"
                                     :field="{ is_multiple: false, creatable: true, option_key: option_key }"/>
                    <span v-else>{{$t('Loading...')}}</span>
                </td>
                <td>
                    <el-switch active-value="yes" inactive-value="no" v-model="tag.will_create"/>
                </td>
            </tr>
            </tbody>
        </table>
        <p v-html="current_driver[option_key + '_map_info']"></p>
    </div>
</template>

<script type="text/babel">
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';

export default {
    name: 'TagMapper',
    components: {
        OptionSelector
    },
    props: ['tag_options', 'driver', 'current_driver', 'item_label', 'option_key'],
    data() {
        return {
            app_ready: false,
            simulated_tag_id: '',
            element_ready: false,
            autoCreateAll: 'no'
        }
    },
    watch: {
        autoCreateAll(newValue) {
            if (newValue == 'yes') {
                this.tag_options.forEach(tag => {
                    tag.will_create = 'yes';
                });
            } else {
                this.tag_options.forEach(tag => {
                    tag.will_create = 'no';
                });
            }
        }
    },
    methods: {
        initOptions() {
            this.element_ready = true;
        }
    },
    mounted() {
        this.app_ready = true;
    }
}
</script>
