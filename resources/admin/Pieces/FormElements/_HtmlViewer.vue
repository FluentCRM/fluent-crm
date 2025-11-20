<template>
    <div class="fc_html">
        <h3 style="margin-bottom: 0;" v-if="field.heading">{{field.heading}}</h3>
        <div v-html="field.info"></div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'HtmlViewer',
    props: ['field']
}
</script>
