export default {
    get(route, data = {}) {
        return window.FLUENTCRM.request('GET', route, data);
    },
    post(route, data = {}) {
        return window.FLUENTCRM.request('POST', route, data);
    },
    delete(route, data = {}) {
        return window.FLUENTCRM.request('DELETE', route, data);
    },
    put(route, data = {}) {
        return window.FLUENTCRM.request('PUT', route, data);
    },
    patch(route, data = {}) {
        return window.FLUENTCRM.request('PATCH', route, data);
    }
};

jQuery(($) => {
    (() => {
        $.ajaxSetup({
            success: function (response, status, xhr) {
                const nonce = xhr.getResponseHeader('X-WP-Nonce');
                if (nonce) {
                    window.fcAdmin.rest.nonce = nonce;
                }
            },
            error: function (response, status, xhr) {
                if (this.url.indexOf('fluent-crm/v2/') === -1) {
                    return;
                }

                if (Number(response.status) > 423 && Number(response.status) < 410) {
                    let message = '';

                    if (response.responseJSON) {
                        message = response.responseJSON.message || response.responseJSON.error;
                    }

                    return message && window.FLUENTCRM.Vue.prototype.$notify({
                        message: message,
                        type: 'error',
                        customClass: 'fc_bottom-right'
                    });
                } else if (response.responseJSON && response.responseJSON.code == 'rest_cookie_invalid_nonce') {
                    // Send the ajax request to get the new nonce
                    jQuery.post(window.fcAdmin.ajaxurl, {
                        action: 'fluentcrm_renew_rest_nonce'
                    })
                        .then(response => {
                            window.fcAdmin.rest.nonce = response.nonce;
                            window.FLUENTCRM.Vue.prototype.$notify({
                                message: 'Nonce has been renewed. Please try again',
                                type: 'info',
                                customClass: 'fc_bottom-right'
                            });
                        })
                        .catch(() => {
                            window.FLUENTCRM.Vue.prototype.$notify({
                                message: 'FluentCRM could not renew the nonce. Please refresh the page and try again.',
                                type: 'info',
                                customClass: 'fc_bottom-right'
                            });
                        });
                }
            }
        });
    })();
});
