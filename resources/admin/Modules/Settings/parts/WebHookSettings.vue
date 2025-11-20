<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Incoming Webhook Settings')}}</h3>
            </div>
            <div v-if="has_campaign_pro" class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div class="fc-search-box">
                    <el-input
                        clearable
                        size="mini"
                        v-model="search"
                        @clear="fetch"
                        @keyup.enter.native="fetch"
                        :placeholder="$t('Type and Enter...')"
                    >
                        <el-button @click="fetch" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>

                <el-button
                    type="primary"
                    size="medium"
                    @click="create"
                    :loading="btnFromLoading || loading"
                    style="height: 32px;line-height: 10px;"
                >
                    {{$t('Create Webhook')}}
                </el-button>
            </div>
        </div>

        <div class="fluentcrm_pad_around fluentcrm_smart_links_wrap" style="position: relative;">

            <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <el-table :empty-text="$t('No Data Found')" v-if="has_campaign_pro && !loading" border stripe :data="webhooks">

                <el-table-column class="hello" type="expand">
                    <template slot-scope="scope">
                        <div style="padding-left: 47px;">
                            <h4 style="margin: 8px 0 8px 0;">{{$t('Attached Tags and Lists')}}</h4>
                            <p>
                                <list-tag-items v-if="scope.row.value.lists && scope.row.value.lists.length" item_type="lists" :items="scope.row.value.lists"/>
                                <list-tag-items item_type="tags" :items="scope.row.value.tags"/>
                            </p>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column :min-width="100" :label="$t('Title')">
                    <template slot-scope="scope">
                        {{ scope.row.value.name }}
                    </template>
                </el-table-column>

                <el-table-column :min-width="250" :label="$t('Smart URL')">
                    <template slot-scope="scope">
                        <item-copier :text="scope.row.value.url" />
                    </template>
                </el-table-column>

                <el-table-column width="120" :label="$t('Actions')">
                    <template slot-scope="scope">
                        <el-button type="primary"
                                   @click="edit(scope.row.value, scope.row.id)"
                                   size="mini"
                                   icon="el-icon-edit"
                        />
                        <confirm @yes="remove(scope.row.id)">
                            <el-button
                                size="mini"
                                type="danger"
                                slot="reference"
                                icon="el-icon-delete"
                            />
                        </confirm>
                    </template>
                </el-table-column>

            </el-table>
            <div v-else>
                <webhook-promo v-if="!loading" />
            </div>
        </div>

        <el-dialog
            width="60%"
            :title="dialogTitle"
            :close-on-click-modal="false"
            :append-to-body="true"
            :visible.sync="addWebhookVisible"
        >
            <el-form :label-position="labelPosition" label-width="150px" :model="webhook">
                <template>
                    <el-form-item :label="$t('Name')">
                        <el-input v-model="webhook.name"></el-input>
                        <error :error="errors.get('name')"/>
                    </el-form-item>

                    <el-form-item :label="$t('Default List')">
                        <el-select
                            multiple
                            v-model="webhook.lists"
                            :placeholder="$t('Select lists')"
                        >
                            <el-option
                                v-for="list in lists"
                                :key="list.id"
                                :label="list.title"
                                :value="String(list.id)">
                            </el-option>
                        </el-select>
                    </el-form-item>

                    <el-form-item :label="$t('Default Tags')">
                        <el-select
                            multiple
                            v-model="webhook.tags"
                            :placeholder="$t('Select tags')"
                        >
                            <el-option
                                v-for="tag in tags"
                                :key="tag.id"
                                :label="tag.title"
                                :value="String(tag.id)">
                            </el-option>
                        </el-select>
                    </el-form-item>

                    <el-form-item v-if="has_company_module" :label="$t('Default Companies')">
                        <el-select
                            multiple
                            v-model="webhook.companies"
                            :placeholder="$t('Select Companies')"
                        >
                            <el-option
                                v-for="company in companies"
                                :key="company.id"
                                :label="company.name"
                                :value="String(company.id)">
                            </el-option>
                        </el-select>
                    </el-form-item>

                    <el-form-item :label="$t('Status')">
                        <el-select
                            v-model="webhook.status"
                            :placeholder="$t('Select status')"
                        >
                            <el-option
                                v-for="status in statuses"
                                :key="status.value"
                                :value="status.value"
                                :label="status.label">
                            </el-option>
                        </el-select>
                        <error :error="errors.get('status')"/>
                        <p v-if="webhook.status == 'pending'">{{$t('A double opt-in email will be sent if the contact is new')}}</p>
                    </el-form-item>
                </template>

                <template v-if="editing">

                    <el-alert
                        type="info"
                        :closable="false"
                        :title="$t('WebHookSettings.Copy_Webhook_url')"
                    />

                    <item-copier :text="webhook.url" style="margin:20px 0" />

                    <el-alert type="info" :closable="false" style="margin-bottom:20px">
                        {{$t('copy_webhook_keys_intro')}}
                        <span style="color:#E6A23C">{{$t('The email address is required!')}}</span> <a target="_blank" rel="noopener" href="https://fluentcrm.com/docs/webhook-integration/">{{$t('Read the documentation')}}</a>
                    </el-alert>

                    <el-table :empty-text="$t('No Data Found')" border stripe height="250" :data="fields">
                        <el-table-column :label="$t('Contact Field')" prop="field" width="300"/>
                        <el-table-column :label="$t('Key')" prop="key"/>
                    </el-table>

                    <div v-if="customFields">
                        <el-alert
                            :title="$t('Custom Contact Fields')"
                            type="info"
                            :closable="false"
                            style="margin:20px 0;"
                        >
                            {{$t('custom_contact_fields')}}
                        </el-alert>

                        <el-table :empty-text="$t('No Data Found')" border stripe height="250" :data="customFields">
                            <el-table-column :label="$t('Custom Contact Field')" prop="field" width="300"/>
                            <el-table-column :label="$t('Key')" prop="key"/>
                        </el-table>
                    </div>
                </template>
            </el-form>

            <div slot="footer" class="dialog-footer">
                <el-button v-if="editing" @click="update" type="success" size="small">{{$t('Update')}}</el-button>
                <el-button v-else @click="store" type="primary" size="small">{{$t('Create')}}</el-button>
            </div>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import Errors from '@/Bits/Errors';
import Error from '@/Pieces/Error';
import ItemCopier from '@/Pieces/ItemCopier';
import ListTagItems from '@/Pieces/ListTagItems';
import WebhookPromo from '../../Promos/WebhookPromo';

export default {
    name: 'WebHookSettings',
    components: {
        Confirm,
        Error,
        WebhookPromo,
        ItemCopier,
        ListTagItems
    },
    data() {
        return {
            statuses: [
                {value: 'pending', label: this.$t('Pending')},
                {value: 'subscribed', label: this.$t('Subscribed')},
                {value: 'unsubscribed', label: this.$t('Unsubscribed')}
            ],
            dialogTitle: this.$t('Create New Incoming Webhook'),
            addWebhookVisible: false,
            btnFromLoading: false,
            labelPosition: 'left',
            errors: new Errors(),
            loading: false,
            editing: false,
            lists: [],
            tags: [],
            companies: [],
            webhooks: [],
            webhook: {},
            fields: [],
            customFields: [],
            schema: {},
            id: null,
            search: ''
        };
    },
    methods: {
        fetch() {
            this.loading = true;
            this.$get('webhooks', {
                search: this.search
            })
                .then(response => {
                    this.webhooks = response.webhooks;
                    this.fields = response.fields;
                    this.customFields = response.custom_fields;
                    this.schema = response.schema;
                    this.lists = response.lists;
                    this.tags = response.tags;
                    if (this.has_company_module) {
                        this.companies = response.companies;
                    }
                })
                .catch(() => {
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        create() {
            this.editing = false;
            this.addWebhookVisible = true;
            this.webhook = {...this.schema};
            this.dialogTitle = this.$t('Create New Incoming Webhook');
        },
        store() {
            this.errors.clear();
            this.btnFromLoading = true;
            this.$post('webhooks', this.webhook)
                .then(r => {
                    this.webhooks = r.webhooks;
                    this.edit(r.webhook, r.id);
                    this.$notify.success(r.message);
                })
                .catch(errors => {
                    this.errors.record(errors);
                })
                .finally(() => {
                    this.btnFromLoading = false;
                });
        },
        edit(webhook, id = null) {
            this.id = id;
            this.editing = true;
            this.webhook = {...webhook};
            this.addWebhookVisible = true;
            this.dialogTitle = this.webhook.name;
        },
        update() {
            this.errors.clear();
            this.btnFromLoading = true;
            this.$put(`webhooks/${this.id}`, this.webhook)
                .then(r => {
                    this.webhooks = r.webhooks;
                    this.$notify.success(r.message);
                })
                .catch(errors => {
                    this.errors.record(errors);
                })
                .finally(() => {
                    this.btnFromLoading = false;
                    this.addWebhookVisible = false;
                });
        },
        remove(id) {
            this.$del(`webhooks/${id}`)
                .then(r => {
                    this.webhooks = r.webhooks;
                    this.$notify.success(r.message);
                })
            .catch(errors => {
                this.errors.record(errors);
            });
        },
        getListTitles(lists) {
            const listTitles = [];
            for (const id in lists) {
                if (this.lists[id]) {
                    listTitles.push(this.lists[id].title);
                }
            }
            return listTitles.join(', ');
        },
        getTagTitle(tagId) {
            const tag = this.tags.find(t => t.id === tagId);
            if (tag) {
                return tag.title;
            }
            return '';
        }
    },
    mounted() {
        if (this.has_campaign_pro) {
            this.fetch();
        }
        this.changeTitle(this.$t('Webhook Settings'));
    }
};
</script>

<style>
.tag {
    border: solid #333 1px;
    border-radius: 10px;
    padding: 3px;
    margin-right: 5px;
    font-size: 12px;
}

.name {
    font-size: 15px;
    font-weight: 700;
}

.url {
    font-weight: 500;
    color: inherit;
    cursor: inherit;
    margin: 0 0 5px;
}
</style>
