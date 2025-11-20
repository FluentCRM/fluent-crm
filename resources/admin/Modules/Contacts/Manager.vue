<template>
    <div class="fluentcrm-filter-manager">
        <template v-if="selection">
            <editor
                :type="type"
                :options="choices"
                :noMatch="noMatch"
                :matched="matched"
                :selectionCount="selectionCount"
                @search="search"
                @subscribe="updatePayLoad"
                :placement="placement"
            >
                <div style="padding: 10px; text-align: center;" slot="footer">
                    <el-button :disabled="!new_payload" @click="pushPayload()" style="width: 100%;" type="success" size="mini">
                        {{$t('Confirm')}}
                    </el-button>
                </div>
            </editor>
        </template>

        <template v-else>
            <filters
                :type="type"
                :options="choices"
                :noMatch="noMatch"
                :selected="selected"
                :count="total_match"
                @search="search"
                @filter="filter"
                :add_label="filterLabel"
            />
        </template>
    </div>
</template>

<script type="text/babel">
    import Editor from './Filter/Editor';
    import Filters from './Filter/Filters';
    import Tagger from '@/Bits/Mixins/Tagger';

    export default {
        name: 'Manager',
        props: [
            'type',
            'matched',
            'options',
            'selected',
            'selection',
            'subscribers',
            'selectionCount',
            'total_match'
        ],
        data() {
            return {
                placement: 'bottom-start',
                new_payload: false,
                filterLabel: this.$t('Filters.instruction')
            }
        },
        components: {
            Editor,
            Filters
        },
        mixins: [Tagger],
        methods: {
            filter(selection) {
                this.$emit('filter', this.payload(selection));
            },
            updatePayLoad(payload) {
                this.new_payload = payload;
            },
            pushPayload() {
                if (this.new_payload) {
                    this.subscribe(this.new_payload);
                    setTimeout(() => {
                        this.new_payload = false;
                    }, 500);
                } else {
                    this.$notify.error(this.$t('No changes found'));
                }
            }
        },
        mounted() {
            if (this.appVars.is_rtl) {
                this.placement = 'bottom-end';
            }
        }
    }
</script>
