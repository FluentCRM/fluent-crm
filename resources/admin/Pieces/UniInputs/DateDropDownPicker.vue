<template>
    <div v-if="appReady" class="fc_date_parts_picker">
        <div>
            <el-select clearable v-model="dateParts.day" :placeholder="$t('Day')">
                <el-option
                        v-for="day in days"
                        :key="day"
                        :label="day"
                        :value="day">
                </el-option>
            </el-select>
            <p v-show="dateParts.day">{{ $t('Day') }}</p>
        </div>
        <div class="fc_date_picker_month">
            <el-select clearable v-model="dateParts.month" :placeholder="$t('Month')">
                <el-option
                        v-for="month in months"
                        :key="month.value"
                        :label="month.label"
                        :value="month.value">
                </el-option>
            </el-select>
            <p v-show="dateParts.month">{{ $t('Month') }}</p>
        </div>
        <div>
            <el-select clearable filterable v-model="dateParts.year" :placeholder="$t('Year')">
                <el-option
                    v-for="year in years"
                    :key="year"
                    :label="year"
                    :value="year">
                </el-option>
            </el-select>
            <p v-show="dateParts.year">{{ $t('Year') }}</p>
        </div>
    </div>
</template>

<script type="text/babel">
import range from 'lodash/range';
import map from 'lodash/map';
import padStart from 'lodash/padStart';
export default {
    name: 'DateDropDownPicker',
    props: ['value'],
    data() {
        return {
            appReady: false,
            dateParts: {
                day: '',
                month: '',
                year: ''
            },
            months: [
                {
                    label: this.$t('January'),
                    value: '01'
                },
                {
                    label: this.$t('February'),
                    value: '02'
                },
                {
                    label: this.$t('March'),
                    value: '03'
                },
                {
                    label: this.$t('April'),
                    value: '04'
                },
                {
                    label: this.$t('May'),
                    value: '05'
                },
                {
                    label: this.$t('June'),
                    value: '06'
                },
                {
                    label: this.$t('July'),
                    value: '07'
                },
                {
                    label: this.$t('August'),
                    value: '08'
                },
                {
                    label: this.$t('September'),
                    value: '09'
                },
                {
                    label: this.$t('October'),
                    value: '10'
                },
                {
                    label: this.$t('November'),
                    value: '11'
                },
                {
                    label: this.$t('December'),
                    value: '12'
                }
            ],
            days: [],
            years: []
        }
    },
    watch: {
        dateParts: {
            handler() {
                if (this.appReady) {
                    this.pushDate();
                }
            },
            deep: true
        }
    },
    methods: {
        initDateParts() {
            if (this.value) {
                const date = window.moment(this.value);
                this.dateParts.day = date.format('DD');
                this.dateParts.month = date.format('MM');
                this.dateParts.year = date.format('YYYY');
            }

            this.$nextTick(() => {
                this.appReady = true;
            });
        },
        range,
        pushDate() {
            const {day, month, year} = this.dateParts;
            if (day && month && year) {
                const date = window.moment(`${year}-${month}-${day}`);
                if (!date.isValid()) {
                     this.$emit('input', '');
                    return;
                }
                this.$emit('input', date.format('YYYY-MM-DD'));
            } else {
                this.$emit('input', '');
            }
        }
    },
    mounted() {
        this.days = map(range(1, 32), (day) => padStart(day, 2, '0'));
        this.years = range((new Date()).getFullYear(), 1899);
        this.initDateParts();
    }
}
</script>
