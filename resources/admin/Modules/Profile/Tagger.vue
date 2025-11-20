<template>
    <div v-if="taggables && showItem">
        <div class="header">
            <h2>{{ trans(type) | ucFirst }}</h2>
            <editor
                :type="type"
                :options="choices"
                :noMatch="noMatch"
                :matched="matched"
                :selectionCount="1"
                placement="bottom-end"
                @search="search"
                @addedNew="(item) => { $emit('addedNew', item) }"
                :creatable="creatable"
                @subscribe="updatePayLoad"
            >
                <el-button
                    plain
                    size="mini"
                    slot="header"
                    icon="el-icon-plus"
                />
                <div class="fc_tagger_footer" slot="footer">
                    <el-button @click="pushPayload()" style="width: 100%;" type="primary" size="small" class="fc_primary_button">{{$t('Confirm')}}
                    </el-button>
                </div>
            </editor>
        </div>

        <div class="items">
            <el-tag
                :key="item.title"
                class="el-tag--white"
                :title="getDescription(item)"
                v-for="item in taggables">
                {{ item.title }}
                <confirm :message="$t('Are you sure you want to remove this?')" placement="top-start" @yes="remove(item.slug)">
                    <i slot="reference" class="el-tag__close el-icon-close"></i>
                </confirm>
            </el-tag>

            <el-alert
                v-if="!taggables.length"
                :title="none"
                type="warning"
                :closable="false"
            />
        </div>
    </div>
</template>

<script type="text/babel">
import Tagger from '@/Bits/Mixins/Tagger';
import Editor from '@/Modules/Contacts/Filter/Editor';
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'Tagger',

    components: {
        Editor,
        Confirm
    },

    data() {
        return {
            new_payload: false,
            showItem: true
        }
    },

    props: ['type', 'taggables', 'options', 'matched', 'creatable'],

    mixins: [Tagger],

    computed: {
        none() {
            if (this.type == 'tags') {
                return this.$t('No tags found');
            } else if (this.type == 'lists') {
                return this.$t('No lists found');
            }
            return 'No ' + this.type + ' found';
        }
    },

    methods: {
        remove(slug) {
            this.subscribe({attach: [], detach: [slug]});
        },
        updatePayLoad(payload) {
            this.new_payload = payload;
        },
        pushPayload() {
            if (this.new_payload) {
                this.subscribe(this.new_payload);
                setTimeout(() => {
                    this.showItem = false;
                    this.$nextTick(() => {
                        this.showItem = true;
                    });
                }, 500);
            } else {
                this.$notify.error(this.$t('No changes found'));
            }
        },
        getDescription(item) {
            if (!item.pivot) {
                return '';
            }
            return this.$t('Added @ ') + item.pivot.created_at;
        }
    }
};
</script>
