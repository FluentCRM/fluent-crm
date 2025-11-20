import Vue from '@/Bits/elements';
import Storage from '@/Bits/Storage';
import Rest from '@/Bits/Rest';
import Router from 'vue-router';
import {addAction, addFilter, applyFilters, doAction, removeAllActions} from '@wordpress/hooks';
import {$t} from './data_config';
import each from 'lodash/each';
import isEmpty from 'lodash/isEmpty';

const moment = window.moment;

const appStartTime = new Date();

export default class FLUENTCRM {
    constructor() {
        this.Router = Router;
        this.doAction = doAction;
        this.addFilter = addFilter;
        this.addAction = addAction;
        this.applyFilters = applyFilters;
        this.removeAllActions = removeAllActions;

        this.$rest = Rest;
        this.appVars = window.fcAdmin;
        this.Vue = this.extendVueConstructor();
    }

    extendVueConstructor() {
        const self = this;

        Vue.mixin({
            data() {
                let drawerSize = '50%';
                if (window.innerWidth < 800) {
                    drawerSize = '70%';
                } else if (window.innerWidth < 1000) {
                    drawerSize = '60%'
                }

                return {
                    storage: Storage,
                    permissions: self.appVars.auth.permissions,
                    has_campaign_pro: self.appVars.addons && self.appVars.addons.fluentcampaign,
                    has_company_module: self.appVars.addons && self.appVars.addons.company_module,
                    globalDrawerSize: drawerSize
                }
            },
            methods: {
                addFilter,
                applyFilters,
                doAction,
                addAction,
                removeAllActions,
                nsDateFormat: self.nsDateFormat,
                smartDate: self.smartDate,
                nsHumanDiffTime: self.humanDiffTime,
                ucFirst: self.ucFirst,
                ucWords: self.ucWords,
                slugify: self.slugify,
                handleError: self.handleError,
                percent: self.percent,
                changeTitle(title) {
                    jQuery('head title').text(title + ' - FluentCRM');
                },
                $t,
                $_n(singular, plural, count) {
                    const number = parseInt(count.toString().replace(/,/g, ''), 10);
                    if (number > 1) {
                        return this.$t(plural, count);
                    }

                    return this.$t(singular, count);
                },
                trans(string) {
                    return window.fcAdmin.trans[string] || string;
                },
                renewOptions(optionType) {
                    optionType = optionType + 's';
                    this.$get('reports/options', {fields: optionType})
                        .then(response => {
                            this.appVars['available_' + optionType] = response.options[optionType];
                        });
                },
                hasPermission: self.hasPermission,
                renewOptionCache: self.renewOptionCache,
                each: each,
                isEmptyValue: isEmpty,
                formatMoney: self.formatMoney,
                unmountBlockEditor() {
                    const item = document.getElementById('fluentcrm_block_editor_x');
                    if (item) {
                        window.wp.element.unmountComponentAtNode(item);
                    }
                },
                currentDateTime(format = 'YYYY-MM-DD h:mma') {
                    const endTime = new Date();
                    const timeDiff = endTime - appStartTime; // in ms
                    return moment(window.fcAdmin.server_time).add(timeDiff, 'milliseconds').format(format);
                },
                logConsole(data) {
                    console.log(data);
                }
            }
        });

        Vue.filter('nsDateFormat', self.nsDateFormat);
        Vue.filter('formatMoney', self.formatMoney);
        Vue.filter('nsHumanDiffTime', self.humanDiffTime);
        Vue.filter('ucFirst', self.ucFirst);
        Vue.filter('ucWords', self.ucWords);

        Vue.use(this.Router);
        return Vue;
    }

    formatMoney(amount, decimalCount = 2, decimal = '.', thousands = ',') {
        try {
            decimalCount = Math.abs(decimalCount);
            decimalCount = isNaN(decimalCount) ? 2 : decimalCount;

            if (parseInt(amount) == amount) {
                decimalCount = 0;
            }

            const negativeSign = amount < 0 ? '-' : '';

            const i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();

            const j = (i.length > 3) ? i.length % 3 : 0;

            return negativeSign + (j ? i.substr(0, j) + thousands : '') +
                i.substr(j).replace(/(\d{3})(?=\d)/g, '$1' + thousands) +
                (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : '');
        } catch (e) {
            return '';
        }
    }

    registerBlock(blockLocation, blockName, block) {
        this.addFilter(blockLocation, this.appVars.slug, function (components) {
            components[blockName] = block;
            return components;
        });
    }

    registerTopMenu(title, route) {
        if (!title || !route.name || !route.path || !route.component) {
            return;
        }

        this.addFilter('fluentcrm_top_menus', this.appVars.slug, function (menus) {
            menus = menus.filter(m => m.route !== route.name);
            menus.push({
                route: route.name,
                title: title
            });
            return menus;
        });

        this.addFilter('fluentcrm_global_routes', this.appVars.slug, function (routes) {
            routes = routes.filter(r => r.name !== route.name);
            routes.push(route);
            return routes;
        });
    }

    $get(url, options = {}) {
        return window.FLUENTCRM.$rest.get(url, options);
    }

    $post(url, options = {}) {
        return window.FLUENTCRM.$rest.post(url, options);
    }

    $del(url, options = {}) {
        return window.FLUENTCRM.$rest.delete(url, options);
    }

    $put(url, options = {}) {
        return window.FLUENTCRM.$rest.put(url, options);
    }

    $patch(url, options = {}) {
        return window.FLUENTCRM.$rest.patch(url, options);
    }

    renewOptionCache(option, callback) {
        const query = {
            fields: option
        };
        this.$get('reports/options', query)
            .then(response => {
                if (!window.fc_options_cache) {
                    window.fc_options_cache = {};
                }
                if (response.options[option]) {
                    window.fc_options_cache[option] = response.options[option];
                    if (callback) {
                        callback(response.options[option]);
                    }
                }
            });
    }

    nsDateFormat(date, format = null) {
        const dateString = (date === undefined) ? null : date;

        if (!dateString) {
            return '';
        }

        if (format === null) {
            if (moment(date).isSame(new Date(), 'year')) {
                format = 'D MMM';
            } else {
                format = 'D MMM, YYYY';
            }
        }

        const dateObj = moment(dateString);

        return dateObj.isValid() ? dateObj.format(format) : null;
    }

    smartDate(dateString, withTime = false) {
        if (!dateString) {
            return '';
        }

        let format = 'D MMM, YYYY';

        if (moment(dateString).isSame(new Date(), 'year')) {
            format = 'D MMM';
            if (withTime) {
                const diffDays = Math.abs(moment(dateString).diff(new Date(), 'days'));
                if (diffDays <= 5) {
                    if (moment(dateString).minute() == 0) {
                        format = 'D MMM, ha';
                    } else {
                        format = 'D MMM, hh:mma';
                    }
                }
            }
        }

        const dateObj = moment(dateString);

        return dateObj.isValid() ? dateObj.format(format) : null;
    }

    humanDiffTime(date) {
        const dateString = (date === undefined) ? null : date;
        if (!dateString) {
            return '';
        }

        if (window.fcAdmin.disable_time_diff) {
            const dateMoment = moment(dateString);
            return dateMoment.format(window.fcAdmin.wp_date_time_format);
        }

        const endTime = new Date();
        const timeDiff = endTime - appStartTime; // in ms
        const dateObj = moment(dateString);
        return dateObj.from(moment(window.fcAdmin.server_time).add(timeDiff, 'milliseconds'));
    }

    ucFirst(text) {
        if (!text) {
            return text;
        }
        return text[0].toUpperCase() + text.slice(1).toLowerCase();
    }

    ucWords(text) {
        return (text + '').replace(/^(.)|\s+(.)/g, function ($1) {
            return $1.toUpperCase();
        })
    }

    slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-') // Replace spaces with -
            .replace(/[^\w\\-]+/g, '') // Remove all non-word chars
            .replace(/\\-\\-+/g, '-') // Replace multiple - with single -
            .replace(/^-+/, '') // Trim - from start of text
            .replace(/-+$/, ''); // Trim - from end of text
    }

    handleError(response) {
        if (!response) {
            return;
        }
        let errorMessage = '';
        if (typeof response === 'string') {
            errorMessage = response;
        } else if (response && response.message) {
            errorMessage = response.message;
        } else {
            errorMessage = window.FLUENTCRM.convertToText(response);
        }

        if (!errorMessage) {
            errorMessage = 'Something is wrong!';
        }
        this.$notify({
            type: 'error',
            title: 'Error',
            message: errorMessage,
            dangerouslyUseHTMLString: true
        });
    }

    convertToText(obj) {
        const string = [];
        if (typeof (obj) === 'object' && (obj.join === undefined)) {
            for (const prop in obj) {
                string.push(this.convertToText(obj[prop]));
            }
        } else if (typeof (obj) === 'object' && !(obj.join === undefined)) {
            for (const prop in obj) {
                string.push(this.convertToText(obj[prop]));
            }
        } else if (typeof (obj) === 'function') {

        } else if (typeof (obj) === 'string') {
            string.push(obj)
        }

        return string.join('<br />')
    }

    percent(count, total) {
        if (!total || !count) {
            return '--';
        }
        const percent = (count / total) * 100;

        if (Number.isInteger(percent)) {
            return percent + '%';
        }
        return percent.toFixed(2) + '%';
    }

    hasPermission(permission) {
        return window.fcAdmin.auth.permissions.indexOf(permission) !== -1;
    }
}
