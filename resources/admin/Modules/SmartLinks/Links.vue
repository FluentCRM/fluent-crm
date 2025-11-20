<template>
    <div class="fluentcrm-settings fluentcrm_min_bg fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    {{$t('Smart Links')}}
                    <el-popover
                        placement="right"
                        width="400"
                        trigger="hover">
                        <p>
                            {{$t('Lin_SmartLinks_aytta')}}
                        </p>
                        <el-button type="text" slot="reference" icon="el-icon-info"></el-button>
                    </el-popover>
                </h3>
            </div>
            <div v-if="has_campaign_pro" class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div style="margin-right: 10px;" class="fc-search-box">
                    <el-input
                        clearable
                        size="mini"
                        v-model="search"
                        @clear="fetchLinks"
                        @keyup.enter.native="fetchLinks"
                        :placeholder="$t('Type and Enter...')"
                    >
                        <el-button @click="fetchLinks" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>

                <el-button @click="create_link_modal = true" :disabled="require_activation" type="primary" size="small">
                    {{$t('Add New Smart Link')}}
                </el-button>
                <inline-doc :doc_id="11634" />
            </div>
        </div>

        <div v-if="has_campaign_pro" class="fluentcrm_pad_around fluentcrm_smart_links_wrap" style="position: relative;">
            <div v-if="require_activation" class="fc_narrow_box fc_white text-align-center">
                <h1>{{$t('SmartLinks')}}</h1>
                <p>
                    {{$t('Lin_SmartLinks_aytta')}}
                </p>
                <el-button @click="activateModule()" type="success" size="large">
                    {{$t('Start Using SmartLinks')}}
                </el-button>
            </div>
            <div v-else class="settings-section settings-general">
                <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                    <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
                </div>
                <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

                <el-table v-else :empty-text="$t('No Data Found')" stripe :data="links">
                    <el-table-column class="hello" type="expand">
                        <template slot-scope="scope">
                            <div style="padding-left: 60px;">
                                <h4>{{$t('Attached Tags and Lists')}}</h4>
                                <p>
                                    <list-tag-items item_type="tags" :items="scope.row.actions.tags" />
                                    <list-tag-items item_type="lists" :items="scope.row.actions.lists" />
                                </p>
                                <template v-if="scope.row.detach_actions">
                                    <h4>{{$t('Detach Tags and Lists')}}</h4>
                                    <p>
                                        <list-tag-items item_type="tags" :items="scope.row.detach_actions.tags" />
                                        <list-tag-items item_type="lists" :items="scope.row.detach_actions.lists" />
                                    </p>
                                </template>

                                <p><b>{{ $t('Auto Login') }}:</b> {{scope.row.auto_login}}</p>
                                
                                <p><b>{{$t('Subscriber Clicks')}}:</b> {{scope.row.contact_clicks}}</p>
                                <p><b>{{$t('Public User Clicks')}}:</b> {{scope.row.all_clicks - scope.row.contact_clicks}}</p>
                                <template v-if="scope.row.notes">
                                    <h4>{{$t('Note')}}:</h4>
                                    <p v-html="scope.row.notes"></p>
                                </template>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column :min-width="170" width="170" :label="$t('Title')">
                        <template slot-scope="scope">
                            <div class="title">
                                {{scope.row.title}}
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column :min-width="270" :label="$t('Smart URL')">
                        <template slot-scope="scope">
                            <item-copier :text="scope.row.short_url" />
                        </template>
                    </el-table-column>
                    <el-table-column :min-width="270" :label="$t('Target URL')">
                        <template slot-scope="scope">
                            <url-go-to :url="scope.row.target_url" />
                        </template>
                    </el-table-column>
                    <el-table-column width="100" :label="$t('Actions')">
                        <template slot-scope="scope">
                            <el-button
                                type="primary"
                                size="mini"
                                icon="el-icon-edit"
                                @click="showEdit(scope.row)"
                            />

                            <confirm @yes="remove(scope.row)">
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
                <pagination :pagination="pagination" @fetch="fetchLinks"/>
            </div>
        </div>
        <div v-else class="fluentcrm_pad_around">
            <div class="fc_narrow_box fc_white text-align-center">
                <h1>{{$t('SmartLinks')}}</h1>
                <p>
                    {{$t('Lin_SmartLinks_aytta')}}
                </p>
                <p>
                    {{$t('Lin_This_iapfPdtFPta')}}
                </p>
                <div class="">
                    <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                        {{$t('Get FluentCRM Pro')}}
                    </a>
                </div>
            </div>
        </div>

        <el-dialog :close-on-click-modal="false" width="60%" :append-to-body="true" :visible.sync="create_link_modal" :title="$t('Create a new Smart link')">
            <create-link item_mode="create" v-if="create_link_modal" @linkCreated="fetchLinks" />
        </el-dialog>
        <el-dialog :close-on-click-modal="false" width="60%" :append-to-body="true" :visible.sync="edit_modal" :title="$t('Edit Smart link')">
            <create-link item_mode="edit" v-if="edit_modal" :editing_row="editingRow" @linkCreated="fetchLinks" />
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import CreateLink from './_CreateLink'
import Confirm from '@/Pieces/Confirm';
import Pagination from '@/Pieces/Pagination';
import ItemCopier from '@/Pieces/ItemCopier';
import UrlGoTo from '@/Pieces/UrlGoTo';
import ListTagItems from '@/Pieces/ListTagItems';
import InlineDoc from '@/Modules/Documentation/InlineDoc';

export default {
    name: 'actionLinks',
    components: {
        InlineDoc,
        CreateLink,
        Confirm,
        Pagination,
        ItemCopier,
        UrlGoTo,
        ListTagItems
    },
    data() {
        return {
            links: [],
            loading: false,
            activating: false,
            require_activation: false,
            create_link_modal: false,
            pagination: {
                total: 0,
                per_page: 20,
                current_page: 1
            },
            search: '',
            editingRow: false,
            edit_modal: false
        }
    },
    methods: {
        fetchLinks() {
            this.loading = true;
            this.$get('smart-links', {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search
            })
                .then(response => {
                    this.links = response.action_links.data;
                    this.pagination.total = response.action_links.total;
                })
                .catch((errors) => {
                    if (errors.status === 'disabled') {
                        this.require_activation = true;
                    } else {
                        this.handleError(errors);
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        activateModule() {
            this.activating = true;
            this.$post('smart-links/activate')
                .then(response => {
                    this.require_activation = false;
                    this.create_link_modal = true;
                    window.fcAdmin.has_smart_link = true;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.activating = false;
                });
        },
        remove(row) {
            this.$del(`smart-links/${row.id}`)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchLinks();
                })
                .catch(error => {
                    this.handleError(error);
                });
        },
        showEdit(row) {
            if (!row.detach_actions) {
                row.detach_actions = {
                    tags: [],
                    lists: []
                }
            }
            this.editingRow = row;
            this.edit_modal = true;
        }
    },
    created() {
        if (this.has_campaign_pro) {
            this.fetchLinks();
        }
        this.changeTitle(this.$t('Smart Links'));
    }
}
</script>
