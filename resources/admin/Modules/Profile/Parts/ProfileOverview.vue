<template>
    <div class="fluentcrm_databox">
        <el-form label-position="top" ref="basic_form" :model="subscriber" label-width="120px">
            <el-row :gutter="30">
                <el-col :lg="12" :md="12" :sm="24" :xs="24">
                    <h3>{{$t('Basic Information')}}</h3>
                    <div class="fluentcrm_edit_basic">
                        <el-row :gutter="20">
                            <el-col :lg="4" :md="4" :sm="24" :xs="24">
                                <el-form-item :label="$t('Prefix')">
                                    <el-select :placeholder="$t('Select')" v-model="subscriber.prefix" size="small">
                                        <el-option v-for="prefix in name_prefixes" :key="prefix" :label="prefix" :value="prefix"></el-option>
                                    </el-select>
                                </el-form-item>
                            </el-col>
                            <el-col :lg="10" :md="10" :sm="24" :xs="24">
                                <el-form-item :label="$t('First Name')">
                                    <el-input autocomplete="new-password" v-model="subscriber.first_name"></el-input>
                                </el-form-item>
                            </el-col>
                            <el-col :lg="10" :md="10" :sm="24" :xs="24">
                                <el-form-item :label="$t('Last Name')">
                                    <el-input autocomplete="new-password" v-model="subscriber.last_name"></el-input>
                                </el-form-item>
                            </el-col>
                        </el-row>

                        <el-form-item :label="$t('Email Address')">
                            <el-input autocomplete="new-password" v-model="subscriber.email"></el-input>
                            <span class="error" v-if="errors">{{ errors.email.required }}</span>
                        </el-form-item>

                        <el-form-item :label="$t('Phone/Mobile')">
                            <el-input autocomplete="new-password" v-model="subscriber.phone"></el-input>
                        </el-form-item>

                        <el-form-item :label="$t('Date of Birth')">
                            <date-drop-down-picker v-model="subscriber.date_of_birth" />
                        </el-form-item>
                    </div>
                </el-col>
                <el-col :lg="12" :md="12" :sm="24" :xs="24">
                    <h3>{{$t('Address Information')}}</h3>
                    <div class="fluentcrm_edit_basic">
                        <el-form-item :label="$t('Address Line 1')">
                            <el-input autocomplete="new-password" v-model="subscriber.address_line_1"></el-input>
                        </el-form-item>
                        <el-form-item :label="$t('Address Line 2')">
                            <el-input autocomplete="new-password" v-model="subscriber.address_line_2"></el-input>
                        </el-form-item>
                        <el-row :gutter="20">
                            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                                <el-form-item :label="$t('City')">
                                    <el-input autocomplete="new-password" v-model="subscriber.city"></el-input>
                                </el-form-item>
                            </el-col>
                            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                                <el-form-item :label="$t('State')">
                                    <el-input autocomplete="new-password" v-model="subscriber.state"></el-input>
                                </el-form-item>
                            </el-col>
                        </el-row>
                        <el-row :gutter="20">
                            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                                <el-form-item :label="$t('ZIP Code')">
                                    <el-input autocomplete="new-password" v-model="subscriber.postal_code"></el-input>
                                </el-form-item>
                            </el-col>
                            <el-col :lg="12" :md="12" :sm="24" :xs="24">
                                <el-form-item :label="$t('Country')">
                                    <el-select
                                        v-model="subscriber.country"
                                        clearable
                                        filterable
                                        autocomplete="new-password"
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
                    </div>
                </el-col>
            </el-row>

            <custom-fields :subscriber="subscriber" :custom_fields="custom_fields" />
            <el-form-item v-if="hasPermission('fcrm_manage_contacts')" class="text-align-right">
                <el-button v-loading="updating" @click="updateSubscriber()" size="small" type="success">
                    {{$t('Update Contact')}}
                </el-button>
            </el-form-item>
        </el-form>
    </div>
</template>

<script type="text/babel">
    import CustomFields from './_CustomFields'
    import DateDropDownPicker from '@/Pieces/UniInputs/DateDropDownPicker';

    export default {
        name: 'ProfileOverview',
        props: ['subscriber', 'custom_fields'],
        components: {
            CustomFields,
            DateDropDownPicker
        },
        data() {
            return {
                pickerOptions: {
                    disabledDate(date) {
                        return date.getTime() >= (Date.now());
                    }
                },
                errors: null,
                updating: false,
                countries: window.fcAdmin.countries,
                name_prefixes: window.fcAdmin.contact_prefixes
            }
        },
        methods: {
            updateSubscriber() {
                this.errors = null;
                this.updating = true;
                this.$put(`subscribers/${this.subscriber.id}`, {
                    subscriber: JSON.stringify(this.subscriber)
                })
                    .then((response) => {
                        this.$notify.success(response.message);
                    })
                    .catch((errors) => {
                        this.errors = errors;
                        this.handleError(errors);
                        if (errors.email && errors.email.required) {
                            this.$notify.error(errors.email.required);
                        }
                    })
                    .finally(() => {
                        this.updating = false;
                    });
            }
        }
    };
</script>
