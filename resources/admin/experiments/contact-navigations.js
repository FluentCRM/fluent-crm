import FluentContactNav from './parts/ContactNavigation';

let $contactVmApp = false;
window.FLUENTCRM.addAction('fluent_crm_subscriber_loaded', 'fluentcrm_test', function (component) {
    if ($contactVmApp) {
        if (component && component.subscriber) {
            $contactVmApp.subscriber = component.subscriber;
            $contactVmApp.initApp();
        }
        return false;
    }

    $contactVmApp = new window.FLUENTCRM.Vue({
        el: '#fluent_contact_nav',
        data: {
            subscriber: component.subscriber,
            appReady: false,
            cached_items: {
                next: false,
                prev: false
            },
            fetching: false,
            has_next: false,
            has_prev: false,
            require_reload: true
        },
        components: {
            'fluent-contact-nav': FluentContactNav
        },
        methods: {
            goPrev() {
                const prevId = this.getPreviousId(this.subscriber.id);
                const subscriberId = JSON.stringify(this.subscriber.id);
                if (prevId) {
                    this.cached_items.next.unshift(subscriberId);
                    component.$root.$router.push({name: component.$root.$route.name, params: {id: prevId}});
                } else if (this.has_prev) {
                    this.fetchItems((response) => {
                        if (this.cached_items.prev && this.cached_items.prev.length) {
                            this.goPrev();
                        }
                    });
                } else {
                    this.$notify.info('No previous item found');
                }
            },
            goNext() {
                const nextId = this.getNextId(this.subscriber.id);
                const subscriberId = JSON.stringify(this.subscriber.id);
                if (nextId) {
                    this.cached_items.prev.unshift(subscriberId);
                    component.$root.$router.push({ name: component.$root.$route.name, params: {id: nextId} });
                } else if (this.has_next) {
                    this.fetchItems((response) => {
                        if (this.cached_items.next && this.cached_items.next.length) {
                            this.goNext();
                        }
                    });
                } else {
                    this.$notify.info('No next item found');
                }
            },
            initApp() {
                if (this.require_reload) {
                    this.fetchItems(() => {
                        this.appReady = true;
                    });
                } else {
                    this.appReady = true;
                }
                this.require_reload = false;
            },
            fetchItems(callback) {
                if (!window.fcrm_sub_params || !window.fcrm_sub_params.filter_type) {
                    return;
                }
                this.fetching = true;
                this.$get('subscribers/prev-next-ids', {...window.fcrm_sub_params, ...{current_id: this.subscriber.id}})
                    .then(response => {
                        this.cached_items = response.navigation
                        this.has_next = response.has_next
                        this.has_prev = response.has_prev
                        if (callback) {
                            callback(response);
                        }
                    })
                    .catch(errors => {
                        console.log(errors);
                    })
                    .finally(() => {
                        this.fetching = false;
                        this.appReady = true;
                    });
            },
            getNextId(currentId) {
                if (this.cached_items.next && this.cached_items.next.length) {
                    return this.cached_items.next.shift();
                }
                return false;
            },
            getPreviousId(currentId) {
                if (this.cached_items.prev && this.cached_items.prev.length) {
                    return this.cached_items.prev.shift();
                }
                return false;
            }
        },
        mounted() {
            this.initApp();
        }
    });
});

window.FLUENTCRM.addAction('fluent_crm_leaving_profile', 'fluentcrm_test', function (item) {
    if ($contactVmApp) {
        $contactVmApp.require_reload = true;
        $contactVmApp.appReady = false;
    }
});
