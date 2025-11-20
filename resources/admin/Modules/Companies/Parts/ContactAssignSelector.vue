<template>
    <div>
        <el-input style="margin-bottom: 20px;" v-model="contact_search" placeholder="Search Contact">
            <el-button slot="append" icon="el-icon-search" @click="fetchContacts"></el-button>
        </el-input>

        <div v-if="loading">
            <el-skeleton :animated="true" :rows="3"/>
        </div>
        <div v-else-if="searched && !contacts.length">
            <p>{{ $t('No contacts found based on your search') }}</p>
        </div>
        <div v-else class="fc_companies">
            <el-checkbox-group class="fc_rich_checkboxes" v-model="selectedIds">
                <el-checkbox v-for="contact in contacts" :key="contact.id" :label="contact.id">
                    <div class="fc_company_card">
                        <div class="fc_company_body">
                            <div class="company_name">
                                {{ contact.full_name }}
                            </div>
                            <div class="company_domain">
                                {{ contact.email }}
                            </div>
                            <div v-if="company.phone" class="company_email">
                                <span>{{ company.phone }}</span>
                            </div>
                        </div>
                        <div class="fc_company_logo fc_company_photo">
                            <img :src="contact.photo" alt="">
                        </div>
                    </div>
                </el-checkbox>
            </el-checkbox-group>

            <div v-if="selectedIds.length">
                <div style="padding: 10px 10px 20px;">
                    <el-button :disabled="attaching" v-loading="attaching" @click="attachContacts()" size="small"
                               type="success">
                        {{ $t('Attach Selected Contacts') }} ({{ selectedIds.length }})
                    </el-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ContactAssignSelector',
    props: ['company'],
    components: {},
    data() {
        return {
            contact_search: '',
            contacts: [],
            searched: false,
            loading: false,
            selectedIds: [],
            attaching: false
        }
    },
    methods: {
        fetchContacts() {
            this.loading = true;
            this.selectedIds = [];
            this.$get('companies/search-unattached-contacts', {
                limit: 20,
                search: this.contact_search,
                company_id: this.company.id
            })
                .then(response => {
                    this.contacts = response.results;
                    this.searched = true;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        attachContacts() {
            this.attaching = true;
            this.$post('companies/attach-subscribers', {
                subscriber_ids: this.selectedIds,
                company_ids: [this.company.id]
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.$emit('attached');
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.attaching = false;
                });
        }
    }
}
</script>
