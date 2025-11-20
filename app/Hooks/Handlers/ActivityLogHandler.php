<?php
// php
namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\ActivityLog;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\Tag;

class ActivityLogHandler
{
    protected $objectTypeContact = 'FluentCrm\App\Models\Subscriber';

    // Call this once (e.g., on plugins_loaded) to attach the hooks
    public function register()
    {
        return;
//        if (!$this->activityLogEnabled()) {
//            return;
//        }
        // Contact created
        add_action('fluent_crm/contact_created', [$this, 'onContactCreated'], 10, 2);

        // Tags updated
        add_action('fluent_crm/contact_added_to_tags', [$this, 'onTagsAdded'], 10, 3);
        add_action('fluent_crm/contact_removed_from_tags', [$this, 'onTagsRemoved'], 10, 3);

        // Lists updated
        add_action('fluent_crm/contact_added_to_lists', [$this, 'onListsAdded'], 10, 3);
        add_action('fluent_crm/contact_removed_from_lists', [$this, 'onListsRemoved'], 10, 3);

        // Bulk delete subscribers
        add_action('fluentcrm_before_subscribers_deleted', [$this, 'onSubscribersDeleted'], 10,2);
    }

    public function onContactCreated($contact, $source = 'wp-admin')
    {
        $contactId = $this->contactId($contact);
        if (!$contactId) {
            return;
        }

        $email = $this->contactField($contact, 'email');
        $name  = trim($this->contactField($contact, 'first_name') . ' ' . $this->contactField($contact, 'last_name'));

        $description = 'Contact Name: ' . $name . ' | Contact Email: ' . $email;

        $this->log([
            'object_type' => $this->objectTypeContact,
            'object_id'   => $contactId,
            'action'      => 'created contact',
            'source'      => $source,
            'description' => $description
        ]);
    }

    public function onSubscribersDeleted($subscriberIds, $source = 'wp-admin')
    {
        $subscriberIds = array_values(array_filter((array) $subscriberIds));
        if (empty($subscriberIds)) {
            return;
        }

        $subscriberIds = array_values(array_filter((array) $subscriberIds));
        $emails = Subscriber::whereIn('id', $subscriberIds)->take(10)->pluck('email')->toArray();
        $commaSeparatedEmails = implode(',', $emails);
        if (count($subscriberIds) > 10) {
            $commaSeparatedEmails .= '...' . ' (and ' . (count($subscriberIds) - 10) . ' more)';
        }
        $description = 'Deleted Contacts Emails: ' . $commaSeparatedEmails;

        $this->log([
            'object_type' => $this->objectTypeContact,
            'object_id'   => 0,
            'action'      => 'deleted contacts',
            'source'      => $source,
            'description' => $description
        ]);
    }

    public function onTagsAdded($contact, $tagIds, $source = 'wp-admin')
    {
        $this->logTags($contact, $tagIds, 'added tag to contact', $source);
    }

    public function onTagsRemoved($contact, $tagIds, $source = 'wp-admin')
    {
        $this->logTags($contact, $tagIds, 'removed tag from contact', $source);
    }

    public function onListsAdded($contact, $listIds, $source = 'wp-admin')
    {
        $this->logLists($contact, $listIds, 'added list to contact', $source);
    }

    public function onListsRemoved($contact, $listIds, $source = 'wp-admin')
    {
        $this->logLists($contact, $listIds, 'removed list from contact', $source);
    }

    /*
     |----------------------------------------------------------------------
     | Helpers
     |----------------------------------------------------------------------
     */

    protected function logTags($contact, $tagIds, $action, $source)
    {
        $contactId = $this->contactId($contact);
        if (!$contactId) {
            return;
        }

        $tagIds = array_values(array_filter((array) $tagIds));
        $subscriberEmail = Subscriber::where('id', $contactId)->value('email');
        $commaSeparatedTitles = implode(',', Tag::whereIn('id', $tagIds)->pluck('title')->toArray());
        $description = 'Tags: ' . $commaSeparatedTitles . ' | Contact: ' . $subscriberEmail;

        $this->log([
            'object_type' => $this->objectTypeContact,
            'object_id'   => $contactId,
            'action'      => $action,
            'source'      => $source,
            'description' => $description
        ]);
    }

    protected function logLists($contact, $listIds, $action, $source)
    {
        $contactId = $this->contactId($contact);
        if (!$contactId) {
            return;
        }

        $listIds = array_values(array_filter((array) $listIds));

        $subscriberEmail = Subscriber::where('id', $contactId)->value('email');
        $commaSeparatedTitles = implode(',', Lists::whereIn('id', $listIds)->pluck('title')->toArray());
        $description = 'Lists: ' . $commaSeparatedTitles . ' | Contact: ' . $subscriberEmail;

        $this->log([
            'object_type' => $this->objectTypeContact,
            'object_id'   => $contactId,
            'action'      => $action,
            'source'      => $source,
            'description' => $description
        ]);
    }

    protected function log(array $data)
    {
        ActivityLog::create([
            'object_type' => $data['object_type'] ?? 'contact',
            'object_id'   => $data['object_id'] ?? null,
            'action'      => $data['action'] ?? 'unknown',
            'source'      => $data['source'],
            'description' => $data['description'] ?? null,
            'activity_by' => $this->currentUserId()
        ]);
    }

    protected function contactId($contact)
    {
        if (is_object($contact)) {
            // FluentCRM Contact model uses `id`
            return isset($contact->id) ? (int) $contact->id : null;
        }
        if (is_array($contact)) {
            return isset($contact['id']) ? (int) $contact['id'] : null;
        }
        return null;
    }

    protected function contactField($contact, $key)
    {
        if (is_object($contact)) {
            return isset($contact->{$key}) ? $contact->{$key} : '';
        }
        if (is_array($contact)) {
            return isset($contact[$key]) ? $contact[$key] : '';
        }
        return '';
    }

    protected function currentUserId(): int
    {
        if (function_exists('get_current_user_id')) {
            return (int) get_current_user_id();
        }
        return 0;
    }

    protected function activityLogEnabled()
    {
        $settings = get_option('_fluentcrm_experimental_settings', []);
        if (isset($settings['activity_log']) && $settings['activity_log'] == 'no') {
            return false;
        }
        return true;
    }
}
