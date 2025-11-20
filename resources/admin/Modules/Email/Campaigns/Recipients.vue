<template>
    <div class="recipients">
        <el-form v-if="!inserting_now">
            <recipient-tagger-form v-model="campaign.settings"/>
            <el-row style="max-width: 860px; margin: 0 auto" :gutter="20">
                <el-col :span="12">
                    <el-button
                        size="small"
                        type="text"
                        @click="goToPrev()"
                    > {{ $t('Back') }}
                    </el-button>
                </el-col>
                <el-col :span="12">
                    <el-button
                        size="small"
                        type="success"
                        class="pull-right"
                        :loading="btnSubscribing"
                        @click="start_process()"
                    >
                        {{ $t('Rec_Continue_TNS_aS') }}
                    </el-button>
                </el-col>
            </el-row>
        </el-form>
        <div class="text-align-center" v-else>
            <h3>{{ $t('Processing now...') }}</h3>
            <h4>{{ $t('Rec_Please_dnctw') }}</h4>
            <template v-if="total_contacts">
                <h2>{{ inserted_total }}/{{ total_contacts }}</h2>
                <el-progress :text-inside="true" :stroke-width="24"
                             :percentage="progressPercent"
                             status="success"></el-progress>
                <p v-loading="inserting_now && !processingError">{{ $t('Processing now.Please wait a bit...') }}</p>
            </template>

            <div v-if="processingError">
                <h3>{{ $t('Rec_Processing_EhMit') }}</h3>
                <el-button size="small" type="danger" @click="retryProcess()">{{ $t('Resume') }}</el-button>
                <el-button size="small" type="primary" @click="startOver()">{{ $t('StartOver') }}</el-button>
                <div style="text-align: left;">
                    <h3>Error Details</h3>
                    <pre>{{processingError}}</pre>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import RecipientTaggerForm from '@/Pieces/RecipientTaggerForm';

export default {
    name: 'Recipients',
    components: {
        RecipientTaggerForm
    },
    props: ['campaign'],
    data() {
        return {
            btnDeleting: false,
            btnSubscribing: false,
            inserting_page: 1,
            inserting_total_page: 0,
            total_contacts: 0,
            inserted_total: 0,
            inserting_now: false,
            processingError: false
        }
    },
    computed: {
        progressPercent() {
            if (this.total_contacts) {
                return parseInt((this.inserted_total / this.total_contacts) * 100);
            }
            return 1;
        }
    },
    methods: {
        validateLists() {
            const settings = this.campaign.settings;

            const data = settings.subscribers.filter(i => i.list && i.tag);
            const excludeData = settings.excludedSubscribers.filter(i => i.list && i.tag);

            if (settings.sending_filter === 'list_tag') {
                if (
                    (data.length !== settings.subscribers.length) ||
                    (excludeData.length && excludeData.length !== settings.excludedSubscribers.length)
                ) {
                    this.$notify.error({
                        title: this.$t('Oops!'),
                        message: this.$t('Recipients.instruction'),
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
          //  this.inserting_now = true;
            this.processingError = false;

            this.$post(`campaigns/${this.campaign.id}/draft-recipients`, postData)
                .then(response => {
                    if (typeof response != 'object') {
                        this.handleError(response);
                        this.processingError = response;
                        return;
                    }

                    if (response.has_more) {
                        this.inserting_page = response.next_page;
                        this.inserted_total = response.count;
                        // Let's rewind the request
                        this.$nextTick(() => {
                            this.validateLists();
                        });
                    } else {
                        this.campaign.recipients_count = response.count;
                        this.$notify.success({
                            title: this.$t('Great!'),
                            message: this.$t('Contacts has been attached with this campaign'),
                            offset: 19
                        });
                        this.$emit('next', 1);
                        this.btnSubscribing = false;
                        this.inserting_now = false;
                        this.inserting_page = 1;
                    }
                })
                .catch(error => {
                    this.handleError(error);
                    this.processingError = error || this.$t('Unknown error');
                })
                .finally(() => {
                });
        },
        startOver() {
            this.processingError = false;
            this.btnSubscribing = false;
            this.inserting_now = false;
        },
        retryProcess() {
            this.processingError = false;
            this.validateLists();
        },
        goToPrev() {
            this.$emit('prev', 1);
        },
        fetchEstimated() {
            const settings = this.campaign.settings;

            const data = settings.subscribers.filter(i => i.list && i.tag);
            const excludeData = settings.excludedSubscribers.filter(i => i.list && i.tag);

            if (settings.sending_filter === 'list_tag') {
                if (
                    (data.length !== settings.subscribers.length) ||
                    (excludeData.length && excludeData.length !== settings.excludedSubscribers.length)
                ) {
                    return;
                }
            } else if (settings.sending_filter == 'dynamic_segment') {
                if (!settings.dynamic_segment.uid) {
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
                    return;
                }
            }

            const postData = {
                subscribers: data,
                excludedSubscribers: excludeData,
                sending_filter: settings.sending_filter,
                dynamic_segment: settings.dynamic_segment,
                advanced_filters: JSON.stringify(settings.advanced_filters)
            };

            this.$post('campaigns/estimated-contacts', postData)
                .then(response => {
                    this.total_contacts = response.count;
                })
                .catch((error) => {
                    this.handleError(error);
                });
        },
        start_process() {
            this.validateLists();
            this.fetchEstimated();
        }
    }
}
</script>

<style lang="scss">
.list {
    .el-select-dropdown__item {
        height: 55px !important;
    }
}

.list-metrics {
    color: #8492a6;
    font-size: 13px;
    line-height: 1;
    display: block
}

.fluentcrm-campaign .recipients .pull-left {
    float: left;
}

.fluentcrm-campaign .recipients .pull-right {
    float: right;
}

.fluentcrm-campaign .recipients .recipients-label {
    font-size: 14px;
    margin-bottom: 5px;
}

.fluentcrm-campaign .recipients .status {
    display: inline-block;
    font-size: 10px;
    width: 80px;
}

.fluentcrm-campaign .recipients .status-draft {
    color: #909399;
    border: solid 1px #909399;
}

.fluentcrm-campaign .recipients .status-pending {
    color: #409eff;
    border: solid 1px #409eff;
}

.fluentcrm-campaign .recipients .status-archived {
    color: #67c23a;
    border: solid 1px #67c23a;
}

.fluentcrm-campaign .recipients .status-incomplete {
    color: #f56c6c;
    border: solid 1px #f56c6c;
}

.fluentcrm-campaign .recipients .status-working {
    color: #a7cc90;
    border: solid 1px #a7cc90;
    opacity: 1;
    position: relative;
    transition: opacity linear 0.1s;
}

.fluentcrm-campaign .recipients .status-working::before {
    animation: 2s linear infinite working;
    border: solid 3px #eee;
    border-bottom-color: #a7cc90;
    border-radius: 50%;
    content: "";
    height: 10px;
    left: 10px;
    opacity: inherit;
    position: absolute;
    top: 50%;
    transform: translate3d(-50%, -50%, 0);
    transform-origin: center;
    width: 10px;
    will-change: transform;
}

@keyframes working {
    0% {
        transform: translate3d(-50%, -50%, 0) rotate(0deg);
    }
    100% {
        transform: translate3d(-50%, -50%, 0) rotate(360deg);
    }
}

.fluentcrm-campaign .recipients .status-sent {
    color: #67c23a;
    border: solid 1px #67c23a;
}

.fluentcrm-campaign .recipients .status-purged {
    color: #e6a23d;
    border: solid 1px #e6a23d;
}

.fluentcrm-campaign .recipients .lists-string .cell {
    word-break: break-word;
}
</style>
