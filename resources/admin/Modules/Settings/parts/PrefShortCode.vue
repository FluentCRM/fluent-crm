<template>
    <div>
        <el-checkbox true-label="yes" false-label="no" v-model="settings.pref_form">{{ $t('Enable Preference Form Shortcode') }}</el-checkbox>
        <div style="margin-top: 20px;padding: 20px 20px 10px;background: #ebf0f4;" v-if="settings.pref_form == 'yes'">
            <form-builder :formData="settings" :fields="formFields"/>

            <div>
                <h4 class="shortcode-header">{{ $t('Preference Form Shortcode') }}</h4>
                <div style="cursor: pointer;width: 160px;">
                        <item-copier
                            :text="'[fluentcrm_pref]'"
                            :showViewButton="false"
                        ></item-copier>
                </div>
                <span class="helper-text">
                    {{ $t('Preference_Form_Shortcode_Desc') }}
                </span>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import FormBuilder from '@/Pieces/FormElements/_FormBuilder';
import ItemCopier from '@/Pieces/ItemCopier';

export default {
    name: 'PrefShortCodeSettings',
    components: {
        ItemCopier,
        FormBuilder
    },
    props: ['settings'],
    data() {
        return {
            formFields: {
                pref_general: {
                    type: 'checkbox-group',
                    label: this.$t('Primary Fields that can be editable'),
                    help: this.$t('Select the fields that user can manage'),
                    options: [
                        {
                            id: 'prefix',
                            label: this.$t('Name Prefix')
                        },
                        {
                            id: 'first_name',
                            label: this.$t('First Name')
                        },
                        {
                            id: 'last_name',
                            label: this.$t('Last Name')
                        },
                        {
                            id: 'phone',
                            label: this.$t('Phone')
                        },
                        {
                            id: 'date_of_birth',
                            label: this.$t('Date of Birth')
                        },
                        {
                            id: 'address_fields',
                            label: this.$t('Address Fields')
                        }
                    ]
                },
                pref_custom: {
                     type: 'checkbox-group',
                     label: this.$t('Custom Fields that can be editable'),
                     help: 'Select the fields that user can manage',
                     options: window.fcAdmin.contact_custom_fields.map((item) => {
                         return {
                             id: item.slug,
                             label: item.label
                         }
                     })
                 }
            },
            isShortcodeCopied: false
        }
    },
    methods: {

    }
};
</script>

<style scoped>

.shortcode-header {
    margin: 0 0 12px;
    font-size: 15px;
    font-weight: 500;
    color: #606266;
}

.helper-text {
    display: block;
    margin-top: 8px;
    font-size: 12px;
    color: #6b7280;
}
 
</style>
