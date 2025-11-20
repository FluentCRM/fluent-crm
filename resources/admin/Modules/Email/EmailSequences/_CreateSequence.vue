<template>
    <el-dialog
        :title="$t('Create new email sequence')"
        :visible.sync="isVisibile"
        :append-to-body="true"
        :close-on-click-modal="false"
        width="60%">

        <div>
            <el-form @submit.prevent.native="save" :model="sequence" label-position="top">
                <el-form-item :label="$t('Sequence Title')">
                    <el-input
                        ref="title"
                        :placeholder="$t('Sequence Title')"
                        v-model="sequence.title"
                        @keyup.enter.native="save"
                    />
                    <span class="error">{{ errors.title }}</span>
                </el-form-item>
            </el-form>
        </div>

        <span slot="footer" class="dialog-footer">
            <el-button v-loading="saving" :disabled="saving" type="primary" @click="save()">{{$t('Next')}}</el-button>
        </span>
    </el-dialog>
</template>

<script type="text/babel">
export default {
    name: 'CreateSequence',
    props: ['dialogVisible'],
    data() {
        return {
            sequence: {
                title: ''
            },
            errors: {title: ''},
            saving: false
        }
    },
    methods: {
        save() {
            if (!this.sequence.title) {
                this.errors.title = this.$t('Title field is required');
                return;
            }

            this.errors = {title: ''};
            this.saving = true;

            this.$post('sequences', this.sequence)
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'edit-sequence',
                        params: {
                            id: response.sequence.id
                        }
                    })
                })
                .catch(errors => {
                    const res = errors.data ? errors.data : errors;
                    if (res.status && res.status === 403) {
                        this.notify.error(res.message);
                    } else if (res.title) {
                        const keys = Object.keys(res.title);
                        this.errors.title = res.title[keys[0]];
                    }
                })
                .finally(r => {
                    this.saving = false;
                });
        }
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
    }
}
</script>
