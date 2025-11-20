<template>
    <confirm placement="top-start" :message="confirm_message" @yes="deleteSubscribers()">
        <el-button
            style="margin: 20px 10px 10px 15px;"
            type="danger"
            size="mini"
            v-loading="loading"
            slot="reference"
            icon="el-icon-delete"
        >{{$t('Delete Selected')}} ({{selectedSubscribers.length}})
        </el-button>
    </confirm>
</template>
<script type="text/babel">
    import Confirm from '@/Pieces/Confirm';
    export default {
        name: 'PropertyChanger',
        components: {
            Confirm
        },
        props: ['selectedSubscribers'],
        data() {
          return {
              loading: false,
              confirm_message: '<b>' + this.$t('Are you sure to delete?') + '</b><br />' + this.$t('delete_all_contacts_notice')
          }
        },
        methods: {
            deleteSubscribers() {
                this.loading = true;
                this.$del('subscribers', {
                    subscribers: this.selectedSubscribers.map(item => item.id)
                })
                    .then(response => {
                        this.$notify.success(response.message);
                        this.$emit('fetch');
                    })
                    .catch(error => {
                        console.log(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        }
    }
</script>
