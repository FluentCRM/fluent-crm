<template>
    <div class="fc_chart">
        <growth-chart v-if="chartData" :chart_options="chartOptions" :currency_sign="currency_sign" :chart-data="chartData"/>
    </div>
</template>

<script type="text/babel">
import GrowthChart from './_chart'

export default {
    components: {
        GrowthChart
    },
    name: 'ChartBuilder',
    props: ['currency_sign', 'data_sets'],
    data() {
        return {
            chartData: false,
            maxCumulativeValue: 0,
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [
                        {
                            gridLines: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                beginAtZero: true,
                                autoSkip: true,
                                maxTicksLimit: 10
                            }
                        }
                    ]
                },
                drawBorder: false,
                layout: {
                    padding: {
                        left: 20,
                        right: 20,
                        top: 20,
                        bottom: 20
                    }
                }
            }
        }
    },
    methods: {
        setupChartItemsX() {
            const chartData = {
                labels: [],
                datasets: []
            }
            let maxValue = 0;

            this.each(this.data_sets, (dataSet, index) => {
                const item = {
                    label: dataSet.label,
                    data: [],
                    backgroundColor: dataSet.backgroundColor,
                    borderColor: dataSet.borderColor,
                    type: 'line',
                    yAxisID: dataSet.id,
                    fill: dataSet.fill
                };

                this.each(dataSet.data, (count, label) => {
                    item.data.push(count);
                    if (parseInt(count) > maxValue) {
                        maxValue = parseInt(count);
                    }

                    if (index == 0) {
                        chartData.labels.push(label);
                    }
                });
                chartData.datasets.push(item);
            });

            if (maxValue > 10000) {
                maxValue = Math.ceil(maxValue / 1000) * 1000;
            } else if (maxValue > 500) {
                maxValue = Math.ceil(maxValue / 100) * 100;
            } else {
                maxValue = maxValue + 10;
            }

            this.maxCumulativeValue = maxValue;

            const yAxes = [];
            this.each(this.data_sets, (dataSet, index) => {
                yAxes.push({
                    id: dataSet.id,
                    type: 'linear',
                    position: (index == 0) ? 'left' : 'right',
                    gridLines: {
                        drawOnChartArea: index == 0
                    },
                    ticks: {
                        beginAtZero: true,
                        max: this.maxCumulativeValue,
                        userCallback: function (label, index, labels) {
                            // when the floored value is the same as the value we have a whole number
                            if (Math.floor(label) === label) {
                                return label;
                            }
                        },
                        callback: function (value, index, values) {
                            return this.currency_sign + ' ' + value;
                        }
                    }
                });
            });
            this.chartOptions.scales.yAxes = yAxes;
            this.setToolTip();
            this.chartData = chartData;
        },
        setToolTip() {
            var that = this;
            this.chartOptions.tooltips = {
                enabled: false,
                custom: function (tooltipModel) {
                    var tooltipEl = document.getElementById('fc_reports_tooltip');
                    // Create element on first render
                    if (!tooltipEl) {
                        tooltipEl = document.createElement('div');
                        tooltipEl.id = 'fc_reports_tooltip';
                        tooltipEl.innerHTML = '<table></table>';
                        document.body.appendChild(tooltipEl);
                    }

                    // Hide if no tooltip
                    if (tooltipModel.opacity === 0) {
                        tooltipEl.style.opacity = 0;
                        return;
                    }

                    // Set caret Position
                    tooltipEl.classList.remove('above', 'below', 'no-transform');
                    if (tooltipModel.yAlign) {
                        tooltipEl.classList.add(tooltipModel.yAlign);
                    } else {
                        tooltipEl.classList.add('no-transform');
                    }

                    if (!tooltipModel.dataPoints[0]) {
                        return;
                    }

                    const pointIndex = tooltipModel.dataPoints[0].index;

                    const dataLines = [];
                    that.each(that.data_sets, (dataSet, i) => {
                        dataLines.unshift({
                            title: Object.keys(dataSet.data)[pointIndex],
                            value: parseInt(Object.values(dataSet.data)[pointIndex]),
                            color: dataSet.borderColor
                        });
                    });

                    // Set Text
                    if (tooltipModel.body) {
                        var innerHtml = '<tbody>';

                        dataLines.forEach(function (line, i) {
                            innerHtml += '<tr><td style="color: ' + line.color + ';"><b>' + line.title + '</b>: ' + that.currency_sign + '' + that.formatMoney(line.value, 0) + '</td></tr>';
                        });
                        innerHtml += '</tbody>';

                        var tableRoot = tooltipEl.querySelector('table');
                        tableRoot.innerHTML = innerHtml;
                    }

                    // `this` will be the overall tooltip
                    var position = this._chart.canvas.getBoundingClientRect();

                    // Display, position, and set styles for font
                    tooltipEl.style.opacity = 1;
                    tooltipEl.style.position = 'absolute';
                    tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
                    tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
                    tooltipEl.style.fontFamily = tooltipModel._bodyFontFamily;
                    tooltipEl.style.fontSize = tooltipModel.bodyFontSize + 'px';
                    tooltipEl.style.fontStyle = tooltipModel._bodyFontStyle;
                    tooltipEl.style.padding = tooltipModel.yPadding + 'px ' + tooltipModel.xPadding + 'px';
                    tooltipEl.style.pointerEvents = 'none';
                }
            }
        }
    },
    mounted() {
        this.setupChartItemsX();
    }
}
</script>
