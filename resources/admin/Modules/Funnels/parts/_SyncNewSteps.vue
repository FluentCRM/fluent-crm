<template>
    <div v-loading="loading">
        <p style="font-size: 16px;">{{ $t('Automation_Sync_Note_1') }}</p>
        <div v-if="syncableCount">
            <h3>There has around {{syncableCount}} contacts that can be resumed to your newly added automation steps</h3>
            <template v-if="has_campaign_pro">
                <el-button :disabled="processing" @click="syncSteps" v-loading="processing" type="success" siz="small">{{ $t('Automation_Sync_Note_2') }}</el-button>
                <p>{{ $t('Automation_Sync_Note_2') }}</p>
            </template>
            <template v-else>
                <generic-promo />
            </template>
        </div>
        <div v-else-if="loading">
            <h3>{{ $t('Loading.....') }}</h3>
        </div>
        <div v-else>
            <h3>{{ $t('Automation_Sync_Note_4') }}</h3>
        </div>
    </div>
</template>

<script type="text/babel">
import GenericPromo from '../../Promos/GenericPromo.vue';

export default {
    name: 'SyncNewSteps',
    components: {GenericPromo},
    props: ['funnel_id'],
    data() {
        return {
            schedule_at: '',
            syncableCount: 0,
            loading: false,
            processing: false
        }
    },
    methods: {
        getCount() {
            this.loading = true;
            this.$get(`funnels/${this.funnel_id}/syncable-counts`)
                .then(response => {
                    this.syncableCount = response.syncable_count;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        syncSteps() {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Automation_Sync_Note_5'));
                return;
            }

            this.processing = true;
            this.$post(`funnels/${this.funnel_id}/sync-new-steps`)
                .then(response => {
                    this.$notify.success(this.$t('Automation_Sync_Note_6'));
                    this.syncableCount = 0;
                    this.$emit('reload');
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.processing = false;
                });
        }
    },
    mounted() {
        this.getCount();
    }
}
</script>
