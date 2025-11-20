const Pie = window.VueChartJs.Pie;
const mixins = window.VueChartJs.mixins;
const {reactiveProp} = mixins;

export default {
    extends: Pie,
    mixins: [reactiveProp],
    props: ['stats'],
    data() {
        return {
            options: {
                responsive: true,
                legend: {
                    display: false,
                    position: 'right'
                },
                plugins: {
                    legend: {
                        position: 'right',
                        display: false
                    },
                    title: {
                        display: false,
                        text: ''
                    }
                },
                layout: {
                    padding: 20
                }
            }
        }
    },
    methods: {},
    mounted() {
        // this.chartData is created in the mixin.
        // If you want to pass options please create a local options object
        this.renderChart(this.chartData, this.options)
    }
}
