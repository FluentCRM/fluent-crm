<template>
    <el-dialog :title="$t('Export Subscriber')"
               :visible="visible"
               width="50%"
               :append-to-body="true"
               :close-on-click-modal="false"
               @close="hide()"
               class="fluentcrm-subscribers-export-dialog"
    >
        <div :style="{ pointerEvents: csvFileDownloading ? 'none' : 'auto' }" >
            <el-progress v-if="csvFileDownloading" :text-inside="true" :stroke-width="26" :percentage="progressPercentage"></el-progress>
            <div class="fc_form_item">
                <h3>{{ $t('Exp_Please_sctywte') }}</h3>
                <div style="margin-bottom: 15px;">
                    <el-checkbox style="min-width: 250px" v-model="check_all" @change="handleAllCheck">
                        {{ $t('Check All') }}
                    </el-checkbox>
                </div>
                <el-checkbox-group v-model="columns" class="fc_2_col_items">
                    <el-checkbox label="id">{{ $t('ID') }}</el-checkbox>
                    <el-checkbox label="prefix">{{ $t('Name Prefix') }}</el-checkbox>
                    <el-checkbox label="user_id">{{ $t('User ID') }}</el-checkbox>
                    <el-checkbox label="email">{{ $t('Email') }}</el-checkbox>
                    <el-checkbox v-for="column in available_columns" :label="column.value" :key="column.value">{{column.label}}</el-checkbox>
                    <el-checkbox label="ip">{{ $t('IP Address') }}</el-checkbox>
                </el-checkbox-group>

                <template v-if="appVars.contact_custom_fields.length">
                    <h3>{{ $t('Custom Contact Fields') }}</h3>
                    <el-checkbox-group v-model="custom_fields" class="fc_2_col_items">
                        <el-checkbox v-for="customField in appVars.contact_custom_fields" :label="customField.slug" :key="customField.slug">{{customField.label}}</el-checkbox>
                    </el-checkbox-group>
                </template>

                <template v-if="appVars.commerce_provider">
                    <h3>{{ $t('Commerce Fields') }} ({{appVars.commerce_provider | ucFirst}})</h3>
                    <el-checkbox-group v-model="commerce_columns" class="fc_2_col_items">
                        <el-checkbox label="total_order_value">{{ $t('Lifetime Value') }}</el-checkbox>
                        <el-checkbox label="total_order_count">{{ $t('Total Order Count') }}</el-checkbox>
                        <el-checkbox label="first_order_date">{{ $t('Customer Since') }}e</el-checkbox>
                        <el-checkbox label="last_order_date">{{ $t('Last Order Date') }}</el-checkbox>
                    </el-checkbox-group>
                </template>

            </div>
            <div class="fc_form_item fc_t_30 wfc_well">
                <el-row :gutter="20">
                    <el-col :span="12">
                        <label style="margin-bottom: 10px;">{{ $t('Contact Export Limit') }}</label>
                        <el-input type="number" v-model="limit" />
                    </el-col>
                    <el-col :span="12">
                        <label style="margin-bottom: 10px;">{{ $t('Contact Export Offset') }}</label>
                        <el-input type="number" v-model="offset" />
                    </el-col>
                </el-row>
                <p>{{ $t('Exp_Leave_tbfnloo') }}</p>
            </div>
            <p v-if="exporting">{{ $t('Exporting... Please wait') }}</p>
        </div>
        <span v-if="has_campaign_pro" slot="footer" class="dialog-footer">
            <el-button :disabled="csvFileDownloading" size="small" @click="hide">
                {{ $t('Cancel') }}
            </el-button>

            <el-button :disabled="csvFileDownloading" size="small" type="primary" @click="exportContacts()">
                {{ $t('Export Contacts') }}
            </el-button>
        </span>
        <span v-else slot="footer" class="dialog-footer">
            <generic-promo />
        </span>
    </el-dialog>
</template>

<script type="text/babel">
    import {subscriberColumns} from '@/Bits/data_config.js';
    import GenericPromo from '@/Modules/Promos/GenericPromo';

    const DEFAULT_COLUMNS = ['first_name', 'last_name', 'email'];
    const ALL_COLUMNS = ['id', 'first_name', 'last_name', 'email', 'prefix', 'user_id', 'status', 'ip'];
    const ALL_COMMERCE_COLUMNS = ['total_order_value', 'total_order_count', 'first_order_date', 'last_order_date'];

    export default {
        name: 'ExportSubscriber',
        props: ['visible', 'search_query'],
        components: {
            GenericPromo
        },
        data() {
            return {
                columns: [...DEFAULT_COLUMNS],
                available_columns: subscriberColumns,
                custom_fields: [],
                commerce_columns: [],
                limit: '',
                offset: '',
                exporting: false,
                check_all: false,
                progressPercentage: 0,
                csvFileDownloading: false
            }
        },
        methods: {
            handleAllCheck() {
                if (this.check_all) {
                    this.selectAllColumns();
                    if (this.appVars.contact_custom_fields.length) {
                        this.selectAllCustomFields();
                    }
                    if (this.appVars.commerce_provider) {
                        this.commerce_columns = [...ALL_COMMERCE_COLUMNS];
                    }
                } else {
                    this.resetSelections();
                }
            },
            selectAllColumns() {
                this.columns = [...ALL_COLUMNS, ...this.available_columns.map(column => column.value)];
            },
            selectAllCustomFields() {
                this.custom_fields = this.appVars.contact_custom_fields.map(field => field.slug);
            },
            resetSelections() {
                this.columns = [...DEFAULT_COLUMNS];
                this.custom_fields = [];
                this.commerce_columns = [];
            },
            hide() {
                this.$emit('close');
            },
            exportContacts() {
                const requestData = {
                    action: 'fluentcrm_export_contacts',
                    ...this.search_query,
                    columns: this.columns,
                    custom_fields: this.custom_fields,
                    commerce_columns: this.commerce_columns,
                    limit: this.limit,
                    offset: this.offset,
                    format: 'csv'
                };
                this.csvFileDownloading = true;

                jQuery.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: requestData,
                    success: function(response) {
                        if (response.success) {
                            this.progressPercentage = 0;
                            this.pollExportStatus();
                        }
                    }.bind(this),
                    error: function(jqXHR, textStatus, errorThrown) {
                        this.csvFileDownloading = false;
                        this.progressPercentage = 0;
                        console.error('Error during export:', textStatus, errorThrown);
                    }.bind(this)
                });
            },
            pollExportStatus() {
                const requestData = {
                    action: 'fluentcrm_contacts_csv_export_status'
                };

                jQuery.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: requestData,
                    success: function(response) {
                        if (response.success && response.data.status === 'succeed') {
                            this.downloadFile();
                        } else if (response.success && response.data.status.startsWith('preparing')) {
                            const progress = response.data.progress;
                            const total = response.data.total;
                            let percentage = Math.round((progress / total) * 100);
                            if (isNaN(percentage)) {
                                percentage = 0;
                            }
                            this.progressPercentage = percentage;
                            setTimeout(() => this.pollExportStatus(), 5000);
                        } else {
                            this.csvFileDownloading = false;
                            this.progressPercentage = 0;
                            console.error('Unexpected response:', response);
                        }
                    }.bind(this),
                    error: function(jqXHR, textStatus, errorThrown) {
                        this.csvFileDownloading = false;
                        this.progressPercentage = 0;
                        console.error('Error during status check:', textStatus, errorThrown);
                    }.bind(this)
                });
            },
            downloadFile() {
                const requestData = {
                    action: 'fluentcrm_contacts_export_csv_file_download'
                };

                jQuery.ajax({
                    url: window.ajaxurl,
                    method: 'POST',
                    data: requestData,
                    success: function(data, textStatus, jqXHR) {
                        if (data.success && data.data.status === 'preparing') {
                            // If the file is still being prepared, retry after a short delay
                            setTimeout(() => this.downloadFile(), 5000);
                            return;
                        }

                        // Check the Content-Type of the response
                        const contentType = jqXHR.getResponseHeader('Content-Type');

                       if (contentType && contentType.includes('application/csv')) {
                            // Handle binary file response (CSV file)
                            const contentDisposition = jqXHR.getResponseHeader('Content-Disposition');
                            const fileNameMatch = contentDisposition && contentDisposition.match(/filename="([^"]+)"/);
                            const filename = fileNameMatch ? fileNameMatch[1] : 'export.csv';

                            // Ensure the response is treated as a Blob
                            const blob = new Blob([data], { type: 'application/csv' });

                            // Create a blob URL for the file
                            const url = window.URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = url;
                            link.setAttribute('download', filename);
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            this.csvFileDownloading = false
                            this.progressPercentage = 0;
                       } else {
                            this.csvFileDownloading = false;
                            this.progressPercentage = 0;
                            console.error('Unexpected content type:', contentType);
                        }
                    }.bind(this),
                    error: function(jqXHR, textStatus, errorThrown) {
                        this.csvFileDownloading = false
                        this.progressPercentage = 0;
                        console.error('Error during file download:', textStatus, errorThrown);
                    }
                });
            }
        }
    }

</script>
