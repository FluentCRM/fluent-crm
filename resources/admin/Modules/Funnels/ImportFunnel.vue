<template>
    <div class="fluentcrm_settings_wrapper fc_sequence_import">
        <div class="fluentcrm_header">
            <div style="padding-bottom: 10px;" class="fluentcrm_header_title">
                <el-breadcrumb>
                    <el-breadcrumb-item :to="{name: 'funnels'}">{{$t('Automation Funnels')}}</el-breadcrumb-item>
                    <el-breadcrumb-item>{{$t('Import Automation Funnel')}}</el-breadcrumb-item>
                </el-breadcrumb>
            </div>
        </div>
        <div v-loading="importing" style="margin-top: 30px; max-width: 1160px;"
             class="fluentcrm_body fluentcrm_title_cards fc_narrow_box fc_white_inverse">
            <template v-if="has_campaign_pro">
                <div class="fc_funnel_upload text-align-center" v-if="step == 'upload'">
                    <h3>{{$t('import_your_exported_automation_json_file')}}</h3>
                    <el-upload
                        drag
                        :limit="1"
                        :action="url"
                        ref="uploader"
                        :multiple="false"
                        :on-error="error"
                        :on-success="success">
                        <i class="el-icon-upload"/>
                        <div class="el-upload__text">
                            {{$t('Drop JSON file here or')}} <em>{{$t('click to upload')}}</em>
                        </div>
                    </el-upload>
                    <pre v-if="inline_errors">{{inline_errors}}</pre>
                </div>
                <div v-else-if="step == 'root_editor'" class="fc_funnel_editor">
                    <field-editor
                        :title_badge="$t('Trigger')"
                        key="is_editing_root"
                        :show_controls="false"
                        :options="options"
                        :data="funnel.settings"
                        :settings="funnel.settingsFields">
                        <template v-slot:after_header>
                            <el-form-item :label="$t('Funnel Name')">
                                <el-input :placeholder="$t('Funnel Name')" v-model="funnel.title"/>
                            </el-form-item>
                        </template>
                    </field-editor>

                    <el-form class="fc_funnel_conditions fc_block_white" v-if="!isEmptyValue(funnel.conditions)"
                             label-position="top"
                             :data="funnel.conditions">
                        <h3>{{ $t('Conditions') }}</h3>
                        <div v-for="(conditionField, conditionKey) in funnel.conditionFields"
                             :key="conditionKey">
                            <form-field
                                :key="conditionKey" v-model="funnel.conditions[conditionKey]"
                                :field="conditionField"
                                :options="options"></form-field>
                        </div>
                    </el-form>
                    <div class="fc_sequence_navigation">
                        <el-button @click="showSequenceEditor(0)" size="large" type="success">
                            {{ $t('Next') }}
                        </el-button>
                    </div>
                </div>
                <div v-else-if="step == 'sequence_editor' && current_block">
                    <field-editor
                        :title_badge="current_block.type"
                        :show_controls="false"
                        :data="current_block.settings"
                        :options="options"
                        :key="sequence_step+'_'+current_block.action_name"
                        :settings="current_block_fields">
                        <template v-slot:after_header>
                            <el-form-item :label="$t('Internal Label')">
                                <el-input :placeholder="$t('Internal Label')" v-model="current_block.title"/>
                            </el-form-item>
                        </template>
                    </field-editor>

                    <div class="fc_sequence_navigation">
                        <el-button v-if="sequence_step > 0" @click="showSequenceEditor(sequence_step - 1)" type="text">
                            {{$t('Go Back')}}
                        </el-button>
                        <el-button v-if="(sequence_step + 1) < sequences.length"
                                   @click="showSequenceEditor(sequence_step + 1)" size="large" type="success">
                            {{ $t('Next') }} {{ sequence_step + 1 }} / {{ sequences.length }}
                        </el-button>
                        <el-button @click="completeImport()" size="large" type="primary" v-else>
                            {{ $t('Complete Import') }}
                        </el-button>
                    </div>

                </div>
            </template>
            <div class="text-align-center" v-else>
                <h2>{{$t('Import Funnel From JSON File')}}</h2>
                <p>
                    {{ $t('importing_funnel_from_json_file') }}
                </p>
                <hr />
                <p>{{$t('Upgrade_To_Pro')}}</p>
                <a class="el-button el-button--danger" :href="appVars.crm_pro_url" target="_blank" rel="noopener">
                    {{$t('Get FluentCRM Pro')}}
                </a>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import FieldEditor from './FunnelEditor/FieldEditor';
import FormField from './FunnelEditor/_Field';

export default {
    name: 'ImportFunnel',
    components: {
        FieldEditor,
        FormField
    },
    props: ['options'],
    data() {
        return {
            sequences: [],
            funnel: {},
            blocks: {},
            block_fields: {},
            step: 'upload',
            sequence_step: 0,
            importing: false,
            inline_errors: false
        }
    },
    computed: {
        url() {
            let url = window.ajaxurl;
            url += (url.match(/\?/) ? '&' : '?') + jQuery.param({action: 'fluentcrm_import_funnel'});
            return url;
        },
        current_block() {
            return this.sequences[this.sequence_step] || false
        },
        current_block_fields() {
            if (!this.current_block) {
                return {}
            }
            const blockKey = this.current_block.action_name;
            return this.block_fields[blockKey];
        }
    },
    methods: {
        success(response) {
            this.sequences = response.funnel_sequences;
            this.funnel = response.funnel;
            this.blocks = response.blocks;
            this.block_fields = response.block_fields;
            this.step = 'root_editor';
        },
        error(error) {
            const errors = JSON.parse(error.message);
            this.$notify.error(errors.message);
            if (errors.requires) {
                this.inline_errors = errors.requires;
            }
        },
        showSequenceEditor(index) {
            jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
            this.sequence_step = index;
            this.step = 'sequence_editor';
        },
        completeImport() {
            this.importing = true;
            this.$post('funnels/import', {
                funnel: this.funnel,
                sequences: JSON.stringify(this.sequences)
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.$router.push({
                        name: 'edit_funnel',
                        params: {
                            funnel_id: response.funnel.id
                        }
                    });
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.importing = false;
                });
        }
    }
}
</script>
