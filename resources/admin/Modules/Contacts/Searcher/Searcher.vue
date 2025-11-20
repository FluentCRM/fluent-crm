<template>
    <div class="fluentcrm-searcher">
        <el-input
            clearable
            size="mini"
            v-model="model"
            @clear="fire"
            :disabled="disabled"
            @keyup.enter.native="fire"
            :placeholder="$t('Search Type and Enter...')"
        >
            <el-button @click="fire" slot="append" icon="el-icon-search"></el-button>
        </el-input>
    </div>
</template>

<script>
    export default {
        props: ['value', 'disabled'],
        name: 'Searcher',
        data() {
            return {
                model: this.value,
                timeout: null
            }
        },
        methods: {
            fire() {
                if (!this.model) return;
                this.doAction('loading', true);
                this.doAction('search-subscribers', this.model);
            }
        },
        watch: {
            model: function(newValue, oldValue) {
                newValue = jQuery.trim(newValue);
                oldValue = jQuery.trim(oldValue);
                if (oldValue && !newValue) {
                    this.doAction('loading', true);
                    this.doAction('search-subscribers', this.model);
                }
            },
            disabled(newValue) {
                if (newValue) {
                    this.model = '';
                }
            }
        }
    };
</script>
