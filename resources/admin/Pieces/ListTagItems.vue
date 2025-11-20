<template>
    <span class="fc_tag_items fc_list"><i :class="item_class"></i> {{text_lists || $t('n/a')}}</span>
</template>

<script type="text/babel">
import includes from 'lodash/includes';

export default {
    name: 'listTagItems',
    props: ['items', 'item_type'],
    computed: {
        text_lists() {
            const types = this.appVars['available_' + this.item_type];
            if (!types) {
                return '';
            }

            const itemArray = [];

            this.each(types, (item) => {
                if (includes(this.items, item.title) || includes(this.items, item.id)) {
                    itemArray.push(item.title);
                }
            });

            return itemArray.join(', ');
        },
        item_class() {
            if (this.item_type == 'lists') {
                return 'el-icon-files';
            }
            return 'el-icon-price-tag';
        }
    }
}
</script>
