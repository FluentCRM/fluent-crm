<template>
    <el-dialog
        :title="title"
        @close="hide()"
        :visible="visible"
        :append-to-body="true"
        :close-on-click-modal="false"
        class="fluentcrm-lists-dialog"
    >
        <forma :errors="errors" :item="item"/>

        <div slot="footer" class="dialog-footer">
            <div>
                <el-button size="small" @click="hide()">
                    {{$t('Cancel')}}
                </el-button>

                <!--save button-->
                <el-button
                    size="small"
                    :type="item.id ? 'success' : 'primary'"
                    @click="save()">
                    {{item.id ? $t('Update') : $t('Create')}}
                </el-button>
            </div>
        </div>
    </el-dialog>
</template>

<script>
import Forma from './Form';
import Errors from '@/Bits/Errors';

export default {
    name: 'Adder',
    components: {
        Forma
    },
    props: {
        visible: Boolean,
        type: {
            type: String,
            required: true
        },
        api: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            item: this.fresh(),
            errors: new Errors(),
            title: this.$t('Add New') + ' ' + this.ucFirst(this.trans(this.type))
        }
    },
    methods: {
        fresh() {
            return {
                title: null,
                slug: null,
                description: ''
            };
        },
        reset() {
            this.errors.clear();
            this.item = this.fresh();
        },
        hide() {
            this.reset();
            this.$emit('close');
        },
        save() {
            this.errors.clear();

            const query = {
                ...this.item
            };

            let $request = false;
            if (this.item.id) {
                $request = this.$put(this.api.store + '/' + this.item.id, query)
            } else {
                $request = this.$post(this.api.store, query)
            }

            $request
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });

                    this.$emit('fetch', this.item);

                    this.$bus.$emit('renew_options', this.type);

                    this.hide();
                }).catch(errors => {
                    this.errors.record(errors);
                });
        },
        listeners() {
            const event = 'edit-' + this.type;

            this.$bus.$on(event, item => {
                this.item = {
                    id: item.id,
                    slug: item.slug,
                    title: item.title,
                    description: item.description
                };

                this.title = this.$t('Edit') + ' ' + this.ucFirst(this.type);
            });
        }
    },
    mounted() {
        this.listeners();
    }
};
</script>
