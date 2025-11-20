<template>
    <div class="fluentcrm_admin_dashboard"
         :style="loading ? 'background:white;position: relative' : 'position: relative'">
        <div v-if="loading" class="fc_loading_bar">
            <el-progress class="el-progress_animated" :show-text="false" :percentage="30"/>
        </div>
        <el-skeleton style="padding: 20px;" v-if="loading" :rows="8"></el-skeleton>

        <el-row v-if="!loading" :gutter="30">
            <div style="margin-bottom: 20px;" class="dashboard_notices" v-if="dashboard_notices && dashboard_notices.length">
                <div class="fc_dashboard_notice" v-for="(notice, notice_key) in dashboard_notices" :key="notice_key"
                     v-html="notice"></div>
            </div>
            <el-col :sm="24" :md="16" :lg="18">
                <div class="fc_card_widgets">
                    <div v-for="(stat,stat_name) in stats" :key="stat_name" class="fc_card_widget"
                         @click="goToRoute(stat.route)">
                        <div class="fluentcrm_body" v-html="formatMoney(stat.count)"></div>
                        <div class="stat_title" v-html="stat.title"></div>
                    </div>
                </div>
                <div class="ns_subscribers_chart">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            <el-dropdown @command="handleComponentChange" style="margin-top:10px;">
                                <span class="el-dropdown-link">
                                    {{ chartMaps[currently_showing] }}
                                    <i class="el-icon-arrow-down el-icon--right"></i>
                                </span>
                                <el-dropdown-menu slot="dropdown" class="fc_dropdown">
                                    <el-dropdown-item
                                        v-for="(mapName, mapKey) in chartMaps"
                                        :key="mapKey"
                                        :command="mapKey"
                                    >
                                        {{ mapName }}
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </el-dropdown>
                        </div>
                        <div class="fluentcrm-templates-action-buttons fluentcrm-actions">
                            <el-date-picker
                                v-model="date_range"
                                type="daterange"
                                :range-separator="$t('To')"
                                :start-placeholder="$t('Start date')"
                                :end-placeholder="$t('End date')"
                                value-format="yyyy-MM-dd"
                            >
                            </el-date-picker>
                            <el-button @click="filterReport" type="primary">
                                {{ $t('Apply') }}
                            </el-button>
                        </div>
                    </div>
                    <component v-if="showing_charts" :is="currently_showing" :date_range="date_range"></component>
                </div>
            </el-col>
            <el-col :sm="24" :md="8" :lg="6">
                <div v-if="onboarding" class="fc_m_20 fc_onboarding">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            {{ $t('Getting Started') }} ({{ onboarding.completed }} of {{ onboarding.total }} complete)
                        </div>
                    </div>
                    <div class="fluentcrm_body">
                        <ul class="fc_lined_items">
                            <li
                                v-for="(step, index) in onboarding.steps" :key="index"
                                :class="{fc_item_completed: step.completed}"
                                @click="maybeRouteStep(step)"
                            >
                                <i v-if="step.completed" class="el-icon el-icon-success"></i>
                                <i v-else class="fc_el_circle"></i>
                                {{ step.label }}
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-if="!appVars.disable_fluentmail_suggest" class="fc_m_20 fc_quick_links">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            {{ $t('FluentSMTP') }}
                        </div>
                    </div>
                    <div style="padding: 0px 20px 20px" class="fluentcrm_body">
                        <p>
                            {{ $t('fluentsmtp.desc') }}
                        </p>
                        <router-link class="el-button el-button--info el-button--small" :to="{name: 'smtp_settings'}">
                            {{ $t('Das_View_ESSS') }}
                        </router-link>
                    </div>
                </div>
                <div v-if="sales && sales.length" class="fc_m_20 fc_quick_links">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            {{ $t('Sales') }}
                        </div>
                    </div>
                    <div class="fluentcrm_body">
                        <ul class="fc_lined_items">
                            <li v-for="sale in sales" :key="sale.title">
                                <span class="fc_li_title" v-html="sale.title"></span> <span class="fc_li_value"
                                                                                            v-html="sale.content"></span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-if="recommendation" class="fc_m_20 fc_quick_links">
                    <div style="background: #7757e7;color: white;" class="fluentcrm_header">
                        <div style="width: 100%;" class="fluentcrm_header_title text-align-center">
                            {{recommendation.title}}
                        </div>
                    </div>
                    <div class="fluentcrm_body" style="padding: 10px 20px">
                        <p v-html="recommendation.description" style="font-size: 110%;"></p>
                        <p>
                            <a class="el-button el-button--danger el-button--large" href="https://fluentcrm.com/?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp" target="_blank" rel="noopener">{{recommendation.btn_text}}</a>
                        </p>
                        <p v-if="recommendation.learn_more"><a target="_blank" :href="recommendation.learn_more">Learn more</a> and {{recommendation.base_title}}</p>
                    </div>
                </div>
                <div class="fc_m_20 fc_quick_links">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            {{ $t('Quick Links') }}
                        </div>
                    </div>
                    <div class="fluentcrm_body">
                        <ul class="fc_quick_links">
                            <li v-for="(link,linkIndex) in quick_links" :key="linkIndex">
                                <a :target="(link.is_external) ? '_blank' : '_self'" :href="link.url">
                                    <i :class="link.icon"></i>
                                    {{ link.title }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-if="!ff_config.is_installed" class="fc_m_20 fc_quick_links">
                    <div class="fluentcrm_header">
                        <div class="fluentcrm_header_title">
                            {{ $t('Grow Your Audience') }}
                        </div>
                    </div>
                    <div element-loading-text="Installing Fluent Forms..." v-loading="installing_ff"
                         style="padding: 0px 20px 20px" class="fluentcrm_body">
                        <p>{{ $t('quick_links.ff_desc') }}</p>
                        <div class="text-align-center">
                            <el-button @click="installFF()" type="primary" size="small">{{ $t('Das_Activate_FFI') }}
                            </el-button>
                        </div>
                    </div>
                </div>
                <template v-else-if="!has_campaign_pro">
                    <div class="fc_m_20 fc_quick_links">
                        <div class="fluentcrm_header">
                            <div class="fluentcrm_header_title">
                                Hi {{ appVars.auth.first_name }} {{ appVars.auth.last_name }},
                            </div>
                        </div>
                        <div class="fluentcrm_body" style="padding: 10px 20px">
                            <p style="font-size: 110%;">Do more with <b>FluentCRM Pro</b> by using more integrations, advanced automations, sequence emails and in-detailed analytics.</p>
                            <p>
                                <a class="el-button el-button--primary el-button--large" href="https://fluentcrm.com/?utm_source=dashboard&utm_medium=plugin&utm_campaign=pro&utm_id=wp" target="_blank" rel="noopener">Upgrade to Pro Now</a>
                            </p>
                        </div>
                    </div>
                </template>
                <div v-if="system_tips" class="fc_m_20 fc_system_tips">
                    <div style="    background: rgb(255 239 98);color: black;" class="fluentcrm_header">
                        <div style="width: 100%;" class="fluentcrm_header_title text-align-center">
                            {{system_tips.title}}
                        </div>
                    </div>
                    <div class="fluentcrm_body" style="padding: 10px 20px 20px;">
                        <div v-html="system_tips.body"></div>
                    </div>
                </div>

                <div class="fc_m_20 fc_request_review_widget fc_quick_links" v-if="showReviewWidget">
                    <div class="fluentcrm_body">
                        <div class="fc_request_review_header">
                            <h4>{{ $t('Love this Plugin ?') }}</h4>
                            <i class="el-icon-circle-close" @click="closeReviewWidget"></i>
                        </div>
                        <p>{{ $t('Request_review_Desc') }}</p>
                        <a href="https://wordpress.org/support/plugin/fluent-crm/reviews/#new-post" target="_blank"
                           class="el-button el-button--primary el-button--small fc_primary_button">{{ $t('Write a Review') }}</a>
                    </div>
                </div>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
import SubscribersChart from './Charts/Subscribers';
import EmailSentChart from './Charts/EmailChart';
import EmailOpenChart from './Charts/EmailOpen';
import EmailClickChart from './Charts/EmailClick';

export default {
    name: 'Dashboard',
    components: {
        SubscribersChart,
        EmailSentChart,
        EmailOpenChart,
        EmailClickChart
    },
    data() {
        return {
            loading: true,
            stats: [],
            quick_links: [],
            date_range: '',
            currently_showing: 'subscribers-chart',
            chartMaps: {
                'subscribers-chart': this.$t('Subscribers Growth'),
                'email-sent-chart': this.$t('Email Sending Stats'),
                'email-open-chart': this.$t('Email Open Stats'),
                'email-click-chart': this.$t('Email Link Click Stats')
            },
            showing_charts: true,
            timerId: null,
            ff_config: {
                is_installed: true,
                create_form_link: ''
            },
            installing_ff: false,
            sales: [],
            onboarding: null,
            data_ready: false,
            dashboard_notices: [],
            recommendation: false,
            system_tips: null,
            showReviewWidget: true
        }
    },
    methods: {
        fetchDashBoardData() {
            this.loading = true;
            setTimeout(() => {
                this.$get('reports/dashboard-stats')
                    .then(response => {
                        this.stats = response.stats;
                        this.sales = response.sales;
                        this.quick_links = response.quick_links;
                        this.ff_config = response.ff_config;
                        this.onboarding = response.onboarding;
                        this.dashboard_notices = response.dashboard_notices;
                        this.recommendation = response.recommendation;
                        this.system_tips = response.system_tips;
                    })
                    .finally(() => {
                        this.loading = false;
                        this.data_ready = true;
                    });
            }, 500);
        },
        goToRoute(route) {
            if (route) {
                this.$router.push(route);
            }
        },
        maybeRouteStep(step) {
            if (!step.completed) {
                this.goToRoute(step.route);
            }
        },
        handleComponentChange(item) {
            this.currently_showing = item;
        },
        filterReport() {
            const current = this.currently_showing;
            this.currently_showing = {
                render: () => {
                }
            };
            this.$nextTick(() => {
                this.currently_showing = current;
            });
        },
        refreshData() {
            this.fetchDashBoardData();
            this.showing_charts = false;
            this.$nextTick(() => {
                this.showing_charts = true;
            });
        },
        installFF() {
            this.installing_ff = true;
            this.$post('setting/install-fluentform')
                .then(response => {
                    this.ff_config = response.ff_config;
                    this.$notify.success(response.message);
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.installing_ff = false;
                });
        },
        closeReviewWidget() {
            this.showReviewWidget = false;
            let expires = '';
            const days = 7;
            const date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + date.toUTCString();
            document.cookie = 'showReviewWidget=false' + expires;
        },
        checkIfReviewWidgetEnabled() {
            const cookieKey = 'showReviewWidget=';
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.indexOf(cookieKey) !== -1) {
                    this.showReviewWidget = false;
                }
            }
        }
    },
    mounted() {
        this.fetchDashBoardData();
        this.checkIfReviewWidgetEnabled();
        this.timerId = setInterval(() => {
            this.refreshData();
        }, 300 * 1000); // 60 * 1000 milsec
        this.changeTitle(this.$t('Dashboard'));
    },
    beforeDestroy() {
        if (this.timerId) {
            clearInterval(this.timerId); // The setInterval it cleared and doesn't run anymore.
        }
    }
};
</script>

<style>
.fluentcrm-templates-action-buttons .el-date-editor .el-range-separator {
    width: 7% !important;
}
</style>
