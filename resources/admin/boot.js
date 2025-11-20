import FLUENTCRM from '@/Bits/FLUENTCRM';
import FluentLoader from '@/Pieces/FluentLoader';

window.FLUENTCRM = new FLUENTCRM();

window.FLUENTCRM.Vue.prototype.appVars = window.fcAdmin;

window.FLUENTCRM.Vue.prototype.$rest = window.FLUENTCRM.$rest;
window.FLUENTCRM.Vue.prototype.$get = window.FLUENTCRM.$get;
window.FLUENTCRM.Vue.prototype.$post = window.FLUENTCRM.$post;
window.FLUENTCRM.Vue.prototype.$del = window.FLUENTCRM.$del;
window.FLUENTCRM.Vue.prototype.$put = window.FLUENTCRM.$put;
window.FLUENTCRM.Vue.prototype.$patch = window.FLUENTCRM.$patch;
window.FLUENTCRM.Vue.prototype.$bus = new window.FLUENTCRM.Vue();
window.FLUENTCRM.Vue.component('fluent-loader', FluentLoader);

(($notify) => {
    const success = $notify.success;
    $notify.success = (options) => {
        if (options) {
            if (typeof options === 'string') {
                options = {message: options};
            }
            return success.call($notify, {offset: 19, title: 'Great!', ...options});
        }
    };

    const error = $notify.error;
    $notify.error = (options) => {
        if (options) {
            if (typeof options === 'string') {
                options = {message: options};
            }
            return error.call($notify, {offset: 19, title: 'Oops!', ...options});
        }
    };
})(window.FLUENTCRM.Vue.prototype.$notify);

window.FLUENTCRM.request = function (method, route, data = {}) {
    const url = `${window.fcAdmin.rest.url}/${route}`;

    const headers = {'X-WP-Nonce': window.fcAdmin.rest.nonce};

    if (['PUT', 'PATCH', 'DELETE'].indexOf(method.toUpperCase()) !== -1) {
        headers['X-HTTP-Method-Override'] = method;
        method = 'POST';
    }

    data.query_timestamp = Date.now();

    return new Promise((resolve, reject) => {
        window.jQuery.ajax({
            url: url,
            type: method,
            data: data,
            headers: headers
        })
            .then(response => resolve(response))
            .fail((errors) => {
                if (!errors.responseJSON) {
                    window.FLUENTCRM.Vue.prototype.$notify({
                        message: 'Unexpected error from server. Please check browser console. <button class="el-button el-button--small el-button--danger">Click to view details.</button>',
                        type: 'error',
                        dangerouslyUseHTMLString: true,
                        customClass: 'fc_bottom-right',
                        onClick: () => {
                            window.FLUENTCRM.Vue.prototype.$bus.$emit('show-error-modal', errors);
                        },
                        duration: 10000
                    });
                    console.info('Your server firewall blocked the request or it\'s a plugin conflict. Please check the detailed error.');
                    console.log(errors);
                }
                reject(errors.responseJSON);
            });
    });
};

/*
* Disable Emoji for Email Editor
 */
delete window._wpemojiSettings;
