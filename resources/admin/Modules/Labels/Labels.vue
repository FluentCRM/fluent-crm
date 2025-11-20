<template>
    <el-drawer
        :append-to-body="true"
        :title="visibleForm && !updateLabel ? $t('Create New Label') : $t('Manage Labels')"
        :visible.sync="openDrawer"
        @close="handleCloseDrawer"
        class="fc_drawer fc_drawer_manage_labels">
        <div class="fc_drawer_manage_labels_header">
            <h3 style="cursor: pointer;" v-if="visibleForm" @click="resetForm"><i class="el-icon-back"></i> {{ $t('Back to Labels') }}</h3>
            <h3 v-else>{{ $t('System Labels') }}</h3>
            <el-button
                v-if="!visibleForm"
                type="primary"
                class="fc_primary_button"
                @click="handleCreateLabelDialog"
                size="small">{{ $t('Create New Label') }}</el-button>
        </div>
        <div class="fc_drawer_manage_labels_body">
            <el-skeleton v-if="fetchingLabel" animated :rows="6" />
            <el-table
                v-if="!fetchingLabel && !visibleForm"
                :data="labels"
                style="width: 100%">
                <el-table-column
                    prop="title"
                    :label="$t('Label Title')">
                    <template slot-scope="scope">
                            <span class="fc_label_name">
                                <span class="fc_label_color" :style="'background:' + scope.row.settings.color"></span> {{ scope.row.title }}
                            </span>
                    </template>
                </el-table-column>
                <el-table-column
                    prop="slug"
                    :label="$t('Slug')"
                    width="180">
                    <template slot-scope="scope">
                        <span class="fc_label_slug">{{ scope.row.slug }}</span>
                    </template>
                </el-table-column>
                <el-table-column
                    width="90"
                    :label="$t('Action')">
                    <template slot-scope="scope">
                        <div class="fc_drawer_label_action">
                            <el-button
                                type="primary"
                                size="mini"
                                icon="el-icon-edit"
                                @click="handleEditLabel(scope.row)">
                            </el-button>
                            <confirm placement="top-start" @yes="handleDeleteLabel(scope.row.id)">
                                <el-button
                                    slot="reference"
                                    type="danger"
                                    size="mini"
                                    icon="el-icon-delete"></el-button>
                            </confirm>
                        </div>
                    </template>
                </el-table-column>
            </el-table>

            <el-form class="fc_label_form" v-if="visibleForm" label-position="top">
                <el-form-item :label="$t('Label Title')">
                    <el-input ref="labelInput"  v-model="labelForm.title" autocomplete="off"></el-input>
                </el-form-item>
                <el-form-item :label="$t('Label Color')">
                    <el-radio-group v-model="labelForm.color" class="fc_labels_radio">
                        <el-radio
                            v-for="labelColor in labelColors"
                            :key="labelColor"
                            :label="labelColor"
                            :style="'background: ' + labelColor"></el-radio>
                    </el-radio-group>
                </el-form-item>
                <el-form-item>
                    <el-button
                        v-if="updateLabel"
                        type="primary"
                        class="fc_primary_button"
                        size="small"
                        @click="handleUpdateLabel"
                        :disabled="creatingLabel"
                    >{{$t('Save') }}</el-button>
                    <el-button
                        v-else
                        type="primary"
                        class="fc_primary_button"
                        size="small"
                        @click="handleCreateLabel"
                        v-loading="creatingLabel"
                        :disabled="creatingLabel"
                    >{{ $t('Create') }}</el-button>
                    <el-button @click="resetForm" size="small">{{ $t('Cancel') }}</el-button>
                </el-form-item>
            </el-form>
        </div>
    </el-drawer>
</template>

<script>
import Confirm from '@/Pieces/Confirm';

export default {
    name: 'GlobalLabelDrawer',
    props: ['open'],
    components: {
        Confirm
    },
    data() {
        return {
            openDrawer: this.open,
            updateLabel: false,
            visibleForm: false,
            creatingLabel: false,
            labelColors: this.appVars.available_funnel_label_colors,
            fetchingLabel: false,
            labels: [],
            labelForm: {
                id: '',
                title: '',
                color: '#D6D8FF',
                slug: ''
            }
        }
    },
    methods: {
        fetchLabels() {
            this.fetchingLabel = true;
            this.$get('labels')
                .then((response) => {
                    this.labels = response.labels;
                }).catch((errors) => {
                this.handleError(errors);
            }).finally(() => {
                this.fetchingLabel = false;
            });
        },
        handleCreateLabelDialog() {
            this.visibleForm = true;
            this.$nextTick(() => {
                this.$refs.labelInput.focus();
            });
        },
        handleCreateLabel() {
            this.creatingLabel = true;
            if (!this.labelForm.title) {
                this.$notify.error(this.$t('Label name is required'));
                return;
            }
            if (!this.updateLabel) {
                this.labelForm.slug = this.generateSlug(this.labelForm.title);
            }
            this.$post('labels', {
                label: this.labelForm
            }).then((response) => {
                this.$notify.success(response.message);
                this.fetchLabels();
                this.$emit('callFetchLabels');
            }).catch((errors) => {
                this.handleError(errors);
            }).finally(() => {
                this.visibleForm = false;
                this.creatingLabel = false;
                this.labelForm = {
                    title: '',
                    color: '#D6D8FF',
                    slug: ''
                }
            });
        },
        handleUpdateLabel() {
            this.creatingLabel = true;
            if (!this.labelForm.title) {
                this.$notify.error(this.$t('Label name is required'));
                this.creatingLabel = false;
                return;
            }
            this.$put(`labels/${this.labelForm.id}`, {
                label: this.labelForm
            }).then((response) => {
                this.$notify.success(response.message);
                this.fetchLabels();
                this.$emit('callFetchLabels');
            }).catch((errors) => {
                this.handleError(errors);
            }).finally(() => {
                this.visibleForm = false;
                this.creatingLabel = false;
                this.labelForm = {
                    title: '',
                    color: '#D6D8FF',
                    slug: ''
                }
            });
        },
        generateSlug(name) {
            return name
                .toLowerCase() // Convert to lowercase
                .trim() // Trim any leading/trailing whitespace
                .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-') // Replace spaces with hyphens
                .replace(/-+/g, '-'); // Ensure a single hyphen between words
        },
        handleEditLabel(label) {
            this.updateLabel = true;
            this.labelForm = {
                id: label.id,
                title: label.title,
                color: label.settings.color,
                slug: label.slug
            };
            this.visibleForm = true;
            this.$nextTick(() => {
                this.$refs.labelInput.focus();
            });
        },
        resetForm() {
            this.updateLabel = false;
            this.labelForm = {
                title: '',
                color: '#D6D8FF',
                slug: ''
            };
            this.visibleForm = false;
        },
        handleDeleteLabel(id) {
            this.$del('labels/' + id)
                .then((response) => {
                    this.$notify.success(response.message);
                    this.fetchLabels();
                    this.$emit('callFetchLabels');
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {

                });
        },
        handleCloseDrawer() {
            this.openDrawer = false;
            this.$emit('close');
        }
    },
    mounted() {
        this.fetchLabels();
    }
}
</script>
