<template>
    <div v-loading="fetching" class="fluentcrm_body fc_chart_box">
        <growth-chart :maxCumulativeValue="maxCumulativeValue" :chart-data="chartData"/>
    </div>
</template>

<script type="text/babel">
    import GrowthChart from './_chart'

    export default {
        name: 'email-click-chart',
        props: ['date_range'],
        components: {
            GrowthChart
        },
        data() {
            return {
                fetching: false,
                stats: {},
                chartData: {},
                maxCumulativeValue: 0
            }
        },
        computed: {},
        methods: {
            fetchReport() {
                this.fetching = true;
                this.$get('reports/email-clicks', { date_range: this.date_range })
                    .then(response => {
                        this.stats = response.stats;
                        this.setupChartItems();
                    })
                    .finally(() => {
                        this.fetching = false;
                    });
            },
            setupChartItems() {
                const labels = [];
                const ItemValues = {
                    label: this.$t('By Date'),
                    yAxisID: 'byDate',
                    backgroundColor: 'rgba(81, 52, 178, 0.5)',
                    borderColor: '#b175eb',
                    data: [],
                    fill: false,
                    gridLines: {
                        display: false
                    }
                };

                const cumulativeItems = {
                    label: this.$t('Cumulative'),
                    backgroundColor: 'rgba(55, 162, 235, 0.1)',
                    borderColor: '#37a2eb',
                    data: [],
                    yAxisID: 'byCumulative',
                    type: 'line'
                };

                let currentTotal = 0;
                this.each(this.stats, (count, label) => {
                    ItemValues.data.push(count);
                    labels.push(label);
                    currentTotal += parseInt(count);
                    cumulativeItems.data.push(currentTotal);
                });
                this.maxCumulativeValue = currentTotal + 10;
                this.chartData = {
                    labels: labels,
                    datasets: [ItemValues, cumulativeItems]
                }
            }
        },
        mounted() {
            this.fetchReport();
        }
    };
</script>
