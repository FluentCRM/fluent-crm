<template>
    <div class="fluentcrm-forms fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header align-center justify-between">
            <div class="fluentcrm_header_title">
                <h3>{{$t('Forms')}}</h3>
                <p v-if="pagination.total">{{$t('For_Fluent_FtacwyC')}}</p>
            </div>
            <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                <div style="margin-right: 10px;" class="fc-search-box">
                    <el-input
                        clearable
                        size="mini"
                        v-model="search"
                        @clear="fetchForms"
                        @keyup.enter.native="fetchForms"
                        :placeholder="$t('Type and Enter...')"
                    >
                        <el-button @click="fetchForms" slot="append" icon="el-icon-search"></el-button>
                    </el-input>
                </div>

                <el-button style="height: 30px;line-height:10px" @click="create_form_modal = true" v-if="!need_installation" size="small" type="primary">
                    {{$t('Create a New Form')}}
                </el-button>
                <inline-doc :doc_id="267" />
            </div>
        </div>
        <div class="fluentcrm_body" style="position: relative;">
            <div v-if="loading" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <div v-loading="installing_ff" element-loading-text="Installing Fluent Forms..."
                 class="fc_narrow_box fc_white_inverse text-align-center" v-if="need_installation">
                <h2>{{$t('For_Grow_YAbOF')}}</h2>
                <p>{{$t('Forms.desc')}}</p>
                <el-button @click="installFF()" type="success">{{$t('Das_Activate_FFI')}}</el-button>
            </div>
            <div v-else class="fc_forms" style="padding-top: 25px">
                <div class="fc_narrow_box fc_white_inverse text-align-center" v-if="!pagination.total && !loading">
                    <h2>{{$t('For_Looks_Lydncafy')}}</h2>
                    <el-button @click="create_form_modal = true" type="danger">{{$t('Create Your First Form')}}</el-button>
                </div>
                <div v-else>
                    <el-table border v-if="forms.length" :empty-text="$t('No Form Found')" stripe :data="forms" width="100%">
                        <el-table-column width="80" :label="$t('ID')" prop="id"></el-table-column>
                        <el-table-column width="250" :label="$t('Title')">
                            <template slot-scope="scope">
                                {{ scope.row.title }}
                            </template>
                        </el-table-column>
                        <el-table-column width="320" :label="$t('Info')">
                            <template slot-scope="scope">
                                <span v-if="scope.row.associate_lists" class="fc_tag_items fc_list"><i
                                    class="el-icon-files"></i>{{scope.row.associate_lists}}</span>
                                <span v-if="scope.row.associate_tags" class="fc_tag_items fc_tag"><i
                                    class="el-icon-price-tag"></i> {{scope.row.associate_tags}}</span>
                            </template>
                        </el-table-column>
                        <el-table-column width="250" :label="$t('Shortcode')">
                            <template slot-scope="scope">
                                <item-copier :text="scope.row.shortcode" />
                            </template>
                        </el-table-column>
                        <el-table-column width="200" :label="$t('Created at')">
                            <template slot-scope="scope">
                                <span :title="scope.row.created_at">
                                    {{ scope.row.created_at | nsHumanDiffTime }}
                                </span>
                            </template>
                        </el-table-column>
                        <el-table-column fixed="right" :label="$t('Actions')" min-width="150">
                            <template slot-scope="scope">
                                <el-dropdown @command="handleActionCommand">
                                    <el-button size="small" type="info">
                                        {{$t('Actions')}} <i class="el-icon-arrow-down el-icon--right"></i>
                                    </el-button>
                                    <el-dropdown-menu slot="dropdown">
                                        <el-dropdown-item
                                            :command="{ form: scope.row, url: 'preview_url', target: 'blank' }">
                                            {{$t('Preview Form')}}
                                        </el-dropdown-item>
                                        <el-dropdown-item
                                            v-if="scope.row.feed_url"
                                            :command="{ form: scope.row, url: 'feed_url', target: 'blank' }">
                                            {{$t('Edit Integration Settings')}}
                                        </el-dropdown-item>
                                        <el-dropdown-item
                                            v-if="scope.row.funnel_url"
                                            :command="{ form: scope.row, url: 'funnel_url', target: 'same' }">
                                            {{$t('Edit Connected Automation')}}
                                        </el-dropdown-item>
                                        <el-dropdown-item
                                            :command="{ form: scope.row, url: 'edit_url', target: 'blank' }">
                                            {{$t('Edit Form')}}
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </el-dropdown>
                            </template>
                        </el-table-column>
                    </el-table>
                    <el-row v-if="!loading" style="padding: 20px;" :gutter="20">
                        <el-col :md="12" :sm="24">
                            {{$t('Forms.if_need_desc')}}
                        </el-col>
                        <el-col :md="12" :sm="24">
                            <pagination :pagination="pagination" @fetch="fetchForms"/>
                        </el-col>
                    </el-row>
                </div>
            </div>
        </div>

        <el-drawer
            class="fc_company_info_drawer"
            :with-header="true"
            :size="globalDrawerSize"
            :title="$t('Create a Form')"
            :append-to-body="true"
            :wrapper-closable="false"
            :visible.sync="create_form_modal">
            <div style="padding: 0 20px;">
                <create-form v-if="create_form_modal" />
            </div>
        </el-drawer>
    </div>
</template>
<script type="text/babel">
    import createForm from './_CreateForm';
    import Pagination from '@/Pieces/Pagination';
    import ItemCopier from '@/Pieces/ItemCopier';
    import InlineDoc from '@/Modules/Documentation/InlineDoc';

    export default {
        name: 'FluentForms',
        components: {
            createForm,
            Pagination,
            ItemCopier,
            InlineDoc
        },
        data() {
            return {
                forms: [],
                pagination: {
                    page: 1,
                    per_page: 10,
                    total: 0
                },
                loading: false,
                installing_ff: false,
                need_installation: false,
                create_form_modal: false,
                search: ''
            }
        },
        methods: {
            fetchForms() {
                const query = {
                    per_page: this.pagination.per_page,
                    page: this.pagination.current_page,
                    search: this.search
                };
                this.loading = true;
                this.$get('forms', query)
                    .then(response => {
                        if (response.installed) {
                            this.forms = response.forms.data;
                            this.pagination.total = response.forms.total;
                            this.need_installation = false;
                        } else {
                            this.need_installation = true;
                        }
                    })
                    .catch((errors) => {
                        this.handleError(errors);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            installFF() {
                this.installing_ff = true;
                this.$post('setting/install-fluentform')
                    .then(response => {
                        this.fetchForms();
                        this.$notify.success(response.message);
                    })
                    .catch((error) => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.installing_ff = false;
                    });
            },
            handleActionCommand(data) {
                const targetUrl = data.form[data.url];
                const target = data.target;
                if (!targetUrl) {
                    return;
                }
                if (target === 'blank') {
                    window.open(targetUrl, '_blank');
                } else {
                    window.location.href = targetUrl;
                }
            }
        },
        mounted() {
            this.fetchForms();
            this.changeTitle(this.$t('Forms'));
        }
    };
</script>
