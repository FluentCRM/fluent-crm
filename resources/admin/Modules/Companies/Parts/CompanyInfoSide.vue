<template>
    <div :class="{fc_company_in_drawer: is_drawer, fc_company_unsaved: isDirty || !company.id}"
         class="fc_company_info_wrapper">
        <div :class="{ fc_header_editing: isHeaderEditing || !company.id }" class="fc_company_header">
            <div class="company_head_logo">
                <p v-if="isHeaderEditing || !company.id"><b>{{ $t('Company Logo') }}</b></p>
                <div class="fluentcrm_profile-photo">
                    <div :class="photo_holder"
                         :style="{ backgroundImage: company.logo ? 'url(' + company.logo + ')' : ''}"></div>
                    <photo-widget
                        class="fc_photo_changed"
                        btn_type="default"
                        btn_text="+"
                        :btn_mode="true"
                        @changed="updateAvatar"
                        v-model="company.logo"
                    >
                        <template v-if="company.id && company.website" #after>
                            <div class="fc_photo_actions">
                                <el-button v-loading="updating" @click="reFetchLogo()" size="mini" type="default"
                                           icon="el-icon-refresh"></el-button>
                            </div>
                        </template>
                    </photo-widget>
                </div>
            </div>
            <div style="flex: 1" class="fc_company_info">
                <template v-if="isHeaderEditing || !company.id">
                    <el-form class="fc_compact_form" label-position="top" :model="model">
                        <el-form-item :label="$t('Company Name (required)')">
                            <el-input :placeholder="$t('Company Name')" v-model="model.name"/>
                        </el-form-item>
                        <el-form-item :label="$t('Company Email')">
                            <el-input type="email" :placeholder="$t('Company Email')" v-model="model.email"/>
                        </el-form-item>
                        <el-form-item :label="$t('Company Phone Number')">
                            <el-input :placeholder="$t('Phone Number')" v-model="model.phone"/>
                        </el-form-item>
                    </el-form>
                </template>
                <template v-else>
                    <h3>
                        {{ company.name }}
                        <i @click="enableEdit()" class="el-icon el-icon-edit"
                           style="cursor: pointer; color: gray; margin-left: 10px;"></i>
                    </h3>
                    <div v-if="domainName" class="company_domain">
                        <a target="_blank" rel="noopener" :href="company.website">{{ domainName }} <span
                            class="fc_dash_extrernal dashicons dashicons-external"></span></a>
                    </div>
                    <div v-if="company.email || company.phone" class="company_email">
                    <span v-if="model.email">
                        {{ company.email }}
                        <span v-show="company.phone" class="fc_middot">·</span>
                    </span>
                        <span>{{ model.phone }}</span>
                    </div>
                </template>
            </div>
        </div>
        <hr/>
        <h3 class="fc_section_title">{{ $t('About this company') }}</h3>
        <div class="fc_company_about">
            <el-form class="fc_compact_form" label-position="top" :model="model">
                <el-form-item :label="$t('Website')">
                    <el-input type="url" :placeholder="$t('website url')" v-model="model.website"/>
                </el-form-item>
                <el-form-item :label="$t('Industry')">
                    <el-select v-model="model.industry" allow-create clearable filterable
                               :placeholder="$t('Company Industry')">
                        <el-option
                            v-for="category in appVars.company_categories"
                            :key="category"
                            :label="category"
                            :value="category"
                        >
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item :label="$t('Type')">
                    <el-select v-model="model.type" clearable filterable :placeholder="$t('Relationship Type')">
                        <el-option
                            v-for="category in appVars.company_types"
                            :key="category"
                            :label="category"
                            :value="category"
                        >
                        </el-option>
                    </el-select>
                </el-form-item>
                <el-form-item :label="$t('Company Owner')">
                    <contact-selector v-model="model.owner_id"
                                      :field="{ size: 'small', clearable: true, pre_options: [company.owner] }"/>
                </el-form-item>
                <el-form-item :label="$t('Description')">
                    <el-input type="textarea" :rows="3" placeholder="Description" v-model="model.description"/>
                </el-form-item>

                <el-form-item :label="$t('Number of Employees')">
                    <el-input :min="0" type="number" autocomplete="new-password" placeholder="Employees"
                              v-model="model.employees_number"/>
                </el-form-item>

                <el-form-item v-if="!company.id">
                    <el-checkbox v-model="show_address_field">{{ $t('Add Address Info') }}</el-checkbox>
                </el-form-item>
                <div v-else>
                    <h4 style="font-size: 16px;">
                        {{ $t('Address') }}
                        <i @click="show_address_field = !show_address_field;" class="el-icon el-icon-edit"
                           style="cursor: pointer; color: gray; margin-left: 10px;"></i>
                    </h4>
                </div>

                <template v-if="show_address_field">
                    <el-form-item :label="$t('Address Line 1')">
                        <el-input :placeholder="$t('Address Line 1')" autocomplete="new-password"
                                  v-model="model.address_line_1"/>
                    </el-form-item>
                    <el-form-item :label="$t('Address Line 2')">
                        <el-input :placeholder="$t('Address Line 2')" autocomplete="new-password"
                                  v-model="model.address_line_2"/>
                    </el-form-item>
                    <el-row :gutter="20">
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('City')">
                                <el-input :placeholder="$t('City')" autocomplete="new-password" v-model="model.city"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('State')">
                                <el-input :placeholder="$t('State')" autocomplete="new-password" v-model="model.state"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('Postal Code')">
                                <el-input :placeholder="$t('Postal Code')" autocomplete="new-password"
                                          v-model="model.postal_code"/>
                            </el-form-item>
                        </el-col>
                        <el-col :md="12" :xs="24">
                            <el-form-item :label="$t('Country')">
                                <el-select
                                    v-model="model.country"
                                    clearable
                                    filterable
                                    autocomplete="off"
                                    :placeholder="$t('Select country')"
                                    class="el-select-multiple"
                                >
                                    <el-option
                                        v-for="item in appVars.countries"
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
                <p v-else-if="company.id">
                    {{ getFormattedAddress(company) || $t('n/a') }}
                </p>

                <el-form-item v-if="!company.id">
                    <el-checkbox v-model="show_social_urls">{{ $t('Add Social Media URLs') }}</el-checkbox>
                </el-form-item>
                <div v-else>
                    <h4 style="font-size: 16px;">
                        {{ $t('Social Links') }}
                        <i @click="show_social_urls = !show_social_urls;" class="el-icon el-icon-edit"
                           style="cursor: pointer; color: gray; margin-left: 10px;"></i>
                    </h4>
                </div>

                <template v-if="show_social_urls">
                    <el-form-item :label="$t('Linkedin Url')">
                        <el-input type="url" :placeholder="$t('LinkedIn Company Page URL')"
                                  v-model="model.linkedin_url"/>
                    </el-form-item>
                    <el-form-item :label="$t('Facebook Page Url')">
                        <el-input type="url" :placeholder="$t('Facebook Company Page URL')"
                                  v-model="model.facebook_url"/>
                    </el-form-item>
                    <el-form-item :label="$t('Twitter Url')">
                        <el-input type="url" :placeholder="$t('Twitter Handle URL')" v-model="model.twitter_url"/>
                    </el-form-item>
                </template>
                <div class="fc_social_lists" v-else-if="company.id">
                    <el-input size="mini" readonly placeholder="n/a" v-model="company.linkedin_url">
                        <template slot="prepend"><span class="dashicons dashicons-linkedin"></span></template>
                        <template v-if="company.linkedin_url" slot="append">
                            <a target="_blank" rel="noopener" :href="company.linkedin_url"><span
                                class="dashicons dashicons-external"></span></a>
                        </template>
                    </el-input>
                    <el-input size="mini" readonly placeholder="n/a" v-model="company.facebook_url">
                        <template slot="prepend"><span class="dashicons dashicons-facebook-alt"></span></template>
                        <template v-if="company.facebook_url" slot="append">
                            <a target="_blank" rel="noopener" :href="company.facebook_url"><span
                                class="dashicons dashicons-external"></span></a>
                        </template>
                    </el-input>
                    <el-input size="mini" readonly placeholder="n/a" v-model="company.twitter_url">
                        <template slot="prepend"><span class="dashicons dashicons-twitter"></span></template>
                        <template v-if="company.twitter_url" slot="append">
                            <a target="_blank" rel="noopener" :href="company.twitter_url"><span
                                class="dashicons dashicons-external"></span></a>
                        </template>
                    </el-input>
                </div>

                <CustomFieldsForm :custom_values="model.custom_values"/>

                <el-form-item class="fc_company_save_wrap">
                    <el-button v-loading="updating" :disabled="updating || !isDirty" type="success"
                               @click="updateInfo()">
                        <span v-if="company.id">{{ $t('Update info') }}</span>
                        <span v-else>{{ $t('Create Company') }}</span>
                    </el-button>
                    <router-link v-if="is_drawer && company.id"
                                 :to="{ name: 'view_company', params: { company_id: company.id } }">
                        {{ $t('Go to company record') }}
                    </router-link>
                </el-form-item>
            </el-form>
        </div>
    </div>
</template>

<script type="text/babel">
import PhotoWidget from '@/Pieces/PhotoWidget';
import ContactSelector from '@/Pieces/FormElements/_ContactSelector';
import {getDomainName, getFormattedAddress} from '@/Bits/data_config.js';
import CustomFieldsForm from './_CustomFieldsForm.vue';
import isObject from 'lodash/isObject';
import isArray from 'lodash/isArray';

export default {
    name: 'CompanyInfoSide',
    components: {
        PhotoWidget,
        ContactSelector,
        CustomFieldsForm
    },
    props: {
        company: {
            type: Object,
            default: () => {
                return null
            }
        },
        photo_holder: {
            type: String,
            default: 'fc_photo_holder_mini'
        },
        is_drawer: {
            type: Boolean,
            default: false
        },
        intended_contact_id: {
            type: Number,
            default: null
        }
    },
    data() {
        return {
            model: {},
            isDirty: false,
            appReady: false,
            updating: false,
            isHeaderEditing: false,
            show_address_field: false,
            show_social_urls: false
        }
    },
    watch: {
        model: {
            handler(newVal, oldVal) {
                if (this.appReady) {
                    this.isDirty = true;
                }
            },
            deep: true
        }
    },
    computed: {
        domainName() {
            return getDomainName(this.company.website);
        }
    },
    methods: {
        updateAvatar(url) {
            if (this.company.id) {
                this.updateProperty('logo', url);
            } else {
                this.model.logo = url;
            }
        },
        updateProperty(prop, value, callback) {
            this.$put('companies/companies-property', {
                property: prop,
                companies: [this.company.id],
                value: value
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    this.company[prop] = value;

                    if (callback) {
                        callback(response);
                    }
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        updateInfo() {
            this.updating = true;

            if (!this.company.id) {
                this.company.id = 0;
                if (this.intended_contact_id) {
                    this.model.intended_contact_id = this.intended_contact_id;
                }
            }

            this.$put(`companies/${this.company.id}`, this.model)
                .then(response => {
                    this.$notify.success(response.message);

                    this.each(this.model, (value, key) => {
                        this.company[key] = value;
                    });

                    if (response.update_data) {
                        this.each(response.update_data, (value, key) => {
                            this.company[key] = value;
                        });
                    }

                    this.isHeaderEditing = false;

                    if (this.company.id) {
                        this.$emit('companyUpdated', response.company);
                    } else {
                        this.$emit('companyCreated', response.company);
                    }
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        enableEdit() {
            this.isHeaderEditing = true;
            this.isDirty = true
            this.show_address_field = true;
            this.show_social_urls = true;
        },
        getFormattedAddress,
        reFetchLogo() {
            this.updating = true;
            this.updateProperty('refetch_logo', this.company.website, (response) => {
                if (response.updated_logo) {
                    this.company.logo = response.updated_logo;
                    this.model.logo = response.updated_logo;
                }
            });
        }
    },
    created() {
        const company = this.company;
        this.model = {
            owner_id: company.owner_id,
            name: company.name,
            logo: company.logo,
            email: company.email,
            phone: company.phone,
            website: company.website,
            industry: company.industry,
            type: company.type,
            address_line_1: company.address_line_1,
            address_line_2: company.address_line_2,
            city: company.city,
            state: company.state,
            employees_number: (company.employees_number && company.employees_number != '0') ? company.employees_number : '',
            postal_code: company.postal_code,
            country: company.country,
            description: company.description,
            linkedin_url: company.linkedin_url,
            twitter_url: company.twitter_url,
            facebook_url: company.facebook_url,
            custom_values: isObject(company.meta?.custom_values) && !isArray(company.meta?.custom_values) ? company.meta.custom_values : {}
        }

        setTimeout(() => {
            this.appReady = true;
        }, 500);
    }
}
</script>
