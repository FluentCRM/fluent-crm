/* eslint-disable */
import '../scss/globalSearch.scss';

const elem = function (tagName, attributes, children, isHTML = null) {
    let parent;

    if (typeof tagName == "string") {
        parent = document.createElement(tagName);
    } else if (tagName instanceof HTMLElement) {
        parent = tagName;
    }

    if (attributes) {
        for (let attribute in attributes) {
            parent.setAttribute(attribute, attributes[attribute]);
        }
    }

    if (children || children == 0) {
        elem.append(parent, children, isHTML);
    }

    return parent;
};

elem.append = function (parent, children, isHTML) {
    if (parent instanceof HTMLTextAreaElement || parent instanceof HTMLInputElement) {

        if (children instanceof Text || typeof children == "string" || typeof children == "number") {
            parent.value = children;
        } else if (children instanceof Array) {
            children.forEach(function (child) {
                elem.append(parent, child);
            });
        } else if (typeof children == "function") {
            elem.append(parent, children());
        }

    } else {

        if (children instanceof HTMLElement || children instanceof Text) {
            parent.appendChild(children);
        } else if (typeof children == "string" || typeof children == "number") {
            if (isHTML) {
                parent.innerHTML += children;
            } else {
                parent.appendChild(document.createTextNode(children));
            }
        } else if (children instanceof Array) {
            children.forEach(function (child) {
                elem.append(parent, child);
            });
        } else if (typeof children == "function") {
            elem.append(parent, children());
        }

    }
};

function trans(string) {
   return window.fc_bar_vars.trans[string] || string;
}

const FcGlobalSearchApp = {
    init() {
        this.initButton();
        if (window.fc_bar_vars.edit_user_vars && window.fc_bar_vars.edit_user_vars.crm_profile_url) {
            this.maybeUserProfile(window.fc_bar_vars.edit_user_vars);
        }
    },
    current_page: 1,
    initButton() {
        const that = this;
        const parentElement = document.getElementById('wp-admin-bar-fc_global_search');
        const $parent = jQuery('#wp-admin-bar-fc_global_search');
        // const $button = jQuery('#wp-admin-bar-fc_global_search a');

        const mainDom = this.getSearchDom();

        mainDom.append(elem('div', {class: 'fc_load_more'}, [
            elem('button', {id: 'fc_load_more_result'}, trans('Load More'))
        ]));

        mainDom.append(this.getQuickLinks());

        parentElement.append(mainDom);

        $parent.on('mouseenter', function () {
            const $container = $parent.find('.fc_search_container');
            $container.addClass('fc_show');
            if ($container.hasClass('fc_show')) {
                $container.find('input').focus();
            }
        }).on('mouseleave', function () {
            const $container = $parent.find('.fc_search_container');
            $container.removeClass('fc_show');
        });


        jQuery('#fc_search_input').on('keypress', function (e) {
            that.current_page = 1;
            if (e.which != 13) {
                return;
            }

            const search = jQuery.trim(jQuery(this).val());

            if (jQuery('#fc_search_input').attr('data-searched') !== search) {
                e.preventDefault();
                that.current_page = 1;
                that.performSearch(search);
            }

        }).on('keyup', function (e) {
            const search = jQuery.trim(jQuery(this).val());

            if (!search) {
                jQuery('#fc_search_input').attr('data-searched', '');
                jQuery('#fc_search_result_wrapper')
                    .html('<p class="fc_no_result">'+trans('Type and press enter')+'</p>')
                    .removeClass('fc_has_results').removeClass('fc_has_more');
            }
        });

        jQuery('#fc_load_more_result').on('click', function (e) {
            e.preventDefault();
            that.current_page++;
            that.performSearch(jQuery('#fc_search_input').val());
        });

    },
    getSearchDom() {
        return elem('div', {class: 'fc_search_container'}, [
            elem('div', {class: 'fc_search_box'}, [
                elem('input', {
                    type: 'search',
                    placeholder: trans('Search Contacts'),
                    autocomplete: 'off',
                    id: 'fc_search_input',
                    autocorrect: 'off',
                    autocapitalize: 'none',
                    spellcheck: 'false'
                })
            ]),
            elem('div', {id: 'fc_search_result_wrapper'}, trans('Type to search contacts'))
        ]);
    },
    getQuickLinks() {
        const linkDoms = [];
        jQuery.each(window.fc_bar_vars.links, (index, link) => {
            linkDoms.push(elem('li', {}, [
                elem('a', {href: link.url}, link.title)
            ]));
        });

        return elem('div', {class: 'fc_quick_links_wrapper'}, [
            elem('h4', {}, trans('Quick Links')),
            elem('ul', {class: 'fc_quick_links'}, linkDoms)
        ]);
    },
    performSearch(search) {
        if (!search) {
            return '';
        }

        jQuery('#fc_search_result_wrapper').addClass('fc_loading');

        this.$get('subscribers', {
            per_page: 10,
            page: this.current_page,
            search: search,
            sort_by: 'id',
            sort_type: 'DESC'
        })
            .then(response => {
                this.pushSearchResult(
                    response.subscribers.data,
                    parseInt(response.subscribers.current_page) < response.subscribers.last_page
                );

                jQuery('#fc_search_input').attr('data-searched', search);
            })
            .catch((error) => {
                // ..
            })
            .finally(() => {
                jQuery('#fc_search_result_wrapper').removeClass('fc_loading');
            });

    },

    pushSearchResult(results, hasMore = false) {
        const $wrapper = jQuery('#fc_search_result_wrapper');
        if (!results.length) {
            $wrapper
                .html('<p class="fc_no_result">'+ trans('Sorry no contact found') +'</p>')
                .removeClass('fc_has_results').removeClass('fc_has_more');
            return;
        }

        const resultsDom = [];
        jQuery.each(results, (index, result) => {
            resultsDom.push(elem('li', {}, [
                elem('a', {href: window.fc_bar_vars.subscriber_base + result.id + '?t=' + result.hash}, result.full_name + ' - ' + result.email)
            ]))
        });
        const fullResult = elem('ul', {class: 'fc_result_lists'}, resultsDom);

        $wrapper.html(fullResult).addClass('fc_has_results');

        if (hasMore) {
            jQuery('.fc_load_more').addClass('fc_has_more');
        } else {
            jQuery('.fc_load_more').removeClass('fc_has_more');
        }
    },

    $get(route, data = {}) {
        const url = `${window.fc_bar_vars.rest.url}/${route}`;

        return new Promise((resolve, reject) => {
            window.jQuery.ajax({
                url: url,
                type: 'GET',
                data: data,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', window.fc_bar_vars.rest.nonce);
                }
            })
                .then(response => resolve(response))
                .catch(errors => reject(errors.responseJSON));
        });
    },

    maybeUserProfile(profileVars) {
        window.jQuery('<a style="background: #7757e6;color: white;border-color: #7757e6;" class="page-title-action" href="' + profileVars.crm_profile_url + '">View CRM Profile</a>').insertBefore('#profile-page > .wp-header-end');
    }
};

FcGlobalSearchApp.init();
