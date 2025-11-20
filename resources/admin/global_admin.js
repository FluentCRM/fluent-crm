jQuery(document).ready(function ($) {
    $('.fluentcrm_submenu_items a').on('click', function () {
        if (window.innerWidth > 768) {
            $(this).closest('.fluentcrm_submenu_items').addClass('fluentcrm_force_hide');
        }
    });

    $('.fluentcrm_menu_item a').on('click', function (e) {
        $('.fluentcrm_menu_item').removeClass('fluentcrm_active');
        $(this).closest('.fluentcrm_menu_item').addClass('fluentcrm_active');
        if (e.target.nodeName != 'SPAN') {
            $('.fluentcrm_menu').removeClass('fluentcrm_menu_open');
        }
    });

    var $submenuItems = jQuery('.fluentcrm_has_sub_items');
    $submenuItems.on('mouseenter', function () {
        $(this).find('.fluentcrm_submenu_items').removeClass('fluentcrm_force_hide');
    });

    $('.fluentcrm_handheld').on('click', function () {
        $('.fluentcrm_menu').toggleClass('fluentcrm_menu_open');
    });

    jQuery('body').on('click', '.components-color-palette__custom-color', function(e) {
        e.preventDefault();
    });

    function setCurrentDateTime() {
        const serverTimeStamp = parseInt($('#fc_server_timestamp').data('timestamp'));

        const date = new Date(serverTimeStamp * 1000); // Convert to milliseconds
        const formattedDate = date.getUTCFullYear() + '-' +
            ('0' + (date.getUTCMonth() + 1)).slice(-2) + '-' +
            ('0' + date.getUTCDate()).slice(-2) + ' ' +
            ('0' + (date.getUTCHours() % 12 || 12)).slice(-2) + ':' +
            ('0' + date.getUTCMinutes()).slice(-2) +
            (date.getUTCHours() < 12 ? ' am' : ' pm');

        $('#fc_server_timestamp').text('Server Time: ' + formattedDate);
        $('#fc_server_timestamp').data('timestamp', serverTimeStamp + 60);
    }

    setCurrentDateTime();

    setInterval(setCurrentDateTime, 60000);
});

jQuery(document).on('fluentcrm_route_change', function (e, item) {
    jQuery('.fluentcrm_menu_item').removeClass('fluentcrm_active');
    jQuery('.fluentcrm_item_' + item).addClass('fluentcrm_active');
});
