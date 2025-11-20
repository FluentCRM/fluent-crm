jQuery(document).ready(function ($) {
    var $unSubForm = $('.fluentcrm_public_pref_form');

    if ($unSubForm.length) {
        $unSubForm.find('input[name=reason]').on('change', function () {
            var val = $unSubForm.find('input[name=reason]:checked').val();
            if (val === 'other') {
                $unSubForm.find('#fluentcrm_other_reason_wrapper').show();
            } else {
                $unSubForm.find('#fluentcrm_other_reason_wrapper').hide();
            }
        });

        $unSubForm.on('submit', function (e) {
            e.preventDefault();
            var data = $(this).serialize();
            $('.fluentcrm_form_responses').html('');
            $.post(window.fluentcrm_public_pref.ajaxurl, data)
                .then(response => {
                    $('.fluentcrm_un_form_wrapper').html(
                        '<div class="fluentcrm_success">' + response.data.message + '</div>'
                    );

                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    }
                })
                .fail((error) => {
                    let message = 'Sorry! Something is wrong. Please try again';
                    if (error.responseJSON && error.responseJSON.data && error.responseJSON.data.message) {
                        message = error.responseJSON.data.message;
                    }
                    $('.fluentcrm_form_responses').html(
                        '<div class="fluentcrm_error">' + message + '</div>'
                    );
                })
                .always(() => {
                    // ...
                });
        });

        if (window.fluentcrm_public_pref.auto_unsubscribe == 'yes') {
            setTimeout(function () {
                $('#fluentcrm_unsubscribe_submit').click();
            }, 500);
        }
    }

    var $manageForm = $('#fc_pref_form');

    if ($manageForm.length) {
        $manageForm.on('submit', function (e) {
            e.preventDefault();
            var data = $(this).serialize();
            var $btn = $('#fluentcrm_preferences_submit');

            $('.fluentcrm_form_responses').html('');

            $btn.attr('disabled', true).addClass('fc_btn_loading');

            $.post(window.fluentcrm_sub_pref.ajaxurl, data)
                .then(response => {
                    $('.fluentcrm_form_responses').html(
                        '<div class="fluentcrm_success">' + response.data.message + '</div>'
                    );
                })
                .fail((error) => {
                    let message = 'Sorry! Something is wrong. Please try again';
                    if (error.responseJSON && error.responseJSON.data && error.responseJSON.data.message) {
                        message = error.responseJSON.data.message;
                    }
                    $('.fluentcrm_form_responses').html(
                        '<div class="fluentcrm_error">' + message + '</div>'
                    );
                })
                .always(() => {
                    $btn.attr('disabled', false).removeClass('fc_btn_loading');
                });
        });
    }
});
