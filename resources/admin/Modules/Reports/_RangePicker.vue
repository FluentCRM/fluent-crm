<template>
    <div class="fc_range_picker">
        <el-date-picker
            v-model="range_settings.date_range"
            class="mr-5"
            type="daterange"
            align="center"
            size="large"
            @change="onChange()"
            value-format="yyyy-MM-dd"
            format="dd, MMM"
            unlink-panels
            :range-separator="$t('To')"
            :start-placeholder="$t('Start date')"
            :end-placeholder="$t('End date')"
            :picker-options="pickerOptions">
        </el-date-picker>
        <span style="white-space: nowrap;">{{$t('compare to')}}</span>
        <el-select class="ml-5" popper-class="fc_dropdown" @change="onChange()" style="display: inline-block; max-width: 140px"
                   v-model="range_settings.compare_type" size="large" :placeholder="$t('Select')">
            <el-option
                v-for="(label,value) in compare_type_options"
                :key="value"
                :label="label"
                :value="value">
            </el-option>
        </el-select>
        <el-date-picker
            @change="onChange()"
            v-if="range_settings.compare_type == 'custom'"
            v-model="range_settings.compare_date"
            align="right"
            size="large"
            class="fc_range_picker_custom_date"
            value-format="yyyy-MM-dd"
            format="dd, MMM yyyy"
            :placeholder="$t('Compare Date')"
        >
        </el-date-picker>
    </div>
</template>
<script type="text/babel">
import {dateConfig} from '@/Bits/data_config';
export default {
    name: 'RangePicker',
    props: ['range_settings'],
    data() {
        return {
            pickerOptions: {
                shortcuts: dateConfig
            },
            compare_type_options: {
                previous_period: this.$t('Previous Period'),
                previous_month: this.$t('Previous Month'),
                previous_quarter: this.$t('Previous Quarter'),
                previous_year: this.$t('Previous Year'),
                custom: this.$t('Custom'),
                no_comparison: this.$t('No Comparison')
            }
        }
    },
    methods: {
        onChange(value) {
            this.$emit('changed');
        }
    }
}
</script>
