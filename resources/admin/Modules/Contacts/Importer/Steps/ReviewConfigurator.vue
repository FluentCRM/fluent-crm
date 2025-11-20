<template>
    <div class="fc_review_configurator">
        <template v-if="!done">
            <el-form v-if="!importing" label-position="top" class="manager">
                <template v-if="import_info.subscribers">
                    <el-form-item :label="$t('Use_Some_otumyc')"/>
                    <!--sample matching users-->
                    <el-table
                        border
                        stripe
                        :data="import_info.subscribers"
                        style="width: 100%; margin-bottom: 30px;"
                    >
                        <el-table-column
                            prop="name"
                            :label="$t('Name')"
                        />

                        <el-table-column
                            prop="email"
                            :label="$t('Email')"
                        />
                    </el-table>

                    <p v-if="import_info.total">{{ $t('Total Found Result:') }} {{ import_info.total }}</p>
                </template>
                <el-row :gutter="20">
                    <el-col v-if="import_info.has_list_config" :lg="12" :md="12" :sm="12" :xs="12">
                        <el-form-item :label="$t('Apply Lists')">
                            <option-selector v-model="general_config.lists"
                                             :field="{ is_multiple: true, creatable: true, option_key: 'lists' }"/>
                        </el-form-item>
                    </el-col>
                    <el-col v-if="import_info.has_tag_config" :lg="12" :md="12" :sm="12" :xs="12">
                        <el-form-item :label="$t('Apply Tags')">
                            <option-selector v-model="general_config.tags"
                                             :field="{ is_multiple: true, creatable: true, option_key: 'tags' }"/>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-row :gutter="20">
                    <el-col v-if="import_info.has_update_config" :lg="12" :md="12" :sm="12" :xs="12">
                        <el-form-item class="no-margin-bottom">
                            <template slot="label">
                                {{ $t('Update Subscribers') }}
                                <el-tooltip class="item" placement="bottom-start" effect="light">
                                    <div slot="content">
                                        <h3>{{ $t('Update Subscribers') }}</h3>
                                        <p>
                                            {{$t('update_subscribers_data_notice')}}
                                        </p>
                                    </div>

                                    <i class="el-icon-info text-info"></i>
                                </el-tooltip>
                            </template>
                            <el-radio-group v-model="general_config.update">
                                <el-radio :label="true">{{ $t('Yes') }}</el-radio>
                                <el-radio :label="false">{{ $t('No') }}</el-radio>
                            </el-radio-group>
                        </el-form-item>
                    </el-col>
                    <el-col v-if="import_info.has_status_config" :lg="12" :md="12" :sm="12" :xs="12">
                        <el-form-item class="no-margin-bottom">
                            <template slot="label">
                                {{ $t('New Subscriber Status') }}
                                <el-tooltip class="item" placement="bottom-start" effect="light">
                                    <div slot="content">
                                        {{ $t('New Subscriber Status') }}

                                        <p>
                                            {{ $t('Map_Status_ftns') }}
                                        </p>
                                    </div>
                                    <i class="el-icon-info text-info"></i>
                                </el-tooltip>
                            </template>
                            <option-selector v-model="general_config.status"
                                             :field="{ is_multiple: false, option_key: 'editable_statuses' }"/>
                            <el-checkbox v-if="general_config.status == 'pending'" true-label="yes"
                                         v-model="general_config.double_optin_email">
                                {{ $t('Send Double Optin Email for new contacts') }}
                            </el-checkbox>
                        </el-form-item>
                    </el-col>
                </el-row>
                <el-checkbox v-if="import_info.has_silent_config" style="margin-top: 20px;" true-label="yes"
                             false-label="no" v-model="general_config.import_silently">
                    {{$t('Do Not Trigger Automations (Tag & List related Events)')}}
                </el-checkbox>
                <p v-if="general_config.import_silently == 'yes'">
                    {{ $t('Uncheck this if you want to trigger automation events') }}
                </p>
            </el-form>

            <div class="text-align-center" v-else>
                <h3>{{ $t('Importing now...') }}</h3>
                <h4>{{ $t('Use_Please_dnctm') }}</h4>
                <template v-if="import_page_total">
                    <h2 v-if="import_page_total">{{ importing_page }}/{{ import_page_total }}</h2>
                    <el-progress :text-inside="true" :stroke-width="24"
                                 :percentage="parseInt((importing_page / import_page_total) * 100)"
                                 status="success"></el-progress>
                </template>
            </div>

            <div v-if="!importing" slot="footer" class="dialog-footer">
                <el-button
                    size="small"
                    type="primary"
                    @click="importData()">
                    <span v-if="labels.step_3">{{ labels.step_3 }}</span>
                    <span v-else>{{ $t('Import Now') }}</span>
                </el-button>
            </div>
        </template>
        <div v-else>
            <h3 style="text-align: center;">{{ done_message }}</h3>
        </div>
    </div>
</template>

<script type="text/babel">
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';

export default {
    name: 'ReviewConfigurator',
    components: {
        OptionSelector
    },
    props: ['config', 'import_info', 'driver', 'labels'],
    data() {
        return {
            general_config: {
                tags: [],
                lists: [],
                update: true,
                new_status: '',
                import_silently: 'yes',
                double_optin_email: 'no'
            },
            importing: false,
            importing_page: 1,
            import_page_total: 0,
            done: false,
            done_message: this.$t('Import has been completed, You may close this modal now')
        }
    },
    methods: {
        importData() {
            this.importing = true;
            this.$post('import/drivers/' + this.driver, {
                config: {
                    ...this.config,
                    ...this.general_config
                },
                importing_page: this.importing_page
            })
                .then(response => {
                    if (response.has_more) {
                        this.import_page_total = response.page_total;
                        if (response.next_page) {
                            this.importing_page = response.next_page;
                        } else {
                            this.importing_page = this.importing_page + 1;
                        }

                        this.$nextTick(() => {
                            this.importData();
                        });
                    } else {
                        this.done = true;
                        if (response.message) {
                            this.done_message = response.message;
                            this.$notify.success(response.message);
                        }
                        this.$emit('fetch');
                    }
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {

                });
        }
    }
}
</script>
