<template>
    <el-form label-position="top" label-width="100px">
        <h3>{{ $t('Basic Info') }}</h3>
        <el-row :gutter="20">
            <el-col :lg="4" :md="4" :sm="24" :xs="24">
                <el-form-item :label="$t('Prefix')">
                    <el-select :placeholder="$t('Select')" v-model="subscriber.prefix">
                        <el-option v-for="prefix in name_prefixes" :key="prefix" :label="prefix"
                                   :value="prefix"></el-option>
                    </el-select>
                </el-form-item>
            </el-col>
            <el-col :lg="8" :md="8" :sm="24" :xs="24">
                <el-form-item :label="$t('First Name')">
                    <el-input autocomplete="new-password" v-model="subscriber.first_name"/>
                    <error :error="errors.get('first_name')"/>
                </el-form-item>
            </el-col>
            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                <el-form-item :label="$t('Last Name')">
                    <el-input autocomplete="new-password" v-model="subscriber.last_name"/>

                    <error :error="errors.get('last_name')"/>
                </el-form-item>
            </el-col>
        </el-row>
        <el-row :gutter="20">
            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                <el-form-item :label="$t('Email')" class="is-required">
                    <el-input autocomplete="new-password" v-model="subscriber.email"/>
                    <error :error="errors.get('email')"/>
                </el-form-item>
            </el-col>
            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                <el-form-item :label="$t('Phone')">
                    <el-input autocomplete="new-password" v-model="subscriber.phone"/>
                </el-form-item>
            </el-col>
        </el-row>
        <el-row :gutter="20">
            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                <el-form-item :label="$t('Date of Birth')">
                    <el-date-picker
                        v-if="false"
                        type="date"
                        value-format="yyyy-MM-dd"
                        :picker-options="pickerOptions"
                        :placeholder="$t('Pick a date')"
                        v-model="subscriber.date_of_birth" style="width: 100%;"></el-date-picker>

                    <date-drop-down-picker v-model="subscriber.date_of_birth" />
                </el-form-item>
            </el-col>

            <el-col v-if="has_company_module && !company_id" :lg="12" :md="12" :sm="24" :xs="24">
                <el-form-item :label="$t('Company / Business')">
                    <company-selector :field="{ is_multiple: false, creatable: true }"
                                      v-model="subscriber.company_id"></company-selector>
                </el-form-item>
            </el-col>

        </el-row>
        <el-form-item>
            <el-checkbox v-model="show_address">{{ $t('Add Address Info') }}</el-checkbox>
        </el-form-item>

        <template v-if="show_address">
            <el-row :gutter="20">
                <el-col :md="12">
                    <el-form-item :label="$t('Address Line 1')">
                        <el-input :placeholder="$t('Address Line 1')" autocomplete="new-password"
                                  v-model="subscriber.address_line_1"/>
                    </el-form-item>
                </el-col>
                <el-col :md="12">
                    <el-form-item :label="$t('Address Line 2')">
                        <el-input :placeholder="$t('Address Line 2')" autocomplete="new-password"
                                  v-model="subscriber.address_line_2"/>
                    </el-form-item>
                </el-col>
                <el-col :md="12">
                    <el-form-item :label="$t('City')">
                        <el-input :placeholder="$t('City')" autocomplete="new-password" v-model="subscriber.city"/>
                    </el-form-item>
                </el-col>

                <el-col :md="12">
                    <el-form-item :label="$t('State')">
                        <el-input :placeholder="$t('State')" autocomplete="new-password" v-model="subscriber.state"/>
                    </el-form-item>
                </el-col>

                <el-col :md="12">
                    <el-form-item :label="$t('Postal Code')">
                        <el-input :placeholder="$t('Postal Code')" autocomplete="new-password"
                                  v-model="subscriber.postal_code"/>
                    </el-form-item>
                </el-col>

                <el-col :md="12">
                    <el-form-item :label="$t('Country')">
                        <el-select
                            v-model="subscriber.country"
                            clearable
                            filterable
                            autocomplete="off"
                            :placeholder="$t('Select country')"
                            class="el-select-multiple"
                        >
                            <el-option
                                v-for="item in countries"
                                :key="item.code"
                                :value="item.code"
                                :label="item.title"
                            >
                                {{ item.title }}
                            </el-option>
                        </el-select>
                    </el-form-item>
                </el-col>
            </el-row>
        </template>

        <el-form-item v-if="options.custom_fields.length">
            <el-checkbox v-model="show_custom_data">{{ $t('Add Custom Data') }}</el-checkbox>
        </el-form-item>

        <template v-if="show_custom_data">
            <custom-fields :subscriber="subscriber" :custom_fields="options.custom_fields"/>
        </template>

        <h3>{{ $t('Identifiers') }}</h3>

        <el-row :gutter="20">
            <el-col :md="8" :sm="24" v-if="!listId">
                <el-form-item :label="$t('Lists')">
                    <option-selector v-model="subscriber.lists"
                                     :field="{ placeholder: $t('Select lists'), is_multiple: true, creatable: true, option_key: 'lists' }"/>
                </el-form-item>
            </el-col>

            <el-col :md="8" :sm="24" v-if="!tagId">
                <el-form-item :label="$t('Tags')">
                    <option-selector v-model="subscriber.tags"
                                     :field="{ placeholder: $t('Select Tags'), is_multiple: true, creatable: true, option_key: 'tags' }"/>
                </el-form-item>
            </el-col>

            <el-col :md="8" :sm="24">
                <el-form-item :label="$t('Status')" class="is-required">
                    <el-select v-model="subscriber.status" :placeholder="$t('Select')">
                        <el-option
                            v-for="item in options.statuses"
                            :key="item.id"
                            :label="item.title|ucFirst"
                            :value="item.id"
                        >
                        </el-option>
                    </el-select>
                    <error :error="errors.get('status')"/>
                </el-form-item>
            </el-col>
        </el-row>
        <el-row v-if="subscriber.status === 'pending'">
            <el-col>
                <el-checkbox
                    :label="$t('Enable Double-Optin Email Confirmation')"
                    v-model="subscriber.double_optin"
                ></el-checkbox>
            </el-col>
        </el-row>
    </el-form>
</template>

<script type="text/babel">
import Error from '@/Pieces/Error';
import CustomFields from '@/Modules/Profile/Parts/_CustomFields'
import CompanySelector from '@/Pieces/FormElements/_CompanySelector'
import OptionSelector from '@/Pieces/FormElements/_OptionSelector';
import DateDropDownPicker from '@/Pieces/UniInputs/DateDropDownPicker';

export default {
    name: 'Form',
    components: {
        Error,
        CustomFields,
        CompanySelector,
        OptionSelector,
        DateDropDownPicker
    },
    props: {
        subscriber: {
            required: true,
            type: Object
        },
        errors: {
            required: true,
            type: Object
        },
        listId: {
            default: null
        },
        tagId: {
            default: null
        },
        company_id: {
            default: null
        }
    },
    data() {
        return {
            show_address: false,
            show_custom_data: false,
            countries: window.fcAdmin.countries,
            name_prefixes: window.fcAdmin.contact_prefixes,
            pickerOptions: {
                disabledDate(date) {
                    return date.getTime() >= (Date.now());
                }
            },
            options: {
                statuses: this.appVars.available_contact_statuses,
                contact_types: this.appVars.available_contact_types,
                custom_fields: this.appVars.available_custom_fields
            }
        }
    }
}
</script>
