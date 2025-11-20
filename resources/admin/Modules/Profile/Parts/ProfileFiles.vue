<template>
    <div v-loading="loading" class="fluentcrm_databox">

        <div class="fluentcrm_contact_header">
            <el-row :gutter="30">
                <el-col :span="12">
                    <h3>{{$t('Pro_Contact_F_A')}}</h3>
                </el-col>
                <el-col class="text-align-right" :span="12">
                    <el-button @click="adding_file = !adding_file" type="primary" size="small">{{$t('Add New')}}</el-button>
                </el-col>
            </el-row>
        </div>

        <div class="fc_uploader_drawer">
            <el-uploader></el-uploader>
        </div>

        <div>

        </div>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'ProfileFiles',
        props: ['subscriber_id'],
        data() {
            return {
                adding_file: false,
                files: [],
                loading: false,
                pagination: {
                    total: 0,
                    per_page: 10,
                    current_page: 1
                },
                new_note: {
                    title: '',
                    description: '',
                    type: 'note'
                }
            }
        },
        methods: {
            fetchAttachments() {

            }
        },
        mounted() {
            this.fetchAttachments();
        }
    }
</script>
