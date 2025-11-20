<template>
    <div style="margin-bottom: 20px;" class="fluentcrm_sequence_sub_adder">
        <template v-if="ready_tagger">
            <recipient-tagger-form v-model="settings">
                <template slot="fc_tagger_bottom">
                    <div class="text-align-center">
                        <p>
                            {{$t('_Su_Please_ntsewbstt')}}
                        </p>
                    </div>
                    <div class="text-align-center">
                        <el-button @click="processSubscribers()" type="success" size="large">
                            {{$t('Add to this Sequence')}}
                        </el-button>
                    </div>
                </template>
            </recipient-tagger-form>
        </template>
        <div class="text-align-center fluentcrm_hero_box" v-else>
            <template v-if="batch_completed">
                <h3>{{$t('Completed')}}</h3>
                <h4>{{$t('_Su_All_SLashbaSttsE')}}</h4>
                <el-button v-show="batch_completed" @click="resetSettings()" type="success" size="small">
                    {{$t('Back')}}
                </el-button>
                <p><b>{{ in_total }}</b> {{$t('_Su_Subscribers_hbat')}}</p>
            </template>
            <template v-else>
                <h3>{{$t('Processing now...')}}</h3>
                <h4>{{$t('Rec_Please_dnctw')}}</h4>
                <template v-if="inserting_total_page">
                    <h2>{{ inserting_page }}/{{ inserting_total_page }}</h2>
                    <el-progress :text-inside="true" :stroke-width="24"
                                 :percentage="parseInt((inserting_page / inserting_total_page) * 100)"
                                 status="success"></el-progress>
                </template>
            </template>
        </div>
    </div>
</template>

<script type="text/babel">
import RecipientTaggerForm from '@/Pieces/RecipientTaggerForm';

export default {
    name: 'sequence_sub_adder',
    props: ['sequence_id'],
    components: {
        RecipientTaggerForm
    },
    data() {
        return {
            settings: {
                subscribers: [
                    {
                        list: null,
                        tag: null
                    }
                ],
                excludedSubscribers: [
                    {
                        list: null,
                        tag: null
                    }
                ],
                sending_filter: 'list_tag',
                dynamic_segment: {
                    id: '',
                    slug: ''
                }
            },
            ready_tagger: true,
            inserting_page: 1,
            inserting_total_page: 0,
            inserting_now: false,
            btnSubscribing: false,
            batch_completed: false,
            in_total: 0,
            estimated_count: 0
        }
    },
    watch: {

    },
    methods: {
        processSubscribers() {
            const settings = this.settings;

            const data = settings.subscribers.filter(i => i.list && i.tag);
            const excludeData = settings.excludedSubscribers.filter(i => i.list && i.tag);

            if (settings.sending_filter === 'list_tag') {
                if (
                    (data.length !== settings.subscribers.length) ||
                    (excludeData.length && excludeData.length !== settings.excludedSubscribers.length)
                ) {
                    this.$notify.error({
                        title: this.$t('Oops!'),
                        message: this.$t('_Su_Invalid_solatisi'),
                        offset: 19
                    });
                    return;
                }
            } else if (settings.sending_filter == 'dynamic_segment') {
                if (!settings.dynamic_segment.uid) {
                    this.$notify.error({
                        title: this.$t('Oops!'),
                        message: this.$t('Please select the segment'),
                        offset: 19
                    });
                    return;
                }
            } else if (settings.sending_filter == 'advanced_filters') {
                let isValid = false;
                this.each(settings.advanced_filters, (filter) => {
                    if (!this.isEmptyValue(filter)) {
                        isValid = true;
                    }
                });

                if (!isValid) {
                    this.$notify.error({
                        title: this.$t('Oops!'),
                        message: this.$t('Please select the filters'),
                        offset: 19
                    });
                    return;
                }
            }

            const postData = {
                subscribers: data,
                excludedSubscribers: excludeData,
                sending_filter: settings.sending_filter,
                dynamic_segment: settings.dynamic_segment,
                page: this.inserting_page,
                advanced_filters: JSON.stringify(settings.advanced_filters)
            };

            this.btnSubscribing = true;
            this.inserting_now = true;
            this.ready_tagger = false;
            this.$post(`sequences/${this.sequence_id}/subscribers`, postData)
                .then(response => {
                    if (response.remaining) {
                        if (this.inserting_page === 1) {
                            this.inserting_total_page = response.page_total;
                        }
                        this.inserting_page = response.next_page;
                        // Let's rewind the request
                        this.$nextTick(() => {
                            this.processSubscribers();
                        });
                    } else {
                        this.batch_completed = true;
                        this.$notify.success({
                            title: this.$t('Great!'),
                            message: this.$t('_Su_Subscribers_hbas'),
                            offset: 19
                        });
                        this.in_total = response.in_total;
                    }
                })
                .catch(error => {
                    this.handleError(error);
                    this.btnSubscribing = false;
                    this.inserting_now = false;
                })
                .finally(() => {
                });
        },
        resetSettings() {
            this.ready_tagger = false;
            this.batch_completed = false;
            this.inserting_now = false;
            this.btnSubscribing = false;
            this.inserting_page = 1;
            this.inserting_total_page = 0;
            this.settings = {
                subscribers: [
                    {
                        list: null,
                        tag: null
                    }
                ],
                excludedSubscribers: [
                    {
                        list: null,
                        tag: null
                    }
                ],
                sending_filter: 'list_tag',
                dynamic_segment: {
                    id: '',
                    slug: ''
                }
            };
            this.$nextTick(() => {
                this.ready_tagger = true;
            });
        }
    }
}
</script>
