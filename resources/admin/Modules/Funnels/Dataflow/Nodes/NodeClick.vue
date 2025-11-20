<template>
    <div class="card-devices">
        <div class="header">
            <h3>{{$t('User Signup On Form')}}</h3>
        </div>
        <div class="body">
            <pre>{{value}}</pre>
            <pre>{{name}}</pre>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ClickChecker',
    props: ['value', 'name', 'namex'],
    methods: {
        Clicked() {
            alert('hello-drawflow');
        }
    }
}
</script>
