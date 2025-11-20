<template>
    <div class="fc_docs">
        <div style="max-width: 800px; margin: 50px auto; padding: 0px 20px;" class="fc_doc_header text-align-center">
            <h1>{{ $t('How can we help you?') }}</h1>
            <p>{{ $t('Please view the') }} <a href="https://fluentcrm.com/docs">{{ $t('documentation') }}</a>
                {{ $t('still_cant_find_the_answer') }} <a
                    href="https://wpmanageninja.com/support-tickets/">{{ $t('open a support ticket') }}</a>
                {{ $t('and we will be happy to answer your questions and assist you with any problems.') }}.
                <br />Want to discuss something with users like you? <a target="_blank" rel="noopener" href="https://www.facebook.com/groups/fluentcrm"><b>Join our facebook community</b></a>
            </p>
            <el-input
                v-loading="fetching"
                clearable
                :disabled="fetching"
                size="large"
                v-model="search"
                :placeholder="$t('Search Type and Enter...')"
            >
                <el-button @click="reading_doc_status = false" slot="append" icon="el-icon-search"></el-button>
            </el-input>
            <div v-if="search" class="search_result">
                <div class="fc_doc_items">
                    <div class="fc_doc_header">
                        <h3>{{ $t('Search Results for:') }} {{ search }}</h3>
                    </div>
                    <div class="fc_doc_lists">
                        <ul v-if="search_items.length">
                            <li v-for="(doc, docIndex) in search_items" :key="doc.id">
                                <span @click="readDoc(doc, docIndex, 0, true)" v-html="doc.title"></span>
                                <a style="float: right;" target="_blank" :href="doc.link + utl_param"><span
                                    class="dashicons dashicons-external"></span></a>
                            </li>
                        </ul>
                        <p v-else>{{ $t('Sorry! No docs found') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div id="doc_search_end"></div>
        <div v-if="reading_doc_status">
            <div class="doc_read">
                <h1><a target="_blank" rel="noopener" :href="reading_doc.doc.link + utl_param" v-html="reading_doc.doc.title"></a></h1>
                <div class="simple_dic">
                    You are reading the simple version of this document. <a target="_blank" rel="noopener" :href="reading_doc.doc.link + utl_param">To read the doc on our site click here</a>. To view all doc index <a @click.prevent="reading_doc_status = false" href="#"><b>click here</b></a>
                </div>
                <div v-html="reading_doc.doc.content" class="reading_doc_body"></div>
                <div class="doc_navigation">
                    <el-button @click="goToNext()">Read Next Documentation</el-button>
                </div>
            </div>
        </div>
        <div v-else class="doc_body">
            <div class="doc_each_items" v-for="(docItems, docCatIndex) in doc_cats" :key="docCatIndex">
                <div class="fc_doc_items">
                    <div class="fc_doc_header">
                        <h3>{{ docItems.label }}</h3>
                    </div>
                    <div class="fc_doc_lists">
                        <ul>
                            <li v-for="(doc, docIndex) in docItems.docs" :key="doc.id">
                                <span @click="readDoc(doc, docIndex, docCatIndex)" v-html="doc.title"></span>
                                <a style="float: right;" rel="noopener" target="_blank" :href="doc.link + utl_param">
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import Fuse from 'fuse.js';

export default {
    name: 'Documentations',
    data() {
        return {
            search: '',
            fetching: false,
            docs: [],
            utl_param: '?utm_source=wp&utm_medium=doc&utm_campaign=doc',
            reading_doc_status: false,
            reading_doc: {},
            fuseDocs: null
        }
    },
    computed: {
        doc_cats() {
            if (!this.docs.length) {
                return [];
            }

            const items = {
                item_3: {
                    label: this.$t('Getting Started With Audience'),
                    docs: []
                },
                item_7: {
                    label: this.$t('Grow Your Audience'),
                    docs: []
                },
                item_4: {
                    label: this.$t('Email Campaign'),
                    docs: []
                },
                item_5: {
                    label: this.$t('Automation Funnels'),
                    docs: []
                }
            };
            this.each(this.docs, (doc) => {
                const keyName = 'item_' + doc.category.value;
                if (!items[keyName]) {
                    items[keyName] = {
                        label: doc.category.label,
                        cat_id: doc.category.value,
                        docs: []
                    }
                }
                items[keyName].docs.push(doc);
            });
            return Object.values(items);
        },
        search_items() {
            if (!this.search || !this.docs.length) {
                return [];
            }

            const items = this.fuseDocs.search(this.search);

            return items.map((item) => {
                return item.item;
            });
        }
    },
    methods: {
        openSearch() {

        },
        fetchDocs() {
            this.fetching = true;
            this.$get('docs')
                .then(response => {
                    this.docs = response.docs;
                    this.fuseDocs = new Fuse(this.docs, {
                        keys: ['title', 'content']
                    });
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.fetching = false;
                });
        },
        readDoc(doc, docIndex, catIndex, isSearch = false) {
            document.getElementById('doc_search_end').scrollIntoView();
            this.reading_doc = {
                doc: doc,
                docIndex,
                catIndex,
                isSearch
            }
            this.reading_doc_status = true;
        },
        goToNext() {
            if (this.reading_doc.isSearch) {
                if (this.search_items[this.reading_doc.docIndex + 1]) {
                    this.readDoc(this.search_items[this.reading_doc.docIndex + 1], this.reading_doc.docIndex + 1, 0, true);
                } else {
                    this.reading_doc_status = false;
                    this.search = '';
                }
                return false;
            }
            if (this.doc_cats[this.reading_doc.catIndex].docs[this.reading_doc.docIndex + 1]) {
                this.readDoc(this.doc_cats[this.reading_doc.catIndex].docs[this.reading_doc.docIndex + 1], this.reading_doc.docIndex + 1, this.reading_doc.catIndex);
            } else if (this.doc_cats[this.reading_doc.catIndex + 1] && this.doc_cats[this.reading_doc.catIndex + 1].docs[0]) {
                this.readDoc(this.doc_cats[this.reading_doc.catIndex + 1].docs[0], 0, this.reading_doc.catIndex + 1);
            } else {
                this.reading_doc_status = false;
            }
        }
    },
    mounted() {
        this.fetchDocs();
    }
}
</script>
