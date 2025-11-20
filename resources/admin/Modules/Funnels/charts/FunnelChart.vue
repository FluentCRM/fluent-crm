<template>
    <div style="min-height: 400px;" v-loading="fetching">
        <growth-chart :maxCumulativeValue="maxCumulativeValue" :chart-data="chartData"/>
    </div>
</template>

<script type="text/babel">
import GrowthChart from './_BarFunnel'

export default {
    name: 'funnel-reporting-bar',
    props: ['funnel_id', 'stats'],
    components: {
        GrowthChart
    },
    data() {
        return {
            fetching: true,
            chartData: {},
            maxCumulativeValue: 0
        }
    },
    computed: {},
    methods: {
        setupChartItems() {
            const labels = [];
            const ItemValues = {
                label: this.$t('Contacts'),
                yAxisID: 'byDate',
                backgroundColor: 'rgba(81, 52, 178, 0.5)',
                borderColor: '#b175eb',
                data: [],
                fill: true,
                gridLines: {
                    display: false
                }
            };

            const cumulativeItems = {
                label: this.$t('Line'),
                backgroundColor: 'rgba(55, 162, 235, 0.1)',
                borderColor: '#37a2eb',
                data: [],
                yAxisID: 'byCumulative',
                type: 'line'
            };

            ItemValues.backgroundColor = this.getBackgroundColors(this.stats.metrics.length);

            let currentTotal = 0;
            this.each(this.stats.metrics, (item, index) => {
                ItemValues.data.push(item.count);
                const titles = [item.label, item.percent + '%'];
                if (item.revenues) {
                    titles.push('Revenue: ' + item.revenues.join(' & '));
                }
                labels.push(titles);
                cumulativeItems.data.push(item.count);
                if (item.count > currentTotal) {
                    currentTotal = item.count;
                }
                if (item.type === 'benchmark') {
                    ItemValues.backgroundColor[index] = 'red';
                }
            });
            this.maxCumulativeValue = currentTotal + 10;

            this.chartData = {
                labels: labels,
                datasets: [ItemValues, cumulativeItems]
            }
            this.fetching = false;
        },
        getBackgroundColors(limit) {
            const colors = [
                '#255A65',
                '#22666C',
                '#227372',
                '#258077',
                '#2D8C79',
                '#3A997A',
                '#4BA579',
                '#5EB177',
                '#73BD73',
                '#8AC870',
                '#A4D36C',
                '#BFDC68',
                '#DBE566',
                '#544b66',
                '#4f30c6',
                '#190b1f',
                '#6f23a7',
                '#2d2134',
                '#483ba6',
                '#0e0d2c',
                '#7a2d88',
                '#181837',
                '#2d187b',
                '#2e2d4e',
                '#491963',
                '#1e052c',
                '#3d3e8a',
                '#2d163c',
                '#644378',
                '#210a50',
                '#3f2c5b',
                '#19164b',
                '#461748'
            ];
            return colors.slice(0, limit);
        }
    },
    mounted() {
        this.setupChartItems();
    }
};
</script>
