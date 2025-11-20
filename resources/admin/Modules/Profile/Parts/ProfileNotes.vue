<template>
    <div class="fc_notes_wrapper">
        <object-notes-template :subscriber_id="subscriber_id"
                               route_prefix="subscribers"
                               :section_title="$t('Pro_Contact_N_A')"
        />
    </div>
</template>

<script type="text/babel">
import ObjectNotesTemplate from '@/Pieces/ObjectNotesTemplate';

export default {
    name: 'ProfileNotes',
    props: ['subscriber_id'],
    components: {
        ObjectNotesTemplate
    },
    mounted() {
        this.changeTitle(this.$t('Notes'));
    }
}
</script>
