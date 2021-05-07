<?php

use FluentCrm\App\App;
use FluentCrm\App\Models\Meta;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;

# $app is available

if (!function_exists('FluentCrm')) {
    function FluentCrm($module = null)
    {
        return App::getInstance($module);
    }
}

if (!function_exists('FluentCrmApi')) {
    function FluentCrmApi($key = null)
    {
        $api = FluentCrm('api');

        return is_null($key) ? $api : $api->{$key};
    }
}

if (!function_exists('dd')) {
    function dd($data)
    {
        foreach (func_get_args() as $arg) {
            echo "<pre>";
            print_r($arg);
            echo "</pre>";
        }
        die;
    }
}

if (!function_exists('ddd')) {
    function ddd($data)
    {
        foreach (func_get_args() as $arg) {
            echo "<pre>";
            print_r($arg);
            echo "</pre>";
        }
    }
}

if (!function_exists('fluentCrmMix')) {
    /**
     * Get the path to a versioned Mix file.
     *
     * @param string $path
     * @param string $manifestDirectory
     *
     * @return string
     */
    function fluentCrmMix($path, $manifestDirectory = '')
    {
        return FluentCrm('url.assets') . ltrim($path, '/');
    }
}

function fluentCrmTimestamp($timestamp = null)
{
    return current_time('mysql');
}

function fluentCrmUTCTimestamp($timestamp = null)
{
    $timestamp = is_null($timestamp) ? time() : $timestamp;

    $date = new \DateTime(
        null, new \DateTimeZone('UTC')
    );

    return $date->setTimestamp($timestamp)->format('Y-m-d H:i:s');
}

/**
 * Modified version of source
 *
 * @source https://www.skyverge.com/blog/down-the-rabbit-hole-wordpress-and-timezones/
 * @return string
 */
function fluentCrmGetTimezoneString()
{
    // if site timezone string exists, return it
    $timezone = get_option('timezone_string');
    if ($timezone) {
        return $timezone;
    }

    // get UTC offset, if it isn't set then return UTC
    $utcOffset = get_option('gmt_offset', 0);
    if ($utcOffset === 0) {
        return 'UTC';
    }

    // Adjust UTC offset from hours to seconds
    $utcOffset *= 3600;

    // Attempt to guess the timezone string from the UTC offset
    $timezone = timezone_name_from_abbr('', $utcOffset, 0);
    if ($timezone) {
        return $timezone;
    }

    // Guess timezone string manually
    $isDst = date('I');
    foreach (timezone_abbreviations_list() as $abbr) {
        foreach ($abbr as $city) {
            if ($city['dst'] == $isDst && $city['offset'] == $utcOffset) {
                $timezoneId = $city['timezone_id'];
                $timezone = $timezoneId ?: timezone_name_from_abbr('', $timezoneId, 0);
                if ($timezone) return $timezone;
            }
        }
    }

    // Fallback
    return 'UTC';
}

function fluentCrmMaybeRegisterQueryLoggerIfAvailable($app)
{
    if (fluentCrmQueryLoggingEnabled()) {
        $app->addAction('init', ['WpQueryLogger', 'init']);
    }
}

function fluentCrmQueryLoggingEnabled()
{
    if (file_exists(__DIR__ . '/../Hooks/Handlers/WpQueryLogger.php')) {
        return defined('SAVEQUERIES') && SAVEQUERIES;
    }
}

function fluentCrmEnableQueryLog()
{
    if (file_exists(__DIR__ . '/../Hooks/Handlers/WpQueryLogger.php')) {
        FluentCrm\App\Hooks\Handlers\WpQueryLogger::enableQueryLog();
    }
}

function fluentCrmGetQueryLog($withStack = true)
{
    if (file_exists(__DIR__ . '/../Hooks/Handlers/WpQueryLogger.php')) {
        return json_encode([
            FluentCrm\App\Hooks\Handlers\WpQueryLogger::getQueryLog($withStack)
        ]);
    }
}

function fluentcrm_get_meta($objectId, $objectType, $key)
{
    return Meta::where('object_id', $objectId)
        ->where('object_type', $objectType)
        ->where('key', $key)
        ->first();
}

function fluentcrm_update_meta($objectId, $objectType, $key, $value)
{
    $model = fluentcrm_get_meta($objectId, $objectType, $key);

    if ($model) {
        $model->value = $value;
        $model->save();
        return $model;
    }

    return Meta::create([
        'key'         => $key,
        'value'       => $value,
        'object_id'   => $objectId,
        'object_type' => $objectType
    ]);
}

function fluentcrm_delete_meta($objectId, $objectType, $key)
{
    return Meta::where('object_id', $objectId)
        ->where('object_type', $objectType)
        ->where('key', $key)
        ->delete();
}

function fluentcrm_get_option($optionName, $default = '')
{
    $option = Meta::where('key', $optionName)
        ->where('object_type', 'option')
        ->first();

    if (!$option) {
        return $default;
    }
    return ($option->value) ? $option->value : $default;
}

function fluentcrm_update_option($optionName, $value)
{
    $option = Meta::where('key', $optionName)
        ->where('object_type', 'option')
        ->first();
    if ($option) {
        $option->value = $value;
        $option->save();
        return $option->id;
    }

    $model = Meta::create([
        'key'         => $optionName,
        'value'       => $value,
        'object_type' => 'option'
    ]);

    return $model->id;

}

function fluentcrm_get_campaign_meta($objectId, $key, $returnValue = false)
{
    $item = fluentcrm_get_meta($objectId, 'FluentCrm\App\Models\Campaign', $key);
    if ($returnValue) {
        if ($item) {
            return $item->value;
        }
        return false;
    }

    return $item;
}

function fluentcrm_update_campaign_meta($objectId, $key, $value)
{
    return fluentcrm_update_meta($objectId, 'FluentCrm\App\Models\Campaign', $key, $value);
}

function fluentcrm_delete_campaign_meta($objectId, $key)
{
    return fluentcrm_delete_meta($objectId, 'FluentCrm\App\Models\Campaign', $key);
}

function fluentcrm_get_template_meta($objectId, $key)
{
    return fluentcrm_get_meta($objectId, 'FluentCrm\App\Models\Template', $key);
}

function fluentcrm_update_template_meta($objectId, $key, $value)
{
    return fluentcrm_update_meta($objectId, 'FluentCrm\App\Models\Template', $key, $value);
}

function fluentcrm_delete_template_meta($objectId, $key)
{
    return fluentcrm_delete_meta($objectId, 'FluentCrm\App\Models\Template', $key);
}

function fluentcrm_get_list_meta($objectId, $key)
{
    return fluentcrm_get_meta($objectId, 'FluentCrm\App\Models\Lists', $key);
}

function fluentcrm_update_list_meta($objectId, $key, $value)
{
    return fluentcrm_update_meta($objectId, 'FluentCrm\App\Models\Lists', $key, $value);
}

function fluentcrm_delete_list_meta($objectId, $key)
{
    return fluentcrm_delete_meta($objectId, 'FluentCrm\App\Models\Lists', $key);
}

function fluentcrm_get_subscriber_meta($subscriberId, $key, $deafult = '')
{
    $item = SubscriberMeta::where('key', $key)
        ->where('subscriber_id', $subscriberId)
        ->first();

    if ($item && $item->value) {
        return maybe_unserialize($item->value);
    }

    return $deafult;
}

function fluentcrm_update_subscriber_meta($subscriberId, $key, $value)
{
    $value = maybe_serialize($value);
    // check if exists
    $model = SubscriberMeta::where('key', $key)
        ->where('subscriber_id', $subscriberId)
        ->first();

    if ($model) {
        $model->updated_at = fluentCrmTimestamp();
        $model->value = $value;
        return $model->save();
    }

    return SubscriberMeta::create([
        'key'           => $key,
        'value'         => $value,
        'subscriber_id' => $subscriberId,
        'created_at'    => fluentCrmTimestamp()
    ]);
}

function fluentcrm_delete_subscriber_meta($subscriberId, $key)
{
    return SubscriberMeta::where('key', $key)
        ->where('subscriber_id', $subscriberId)
        ->delete();
}

/**
 * Get all subscriber status options.
 *
 * @return array
 */
function fluentcrm_subscriber_statuses()
{
    return apply_filters('fluentcrm_subscriber_statuses', [
        'subscribed',
        'pending',
        'unsubscribed',
        'bounced',
        'complained'
    ]);
}

/**
 * Get all subscriber editable status options.
 *
 * @return array
 */
function fluentcrm_subscriber_editable_statuses()
{
    return apply_filters('fluentcrm_subscriber_editable_statuses', [
        'subscribed',
        'unsubscribed',
        'pending'
    ]);
}

function fluentcrm_contact_types()
{
    return apply_filters('fluentcrm_contact_types', [
        'lead' => __('Lead', 'fluent-crm'),
        'customer' => __('Customer', 'fluent-crm')
    ]);
}

function fluentcrm_activity_types()
{
    return apply_filters('fluentcrm_contact_activity_types', [
        'note'              => __('Note', 'fluent-crm'),
        'call'              => __('Call', 'fluent-crm'),
        'email'             => __('Email', 'fluent-crm'),
        'meeting'           => __('Meeting', 'fluent-crm'),
        'quote_sent'        => __('Quote: Sent', 'fluent-crm'),
        'quote_accepted'    => __('Quote: Accepted', 'fluent-crm'),
        'quote_refused'     => __('Quote: Refused', 'fluent-crm'),
        'invoice_sent'      => __('Invoice: Sent', 'fluent-crm'),
        'invoice_part_paid' => __('Invoice: Part Paid', 'fluent-crm'),
        'invoice_paid'      => __('Invoice: Paid', 'fluent-crm'),
        'invoice_refunded'  => __('Invoice: Refunded', 'fluent-crm'),
        'transaction'       => __('Transaction', 'fluent-crm'),
        'feedback'          => __('Feedback', 'fluent-crm'),
        'tweet'             => __('Tweet', 'fluent-crm'),
        'facebook_post'     => __('Facebook Post', 'fluent-crm')
    ]);
}

function fluentcrm_strict_statues()
{
    return apply_filters('subscriber_strict_statuses', [
        'unsubscribed',
        'bounced',
        'complained'
    ]);
}

function fluentcrmTemplateCPTSlug()
{
    return 'fc_template';
}

function fluentcrmCampaignTemplateCPTSlug()
{
    return FLUENTCRM . 'campaigntemplate';
}

/**
 * Get the possible csv mimes.
 *
 * @return array
 */
function fluentcrmCsvMimes()
{
    return apply_filters('fluentcrm_csv_mimes', [
        'text/csv',
        'text/plain',
        'application/csv',
        'text/comma-separated-values',
        'application/excel',
        'application/vnd.ms-excel',
        'application/vnd.msexcel',
        'text/anytext',
        'application/octet-stream',
        'application/txt'
    ]);
}

/**
 * Get the gravatar from an email.
 *
 * @param string $email
 * @return string
 */
function fluentcrmGravatar($email)
{
    $hash = md5(strtolower(trim($email)));
    return apply_filters(
        FLUENTCRM . '_get_avatar',
        "https://www.gravatar.com/avatar/${hash}?s=128",
        $email
    );
}

function fluentcrmGetGlobalSettings($key, $default = false)
{
    $settings = get_option(FLUENTCRM . '-global-settings');
    if ($settings && isset($settings[$key])) {
        return $settings[$key];
    }
    return $default;
}

function fluentcrmHrefParams($content, $params = [])
{
    if (!$params) {
        return $content;
    }
    // todo: We have to implement this here
    return $content;
}


function fluentcrmTrackClicking()
{
    return apply_filters('fluentcrm_track_click', true);
}


function fluentCrmWillTrackIp()
{
    return apply_filters('fluentcrm_will_track_user_ip', true);
}

function fluentcrm_contact_added_to_tags($attachedTagIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_added_to_tags',
        (array)$attachedTagIds,
        $subscriber
    );
}

function fluentcrm_contact_added_to_lists($attachedListIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_added_to_lists',
        (array)$attachedListIds,
        $subscriber
    );
}

function fluentcrm_contact_removed_from_tags($detachedTagIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_removed_from_tags',
        (array)$detachedTagIds,
        $subscriber
    );
}

function fluentcrm_contact_removed_from_lists($detachedListIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_removed_from_lists',
        (array)$detachedListIds,
        $subscriber
    );
}

/*
 * @return object \FluentCrm\App\Models\Subscriber
 */
function fluentcrm_get_current_contact()
{
    $subscriber = false;
    $userId = get_current_user_id();

    if ($userId) {
        $subscriber = Subscriber::where('user_id', $userId)->first();
        if (!$subscriber) {
            $user = get_user_by('ID', $userId);
            $subscriber = Subscriber::where('email', $user->user_email)->first();
        }
    } else {
        $fcSubscriberHash = FluentCrm\Includes\Helpers\Arr::get($_COOKIE, 'fc_s_hash');
        if($fcSubscriberHash) {
            $subscriber = Subscriber::where('hash', $fcSubscriberHash)->first();
        } else {
            // @todo: We will remove this after february
            $subscriberId = intval(FluentCrm\Includes\Helpers\Arr::get($_COOKIE, 'fc_sid'));
            if ($subscriberId) {
                $subscriber = Subscriber::where('id', $subscriberId)->first();
            }
        }

    }

    return $subscriber;
}

function fluentcrm_get_crm_profile_html($userIdOrEmail, $checkPermission = true, $withCss = true)
{
    if (!$userIdOrEmail) {
        return '';
    }
    if ($checkPermission) {
        $contactPermission = \FluentCrm\App\Services\PermissionManager::currentUserCan('fcrm_read_contacts');
        if (!$contactPermission) {
            return '';
        }
    }

    $profile = FluentCrmApi('contacts')->getContactByUserRef($userIdOrEmail);
    if (!$profile) {
        return '';
    }

    $urlBase = apply_filters('fluentcrm_menu_url_base', admin_url('admin.php?page=fluentcrm-admin#/'));
    $crmProfileUrl = $urlBase . 'subscribers/' . $profile->id;
    $tags = $profile->tags;
    $lists = $profile->lists;

    $stats = $profile->stats();

    ob_start();
    ?>
    <div class="fc_profile_external">
        <div class="fluentcrm_profile-photo">
            <a title="View Profile: <?php echo $profile->email; ?>" href="<?php echo $crmProfileUrl; ?>">
                <img src="<?php echo $profile->photo; ?>"/>
            </a>
        </div>
        <div class="profile-info">
            <div class="profile_title">
                <h3>
                    <a title="View Profile: <?php echo $profile->email; ?>" href="<?php echo $crmProfileUrl; ?>">
                        <?php echo $profile->full_name; ?>
                    </a>
                </h3>
                <p><?php echo $profile->status; ?></p>
            </div>
            <div class="fc_tag_lists">
                <div class="fc_stats" style="text-align: center">
                    <?php foreach ($stats as $statKey => $stat): ?>
                        <span><?php echo ucfirst($statKey); ?>: <?php echo $stat; ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if (!$lists->isEmpty()): ?>
                    <div class="fc_taggables">
                        <i class="dashicons dashicons-list-view"></i>
                        <?php foreach ($lists as $list): ?>
                            <span><?php echo $list->title; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!$tags->isEmpty()): ?>
                    <div class="fc_taggables">
                        <i class="dashicons dashicons-tag"></i>
                        <?php foreach ($tags as $tag): ?>
                            <span><?php echo $tag->title; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($withCss): ?>
    <style>
        .fc_profile_external {
        }

        .fc_profile_external .fluentcrm_profile-photo {
            max-width: 100px;
            margin: 0 auto;
        }

        .fc_profile_external .fluentcrm_profile-photo img {
            width: 80px;
            height: 80px;
            border: 6px solid #e6ebf0;
            border-radius: 50%;
            vertical-align: middle;
            background-position: center center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .fc_profile_external .profile_title {
            margin-bottom: 10px;
            text-align: center;
        }

        .fc_profile_external .profile_title h3 {
            margin: 0;
            padding: 0;
            display: inline-block;
        }

        .fc_profile_external .profile_title a {
            text-decoration: none;
        }

        .fc_profile_external p {
            margin: 0 0 5px;
            padding: 0;
        }

        .fc_taggables span {
            border: 1px solid #d3e7ff;
            margin-left: 4px;
            padding: 2px 5px;
            display: inline-block;
            margin-bottom: 10px;
            font-size: 11px;
            border-radius: 3px;
            color: #2196F3;
        }

        .fc_taggables i {
            font-size: 11px;
            margin-top: 7px;
        }
        .fc_stats {
            list-style: none;
            margin-bottom: 20px;
            padding: 0;
            box-sizing: border-box;
        }

        .fc_stats span {
            border: 1px solid #d9ecff;
            margin: 0 -4px 0px 0px;
            padding: 3px 6px;
            display: inline-block;
            background: #ecf5ff;
            color: #409eff;
        }
    </style>
<?php endif; ?>
    <?php
    return ob_get_clean();
}


function fluentcrm_maybe_disable_fsmtp_log($status, $settings)
{
    if(!$status) {
        return $status;
    }

    if(isset($settings['disable_fluentcrm_logs']) && $settings['disable_fluentcrm_logs'] == 'yes') {
        return false;
    }

    return $status;
}


function fluentcrm_get_custom_contact_fields()
{
    static $fields;
    if($fields) {
        return $fields;
    }
    $fields = fluentcrm_get_option('contact_custom_fields', []);

    return $fields;
}
