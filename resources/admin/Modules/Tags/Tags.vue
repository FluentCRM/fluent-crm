<template>
    <div class="fluentcrm-lists fluentcrm_min_bg fluentcrm-view-wrapper fluentcrm_view">
        <div class="fluentcrm_header">
            <div class="fluentcrm_header_title">
                <h3>
                    <contact-header-pop-nav :head_title="$t('Tags')" />
                </h3>
                <p>{{ $t('Tag_Tags_alLbmwtfyci') }}</p>
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
                <action-menu v-if="hasPermission('fcrm_manage_contact_cats')" type="tag" :api="api" @fetch="fetch"/>

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
                        {{   $t('Export All tags') }}
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

        <div class="fluentcrm_body pad-b-20" style="position: relative;">
            <div v-if="loading" slot="before_contacts_table" class="fc_loading_bar">
                <el-progress class="el-progress_animated" :show-text="false" :percentage="30" />
            </div>
            <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

            <div v-else class="lists-table">
                <el-row v-if="hasPermission('fcrm_manage_contact_cats_delete')">
                    <el-col :md="12" :sm="24">
                        <confirm @yes="doBulkAction()" placement="top-start" v-if="selectedTags.length && selection">
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
                         <el-button
                            v-if="selectedTags.length && selection"
                                size="mini"
                                type="info"
                                :loading="exportLoading"
                                @click="handleExport('selected')"
                                icon="el-icon-download"
                            >
                                {{ $t('Export') }}
                            </el-button>
                        &nbsp;
                    </el-col>
                </el-row>

                <el-table border :default-sort="{ prop: sortBy, order: (sortType == 'DESC') ? 'descending' : 'ascending' }"  :empty-text="$t('No Data Found')"
                          @selection-change="onSelection"
                          @sort-change="handleSortable"
                          :data="tags">
                    <el-table-column v-if="hasPermission('fcrm_manage_contact_cats_delete')" type="selection" />

                    <el-table-column property="id" sortable="custom" :label="$t('ID')" width="80">
                        <template slot-scope="scope">
                            {{ scope.row.id }}
                        </template>
                    </el-table-column>
                    <el-table-column :min-width="200" property="title" sortable="custom" :label="$t('Title')">
                        <template slot-scope="scope">
                            <router-link :to="{ name: 'tag', params: { tagId: scope.row.id } }">
                                <h3 class="no-margin url">
                                    {{ scope.row.title }}
                                </h3>
                            </router-link>

                            <span class="list-created">{{ scope.row.description }}</span>
                        </template>
                    </el-table-column>

                    <el-table-column :width="200" :label="$t('Contacts')">
                        <template slot-scope="scope">
                            <h4 class="no-margin">
                                {{ scope.row.subscribersCount }}
                            </h4>
                            {{ $t('Subscribed') }}
                        </template>
                    </el-table-column>
                    <el-table-column :width="200" :label="$t('Created')">
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

                <el-row v-if="hasPermission('fcrm_manage_contact_cats_delete')">
                    <el-col :md="12" :sm="24">
                        <confirm @yes="doBulkAction()" placement="top-start" v-if="selectedTags.length && selection">
                            <el-button
                                style="margin: 12px 5px 10px 18px;"
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
                    <el-col :md="12" :sm="24">
                        <pagination :pagination="pagination" @fetch="fetch"/>
                    </el-col>
                </el-row>

            </div>
        </div>
        <el-drawer
            v-if="hasPermission('fcrm_manage_contact_cats')"
            :title="$t('Import Tags')"
            append-to-body
            :visible.sync="showImportDrawer"
            size="40%"
            direction="rtl"
            @close="handleDrawerClose"
        >
        <div v-if="importStep == 1">
            <div v-if="tagsToImport.length === 0">
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
                       <div class="el-upload__text">{{ $t('Upload a CSV file to import tags') }}</div>
                       <div class="el-upload__tip" slot="tip">
                            {{ $t('Only CSV files are allowed') }}<br>
                            {{ $t('You can download a sample CSV file') }}
                            <el-button type="text" @click="downloadSampleCsv">{{ $t('Download Sample CSV') }}</el-button>
                        </div>
                    </el-upload>
                </div>
            </div>
        </div>
        <div v-if="importStep == 2 && tagsToImport.length > 0">
            <div class="tags-preview">
                <div style="display: flex; align-items: center; margin-bottom: 10px;margin-left: 10px;">
                    <h4 style="margin: 0;">{{ $t('Tags to Import') }} ({{ tagsToImport.length }})</h4>
                </div>
                <el-table :data="tagsToImport" style="width: 100%">
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
                    :disabled="tagsToImport.length === 0"
                >
                    {{ $t('Create Tags') }}
                </el-button>
            </div>
        </div>
            
        </el-drawer>
       
    </div>
</template>

<script type="text/babel">
import Confirm from '@/Pieces/Confirm';
import ActionMenu from '@/Pieces/ActionMenu';
import Pagination from '@/Pieces/Pagination';
import ContactHeaderPopNav from '@/Pieces/ContactHeaderPopNav.vue';

export default {
    name: 'Tags',
    components: {
        Confirm,
        ActionMenu,
        Pagination,
        ContactHeaderPopNav
    },
    data() {
        return {
            loading: false,
            exportLoading: false,
            api: {
                store: 'tags'
            },
            tags: [],
            sortBy: 'id',
            search: '',
            sortType: 'DESC',
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            selectedTags: [],
            selectionCount: 0,
            importStep: 1, // 1 for upload, 2 for preview
            showImportDrawer: false,
            processedFiles: [], // To store processed file data
            tagsToImport: []
        }
    },
    methods: {
        handleImport() {
            this.showImportDrawer = true;
        },
        submitToBackend() {
            // Submit the tags to the backend
            if (this.tagsToImport.length > 0) {
                this.$post('tags/bulk', { 
                    tags: this.tagsToImport 
                })
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Success'),
                        message: this.$t('Tags imported successfully'),
                        offset: 19
                    });
                    this.fetch();
                    this.showImportDrawer = false;
                    this.tagsToImport = [];
                })
                .catch(error => {
                    this.$notify.error({
                        title: this.$t('Error'),
                        message: this.$t('Failed to import tags'),
                        offset: 19
                    });
                    console.error('Error importing tags:', error);
                });
            } else {
                this.$notify.info({
                    title: this.$t('Info'),
                    message: this.$t('No tags to import'),
                        offset: 19
                });
            }
        },
        fetch() {
            this.loading = true;
            this.$get('tags', {
                sort_by: this.sortBy,
                sort_order: this.sortType,
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search
            })
                .then(response => {
                    this.tags = response.tags.data;
                    this.pagination.total = response.tags.total;
                    if (!this.storage.get('_tags_per_page')) {
                        this.storage.set('_tags_per_page', this.pagination.per_page);
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                    this.storage.set('_tags_per_page', 10);
                })
                .finally(() => {
                    this.loading = false;
                    this.selection = false;
                });
        },
        edit(tag) {
            this.$bus.$emit('edit-tag', tag);
        },
        handleExport(type = 'selected') {
            if (type === 'all') {
                // Fetch all tags first then export
                this.fetchAllTagsForExport();
            } else {
                // Export selected tags directly
                this.exportTagsToCsv(this.selectedTags);
            }
        },
        fetchAllTagsForExport() {
            this.exportLoading = true;
            
            // Show loading notification
            this.$notify.info({
                title: this.$t('Preparing Export'),
                message: this.$t('Fetching all tags for export...'),
                offset: 19
            });
            
            // Fetch all tags with all_tags flag
            this.$get('tags', {
                all_tags: 1,
                search: this.search
            }).then(response => {
                // Export the fetched tags
                this.exportTagsToCsv(response.all_tags);
            })
            .catch((error) => {
                this.handleError(error);
                this.$notify.error({
                    title: this.$t('Export Failed'),
                    message: this.$t('Failed to fetch tags for export'),
                    offset: 19
                });
            })
            .finally(() => {
                this.exportLoading = false;
            });
        },
        exportTagsToCsv(tagsToExport) {
            // Define CSV headers
            const headers = ['id', 'title', 'slug', 'description'];
            
            // Create CSV content
            let csvContent = headers.join(',') + '\n';
            // Add data rows
            tagsToExport.forEach(tag => {
                // Format each field with proper escaping for CSV
                const row = [
                    tag.id,
                    this.escapeCsvValue(tag.title),
                    this.escapeCsvValue(tag.slug || ''),
                    this.escapeCsvValue(tag.description || '')
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
            link.setAttribute('download', 'fluent-crm-tags-export-' + new Date().toISOString().slice(0, 19).replace(/[T:.]/g, '-') + '.csv');
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
                message: this.$t('Tags exported successfully'),
                offset: 19
            });
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
            this.$del(`tags/${id}`)
                .then(response => {
                    this.fetch();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                    this.$bus.$emit('renew_options', 'tag');
                });
        },
        doBulkAction() {
            const tagIds = [];
            this.each(this.selectedTags, (list) => {
                tagIds.push(list.id);
            });
            this.$post('tags/do-bulk-action', {
                tagIds: tagIds
            })
                .then(res => {
                    this.$notify.success(res.message);
                    this.fetch();
                    this.$bus.$emit('renew_options', 'tag');
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
        onSelection(tags) {
            this.selection = !!tags.length;

            this.selectedTags = tags;

            this.selectionCount = tags.length;
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
                const tags = rows.slice(1).map(row => {
                    const cols = row.split(',');
                    return {
                        title: cols[titleIdx] ? cols[titleIdx].trim() : '',
                        description: descIdx !== -1 && cols[descIdx] ? cols[descIdx].trim() : '',
                        slug: (cols[titleIdx] ? cols[titleIdx].trim().toLowerCase().replace(/\s+/g, '-') : '')
                    };
                }).filter(tag => tag.title);
                this.tagsToImport = tags;
                this.importStep = 2;
                this.$notify.success({ title: this.$t('Success'), message: this.$t('Ready to import ') + tags.length + ' ' + this.$t('tags'), offset: 19 });
            };
            reader.readAsText(file);
        },
        resetImportStage() {
            this.importStep = 1;
            this.tagsToImport = [];
        },
        handleDrawerClose(done) {
            this.importStep = 1;
            this.tagsToImport = [];
            this.showImportDrawer = false;
            if (typeof done === 'function') done();
        },
        downloadSampleCsv() {
            // Use a temporary anchor for best browser compatibility
            const csvContent = 'title,description\nVIP,Very Important Person\ntest,Test Tag\ndemo,Demo Tag\n';
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'sample-tags.csv';
            document.body.appendChild(a);
            a.click();
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 100);
        }
    },
    mounted() {
        this.pagination.per_page = parseInt(this.storage.get('_tags_per_page', 10));
        this.fetch();
        this.changeTitle(this.$t('Tags'));
        this.$emit('changeMenu', 'tags');
    }
};
</script>
