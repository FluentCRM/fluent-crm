<?php

use FluentCrm\App\App;
use FluentCrm\App\Models\Meta;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;

# $app is available

if (!function_exists('FluentCrm')) {
    /**
     * @param null|string $module Module name or empty string to get the app of specific moduleFh
     * @return \FluentCrm\App\App $app | object $instance if specific FluentCRM Framework Module
     */
    function FluentCrm($module = null)
    {
        return App::getInstance($module);
    }
}

if (!function_exists('FluentCrmApi')) {

    /**
     * @param string|null $key
     * @return Object $phpApiInstance Get Contacts/Lists/Tags Php API Wrapper
     */
    function FluentCrmApi($key = null)
    {
        $api = FluentCrm('api');
        return is_null($key) ? $api : $api->{$key};
    }
}

if (!function_exists('dd')) {
    /**
     * Internal function for debugging
     */
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
    /**
     * Internal function for debugging
     */
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
     * Generate URL for static assets for plugin's assets directory
     *
     * @param string $path
     *
     * @return string
     */
    function fluentCrmMix($path, $manifestDirectory = '')
    {
        return FluentCrm('url.assets') . ltrim($path, '/');
    }
}

/**
 * Get Current Date time
 *
 * @return string Datetime in format Y-m-d H:i:s
 */
function fluentCrmTimestamp($timestamp = null)
{
    return current_time('mysql');
}

/**
 * Get Current Date time as UTC Format
 *
 * @return string Datetime in format Y-m-d H:i:s as UTC Format
 */
function fluentCrmUTCTimestamp($timestamp = null)
{
    $timestamp = is_null($timestamp) ? time() : $timestamp;

    $date = new \DateTime(
        null, new \DateTimeZone('UTC')
    );

    return $date->setTimestamp($timestamp)->format('Y-m-d H:i:s');
}

/**
 * Get Actual Timezone string for WP
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

/*
 * Internal Function For debugging only
 */
function fluentCrmMaybeRegisterQueryLoggerIfAvailable($app)
{
    if (fluentCrmQueryLoggingEnabled()) {
        $app->addAction('init', ['WpQueryLogger', 'init']);
    }
}

/*
 * Internal Function For debugging only
 */
function fluentCrmQueryLoggingEnabled()
{
    if (file_exists(__DIR__ . '/../Hooks/Handlers/WpQueryLogger.php')) {
        return defined('SAVEQUERIES') && SAVEQUERIES;
    }
}

/*
 * Internal Function For debugging only
 */
function fluentCrmEnableQueryLog()
{
    if (file_exists(__DIR__ . '/../Hooks/Handlers/WpQueryLogger.php')) {
        FluentCrm\App\Hooks\Handlers\WpQueryLogger::enableQueryLog();
    }
}

/*
 * Internal Function For debugging only
 */
function fluentCrmGetQueryLog($withStack = true)
{
    if (file_exists(__DIR__ . '/../Hooks/Handlers/WpQueryLogger.php')) {
        return json_encode([
            FluentCrm\App\Hooks\Handlers\WpQueryLogger::getQueryLog($withStack)
        ]);
    }
}


/**
 * Get meta entry of a FluentCRM entity
 * @param int $objectId ID of referenced object
 * @param string $objectType Type of referenced object
 * @param string $key Key of the reference
 * @return object FluentCrm\App\Models\Meta Object of the Meta Table Model
 */
function fluentcrm_get_meta($objectId, $objectType, $key)
{
    return Meta::where('object_id', $objectId)
        ->where('object_type', $objectType)
        ->where('key', $key)
        ->first();
}

/**
 * Update or create a meta entry of the given entity
 * @param int $objectId ID of referenced object
 * @param string $objectType Type of referenced object
 * @param string $key Key of the reference
 * @param mixed $value
 * @return \FluentCrm\App\Models\Meta|object
 */
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

/**
 * Delete a Object Meta of FluentCRM
 * @param int $objectId ID of referenced entity
 * @param string $objectType Type of referenced entity
 * @param string $key Key of the reference
 * @return boolean
 */
function fluentcrm_delete_meta($objectId, $objectType, $key)
{
    return Meta::where('object_id', $objectId)
        ->where('object_type', $objectType)
        ->where('key', $key)
        ->delete();
}

/**
 * Get FluentCRM Option
 * @param $optionName string
 * @param mixed $default
 * @return mixed|string
 */
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

/**
 * Update FluentCRM Option
 * @param $optionName
 * @param $value
 * @return int Created or updated meta entry id
 */
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

/**
 * Get Email Campaign meta value
 * @param int $campaignId ID of the campaign
 * @param string $key Key of the meta
 * @param false $returnValue If true, return the value, otherwise return the meta object
 * @return false|object
 */
function fluentcrm_get_campaign_meta($campaignId, $key, $returnValue = false)
{
    $item = fluentcrm_get_meta($campaignId, 'FluentCrm\App\Models\Campaign', $key);
    if ($returnValue) {
        if ($item) {
            return $item->value;
        }
        return false;
    }

    return $item;
}

/**
 * update Email Campaign meta value
 * @param int $campaignId ID of the campaign
 * @param string $key Key of the meta
 * @param mixed $value Value of the meta
 * @return \FluentCrm\App\Models\Meta|object
 */
function fluentcrm_update_campaign_meta($campaignId, $key, $value)
{
    return fluentcrm_update_meta($campaignId, 'FluentCrm\App\Models\Campaign', $key, $value);
}

/**
 * Delete email campaign meta
 * @param int $campaignId
 * @param string $key Key of the meta
 * @return bool
 */
function fluentcrm_delete_campaign_meta($campaignId, $key)
{
    return fluentcrm_delete_meta($campaignId, 'FluentCrm\App\Models\Campaign', $key);
}

/**
 * Get Email Campaign meta value
 * @param int $templateId ID of the template
 * @param string $key Key of the meta
 * @return object
 */
function fluentcrm_get_template_meta($templateId, $key)
{
    return fluentcrm_get_meta($templateId, 'FluentCrm\App\Models\Template', $key);
}

/**
 * update Email Template meta value
 * @param int $templateId id of the template
 * @param string $key Key of the meta
 * @param mixed $value Value of the meta
 * @return \FluentCrm\App\Models\Meta|object
 */
function fluentcrm_update_template_meta($templateId, $key, $value)
{
    return fluentcrm_update_meta($templateId, 'FluentCrm\App\Models\Template', $key, $value);
}

/**
 * Delete email template meta
 * @param int $templateId
 * @param string $key key of the meta
 * @return bool
 */
function fluentcrm_delete_template_meta($templateId, $key)
{
    return fluentcrm_delete_meta($templateId, 'FluentCrm\App\Models\Template', $key);
}

/**
 * get meta value of Contact lists
 * @param int $listId
 * @param string $key key of the meta
 * @return object
 */
function fluentcrm_get_list_meta($listId, $key)
{
    return fluentcrm_get_meta($listId, 'FluentCrm\App\Models\Lists', $key);
}

/**
 * Update meta value of Contact lists
 * @param int $listId
 * @param string $key
 * @param mixed $value
 * @return \FluentCrm\App\Models\Meta|object
 */
function fluentcrm_update_list_meta($listId, $key, $value)
{
    return fluentcrm_update_meta($listId, 'FluentCrm\App\Models\Lists', $key, $value);
}

/**
 * Delete meta value of Contact lists
 * @param int $listId
 * @param string $key
 * @return bool
 */
function fluentcrm_delete_list_meta($listId, $key)
{
    return fluentcrm_delete_meta($listId, 'FluentCrm\App\Models\Lists', $key);
}

/**
 * get meta value of a Contact
 * @param int $subscriberId contact ID
 * @param string $key key of the meta
 * @param string $deafult default value if meta not found
 * @return mixed|string
 */
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

/**
 * update meta value of a Contact
 * @param int $subscriberId
 * @param string $key
 * @param mixed $value
 * @return \FluentCrm\App\Models\SubscriberMeta
 */
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
        'created_by'    => get_current_user_id(),
        'value'         => $value,
        'subscriber_id' => $subscriberId,
        'created_at'    => fluentCrmTimestamp()
    ]);
}

/**
 * Delete a meta value of a contact
 * @param int $subscriberId
 * @param string $key
 * @return boolean
 */
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
    /**
     * Subscriber Statuses
     *
     * @param: array $statuses array of subscriber statuses
     */
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
    /**
     * Contact's Editable Statuses
     *
     * @param: array $editableStatuses array of subscriber's editable statuses
     */
    return apply_filters('fluentcrm_subscriber_editable_statuses', [
        'subscribed',
        'unsubscribed',
        'pending'
    ]);
}

/**
 * Get Contact Types
 * @return array
 */
function fluentcrm_contact_types()
{
    /**
     * Contact Types
     *
     * @param: array $contactTypes array of contact types
     */
    return apply_filters('fluentcrm_contact_types', [
        'lead'     => __('Lead', 'fluent-crm'),
        'customer' => __('Customer', 'fluent-crm')
    ]);
}

/**
 * Get Contact's Activity Types
 *
 * @return array
 */
function fluentcrm_activity_types()
{
    /**
     * Contact Activities
     *
     * @param: array $activityTypes array of contact's Activity Types
     */
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

/**
 * Get Contact's Strict status Types
 *
 * @return array
 */
function fluentcrm_strict_statues()
{
    /**
     * Contact's strict Statuses
     *
     * @return array contact strict statuses
     */
    return apply_filters('subscriber_strict_statuses', [
        'unsubscribed',
        'bounced',
        'complained'
    ]);
}

/**
 * Email Template CPT Slug
 * @return string
 */
function fluentcrmTemplateCPTSlug()
{
    return 'fc_template';
}

/**
 * Email Template CPT Slug
 * @return string
 */
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
    /**
     * Contact Import CSV Mimes
     *
     * @return array array of CSV mimes
     */
    return apply_filters('fluencrm_csv_mimes', [
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

    /**
     * Gravatar URL by Email
     *
     * @return string $gravatar url of the gravatar image
     */
    return apply_filters('fluentcrm_get_avatar',
        "https://www.gravatar.com/avatar/${hash}?s=128",
        $email
    );
}

/**
 * get FluentCRM's Global Settings
 * @param string $key key of the setting
 * @param mixed $default default value
 * @return mixed
 */
function fluentcrmGetGlobalSettings($key, $default = false)
{
    $settings = get_option('fluentcrm-global-settings');
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


/**
 * get if click tracking is enabled or disabled
 * @return bool
 */
function fluentcrmTrackClicking()
{
    /**
     * Enable or Disable Click Tracking for Emails
     *
     * @param bool $trackClicking if click tracking is enabled or disabled
     * @return bool
     */
    return apply_filters('fluentcrm_track_click', true);
}


/**
 * get if IP Address Tracking is enabled or disabled
 * @return bool
 */
function fluentCrmWillTrackIp()
{
    /**
     * Enable or Disable IP Address Tracking for Emails
     *
     * @param bool $trackIp return true if ip address tracking is enabled or false if disabled
     * @return bool
     */
    return apply_filters('fluentcrm_will_track_user_ip', true);
}

/**
 * Add tags to a subscriber
 * @param array $attachedTagIds IDs of the tags that will be added to the subscriber
 * @param \FluentCrm\App\Models\Subscriber $subscriber
 * @return void
 */
function fluentcrm_contact_added_to_tags($attachedTagIds, Subscriber $subscriber)
{
    if (defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
        return;
    }
    /**
     * Fires when tags have been added to a subscriber
     *
     * @param array $attachedTagIds IDs of the tags that will be added to the subscriber
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     */
    do_action(
        'fluentcrm_contact_added_to_tags',
        (array)$attachedTagIds,
        $subscriber
    );
}

/**
 * Add Lists to a subscriber
 * @param array $attachedListIds IDs of the lists that will be added to the subscriber
 * @param \FluentCrm\App\Models\Subscriber $subscriber
 * @return void
 */
function fluentcrm_contact_added_to_lists($attachedListIds, Subscriber $subscriber)
{
    if (defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
        return;
    }
    /**
     * Fires when lists have been added to a subscriber
     *
     * @param array $attachedListIds IDs of the lists that will be added to the subscriber
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     */
    return do_action(
        'fluentcrm_contact_added_to_lists',
        (array)$attachedListIds,
        $subscriber
    );
}

/**
 * Remove tags from a subscriber
 * @param $detachedTagIds
 * @param \FluentCrm\App\Models\Subscriber $subscriber
 * @return void
 */
function fluentcrm_contact_removed_from_tags($detachedTagIds, Subscriber $subscriber)
{
    if (defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
        return;
    }

    /**
     * Fires when tags have been removed from a subscriber
     *
     * @param array $detachedTagIds IDs of the tags that will be removed from the subscriber
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     */
    do_action(
        'fluentcrm_contact_removed_from_tags',
        (array)$detachedTagIds,
        $subscriber
    );
}

/**
 * Remove lists from a subscriber
 * @param $detachedListIds
 * @param \FluentCrm\App\Models\Subscriber $subscriber
 * @return void
 */
function fluentcrm_contact_removed_from_lists($detachedListIds, Subscriber $subscriber)
{
    if (defined('FLUENTCRM_DISABLE_TAG_LIST_EVENTS')) {
        return;
    }

    /**
     * Fires when lists have been removed from a subscriber
     *
     * @param array $detachedListIds IDs of the lists that will be removed from the subscriber
     * @param \FluentCrm\App\Models\Subscriber $subscriber
     */
    do_action(
        'fluentcrm_contact_removed_from_lists',
        (array)$detachedListIds,
        $subscriber
    );
}

/*
 * Get Current Contact based on the current userID or contact from the cookie value
 *
 * @return false|object \FluentCrm\App\Models\Subscriber
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
        $fcSubscriberHash = FluentCrm\Framework\Support\Arr::get($_COOKIE, 'fc_s_hash');
        if ($fcSubscriberHash) {
            $subscriber = Subscriber::where('hash', $fcSubscriberHash)->first();
        }
    }

    return $subscriber;
}

/**
 * Get FluentCRM's contact profile widget HTML
 *
 * @param int|string $userIdOrEmail User ID or email address
 * @param bool $checkPermission Whether to check permission
 * @param bool $withCss Whether to include CSS
 * @return false|string HTML of the profile Widget
 */
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

    /**
     * Filter for URL Base of CRM Admin menu
     * @param string The full url of the admin page
     */
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


/**
 * Maybe Disable the FluentSMTP's Email Logging based provided $settings
 *
 * @param bool $status
 * @param array $settings
 * @return bool
 */
function fluentcrm_maybe_disable_fsmtp_log($status, $settings)
{
    if (!$status) {
        return $status;
    }

    if (isset($settings['disable_fluentcrm_logs']) && $settings['disable_fluentcrm_logs'] == 'yes') {
        return false;
    }

    return $status;
}


/**
 * Get Custom Fields schema for contacts
 *
 * @return array
 */
function fluentcrm_get_custom_contact_fields()
{
    static $fields;
    if ($fields) {
        return $fields;
    }
    $fields = fluentcrm_get_option('contact_custom_fields', []);

    return $fields;
}

/**
 * Sending a job to background for further processing
 *
 * @param string $callbackName - name of the callback
 * @param mixed $payload
 * @return bool
 */
function fluentcrm_queue_on_background($callbackName, $payload)
{
    $body = [
        'payload'       => $payload,
        'callback_name' => $callbackName
    ];

    $args = array(
        'timeout'   => 0.1,
        'blocking'  => false,
        'body'      => $body,
        'cookies'   => $_COOKIE,
        'sslverify' => apply_filters('fluentcrm_https_local_ssl_verify', false),
    );

    $queryArgs = array(
        'action' => 'fluentcrm_callback_for_background',
        'nonce'  => wp_create_nonce('fluentcrm_callback_for_background'),
    );

    $url = add_query_arg($queryArgs, admin_url('admin-ajax.php'));
    wp_remote_post(esc_url_raw($url), $args);
    return true;
}

/**
 * Check if FluentCRM will render the email contents as RTL mode
 *
 * @return mixed|void
 */
function fluentcrm_is_rtl()
{
    /**
     * If FluentCRM is running on RTL Mode
     *
     * @param bool $is_rtl - return true if you want to render FluentCRM emails in RTL mode
     */
    return apply_filters('fluentcrm_is_rtl', is_rtl());
}

/**
 * Get FluentCRM Query Builder instance
 *
 * @return FluentCrm\Framework\Database\Query\WPDBConnection
 */
function fluentCrmDb()
{
    return fluentCrm('db');
}

function fluentCrmIsMemoryExceeded()
{
    $memory_limit = fluentCrmGetMemoryLimit() * 0.80;
    $current_memory = memory_get_usage(true);

    return $current_memory >= $memory_limit;
}

function fluentCrmGetMemoryLimit()
{
    if (function_exists('ini_get')) {
        $memory_limit = ini_get('memory_limit');
    } else {
        $memory_limit = '128M'; // Sensible default, and minimum required by WooCommerce
    }

    if (!$memory_limit || -1 === $memory_limit || '-1' === $memory_limit) {
        // Unlimited, set to 12GB.
        $memory_limit = '12G';
    }


    if (function_exists('wp_convert_hr_to_bytes')) {
        return wp_convert_hr_to_bytes($memory_limit);
    }

    $value = strtolower(trim($memory_limit));
    $bytes = (int)$value;

    if (false !== strpos($value, 'g')) {
        $bytes *= GB_IN_BYTES;
    } elseif (false !== strpos($value, 'm')) {
        $bytes *= MB_IN_BYTES;
    } elseif (false !== strpos($value, 'k')) {
        $bytes *= KB_IN_BYTES;
    }

    return min($bytes, PHP_INT_MAX);
}
