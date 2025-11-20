<template>
    <div class="fc_contact_side">
        <div v-for="(widget, widgetKey) in top_widgets" :key="'top_widget_' + widgetKey"
             class="fc_sidebar_card fc_sidebar_card_customer">
            <div class="fc_card_header">
                <h3>{{ widget.title }}</h3>
            </div>
            <div class="fc_sidebar_card_content" v-html="widget.content"></div>
            <div style="margin-top: 10px; text-align: right;" class="fc_info_widget_nav" v-if="widget.has_pagination">
                <el-pagination
                    v-loading="loading_widget === widgetKey"
                    small
                    @current-change="fetchWidget(widget, widgetKey)"
                    layout="total, prev, next"
                    :page-size="widget.per_page"
                    :current-page.sync="widget.current_page"
                    :total="widget.total">
                </el-pagination>
            </div>
        </div>

        <contact-companies v-if="has_company_module && subscriber" :subscriber="subscriber"/>

        <div v-for="(widget, widgetKey) in other_widgets" :key="'other_widget_' + widgetKey"
             class="fc_sidebar_card fc_sidebar_card_customer">
            <div class="fc_card_header">
                <h3>{{ widget.title }}</h3>
            </div>
            <div class="fc_sidebar_card_content" v-html="widget.content"></div>
            <div style="margin-top: 10px; text-align: right;" class="fc_info_widget_nav" v-if="widget.has_pagination">
                <el-pagination
                    v-loading="loading_widget === widgetKey"
                    small
                    @current-change="fetchWidget(widget, widgetKey)"
                    layout="total, prev, next"
                    :page-size="widget.per_page"
                    :current-page.sync="widget.current_page"
                    :total="widget.total">
                </el-pagination>
            </div>
        </div>

        <template v-if="!loading">
            <div v-if="!has_campaign_pro">
                <div class="fc_sidebar_card text-align-center">
                    <div class="fc_card_header">
                        <h3>{{ $t('Get more related contact info with Pro') }}</h3>
                    </div>
                    <div style="padding: 10px 20px 20px; background: white;" class="fc_sidebar_card_content">
                        <p>{{ $t('Fluent_CRM_Pro_Alert') }}</p>
                        <a :href="appVars.crm_pro_url" target="_blank"
                           class="button button-primary">{{ $t('Get FluentCRM Pro') }}</a>
                    </div>
                </div>
            </div>
            <div v-else-if="!hasWidgets">
                <div class="fc_sidebar_card text-align-center">
                    <div class="fc_card_header">
                        <h3>{{ $t('Additional Info') }}</h3>
                    </div>
                    <div style="padding: 10px 20px 20px; background: white;" class="fc_sidebar_card_content">
                        <p>{{ $t('No_Additional_Info_Alert') }}</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script type="text/babel">
import ContactCompanies from '@/Modules/Companies/Parts/ContactCompanies.vue';

export default {
    name: 'ContactOverViewSidebar',
    props: ['subscriber', 'subscriber_id'],
    components: {
        ContactCompanies
    },
    watch: {
        subscriber_id() {
            this.fetchWidgets();
        }
    },
    computed: {
        hasWidgets() {
            return this.has_company_module || this.widget_count > 0;
        }
    },
    data() {
        return {
            loading: false,
            top_widgets: [],
            other_widgets: [],
            widget_count: 0,
            loading_widget: ''
        }
    },
    methods: {
        fetchWidgets() {
            this.loading = true;
            this.$get(`subscribers/${this.subscriber_id}/info-widgets`)
                .then(res => {
                    this.top_widgets = res.widgets.top_widgets;
                    this.other_widgets = res.widgets.other_widgets;
                    this.widget_count = res.widgets.widgets_count;
                    this.$emit('widgetsFetched', res.widgets.widgets_count);
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        fetchWidget(widget, widgetKey) {
            this.loading_widget = widgetKey;
            this.$get(`subscribers/${this.subscriber_id}/info-widgets`, {
                by_widget: widgetKey,
                page: widget.current_page
            })
                .then(res => {
                    widget.content = res.widget.content;
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.loading_widget = false;
                });
        }
    },
    mounted() {
        this.fetchWidgets();
    }
}
</script>
