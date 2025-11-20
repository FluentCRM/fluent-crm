<template>
    <filterer :placement="placement">
        <slot name="header" slot="header">
            <el-button plain size="mini">
                {{ $t('Add or Remove') }} {{ trans(type) | ucFirst }}
                <slot name="icon">
                    <i class="el-icon-arrow-down el-icon--right"></i>
                </slot>
            </el-button>
        </slot>

        <!--tag search-->
        <el-dropdown-item slot="items" class="fluentcrm-filter-option no-hover fc-dropdown-search-item">
            <el-input
                class="fc_input"
                size="mini" v-model="query"
                :placeholder="$t('Search...')"
                @keyup.native="search"
            ></el-input>
        </el-dropdown-item>

        <!--choose a tag label-->
        <el-dropdown-item slot="items" class="fc-dropdown-items-label fluentcrm-filter-option">
            {{ $t('Choose an option:') }}
        </el-dropdown-item>

        <!--tag options-->
        <el-checkbox-group v-model="selection"
                           slot="items"
                           @change="save"
                           class="fluentcrm-filter-options fc_checkbox_group"
        >
            <el-checkbox v-for="item in options"
                         :key="item.id"
                         :label="item.slug"
                         :indeterminate="isIndeterminate(item)"
                         class="el-dropdown-menu__item fc_checkbox"
            >
                {{ trans(item.title) }}
            </el-checkbox>
        </el-checkbox-group>

        <template v-if="noMatch">
            <div class="fc_no_match_search_tagger" slot="footer">
                <p>{{ $t('No items found') }}</p>
                <el-button v-if="creatable" type="primary" size="small" class="fc_primary_button" @click="createNewItem">
                    {{ $t('Add new:') }} - {{ query }}
                </el-button>
            </div>
        </template>
        <slot v-else name="footer" slot="footer">

        </slot>
    </filterer>
</template>

<script>
import Filterer from '@/Pieces/Filterer';

export default {
    name: 'Editor',
    components: {
        Filterer
    },
    props: {
        type: {
            required: true
        },
        options: {
            required: true,
            type: Array
        },
        noMatch: {
            required: true,
            type: Boolean
        },
        matched: {
            required: true
        },
        selectionCount: {
            required: true,
            type: Number
        },
        placement: {
            default: 'bottom-start'
        },
        creatable: {
            default: false
        }
    },
    watch: {
        matched() {
            this.init();
        }
    },
    data() {
        return {
            query: null,
            selection: [],
            checkList: [],
            creating: false
        }
    },
    methods: {
        init() {
            this.selection = [];
            for (const slug in this.matched) {
                if (!this.selection.includes(slug)) {
                    this.selection.push(slug);
                }
            }
        },
        search() {
            this.$emit('search', this.query && this.query.toLowerCase());
        },
        isIndeterminate(item) {
            return this.matched[item.slug] &&
                this.matched[item.slug] !== this.selectionCount;
        },
        save(selection) {
            const matched = Object.keys(this.matched);

            const attach = selection.filter(item => !matched.includes(item));
            const detach = matched.filter(item => !selection.includes(item));

            this.$emit('subscribe', {attach, detach});
        },
        createNewItem() {
            this.creating = true;

            this.$post(this.type, {
                title: this.query
            })
                .then(response => {
                    this.$notify.success(response.message);
                    const item = response.item;
                    item.status = true;
                    this.$emit('addedNew', item);
                    this.$nextTick(() => {
                        this.search();
                    });
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.creating = false;
                });
        }
    },
    mounted() {
        this.init();
    }
}
</script>
