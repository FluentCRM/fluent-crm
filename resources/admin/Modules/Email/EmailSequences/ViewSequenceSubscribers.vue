<template>
    <div v-loading="loading" class="fluentcrm-campaigns fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <el-breadcrumb class="fluentcrm_spaced_bottom" separator="/">
                    <el-breadcrumb-item :to="{ name: 'email-sequences' }">
                        {{$t('Email Sequences')}}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item :to="{ name: 'edit-sequence', params: { id: id } }">
                        {{ sequence.title }}
                    </el-breadcrumb-item>
                    <el-breadcrumb-item>
                        {{$t('Subscribers')}}
                    </el-breadcrumb-item>
                </el-breadcrumb>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <el-button @click="backToEmails()" size="small">
                    {{$t('View Emails')}}
                </el-button>
                <el-button v-if="hasPermission('fcrm_manage_emails')" @click="show_adder = !show_adder" type="primary" size="small">
                    <span v-if="!show_adder">{{ $t('Add Subscribers') }}</span>
                    <span v-else>{{$t('Show Subscribers')}}</span>
                </el-button>
            </div>
        </div>
        <div class="fluentcrm_body fluentcrm_pad_30">
            <subscribers-adder v-if="show_adder" @completed="reloadSubscribers()" :sequence_id="id"></subscribers-adder>
            <div v-else class="fluentcrm_sequence_subs">
                <sequence-subscribers-view :reload_count="reload_count" :sequence_id="id"/>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import SubscribersAdder from './_SubscribersAdder';
import SequenceSubscribersView from './_SequenceSubscribers';

export default {
    name: 'SequenceSubscribers',
    props: ['id'],
    components: {
        SubscribersAdder,
        SequenceSubscribersView
    },
    data() {
        return {
            loading: false,
            sequence: {},
            reload_count: 0,
            show_adder: false
        }
    },
    methods: {
        fetchSequence() {
            this.loading = true;
            this.$get(`sequences/${this.id}`)
                .then(response => {
                    this.sequence = response.sequence;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        backToEmails() {
            this.$router.push({
                name: 'edit-sequence',
                params: {
                    id: this.id
                },
                query: {t: (new Date()).getTime()}
            });
        },
        reloadSubscribers() {
            this.show_adder = false;
            this.reload_count += 1;
        }
    },
    mounted() {
        this.fetchSequence();
    }
}
</script>
