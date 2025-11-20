import routes, {profileRoute, companyProfileRoute} from '@/Bits/routes';
import Application from '@/Application';

const profRoute = window.FLUENTCRM.applyFilters('fluentcrm_profile_routes', profileRoute);
routes.push(profRoute);

const comProfRoute = window.FLUENTCRM.applyFilters('fluentcrm_company_profile_routes', companyProfileRoute);
routes.push(comProfRoute);

const vueRouter = new window.FLUENTCRM.Router({
    routes: window.FLUENTCRM.applyFilters('fluentcrm_global_routes', routes),
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        } else {
            const position = {};
            if (to.hash) {
                position.selecor = to.hash;
                if (document.querySelector(to.hash)) {
                    return position;
                }
                return false
            } else {
                return {
                    x: 0,
                    y: 0
                }
            }
        }
    }
});

vueRouter.beforeEach((to, from, next) => {
    if (!to.meta.permission) {
        next();
    } else if (window.FLUENTCRM.hasPermission(to.meta.permission)) {
        next()
    } else {
        next({name: 'no_permission', query: {permission: to.meta.permission}});
    }
});

new window.FLUENTCRM.Vue({
    el: '#fluentcrm_app',
    render: h => h(Application),
    router: vueRouter,
    watch: {
        $route(to, from) {
            if (to.meta.active_menu) {
                jQuery(document).trigger('fluentcrm_route_change', to.meta.active_menu);
            }

            let path = to.meta.side_path;

            if (path == '/') {
                path = '';
            } else {
                path = '#' + path;
            }

            document.dispatchEvent(new CustomEvent('fc_route_changed', {
                detail: { route_to: to, route_from: from, path: path }
            }));

            if (window.fcrm_last_path == path) {
                return;
            }

            window.fcrm_last_path = path;

            jQuery('li#toplevel_page_fluentcrm-admin ul.wp-submenu li').removeClass('current');

            jQuery('li#toplevel_page_fluentcrm-admin ul.wp-submenu a[href="admin.php?page=fluentcrm-admin' + path + '"]').parent().addClass('current');
        }
    },
    mounted() {
        if (this.$route.meta.active_menu) {
            jQuery(document).trigger('fluentcrm_route_change', this.$route.meta.active_menu);

            const routeTo = this.$route;
            document.dispatchEvent(new CustomEvent('fc_route_changed', {
                detail: { route_to: routeTo }
            }));
        }
    }
});
