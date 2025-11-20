const Line = window.VueChartJs.Line;
const mixins = window.VueChartJs.mixins;
const {reactiveProp} = mixins;

export default {
    extends: Line,
    mixins: [reactiveProp],
    props: ['chart_options'],
    mounted() {
        this.renderChart(this.chartData, this.chart_options);
    }
}
