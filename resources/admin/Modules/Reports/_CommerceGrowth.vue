<template>
    <div class="fc_card_widget">
        <ul class="fc_settings_sub_menu fc_report_sub_menu">
            <li v-for="(report, supportKey) in overview.supports" :class="{ fc_active: (supportKey == report_type) }"
                @click="report_type = supportKey" :key="supportKey">
                {{ report.title }}
            </li>
        </ul>
        <div class="fc_report_sub_header fluentcrm_header">
            <div class="fluentcrm_header_title">
                <span v-if="current_report.has_product" style="width: 200px; display: inline-block;">
                    <ajax-selector :field="{
                        placeholder: $t('All Products'),
                        is_multiple: false,
                        option_key: 'product_selector_' + provider,
                        size: 'large',
                        clearable: true }" v-model="product_id"></ajax-selector>
                </span>
            </div>
            <div class="fluentcrm-actions">
                <range-picker @changed="fetch()" :range_settings="range_settings"></range-picker>
            </div>
        </div>
        <div class="fc_report_sub_header2" v-if="current_report.sub_types">
            <div class="fluentcrm_header_title">
                {{$t('Report Type:')}}
            </div>

            <div class="fc_report_sub_header2_contents">
                <div class="fc_report_sub_type">
                    <el-select v-model="sub_type" placeholder="Select" size="small">
                        <el-option
                            v-for="item in current_report.sub_types"
                            :key="item.value"
                            :label="item.label"
                            :value="item.value">
                        </el-option>
                    </el-select>
                </div>
                <div class="fc_sub_type_value" v-if="sub_type != 'all'">
                    <el-select v-model="sub_type_value" filterable placeholder="Select" size="small">
                        <el-option
                            v-for="item in (sub_type == 'tag' ? current_report.tags : current_report.lists)"
                            :key="item.id"
                            :label="item.title"
                            :value="item.id">
                        </el-option>
                    </el-select>
                </div>
            </div>

<!--            <el-radio-group v-model="sub_type">-->
<!--                <el-radio-button v-for="(type, typeName) in current_report.sub_types" :key="typeName" :label="typeName">-->
<!--                    {{ type.label }}-->
<!--                </el-radio-button>-->
<!--            </el-radio-group>-->
        </div>
        <div v-if="app_ready" class="fluentcrm_body">
            <chart-builder
                :data_sets="data_sets"
                :currency_sign="current_report.is_money ? overview.currency_sign : ''"
            />
            <p v-if="range_settings.date_range.length == 2" style="text-align: center;">
                {{$t('Showing stats from')}} {{ range_settings.date_range[0] }} to {{ range_settings.date_range[1] }}
            </p>

            <div class="fluentcrm_history_table_wrap">
                <table class="table fc_horizontal_table">
                    <thead>
                    <tr>
                        <th>{{$t('Date')}}</th>
                        <th v-for="header in tabular_items.headers" :key="header"><span v-html="header"></span></th>
                        <th v-if="tabular_items.is_compare">{{$t('Change')}} (%)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(label, labelIndex) in tabular_items.labels" :key="labelIndex">
                        <td>{{ label.join(' vs ') }}</td>
                        <td v-for="(item, pointIndex) in tabular_items.items[labelIndex]" :key="pointIndex"><span
                            v-html="tabular_items.prefix"></span>{{ formatMoney(item) }}
                        </td>
                        <template v-if="tabular_items.is_compare">
                            <td v-html="getChangeHtml(tabular_items.items[labelIndex])"></td>
                        </template>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td></td>
                        <th v-for="(total, totalIndex) in tabular_items.totals" :key="totalIndex"><span
                            v-html="tabular_items.prefix"></span>{{ formatMoney(total) }}
                        </th>
                        <th v-if="tabular_items.is_compare" v-html="tabular_items.totalChange"></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div v-else class="fluentcrm_body">
            <el-skeleton class="fc_skeleton_loader" :rows="3" animated/>
            <el-skeleton class="fc_skeleton_loader" :rows="6" animated/>
        </div>
    </div>
</template>

<script type="text/babel">
import ChartBuilder from './Char/ChartBuilder';
import AjaxSelector from '../../Pieces/FormElements/_AjaxSelector';
import RangePicker from './_RangePicker';

export default {
    name: 'CommerceGrowth',
    props: ['provider', 'overview'],
    components: {
        ChartBuilder,
        AjaxSelector,
        RangePicker
    },
    data() {
        return {
            data_sets: [],
            range_settings: {
                date_range: [],
                compare_date: '',
                compare_type: 'previous_period'
            },
            report_type: 'product_growth',
            sub_type: 'all',
            product_id: '',
            fetching: false,
            app_ready: false,
            sub_type_value: '',
            subtype_period_value: ''
        }
    },
    computed: {
        current_report() {
            return this.overview.supports[this.report_type];
        },
        tabular_items() {
            const items = [];
            const totals = {};
            const labels = [];
            const headers = [];

            this.each(this.data_sets, (set, setIndex) => {
                totals[setIndex] = 0;
                headers.push(set.label);

                let dataIndex = 0;
                this.each(set.data, (item, itemDate) => {
                    if (!items[dataIndex]) {
                        items[dataIndex] = [];
                    }
                    if (!labels[dataIndex]) {
                        labels[dataIndex] = [];
                    }
                    items[dataIndex].push(Number(item));
                    if (labels[dataIndex].indexOf(itemDate) === -1) {
                        labels[dataIndex].push(itemDate);
                    }

                    totals[setIndex] += Number(item);
                    dataIndex++;
                });
            });

            const isCompare = Object.values(totals).length == 2;

            let totalChange = '';
            if (isCompare) {
                totalChange = this.getChangeHtml(totals);
            }

            return {
                headers,
                items,
                totals,
                labels,
                totalChange,
                is_compare: isCompare,
                prefix: (this.current_report.is_money) ? this.overview.currency_sign : ''
            };
        }
    },
    watch: {
        product_id() {
            this.fetch();
        },
        report_type() {
            this.fetch();
        },
        sub_type(newValue) {
            this.sub_type_value = '';
        },
        sub_type_value() {
            this.fetch();
        }
    },
    methods: {
        fetch() {
            this.fetching = true;
            this.app_ready = false;

            this.$get('commerce-reports/' + this.provider + '/report', {
                item_id: this.product_id,
                ...this.range_settings,
                report_type: this.report_type,
                sub_type: this.sub_type,
                sub_type_value: this.sub_type_value
            })
                .then(response => {
                    this.range_settings.date_range = response.current_range;
                    this.data_sets = response.data_sets;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                    this.app_ready = true;
                });
        },
        getChangeHtml(item) {
            if (item.length < 2 || !item[1]) {
                return 'n/a';
            }
            const percentChange = (item[0] - item[1]) / item[1] * 100;
            return (percentChange > 0) ? '<span class="fc_positive">' + percentChange.toFixed(2) + '%</span>' : '<span class="fc_negative">' + percentChange.toFixed(2) + '%</span>';
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
