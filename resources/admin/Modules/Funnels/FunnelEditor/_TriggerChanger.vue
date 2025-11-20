<template>
    <div class="fcrm_funnel_changer">
        <el-form v-loading="fetching" :data="funnel_data" label-position="top">
            <el-form-item>
                <template slot="label">
                    {{$t('Select New Automation Trigger')}}
                    <p>{{$t('TriggerChanger.instruction')}}</p>
                </template>
                <el-select v-model="funnel_data.trigger_name" filterable :placeholder="$t('Select')">
                    <el-option
                        v-for="(trigger,triggerName) in triggers"
                        :key="triggerName"
                        :label="trigger.category + ' - ' + trigger.label"
                        :value="triggerName">
                    </el-option>
                </el-select>
                <p v-if="triggers[funnel_data.trigger_name]" v-html="triggers[funnel_data.trigger_name].description"></p>
            </el-form-item>
            <template v-if="funnel_data.trigger_name != funnel.trigger_name">
                <el-form-item>
                    <template slot="label">
                        {{$t('Automation Trigger Title')}}
                    </template>
                    <el-input type="text" :placeholder="$t('Trigger Title')" v-model="funnel_data.title" />
                </el-form-item>
                <el-button v-loading="saving" @click="changeTrigger()" type="primary">{{$t('Change Automation Trigger')}}</el-button>
            </template>
        </el-form>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'TriggerChanger',
    props: ['funnel'],
    data() {
        return {
            funnel_data: JSON.parse(JSON.stringify(this.funnel)),
            triggers: [],
            fetching: true,
            saving: false
        }
    },
    methods: {
        getTriggers() {
            this.fetching = true;
            this.$get('funnels/triggers')
                .then(response => {
                    this.triggers = response.triggers;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                });
        },
        changeTrigger() {
            this.saving = true;
            this.$put(`funnels/${this.funnel.id}/change-trigger`, {
                title: this.funnel_data.title,
                trigger_name: this.funnel_data.trigger_name
            })
            .then(response => {
                this.$notify.success(response.message);
                this.$emit('refreshTrigger', response.funnel);
            })
            .catch(errors => {
                this.handleError(errors);
            })
            .finally(() => {
                this.saving = false;
            });
        }
    },
    mounted() {
        this.getTriggers();
    }
}
</script>
