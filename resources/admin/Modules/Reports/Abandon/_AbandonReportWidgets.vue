<template>
    <div class="fcrm-abandon-report-widgets">

        <div
            v-for="(widget, index) in widgets"
            :key="index"
            class="fcrm-abandon-report-widget">
            <div class="widget-title">{{ widget.title }} <span v-if="widget.count != ''" class="count">{{ widget.count }}</span></div>
            <div class="widget-body" v-html="widget.value"></div>
        </div>

    </div>
</template>

<script>
export default {
    name: 'AbandonReportWidgets',
    props: ['widgets']
}
</script>

<style scoped>

</style>
