<template>
    <div class="fc_individual_progress">
        <el-timeline>
            <el-timeline-item
                v-for="(activity, index) in timelines"
                :key="index"
                :class="activity.wrapper_class"
                :icon="activity.icon"
                :type="activity.type"
                :color="activity.color"
                :size="activity.size"
                :timestamp="activity.timestamp">
                {{ activity.content }}
                    <template>
                        <el-tooltip v-if="activity.notes" class="item" effect="dark" :content="activity.notes" placement="top-start">
                            <el-tag size="mini" type="info">{{ activity.status }}</el-tag>
                        </el-tooltip>
                        <el-tag v-else-if="activity.status" size="mini" type="info">{{ activity.status }}</el-tag>
                    </template>
            </el-timeline-item>
        </el-timeline>
    </div>
</template>

<script type="text/babel">
import keyBy from 'lodash/keyBy';

export default {
    name: 'individualProgress',
    props: ['sequences', 'funnel_subscriber', 'funnel'],
    computed: {
        keyedMetrics() {
            return keyBy(this.funnel_subscriber.metrics, 'sequence_id');
        },
        timelines() {
            let activityTitle = 'Entrance ' + '(' + this.funnel.title + ')';
            if (this.funnel_subscriber.status === 'pending') {
                activityTitle += this.$t('_In_Wfdoc');
            } else if (this.funnel_subscriber.status === 'waiting') {
                activityTitle += this.$t('_In_Wfna');
            }

            const activities = [{
                content: activityTitle,
                timestamp: this.nsHumanDiffTime(this.funnel_subscriber.created_at),
                size: 'large',
                type: 'primary',
                icon: 'el-icon-more'
            }];

            let lastParent = false;
            this.each(this.sequences, (sequence, sequenceIndex) => {
                const metric = this.keyedMetrics[sequence.id] || {};
                let title = sequence.title;
                if (title === 'pending') {
                    title += this.$t('_In_Wfdc');
                } else if (title === 'waiting') {
                    title += this.$t('_In_Wfna');
                }

                if (sequence.type == 'conditional') {
                    lastParent = sequence;
                }

                let cssClass = '';
                if (sequence.condition_type) {
                    cssClass = 'fc_path_' + sequence.condition_type;
                    if (lastParent) {
                        cssClass = 'fc_' + lastParent.action_name + ' ' + cssClass;
                    }
                    let prefix = this.$t('Condition:') + ' ' + sequence.condition_type;
                    if (lastParent.action_name == 'funnel_ab_testing') {
                        const path = (sequence.condition_type == 'yes') ? this.$t('B') : this.$t('A');
                        prefix = this.$t('Path: ') + path;
                        cssClass += '_' + path;
                    }
                    title += ' ( ' + prefix + ' )';
                }

                if (!metric.status) {
                    cssClass += ' fc_timeline_empty';
                }

                activities.push({
                    content: title,
                    status: metric.status,
                    notes: metric.notes,
                    timestamp: this.nsHumanDiffTime(metric.created_at),
                    color: this.getTimelineColor(metric),
                    wrapper_class: cssClass
                })
            });
            return activities;
        }
    },
    methods: {
        getTimelineColor(metric) {
            if (!metric.status) {
                return '';
            }

            if (metric.status === 'completed') {
                return '#0bbd87';
            }

            return '';
        }
    }
}
</script>
