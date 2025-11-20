<template>
    <div class="fc_create_link_wrapper">
        <div v-if="link_created" class="fc_narrow_box fc_white_inverse text-align-center">
            <p>
                {{$t('_Cr_Please_utfsuawau')}}
            </p>
            <el-input type="textarea" :readonly="true" v-model="link.short_url" />
        </div>
        <div v-else>
            <form-builder :fields="fields" :formData="link" />
            <el-button v-if="item_mode == 'create'" @click="createLink()" type="primary">
                {{$t('Create Smart Link')}}
            </el-button>
            <el-button v-else @click="updateLink()" type="success">
                {{$t('Update Smart Link')}}
            </el-button>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'CreateLink',
    components: {
        FormBuilder
    },
    props: ['editing_row', 'item_mode'],
    data() {
        return {
            link: {
                title: '',
                notes: '',
                target_url: '',
                actions: { tags: [], lists: []},
                detach_actions: { tags: [], lists: []}
            },
            creating: false,
            fields: {
                title: {
                    type: 'input-text',
                    placeholder: this.$t('Link Title'),
                    label: this.$t('Link Title'),
                    help: this.$t('Your link title so you do not forget it')
                },
                target_url: {
                    type: 'input-text',
                    data_type: 'url',
                    placeholder: this.$t('Your target URL'),
                    label: this.$t('Target Full URL'),
                    help: this.$t('Insert a valid url that this link will direct to')
                },
                actions: {
                    type: 'input-tag-list',
                    label: '',
                    wrapper_class: 'fc_group_field_half',
                    tag_label: this.$t('Apply Tags when clicked (optional)'),
                    tag_help: this.$t('CreateLink.Action.tag_help'),
                    list_label: this.$t('Apply Lists when clicked (optional)'),
                    list_help: this.$t('CreateLink.Action.list_help')
                },
                detach_actions: {
                    type: 'input-tag-list',
                    label: '',
                    wrapper_class: 'fc_group_field_half',
                    tag_label: this.$t('Remove Tags when clicked (optional)'),
                    tag_help: this.$t('CreateLink.Detach_Action.tag_help'),
                    list_label: this.$t('Remove Lists when clicked (optional)'),
                    list_help: this.$t('CreateLink.Detach_Action.list_help')
                },
                auto_login: {
                    type: 'inline-checkbox',
                    true_label: 'yes',
                    false_label: 'no',
                    checkbox_label: this.$t('Auto_Login_Label'),
                    label: this.$t('Auto Login'),
                    help: this.$t('__CreateLinkAutoHelp')
                },
                notes: {
                    type: 'input-text',
                    placeholder: this.$t('Your URL Note'),
                    data_type: 'textarea',
                    label: this.$t('Note (Optional)'),
                    help: this.$t('Feel free to add a note regarding this URL')
                }
            },
            link_created: false
        }
    },
    methods: {
        createLink() {
            this.creating = true;
            this.$post('smart-links', {
                link: this.link
            })
                .then(response => {
                    this.$emit('linkCreated', response.link);
                    this.$notify.success(response.message);
                    this.link.short_url = response.link.short_url;
                    this.link_created = true;
                })
                .catch((errors) => {
                    this.handleError(errors)
                })
                .finally(() => {
                    this.creating = false;
                });
        },
        updateLink() {
            this.$put(`smart-links/${this.link.id}`, {
                link: this.link
            })
                .then(response => {
                    this.$emit('linkCreated', response.link);
                    this.$notify.success(response.message);
                    this.link_created = true;
                })
                .catch((errors) => {
                    this.handleError(errors)
                })
                .finally(() => {
                    this.creating = false;
                });
        }
    },
    created() {
        if (this.item_mode == 'edit') {
            this.link = JSON.parse(JSON.stringify(this.editing_row));
        }
    }
}
</script>
