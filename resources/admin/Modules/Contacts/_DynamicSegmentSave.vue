<template>
    <el-popover
        placement="right"
        width="400"
        trigger="click">
        <div v-loading="saving">
            <h4>{{ $t('Save as dynamic Segment') }}</h4>
            <el-input v-model="title" type="text" placeholder="Name of the segment"></el-input>
            <el-button :disabled="saving" @click="save()" style="margin-top: 20px;" size="small" type="primary">{{ $t('Save') }}</el-button>
        </div>
        <i slot="reference" style="font-size: 18px; cursor: pointer;" class="el-icon el-icon-more icon-90degree el-icon--right"></i>
    </el-popover>
</template>

<script type="text/babel">
export default {
    name: 'DynamicSegmentSave',
    props: ['advance_filters'],
    data() {
        return {
            title: '',
            saving: false
        }
    },
    methods: {
        save() {
            if (!this.title) {
                this.$notify.error(this.$t('Cre_Please_pNotS'));
                return;
            }
            const data = {
                segment: JSON.stringify({
                    title: this.title,
                    filters: this.advance_filters
                }),
                with_subscribers: false
            };
            this.saving = true;
            this.$post('dynamic-segments', data)
                .then(response => {
                    this.title = '';
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'view_segment',
                        params: {
                            slug: response.segment.slug,
                            id: response.segment.id
                        }
                    });
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    }
}
</script>
