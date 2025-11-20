<template>
    <div class="fc_profile_tagger">
        <el-row v-loading="subscribing" :gutter="60">
            <el-col :lg="24" :md="24" :sm="24" :xs="24">
                <tagger
                    type="lists"
                    @search="search"
                    @subscribe="subscribe"
                    class="info-item"
                    :creatable="true"
                    :options="options.lists"
                    @addedNew="(item) => { options.lists.push(item) }"
                    :matched="matches.lists"
                    :taggables="subscriber.lists"
                />
            </el-col>
            <el-col :lg="24" :md="24" :sm="24" :xs="24">
                <tagger
                    type="tags"
                    @search="search"
                    class="info-item"
                    :creatable="true"
                    @subscribe="subscribe"
                    @addedNew="(item) => { options.tags.push(item) }"
                    :options="options.tags"
                    :matched="matches.tags"
                    :taggables="subscriber.tags"
                />
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import Tagger from '../Tagger';

export default {
    name: 'ProfileListTags',
    props: ['subscriber'],
    components: {
        Tagger
    },
    data() {
        return {
            options: {
                tags: [],
                lists: []
            },
            matches: {
                tags: {},
                lists: {}
            },
            subscribing: false
        }
    },
    methods: {
        setup(item) {
            this.matches.tags = [];
            this.matches.lists = [];
            this.subscriber.tags = item.tags;
            this.subscriber.lists = item.lists;
            item.tags.forEach(tag => this.match(tag, this.matches.tags));
            item.lists.forEach(list => this.match(list, this.matches.lists));
        },

        getOptions() {
            this.options = {
                tags: this.appVars.available_tags,
                lists: this.appVars.available_lists
            }
        },
        subscribe({type, payload}) {
            const {attach, detach} = payload;
            const query = {
                type,
                attach,
                detach,
                subscribers: [this.subscriber.id]
            };

            this.subscribing = true;

            this.$post('subscribers/sync-segments', query)
                .then(response => {
                    const subscriber = response.subscribers[0];
                    this.setup(subscriber);
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.subscribing = false;
                });
        },
        search(type, value) {
            this.options[type] = value;
        },
        match(item, container) {
            container[item.slug] = 1;
        }
    },
    mounted() {
        this.getOptions();
        this.setup(this.subscriber);
    }
}
</script>
