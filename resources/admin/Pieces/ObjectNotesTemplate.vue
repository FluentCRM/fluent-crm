<template>
    <div v-loading="loading" class="fluentcrm_databox">
        <div class="fluentcrm_contact_header fc_notes_header">
            <el-row :gutter="30">
                <el-col :span="12">
                    <h3 style="margin: 5px 0;">{{ section_title }}</h3>
                </el-col>
                <el-col v-if="hasPermission('fcrm_manage_contacts')"
                        class="text-align-right d-flex justify-end items-center" :span="12">

                    <el-input @keyup.enter.native="fetch" style="width: 220px; margin-right: 10px" clearable size="mini"
                              :placeholder="$t('Search')" v-model="search" class="input-search-notes">
                        <el-button @click="fetch()" slot="append" icon="el-icon-search"></el-button>
                    </el-input>

                    <el-button @click="initAddNote()" type="primary" size="small">
                        {{ $t('Add New') }}
                    </el-button>
                    <el-button @click="exportNotes()" type="info" size="small" icon="el-icon-download"></el-button>
                </el-col>
            </el-row>
        </div>

        <template v-if="pagination.total">
            <div class="fluentcrm_notes">
                <div v-for="note in notes" :key="note.id" class="fluentcrm_note">
                    <div class="note_header">
                        <div class="note_title">{{ types[note.type] || note.type }} : {{ note.title }}</div>
                        <div class="note_meta">
                            <span v-if="note.added_by && note.added_by.display_name">{{ $t('By') }}
                                {{ note.added_by.display_name }}
                            </span>
                            {{ $t('at') }} <span :title="note.created_at">{{ note.created_at | nsHumanDiffTime }}</span>
                        </div>
                        <div v-if="hasPermission('fcrm_manage_contacts')" class="note_delete">
                            <confirm @yes="remove(note.id)">
                                <i class="el-icon el-icon-delete"></i>
                            </confirm>
                            <i @click="editNote(note)" style="margin-left: 5px" class="el-icon el-icon-edit"></i>
                        </div>
                    </div>
                    <div class="note_body" v-html="note.description"></div>
                </div>
            </div>
            <pagination :pagination="pagination" @fetch="fetch"/>
        </template>
        <div v-else>
            <h4 class="text-align-center">{{ $t('Pro_No_NfPatfn') }}</h4>
        </div>

        <el-drawer
            class="fc_company_info_drawer"
            :with-header="true"
            :size="globalDrawerSize"
            :title="(is_editing_note && editing_note.id) ? $t('Edit Note') : $t('Create a note')"
            :append-to-body="true"
            :before-close="handleClose"
            :visible.sync="is_editing_note">
            <div v-if="is_editing_note" style="padding: 10px 15px;" :class="'fc_note_type_' + editing_note.type" class="fc_company_unsaved fc_company_info_wrapper">

                <form-builder class="mt-20" :formData="editing_note" :fields="note_syncing_fields.fields"></form-builder>

                <div class="fc_company_save_wrap">
                    <el-button v-if="editing_note.id" @click="updateNote()" size="small" type="success">
                        {{ $t('Update Note') }}
                    </el-button>
                    <el-button v-else @click="saveNote()" size="small" type="success">
                        {{ $t('Create') }}
                    </el-button>
                </div>
            </div>
        </el-drawer>
    </div>
</template>

<script type="text/babel">
import Pagination from '@/Pieces/Pagination';
import Confirm from '@/Pieces/Confirm';
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';

export default {
    name: 'ObjectNoteTemplate',
    props: ['subscriber_id', 'route_prefix', 'section_title'],
    components: {
        Pagination,
        Confirm,
        FormBuilder
    },
    data() {
        return {
            loading: false,
            notes: [],
            types: window.fcAdmin.activity_types,
            pagination: {
                total: 0,
                per_page: 10,
                current_page: 1
            },
            editing_note: {
                title: '',
                description: '',
                type: 'note',
                created_at: ''
            },
            is_editing_note: false,
            search: '',
            updating: false,
            is_changed: false,
            note_syncing_fields: {}
        }
    },
    watch: {
        'editing_note.type'(newValue, oldValue) {
            this.handleValueUpdate(newValue, oldValue);
        },
        'editing_note.title'(newValue, oldValue) {
            this.handleValueUpdate(newValue, oldValue);
        },
        'editing_note.description'(newValue, oldValue) {
            this.handleValueUpdate(newValue, oldValue);
        },
        'editing_note.created_at'(newValue, oldValue) {
            this.handleValueUpdate(newValue, oldValue);
        }
    },
    methods: {
        handleValueUpdate(newValue, oldValue) {
            if (newValue !== oldValue) {
                this.is_changed = true;
            }
        },
        handleClose(done) {
            if (this.is_changed) {
                this.$confirm(this.$t('You have unsaved data, proceed?'))
                    .then(_ => {
                        this.is_changed = false;
                        this.is_editing_note = false;
                        this.resetNoteData();
                        done();
                    })
                    .catch(_ => {
                        console.log(_);
                    });
            } else {
                done();
            }
        },
        fetch() {
            this.loading = true;
            this.$get(`${this.route_prefix}/${this.subscriber_id}/notes`, {
                per_page: this.pagination.per_page,
                page: this.pagination.current_page,
                search: this.search
            })
                .then(response => {
                    this.notes = response.notes.data;
                    this.pagination.total = response.notes.total;
                    this.note_syncing_fields = response.fields;
                    this.resetNoteData();
                    this.is_editing_note = false;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        initAddNote() {
            this.resetNoteData();
            this.is_editing_note = true;
        },
        resetNoteData() {
            this.editing_note = {
                title: '',
                description: '',
                type: 'note',
                created_at: ''
            };
        },
        saveNote() {
            this.$post(`${this.route_prefix}/${this.subscriber_id}/notes`, {
                note: this.editing_note
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    this.fetch();
                    this.$emit('added', response.note);
                    this.resetNoteData();
                    this.is_editing_note = false;
                })
                .catch((errors) => {
                    if (errors.title) {
                        this.$notify.error({
                            title: this.$t('Error'),
                            message: errors.title.required
                        });
                    }
                    if (errors.description) {
                        this.$notify.error({
                            title: this.$t('Error'),
                            message: errors.description.required
                        });
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        remove(id) {
            this.$del(`${this.route_prefix}/${this.subscriber_id}/notes/${id}`)
                .then(response => {
                    this.fetch();
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                    this.$emit('deleted', id);
                })
                .catch((errors) => {
                    this.handleError(errors);
                });
        },
        editNote(note) {
            this.editing_note = note;
            this.is_editing_note = true;
        },
        updateNote() {
            this.updating = true;
            this.$put(`${this.route_prefix}/${this.subscriber_id}/notes/${this.editing_note.id}`, {
                note: this.editing_note
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    this.is_editing_note = false;
                    this.resetNoteData();
                    this.$emit('updated', response.note);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        exportNotes() {
            if (!this.has_campaign_pro) {
                this.$notify.error(this.$t('Notes export feature is only available on pro version'));
                return false;
            }
            location.href = window.ajaxurl + '?' + jQuery.param({
                action: 'fluentcrm_export_notes',
                route_prefix: this.route_prefix,
                subscriber_id: this.subscriber_id
            });
        }
    },
    mounted() {
        this.fetch();
    }
}
</script>
