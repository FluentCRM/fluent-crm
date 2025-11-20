<template>
    <div class="fc_individual_progress">
        <el-row :gutter="20">
            <el-col class="fc_progress_card" v-for="(stat,statIndex) in stats.metrics" :key="statIndex" :md="6" :xs="24"
                    :lg="6"
                    :sm="12">
                <div :class="'fc_sequence_type_'+stat.type" class="fc_progress_item">
                    <el-progress type="circle" :percentage="stat.percent > 100 ? 100 : stat.percent" :format="format(stat.percent_text)" :color="colors"></el-progress>
                    <h3>{{ stat.label }}</h3>
                    <div class="stats_badges">
                        <report-widget :stat="stat" />
                    </div>
                </div>
            </el-col>
            <el-col v-if="lastItem" class="fc_progress_card" :md="6" :xs="24" :lg="6" :sm="12">
                <div class="fc_progress_item fc_sequence_type_result">
                    <el-progress type="circle" :percentage="100" status="success"></el-progress>
                    <h3>{{$t('Overall Conversion Rate')}}: {{ lastItem.percent }}%</h3>
                    <div class="stats_badges">
                        <span>{{$t('(y)')}}</span>
                    </div>
                </div>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import ReportWidget from '../FunnelEditor/_report_widget';

export default {
    name: 'FunnelTextReport',
    props: ['stats', 'funnel'],
    components: {
        ReportWidget
    },
    data() {
        return {
            colors: [
                {color: '#f56c6c', percentage: 20},
                {color: '#e6a23c', percentage: 40},
                {color: '#5cb87a', percentage: 60},
                {color: '#1989fa', percentage: 80},
                {color: '#6f7ad3', percentage: 100}
            ]
        }
    },
    methods: {
        format(percentage) {
            return function () {
                return `${percentage}%`
            };
        }
    },
    computed: {
        lastItem() {
            if (this.stats.metrics.length) {
                return this.stats.metrics[this.stats.metrics.length - 1];
            }
            return false;
        }
    },
    mounted() {

    }
}
</script>

<style lang="scss">
.fc_individual_progress {
    padding: 20px 5px 0 42px;
    * {
        box-sizing: border-box;
    }

    .fc_progress_card:nth-child(4n+1) {
        clear: left;
    }
}

.fc_progress_item {
    text-align: center;
    margin-bottom: 20px;
    padding: 20px;
    border: 1px solid #7757e6;
    border-radius: 10px;
    cursor: pointer;
    background: #f7fafc;
    transition: .2s;
    -webkit-transition: .2s;
    -moz-transition: .2s;
    -o-transition: .2s;
    &:hover {
        background: white;
        box-shadow: 0 0 20px rgb(119 86 230 / 20%);
    }

    .stats_badges {
        display: block;
    }

    &.fc_sequence_type_benchmark {
        border: 2px solid #f56c6b;
    }

    &.fc_sequence_type_result {
        border: 2px solid #7757e6;
    }
}

</style>
