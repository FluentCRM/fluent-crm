<template>
    <div class="fluentcrm-campaigns">
        <el-dialog :close-on-click-modal="false" width="50%" :title="dialogTitle" :append-to-body="true" :visible.sync="isVisibile" @opened="opened" @closed="closed">
            <div>
                <el-col :span="24">
                    <el-input
                        ref="title"
                        :placeholder="$t('Title')"
                        v-model="campaign.title"
                        @keyup.enter.native="save"
                    />
                    <span class="error">{{ error }}</span>
                </el-col>
            </div>
            <div slot="footer" class="save-campaign-dialog-footer">
                <el-button style="margin-top: 20px;" type="primary" @click="save" :loading="saving">
                    {{$t('Create Campaign')}}
                </el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script>
export default {
    name: 'SaveCampaign',
    props: ['dialogTitle', 'dialogVisible', 'selectedCampaign'],
    data() {
        return {
            error: '',
            saving: false,
            campaign: {
                id: null,
                title: ''
            }
        };
    },
    computed: {
        isVisibile: {
            get() {
                return this.dialogVisible;
            },
            set(v) {
                this.$emit('toggleDialog', v);
            }
        }
    },
    methods: {
        save() {
            if (!this.campaign.title) {
                this.error = this.$t('Title field is required');
                return;
            }

            this.error = '';
            this.saving = true;

            let $request = false;
            if (parseInt(this.campaign.id)) {
                $request = this.$put(`campaigns/${this.campaign.id}`,
                    this.campaign
                )
            } else {
                $request = this.$post('campaigns',
                    this.campaign
                )
            }

            $request.then(response => {
                this.isVisibile = false;
                this.$emit('saved', response);
            }).catch(r => {
                const res = r.data ? r.data : r;
                if (res.status && res.status === 403) {
                    this.isVisibile = false;
                    this.$message(res.message, this.$t('Oops!'), {
                        center: true,
                        type: 'warning',
                        confirmButtonText: this.$t('Close'),
                        dangerouslyUseHTMLString: true,
                        callback: action => {
                            this.$router.push({
                                name: 'campaigns',
                                query: {t: (new Date()).getTime()}
                            });
                        }
                    });
                } else {
                    const keys = Object.keys(res.title);
                    this.error = res.title[keys[0]];
                }
            }).finally(r => {
                this.saving = false;
            });
        },
        opened() {
            if (this.selectedCampaign) {
                this.campaign.id = this.selectedCampaign.id;
                this.campaign.title = this.selectedCampaign.title;
            }

            this.$refs['title'].focus();
        },
        closed() {
            this.error = '';
            this.campaign.title = '';
        }
    }
};
</script>

<style>
.fluentcrm-campaigns .error {
    color: #f56c6c;
    font-size: 12px;
}

.fluentcrm-campaigns .save-campaign-dialog-footer {
    margin-top: 30px;
}
</style>
