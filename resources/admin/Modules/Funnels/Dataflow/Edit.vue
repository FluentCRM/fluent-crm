<template>
    <div style="position: relative; height: 100vh;" class="draflow_funnels">
        <div style="height: 100vh" id="drawflow"></div>
    </div>
</template>

<script type="text/babel">
import Vue from 'vue'
import NodeClick from './Nodes/NodeClick';

export default {
    name: 'DataFlowEditor',
    props: ['funnel_id'],
    data() {
        return {
            editor: null
        }
    },
    mounted() {
        const id = document.getElementById('drawflow');
        this.editor = new window.Drawflow(id, Vue);
        this.editor.start();
        const props = {
            name: 'jewel'
        };
        const options = {};
        this.editor.registerNode('NodeClick', NodeClick, props, options);
        const data = { namex: 'Shah' };
        this.editor.addNode('Name', 0, 1, 400, 50, 'Class', data, 'NodeClick', 'vue');
        this.editor.addNode('Name2', 1, 0, 400, 150, 'Class', data, 'NodeClick', 'vue');
        this.editor.addNode('Name3', 1, 0, 400, 250, 'Class', data, 'NodeClick', 'vue');
    }
}
</script>
