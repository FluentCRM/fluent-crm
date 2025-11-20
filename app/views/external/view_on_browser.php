<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Imagetoolbar" content="No"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?php echo esc_attr($email_heading); ?></title>
    <?php foreach ($cssAssets as $asset): ?>
        <link rel='stylesheet' href='<?php echo esc_url($asset); ?>' media='all'/>
    <?php endforeach; ?>
    <?php $email && do_action('fluent_crm/view_on_browser_head', $email); ?>
</head>
<body class="fluentcrm_web_body">
<div class="fluentcrm_web_view_wrapper">
    <?php $email && do_action('fluent_crm/view_on_browser_before_heading', $email); ?>

    <div class="fluentcrm_web_view_header">
        <div class="fluentcrm_web_logo">
            <?php if (!empty($business['logo'])): ?>
                <a href="<?php echo esc_url(site_url()); ?>">
                    <img src="<?php echo esc_url($business['logo']); ?>"
                         alt="<?php echo esc_html($business['business_name']); ?>"/>
                </a>
            <?php endif; ?>
        </div>
        <div class="fluentcrm_web_heading">
            <h1><?php echo wp_kses_post($email_heading); ?></h1>
        </div>
    </div>

    <?php $email && do_action('fluent_crm/view_on_browser_before_email_body', $email); ?>

    <div class="fluentcrm_email_wrapper">
        <div id="fluent_email_body"></div>
    </div>

    <?php $email && do_action('fluent_crm/view_on_browser_after_email_body', $email); ?>
</div>
<?php $email && do_action('fluent_crm/view_on_browser_footer', $email); ?>

<script type="text/javascript">
    var fluentCrmEmail = <?php echo json_encode($email_body); ?>;

    const host = document.querySelector("#fluent_email_body");
    const shadow = host.attachShadow({mode: "closed"});
    const div = document.createElement("div");
    div.innerHTML = fluentCrmEmail.rendered;
    shadow.appendChild(div);
</script>
</body>
</html>
