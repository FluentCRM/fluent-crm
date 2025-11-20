<template>
    <div>
        <div class="fluentcrm_inner_header">
            <h3>{{ $t('Share Newsletter via URL') }}</h3>
            <p>{{ $t('Share_Newsletter_Desc') }}</p>
            <hr/>
            <item-copier :text="newsletterUrl" :show-view-button="true" :loading="loading" />
        </div>
    </div>
</template>

<script type="text/javascript">
import ItemCopier from '@/Pieces/ItemCopier';

export default {
    name: 'ViewNewsletterSettings',
    props: ['campaign_id'],
    components: {
        ItemCopier
    },
    data() {
        return {
            newsletterUrl: '',
            loading: false
        }
    },
    methods: {
        fetchNewsletterUrl() {
            this.loading = true;
            this.$get(`campaigns/${this.campaign_id}/share-url`)
                .then(response => {
                    this.newsletterUrl = response.sharable_url;
                })
                .catch(error => {
                    this.$handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.fetchNewsletterUrl();
    }
}
</script>
