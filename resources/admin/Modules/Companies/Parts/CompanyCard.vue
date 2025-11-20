<template>
    <div v-loading="working" class="fc_company_card fc_shadow_hover">
        <div class="fc_company_actions" v-if="subscriber">
            <el-dropdown trigger="click">
                <el-button size="mini" type="info" class="el-dropdown-link">
                    {{ $t('actions') }} <span class="el-icon-arrow-down"></span>
                </el-button>
                <el-dropdown-menu slot="dropdown">
                    <el-dropdown-item class="fc_dropdown_action" v-if="subscriber.company_id != company.id">
                        <span class="el-popover__reference" @click="markAsPrimary()">
                            {{ $t('Mark as Primary') }}
                        </span>
                    </el-dropdown-item>
                    <el-dropdown-item class="fc_dropdown_action" @click="removeAssociation()">
                        <span class="el-popover__reference" @click="removeAssociation()">
                            {{ $t('Remove Association') }}
                        </span>
                    </el-dropdown-item>
                </el-dropdown-menu>
            </el-dropdown>
        </div>
        <div class="fc_company_body">
            <div v-if="subscriber && subscriber.company_id == company.id" class="fc_primary_badge">{{ $t('Primary') }}</div>
            <a href="#" @click.prevent="$emit('showDetails', company)" class="company_name">{{ company.name }}</a>
            <div v-if="domainName" class="company_domain">
                <a target="_blank" rel="noopener" :href="company.website">{{ domainName }} <span
                    class="fc_dash_extrernal dashicons dashicons-external"></span></a>
            </div>
            <div v-if="company.email || company.phone" class="company_email">
                <span v-if="company.email">{{ company.email }} <span v-show="company.phone"
                                                                     class="fc_middot">·</span></span>
                <span>{{ company.phone }}</span>
            </div>
        </div>
        <router-link :to="{ name: 'view_company', params: { company_id: company.id } }" v-if="company.logo"
                     class="fc_company_logo fc_company_photo">
            <img :src="company.logo" alt="">
        </router-link>
    </div>
</template>

<script type="text/babel">
import {getDomainName} from '@/Bits/data_config.js';

export default {
    name: 'CompanyCard',
    props: ['company', 'subscriber'],
    computed: {
        domainName() {
            return getDomainName(this.company.website);
        }
    },
    data() {
        return {
            working: false
        }
    },
    methods: {
        markAsPrimary() {
            this.working = true;
            this.$put('subscribers/subscribers-property', {
                property: 'company_id',
                value: this.company.id,
                subscribers: [this.subscriber.id]
            })
                .then(response => {
                    this.$notify.success(this.$t('Company marked as primary'));
                    this.subscriber.company_id = this.company.id;
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.working = false;
                });
        },
        removeAssociation() {
            this.working = true;
            this.$post('companies/detach-subscribers', {
                subscriber_ids: [this.subscriber.id],
                company_ids: [this.company.id]
            })
                .then(response => {
                    this.$notify.success(this.$t('Company association removed'));
                    this.$emit('removed');
                    if (response.last_primary_company_id) {
                        this.subscriber.company_id = response.last_primary_company_id;
                    }
                })
                .catch(errors => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.working = false;
                });
        }
    }
}
</script>
