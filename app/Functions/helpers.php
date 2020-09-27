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
        echo "<pre>";
        print_r($data);
        echo "<pre>";
        die;
    }
}

if (!function_exists('ddd')) {
    function ddd($data)
    {
        echo "<pre>";
        print_r($data);
        echo "<pre>";
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
        return $model->save();
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

function fluentcrm_get_campaign_meta($objectId, $key)
{
    return fluentcrm_get_meta($objectId, 'FluentCrm\App\Models\Campaign', $key);
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
        'unsubscribed',
        'pending',
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
        'lead',
        'customer'
    ]);
}

function fluentcrm_activity_types()
{
    return apply_filters('fluentcrm_contact_activity_types', [
        'note'              => 'Note',
        'call'              => 'Call',
        'email'             => 'Email',
        'meeting'           => 'Meeting',
        'quote_sent'        => 'Quote: Sent',
        'quote_accepted'    => 'Quote: Accepted',
        'quote_refused'     => 'Quote: Refused',
        'invoice_sent'      => 'Invoice: Sent',
        'invoice_part_paid' => 'Invoice: Part Paid',
        'invoice_paid'      => 'Invoice: Paid',
        'invoice_refunded'  => 'Invoice: Refunded',
        'transaction'       => 'Transaction',
        'feedback'          => 'Feedback',
        'tweet'             => 'Tweet',
        'facebook_post'     => 'Facebook Post'
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
        (array) $attachedTagIds,
        $subscriber
    );
}

function fluentcrm_contact_added_to_lists($attachedListIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_added_to_lists',
        (array) $attachedListIds,
        $subscriber
    );
}

function fluentcrm_contact_removed_from_tags($detachedTagIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_removed_from_tags',
        (array) $detachedTagIds,
        $subscriber
    );
}

function fluentcrm_contact_removed_from_lists($detachedListIds, Subscriber $subscriber)
{
    return do_action(
        'fluentcrm_contact_removed_from_lists',
        (array) $detachedListIds,
        $subscriber
    );
}

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
        $subscriberId = intval(FluentCrm\Includes\Helpers\Arr::get($_COOKIE, 'fc_sid'));
        if ($subscriberId) {
            $subscriber = Subscriber::where('id', $subscriberId)->first();
        }
    }

    return $subscriber;
}
