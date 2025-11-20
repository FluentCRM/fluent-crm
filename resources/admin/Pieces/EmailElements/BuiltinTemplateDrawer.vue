<template>
    <el-drawer
        :title="create_mode ? $t('Create New Email Template') : $t('Built In Templates')"
        :append-to-body="true"
        :wrapperClosable="false"
        :visible.sync="localDrawerVisible"
        size="50%"
    >
        <div class="fc_built_in_templates">
            <div class="fc_template_create_from_stratch" @click="createFromStratch()" v-if="create_mode">
                <h3><i class="el-icon-plus"></i>{{ $t('Create from Scratch') }}</h3>
            </div>
            <el-skeleton
                animated
                :throttle="500"
                :count="3"
                v-if="isLoadingTemplates"
                style="margin-top: 20px;"
            >
                <template slot="template">
                    <div class="skeleton-box">
                        <el-skeleton-item
                            animated
                            variant="image"
                            style="width: 100%; height: 240px;margin-bottom: 10px;"
                        />
                        <el-skeleton-item
                            animated variant="h3" style="width: 50%;" />
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; margin-top: 16px; height: 16px;"
                        >
                            <el-skeleton-item
                                animated variant="text" style="width: 30%;margin-right: 7px;" />
                            <el-skeleton-item
                                animated variant="text" style="width: 30%;" />
                        </div>
                    </div>
                </template>
            </el-skeleton>
            <div v-if="!isLoadingTemplates && builtInTemplates.length" class="fc_built_in_templates_wrap">
                <h4 v-if="create_mode" style="grid-column: 1 / -1; font-size: 16px;">{{ $t('Popular pre-built email templates') }}</h4>
                <el-card class="fc_build_in_temp_box"
                         v-for="template in builtInTemplates"
                         :key="template.id" :label="3">
                    <div class="fc_build_in_temp_box_inner">
                        <div class="img-box">
                            <img v-if="template.cover_image" :src="template.cover_image" :alt="template.title" />
                            <p v-else>{{ $t('Empty') }}</p>
                        </div>
                        <div class="content">
                            <h2>{{ template.title }}</h2>
                            <p>{{ template.short_description }}</p>
                            <div class="actions">
                                <el-button
                                    v-loading="importing && templateIdImporting == template.id"
                                    size="mini"
                                    type="info"
                                    element-loading-spinner="el-icon-loading"
                                    @click="importTemplate(template)"
                                    :disabled="importing"
                                >
                                    {{ $t('Import') }}
                                </el-button>
                                <el-link :href="template.link" type="primary" target="_blank">{{ $t('Preview') }}</el-link>
                            </div>
                        </div>
                    </div>
                </el-card>
            </div>
            <el-empty v-if="!isLoadingTemplates && !builtInTemplates.length" :description="$t('No Built In Templates Found')"></el-empty>
        </div>
    </el-drawer>
</template>

<script type="text/babel">

export default {
    name: 'BuiltinTemplateDrawer',
    props: {
        open_drawer: {
            type: Boolean,
            default: () => {
                return false
            }
        },
        create_mode: {
            type: Boolean,
            default: () => {
                return false
            }
        }
    },
    data() {
        return {
            show_drawer: false,
            builtInTemplates: [],
            isLoadingTemplates: false,
            importing: false,
            templateIdImporting: null,
            oneTimeFetch: true,
            showTemplatePreview: false,
            previewTemplateId: '',
            email_template: {
                post_title: '',
                post_content: '',
                post_excerpt: '',
                email_subject: '',
                edit_type: 'html',
                design_template: 'simple',
                settings: {
                    template_config: {}
                }
            }
        }
    },
    watch: {
        open_drawer(newValue) {
            if (newValue) {
                this.fetchBuiltInTemplates();
            } else {
                this.show_drawer = false;
            }
        }
    },
    computed: {
        localDrawerVisible: {
            get() {
                return this.show_drawer;
            },
            set(value) {
                this.show_drawer = value;
                if (!value) {
                    this.$emit('update:open_drawer', false);
                }
            }
        }
    },
    methods: {
        fetchBuiltInTemplates() {
            this.show_drawer = true;
            if (!this.oneTimeFetch) {
                return
            }
            this.oneTimeFetch = false;
            this.isLoadingTemplates = true;
            this.$get('templates/built-in-templates')
                .then(response => {
                    this.builtInTemplates = response.templates;
                })
                .catch(error => {
                    this.handleError(error)
                })
                .finally(() => {
                    this.isLoadingTemplates = false;
                })
        },
        importTemplate(template) {
            this.templateIdImporting = template.id;
            this.importing = true;
            window.jQuery.post(window.ajaxurl, {
                body: {
                    file: template.content
                },
                action: 'fluentcrm_import_template'
            })
                .then(response => {
                    this.$notify.success(response.message);
                    // location.reload();
                    this.$router.push({
                        name: 'edit_template',
                        params: {
                            template_id: response.template_id
                        }
                    });
                })
                .catch(error => {
                    this.handleError(error.responseJSON.message);
                })
                .always(() => {
                    this.templateIdImporting = null;
                    this.importing = false;
                });
        },
        createFromStratch() {
            this.$router.push({
                name: 'edit_template',
                params: {
                    template_id: 0
                }
            });
        }
    }
}
</script>
