<template>
    <div class="contact_form_handler">
        <SkeletonLoading v-if="creating" />
        <forma
            v-else
            :list-id="listId"
            :tag-id="tagId"
            :errors="errors"
            :subscriber="subscriber"
            :company_id="company_id"
        />
        <div class="fc_drawer_footer_wrap">
            <!--save button-->
            <el-button
                size="small"
                type="primary"
                @click="save()">
                {{ $t('Create Contact') }}
            </el-button>
            <el-button
                size="small"
                type="text"
                @click="save(true)">
                {{ $t('Create & Add Another') }}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import Errors from '@/Bits/Errors';
import Forma from './Form';
import SkeletonLoading from '../../../Pieces/SkeletonLoading';

export default {
    name: 'ContactFormHandler',
    props: ['listId', 'tagId', 'company_id'],
    components: {
        SkeletonLoading,
        Forma
    },
    data() {
        return {
            exist: false,
            errors: new Errors(),
            subscriber: this.fresh(),
            creating: false
        }
    },
    methods: {
        fresh() {
            return {
                first_name: null,
                last_name: null,
                email: null,
                phone: '',
                date_of_birth: '',
                status: 'subscribed',
                address_line_1: '',
                address_line_2: '',
                city: '',
                state: '',
                postal_code: '',
                country: '',
                tags: [],
                lists: [],
                custom_values: {},
                double_optin: false
            };
        },
        save(addMore = false) {
            this.errors.clear();
            if (!this.subscriber.email) {
                this.$notify.error({
                    message: this.$t('Email field is required'),
                    offset: 19
                });
                return;
            }

            const query = {
                ...this.subscriber
            };

            if (this.listId) {
                query.lists = [this.listId];
            }

            if (this.tagId) {
                query.tags = [this.tagId];
            }

            if (this.company_id) {
                query.company_id = this.company_id;
            }

            this.creating = true;

            this.$post('subscribers', query).then(response => {
                this.$notify.success({
                    title: this.$t('Great!'),
                    message: response.message,
                    offset: 19
                });

                this.$emit('created', response.contact, addMore);
            })
                .catch(errors => {
                    this.errors.record(errors);
                    if (errors.subscriber) {
                        this.exist = errors.subscriber;
                    }
                })
                .finally(() => {
                    this.creating = false;
                });

            this.subscriber = this.fresh()
        }
    }
}
</script>
