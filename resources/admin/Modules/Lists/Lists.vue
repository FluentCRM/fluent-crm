<template>
    <div>
        <div class="fluentcrm-lists fluentcrm_min_bg fluentcrm-view-wrapper fluentcrm_view">

            <div class="fluentcrm_header">
                <div class="fluentcrm_header_title">
                    <h3>
                        <contact-header-pop-nav :head_title="$t('Lists')" />
                        <span v-show="pagination.total" class="ff_small">({{ pagination.total  }})</span>
                    </h3>
                    <p>{{$t('Lis_List_acoycYcalaa')}}</p>
                </div>
                <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                    <div class="fc_right_search">
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
                    <action-menu v-if="hasPermission('fcrm_manage_contact_cats')" type="list" :api="api" @fetch="fetch"/>
                    <el-popover
                        trigger="click"
                        :width="240"
                        placement="bottom"
                        popper-class=""
                    >
                        <template #reference>
                            <el-button :title="$t('More')" style="padding: 8px 0px;border:none">
                                <i class="el-icon-more" style="rotate: 90deg;"></i>
                            </el-button>
                        </template>

                        <div >
                            <el-button
                                v-if="hasPermission('fcrm_manage_contact_cats')"
                                type="default"
                                size="mini"
                                icon="el-icon-download"
                                :loading="exportLoading"
                                @click="handleExport('all')"
                            >
                                {{   $t('Export All Lists') }}
                            </el-button>

                            <el-button
                                v-if="hasPermission('fcrm_manage_contact_cats')"
                                type="default"
                                size="mini"
                                icon="el-icon-upload"
                                :loading="exportLoading"
                                @click="handleImport()"
                            >
                                {{   $t('Import') }}
                            </el-button>
                        </div>
                    </el-popover>
                </div>
            </div>

            <div class="fluentcrm_body" style="position: relative;">

                <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                    <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
                </div>
                <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

                <div v-else class="lists-table">
                    <el-row v-if="hasPermission('fcrm_manage_contact_cats_delete')">
                        <el-col :md="12" :sm="24">

                            <confirm @yes="doBulkAction()" placement="top-start" v-if="selectedLists.length && selection">
                                <el-button
                                    style="margin: 12px 5px 10px 16px;"
                                    size="mini"
                                    type="danger"
                                    slot="reference"
                                    icon="el-icon-delete"
                                >
                                    {{ $t('Delete Selected') }}
                                </el-button>
                            </confirm>
                            &nbsp;
                        </el-col>
                    </el-row>

                    <el-table :empty-text="$t('No Data Found')"
                              border
                              @selection-change="onSelection"
                              :default-sort="{ prop: sortBy, order: (sortType == 'DESC') ? 'descending' : 'ascending' }"  @sort-change="handleSortable" :data="lists">
                        <el-table-column v-if="hasPermission('fcrm_manage_contact_cats_delete')" type="selection" />

                        <el-table-column property="id" sortable="custom" :label="$t('ID')" width="80">
                            <template slot-scope="scope">
                                {{ scope.row.id }}
                            </template>
                        </el-table-column>
                        <el-table-column :min-width="200" property="title" sortable="custom" :label="$t('Title')">
                            <template slot-scope="scope">
                                <router-link :to="{ name: 'list', params: { listId: scope.row.id } }">
                                    <h3 class="no-margin url">
                                        {{ scope.row.title }}
                                    </h3>
                                </router-link>

                                <span class="list-created">
                                {{ scope.row.description }}
                            </span>
                            </template>
                        </el-table-column>

                        <el-table-column  :width="200"  :label="$t('Subscribers')">
                            <template slot-scope="scope">
                                <h4 class="no-margin">
                                    {{ scope.row.subscribersCount }} of {{ scope.row.totalCount }}
                                </h4>
                                {{$t('Subscribed')}}
                            </template>
                        </el-table-column>
                        <el-table-column  :width="200"  :label="$t('Created')">
                            <template slot-scope="scope">
                                <span :title="scope.row.created_at">{{ scope.row.created_at | nsHumanDiffTime }}</span>
                            </template>
                        </el-table-column>

                        <el-table-column
                            fixed="right"
                            :label="$t('Actions')">
                            <template slot-scope="scope">
                                <div class="text-align-right d-flex">
                                    <el-button
                                        v-if="hasPermission('fcrm_manage_contact_cats')"
                                        type="primary"
                                        size="mini"
                                        icon="el-icon-edit"
                                        @click="edit(scope.row)"
                                        class="mr-5"
                                    />

                                    <el-button
                                        :title="$t('Double Optin Settings')"
                                        v-if="hasPermission('fcrm_manage_contact_cats')"
                                        type="primary"
                                        size="mini"
                                        icon="el-icon-s-tools"
                                        @click="openDoubleOptInDrawer(scope.row.id)"
                                        class="mr-5 ml-0"
                                    />

                                    <confirm v-if="hasPermission('fcrm_manage_contact_cats_delete')" @yes="remove(scope.row.id)">
                                        <el-button
                                            size="mini"
                                            type="danger"
                                            slot="reference"
                                            icon="el-icon-delete"
                                        />
                                    </confirm>
                                </div>
                            </template>
                        </el-table-column>
                    </el-table>

                    <el-row style="display: flex; justify-content: space-between; align-items: center;">
                        <el-col v-if="hasPermission('fcrm_manage_contact_cats_delete')" :md="12" :sm="24">

                            <confirm @yes="doBulkAction()" placement="top-start"
                                v-if="selectedLists.length && selection">
                                <el-button style="margin: 12px 5px 10px 18px;" size="mini" type="danger"
                                    slot="reference" icon="el-icon-delete">
                                    {{ $t('Delete Selected') }}
                                </el-button>
                            </confirm>
                            &nbsp;
                        </el-col>
                        <el-col>
                            <pagination
                                @per_page_change="(size) => { storage.set('list_perpage', size); }"
                                :pagination="pagination" :hide_on_single="false"
                                :extra_sizes="[200, 250, 300, 400, 600]" @fetch="fetchLists" style="margin-bottom: 15px;" />
                        </el-col>
                    </el-row>
                </div>
            </div>
        </div>
        <el-drawer
            :append-to-body=true
            size="60%"
            title="Double OptIn Settings"
            :visible.sync="drawer"
            :with-header="false"
        >
            <double-opt-in-settings :drawerKey="drawerKey" :listId="selectedListId" :listName="selectedListName" @close="drawer = false" />
        </el-drawer>

        <el-drawer
            v-if="hasPermission('fcrm_manage_contact_cats')"
            :title="$t('Import Lists')"
            append-to-body
            :visible.sync="showImportDrawer"
            size="40%"
            direction="rtl"
            @close="handleImportDrawerClose"
        >
            <div v-if="importStep == 1">
                <div v-if="listsToImport.length === 0">
                    <div style="margin: 20px;">
                        <el-upload
                            :show-file-list="false"
                            :auto-upload="false"
                            :accept="'.csv'"
                            :on-change="handleElUploadChange"
                            drag
                            action="/mock-upload"
                        >
                            <i class="el-icon-upload"></i>
                            <div class="el-upload__text">{{ $t('Upload a CSV file to import lists') }}</div>
                            <div class="el-upload__tip" slot="tip">
                                {{ $t('Only CSV files are allowed') }}<br>
                                {{ $t('You can download a sample CSV file') }}
                                <el-button type="text" @click="downloadSampleCsv">{{ $t('Download Sample CSV') }}</el-button>
                            </div>
                        </el-upload>
                    </div>
                </div>
            </div>
            <div v-if="importStep == 2 && listsToImport.length > 0">
                <div class="tags-preview">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;margin-left: 10px;">
                        <h4 style="margin: 0;">{{ $t('Lists to Import') }} ({{ listsToImport.length }})</h4>
                    </div>
                    <el-table :data="listsToImport" style="width: 100%">
                        <el-table-column prop="title" :label="$t('Title')">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.title" size="small" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="slug" :label="$t('Slug')">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.slug" size="small" />
                            </template>
                        </el-table-column>
                        <el-table-column prop="description" :label="$t('Internal Subtitle')">
                            <template slot-scope="scope">
                                <el-input v-model="scope.row.description" size="small" />
                            </template>
                        </el-table-column>
                    </el-table>

                </div>
                <div style="margin-top: 20px;margin-left: 10px; margin-bottom: 20px;">

                    <el-button
                        @click="resetImportStage()"
                        type="default"
                        size="small"
                    >
                        {{ $t('Back') }}
                    </el-button>

                    <el-button
                        @click="submitToBackend()"
                        type="primary"
                        size="small"
                        :disabled="listsToImport.length === 0"
                    >
                        {{ $t('Create Lists') }}
                    </el-button>
                </div>
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import ActionMenu from '@/Pieces/ActionMenu';
import ContactHeaderPopNav from '@/Pieces/ContactHeaderPopNav.vue';
import DoubleOptInSettings from '@/Modules/Settings/parts/DoubleOptinSettings';
import Pagination from '@/Pieces/Pagination';

export default {
    name: 'Lists',
    components: {
        Confirm,
        ActionMenu,
        ContactHeaderPopNav,
        DoubleOptInSettings,
        Pagination
    },
    data() {
        return {
            loading: false,
            exportLoading: false,
            lists: [],
            api: {
                store: 'lists'
            },
            sortBy: 'id',
            sortType: 'DESC',
            search: '',
            selectedLists: [],
            selection: false,
            selectionCount: 0,
            drawer: false,
            drawerKey: 0,
            selectedListId: null,
            selectedListName: '',
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            showImportDrawer: false,
            importStep: 1, // 1 for upload, 2 for preview
            listsToImport: []
        }
    },
    methods: {
        openDoubleOptInDrawer(listId) {
            this.selectedListId = listId;
            this.selectedListName = this.lists.find(list => list.id === listId).title;
            this.drawerKey++;
            this.drawer = true;
        },
        fetch() {
            this.loading = true;
            this.fetchLists();
        },
        fetchLists() {
            this.pagination.per_page = parseInt(this.storage.get('list_perpage', 10)) || 10
            this.$get('lists', {
                with: ['subscribersCount'],
                sort_by: this.sortBy,
                sort_order: this.sortType,
                search: this.search,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page
            })
                .then(response => {
                    this.lists = response.lists;
                    this.pagination.total = response.pagination.total;
                })
                .finally(() => {
                    this.loading = false;
                    this.selection = false;
                });
        },
        edit(list) {
            this.$bus.$emit('edit-list', list);
            this.$bus.$emit('renew_options', 'list');
        },
        handleExport(type = 'selected') {
            if (type === 'all') {
                // Fetch all Lists first then export
                this.fetchAllListsForExport();
            } else {
                // Export selected Lists directly
                this.exportListsToCsv(this.selectedLists);
            }
        },
        fetchAllListsForExport() {
            this.exportLoading = true;

            // Show loading notification
            this.$notify.info({
                title: this.$t('Preparing Export'),
                message: this.$t('Fetching all lists for export...'),
                offset: 19
            });

            // Fetch all lists with all_lists flag
            this.$get('lists', {
                all_lists: 1,
                search: this.search
            }).then(response => {
                // Export the fetched lists
                this.exportListsToCsv(response.all_lists);
            })
                .catch((error) => {
                    this.handleError(error);
                    this.$notify.error({
                        title: this.$t('Export Failed'),
                        message: this.$t('Failed to fetch lists for export'),
                        offset: 19
                    });
                })
                .finally(() => {
                    this.exportLoading = false;
                });
        },
        exportListsToCsv(ListsToExport) {
            // Define CSV headers
            const headers = ['id', 'title', 'slug', 'description'];

            // Create CSV content
            let csvContent = headers.join(',') + '\n';
            // Add data rows
            ListsToExport.forEach(list => {
                // Format each field with proper escaping for CSV
                const row = [
                    list.id,
                    this.escapeCsvValue(list.title),
                    this.escapeCsvValue(list.slug || ''),
                    this.escapeCsvValue(list.description || '')
                ].join(',');
                csvContent += row + '\n';
            });

            // Create a Blob with the CSV data
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });

            // Create download link
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            // Set link properties
            link.setAttribute('href', url);
            link.setAttribute('download', 'fluent-crm-lists-export-' + new Date().toISOString().slice(0, 19).replace(/[T:.]/g, '-') + '.csv');
            link.style.visibility = 'hidden';

            // Add to document, trigger click and remove
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Show success notification with 1/2 sec delay
            setTimeout(() => {
                this.$notify.closeAll();
            }, 2000);
            this.$notify.success({
                title: this.$t('Export Successful'),
                message: this.$t('Lists exported successfully'),
                offset: 19
            });
        },
        handleElUploadChange(fileObj) {
            // fileObj is { file, fileList }
            const file = fileObj.raw || (fileObj.file && fileObj.file.raw) || fileObj.file;
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                const csv = e.target.result;
                const rows = csv.split(/\r?\n/).filter(r => r.trim());
                if (rows.length < 2) {
                    this.$notify.info({ title: this.$t('Info'), message: this.$t('CSV file is empty or invalid'), offset: 19 });
                    return;
                }
                // Parse header
                const headers = rows[0].split(',').map(h => h.trim().toLowerCase());
                // Map headers to fields
                const titleIdx = headers.findIndex(h => h === 'title');
                const descIdx = headers.findIndex(h => h === 'description' || h === 'internal notes');
                if (titleIdx === -1) {
                    this.$notify.info({ title: this.$t('Info'), message: this.$t('CSV must have a "title" column'), offset: 19 });
                    return;
                }
                const lists = rows.slice(1).map(row => {
                    const cols = row.split(',');
                    return {
                        title: cols[titleIdx] ? cols[titleIdx].trim() : '',
                        description: descIdx !== -1 && cols[descIdx] ? cols[descIdx].trim() : '',
                        slug: (cols[titleIdx] ? cols[titleIdx].trim().toLowerCase().replace(/\s+/g, '-') : '')
                    };
                }).filter(list => list.title);
                this.listsToImport = lists;
                this.importStep = 2;
                this.$notify.success({ title: this.$t('Success'), message: this.$t('Ready to import ') + lists.length + ' ' + this.$t('lists'), offset: 19 });
            };
            reader.readAsText(file);
        },
        resetImportStage() {
            this.importStep = 1;
            this.listsToImport = [];
        },
        handleImport() {
            this.showImportDrawer = true;
        },
        submitToBackend() {
            // Submit the lists to the backend
            if (this.listsToImport.length > 0) {
                this.$post('lists/bulk', {
                    lists: this.listsToImport
                })
                    .then(response => {
                        this.$notify.success({
                            title: this.$t('Success'),
                            message: this.$t('Lists imported successfully'),
                            offset: 19
                        });
                        this.fetch();
                        this.showImportDrawer = false;
                        this.listsToImport = [];
                    })
                    .catch((errors) => {
                        this.handleError(errors);
                        this.$notify.error({
                            title: this.$t('Error'),
                            message: this.$t('Failed to import lists'),
                            offset: 19
                        });
                    });
            } else {
                this.$notify.info({
                    title: this.$t('Info'),
                    message: this.$t('No lists to import'),
                    offset: 19
                });
            }
        },
        handleImportDrawerClose(done) {
            this.importStep = 1;
            this.listsToImport = [];
            this.showImportDrawer = false;
            if (typeof done === 'function') done();
        },
        downloadSampleCsv() {
            // Use a temporary anchor for best browser compatibility
            const csvContent = 'title,description\nVIP,Very Important Person\ntest,Test List\ndemo,Demo List\n';
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'sample-lists.csv';
            document.body.appendChild(a);
            a.click();
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 100);
        },
        // Helper method to properly escape CSV values
        escapeCsvValue(value) {
            if (value == null) return '';

            // Convert to string
            value = String(value);

            // If the value contains commas, quotes or newlines, wrap in quotes
            if (value.includes(',') || value.includes('"') || value.includes('\n')) {
                // Double up any quotes
                value = value.replace(/"/g, '""');
                // Wrap in quotes
                value = `"${value}"`;
            }

            return value;
        },
        remove(id) {
            this.$del(`lists/${id}`).then(response => {
                this.fetch();
                this.$notify.success({
                    title: this.$t('Great!'),
                    message: response.message,
                    offset: 19
                });

                this.$bus.$emit('renew_options', 'list');
            });
        },
        doBulkAction() {
            const listIds = [];
            this.each(this.selectedLists, (list) => {
                listIds.push(list.id);
            });

            this.$post('lists/do-bulk-action', {
                listIds: listIds
            })
            .then(res => {
                this.$notify.success(res.message);
                this.fetch();
                this.$bus.$emit('renew_options', 'list');
            })
            .catch((errors) => {
                this.handleError(errors);
            })
            .finally(() => {

            });
        },
        handleSortable(sorting) {
            if (sorting.order === 'descending') {
                this.sortBy = sorting.prop;
                this.sortType = 'DESC';
            } else {
                this.sortBy = sorting.prop;
                this.sortType = 'ASC';
            }
            this.fetch();
        },
        onSelection(lists) {
            this.selection = !!lists.length;

            this.selectedLists = lists;

            this.selectionCount = lists.length;
        }
    },
    mounted() {
        this.fetch();
        this.changeTitle(this.$t('Lists'));
        this.$emit('changeMenu', 'lists');
    }
};
</script>
