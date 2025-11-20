<template>
    <div class="fc_notes_wrapper fc_company_notes_wrap">
        <object-notes-template style="box-shadow: none; border-radius: 0;" :subscriber_id="company_id"
                               route_prefix="companies"
                               :section_title="$t('Notes & Activities')"
        />
    </div>
</template>

<script type="text/babel">
import ObjectNotesTemplate from '@/Pieces/ObjectNotesTemplate';

export default {
    name: 'CompanyNotes',
    props: ['company_id'],
    components: {
        ObjectNotesTemplate
    },
    mounted() {
        this.changeTitle(this.$t('Notes'));
    }
}
</script>
