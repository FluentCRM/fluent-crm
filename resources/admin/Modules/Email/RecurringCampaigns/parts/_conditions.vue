<template>
    <div v-if="sending_conditions.length" class="flow_conditions">
        <div v-for="(conditions, conditionsIndex) in sending_conditions"
             :key="conditionsIndex"
             class="fc_flow_condition_block">
            <div v-for="(condition, itemIndex) in conditions" :key="itemIndex"
                 class="fc_flow_condition">
                <span>{{ $t('Send emails if') }} </span>
                <span>
                    <el-select size="mini" v-model="condition.object_name">
                        <el-option v-for="postType in appVars.publicPostTypes" :key="postType.id" :value="postType.id" :label="postType.title"></el-option>
                    </el-select>
                </span>
                <span>{{ $t('published within') }}</span>
                <span>
                    <el-input size="mini" type="number" :placeholder="$t('type days')" v-model="condition.compare_value"/>
                </span>
                <span>{{ $t('days') }}</span>
                <span style="margin-left: 20px">
                    <el-button @click="removeCondition(conditionsIndex, itemIndex)" size="mini" type="text" icon="el-icon-delete"></el-button>
                </span>
            </div>
            <p v-if="conditionsIndex + 1 != sending_conditions.length" class="fc_or">
                <span>{{ $t('OR') }}</span>
            </p>
        </div>
        <div class="fc_flow_more">
            <el-button @click="addMoreCondition()" size="small">{{ $t('Add OR Condition') }}</el-button>
        </div>
    </div>
    <div v-else>
        <p>{{ $t('Emails_Will_Sent_Auto') }}, <a @click.prevent="addMoreCondition()" href="#">{{ $t('click here') }}</a></p>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'CampaignConditions',
    props: ['sending_conditions'],
    methods: {
        addMoreCondition() {
            this.sending_conditions.push([
                {
                    object_type: 'cpt',
                    object_name: 'post',
                    object_key: 'post_date',
                    comparison_type: 'within_days',
                    compare_value: 7
                }
            ]);
        },
        removeCondition(conditionsIndex, itemIndex) {
            this.sending_conditions[conditionsIndex].splice(itemIndex, 1);
            if (!this.sending_conditions[conditionsIndex].length) {
                this.sending_conditions.splice(conditionsIndex, 1);
            }
        }
    }
}
</script>
