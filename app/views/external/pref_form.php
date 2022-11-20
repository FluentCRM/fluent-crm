<div class="fc_pref_form_wrap">
    <form method="POST" id="fc_pref_form" class="fc_public_form fc_contact_data_form">
        <?php wp_nonce_field( 'fluent_crm_account_form_fields', '_fc_nonce' ); ?>
        <?php do_action('fluent_crm/before_pref_form', $subscriber, $fields); ?>
        <?php (new \FluentCrm\App\Services\Html\FormElementBuilder)->renderFields($fields, true); ?>
        <?php do_action('fluent_crm/after_pref_form_fields', $subscriber, $fields); ?>
        <?php (new \FluentCrm\App\Services\Html\FormElementBuilder)->renderButton($submitBtn); ?>
    </form>
    <?php do_action('fluent_crm/after_pref_form', $subscriber, $fields); ?>
    <div class="fluentcrm_form_responses"></div>
</div>
