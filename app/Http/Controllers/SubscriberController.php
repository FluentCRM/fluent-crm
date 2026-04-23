<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Hooks\Handlers\PurchaseHistory;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\App\Models\CampaignUrlMetric;
use FluentCrm\App\Models\Company;
use FluentCrm\App\Models\CustomEmailCampaign;
use FluentCrm\App\Models\EventTracker;
use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberMeta;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\SubscriberPivot;
use FluentCrm\App\Services\AutoSubscribe;
use FluentCrm\App\Services\ContactsQuery;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Sanitize;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  SubscriberController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class SubscriberController extends Controller
{
    public function index()
    {
        $filterType = $this->request->get('filter_type', 'simple');

        $with = ['tags', 'lists'];

        if (Helper::isCompanyEnabled()) {
            $with[] = 'company';
            $with[] = 'companies';
        }

        if ($filterType == 'advanced') {
            $queryArgs = [
                'with'               => $with,
                'filter_type'        => 'advanced',
                'filters_groups_raw' => $this->request->getJson('advanced_filters'),
                'search'             => trim(sanitize_text_field($this->request->get('search', ''))),
                'sort_by'            => sanitize_sql_orderby($this->request->get('sort_by', 'id')),
                'sort_type'          => sanitize_sql_orderby($this->request->get('sort_type', 'DESC')),
                'has_commerce'       => $this->request->get('has_commerce'),
                'custom_fields'      => $this->request->get('custom_fields') == 'true',
                'company_ids'        => $this->request->get('company_ids', []),
            ];
        } else {
            $queryArgs = [
                'with'          => $with,
                'filter_type'   => 'simple',
                'search'        => trim(sanitize_text_field($this->request->get('search', ''))),
                'sort_by'       => sanitize_sql_orderby($this->request->get('sort_by', 'id')),
                'sort_type'     => sanitize_sql_orderby($this->request->get('sort_type', 'DESC')),
                'has_commerce'  => $this->request->get('has_commerce'),
                'custom_fields' => $this->request->get('custom_fields') == 'true',
                'tags'          => $this->request->get('tags', []),
                'statuses'      => $this->request->get('statuses', []),
                'lists'         => $this->request->get('lists', []),
                'company_ids'   => $this->request->get('company_ids', []),
            ];
        }

        $subscribers = (new ContactsQuery($queryArgs))->paginate();

        return $this->sendSuccess([
            'subscribers' => $subscribers,
            'custom'      => $this->request->get('custom_fields')
        ]);
    }

    /**
     * Find a subscriber by id
     *
     * @return \WP_REST_Response $object
     */
    public function show()
    {
        $with = $this->request->get('with', []);

        $contactId = $this->request->get('id');

        $defaultWith = ['tags', 'lists'];

        if (Helper::isCompanyEnabled()) {
            $defaultWith[] = 'companies';
        }

        $subscriber = false;
        if ($contactId) {
            $subscriber = Subscriber::with($defaultWith)->find($contactId);
        } else if ($byEmail = $this->request->get('get_by_email')) {
            $subscriber = Subscriber::with($defaultWith)->where('email', sanitize_email($byEmail))->first();
        }

        if (!$subscriber) {
            return $this->sendError([
                'message' => __('Subscriber not found', 'fluent-crm')
            ]);
        }

        if (in_array('commerce_stat', $with)) {
            $subscriber->commerce_stat = [];
            /**
             * Determine the commerce provider for FluentCRM.
             *
             * This filter allows you to modify the commerce provider used in FluentCRM.
             *
             * @param string The current commerce provider.
             * @since 2.5.1
             *
             */
            $commerceProvider = apply_filters('fluentcrm_commerce_provider', '');
            if ($commerceProvider) {
                /**
                 * Determine the purchase statistics for a specific subscriber and commerce provider.
                 *
                 * This filter allows modification of the purchase statistics for a given subscriber
                 * based on the specified commerce provider.
                 *
                 * @param array  The current purchase statistics for the subscriber.
                 * @param int $subscriber_id The ID of the subscriber.
                 *
                 * @return array Modified purchase statistics for the subscriber.
                 * @since 2.7.0
                 *
                 */
                $subscriber->commerce_stat = apply_filters('fluent_crm/contact_purchase_stat_' . $commerceProvider, [], $subscriber->id);
            }
        }

        if ($wpUser = $subscriber->getWpUser()) {
            $subscriber->user_edit_url = get_edit_user_link($wpUser->ID);
            $subscriber->user_roles = array_values($wpUser->roles);
        }

        if (in_array('stats', $with)) {
            $subscriber->stats = $subscriber->stats();
        }

        if (in_array('subscriber.custom_values', $with)) {
            $subscriber->custom_values = (object)$subscriber->custom_fields();
        }

        if ($subscriber->date_of_birth == '0000-00-00') {
            $subscriber->date_of_birth = '';
        }

        if ($subscriber->status == 'unsubscribed') {
            $subscriber->unsubscribe_reason = $subscriber->unsubscribeReason();
            $subscriber->unsubscribe_date = $subscriber->unsubscribeReasonDate();
        } else if ($subscriber->status == 'bounced' || $subscriber->status == 'complained') {
            $subscriber->unsubscribe_reason = $subscriber->unsubscribeReason('reason');
            $subscriber->unsubscribe_date = $subscriber->unsubscribeReasonDate('reason');
        }

        $data = [
            'subscriber' => $subscriber
        ];

        if (in_array('custom_fields', $with)) {
            $data['custom_fields'] = fluentcrm_get_option('contact_custom_fields', []);
        }


        return $this->sendSuccess($data);
    }

    public function updateProperty()
    {
        $column = $this->request->getSafe('property');
        $value = $this->request->getSafe('value');
        $subscriberIds = $this->request->getSafe('subscribers', [], 'intval');

        $validColumns = ['status', 'contact_type', 'avatar', 'company_id'];
        $subscriberStatuses = fluentcrm_subscriber_statuses();
        $leadStatuses = fluentcrm_contact_types();

        $this->validate([
            'column'         => $column,
            'subscriber_ids' => $subscriberIds
        ], [
            'column'         => 'required',
            'subscriber_ids' => 'required'
        ]);

        if (!in_array($column, $validColumns)) {
            return $this->sendError([
                'message' => __('Column is not valid', 'fluent-crm')
            ]);
        }

        if ($column == 'status' && !in_array($value, $subscriberStatuses)) {
            return $this->sendError([
                'message' => __('Value is not valid', 'fluent-crm')
            ]);
        } else if ($column == 'contact_type' && !isset($leadStatuses[$value])) {
            return $this->sendError([
                'message' => __('Value is not valid', 'fluent-crm')
            ]);
        } else if ($column == 'company_id') {
            Company::findOrFail($value); // just a check
        }

        $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();

        foreach ($subscribers as $subscriber) {
            $oldValue = $subscriber->{$column};
            if ($oldValue != $value) {
                $subscriber->{$column} = $value;
                $subscriber->save();
                if (in_array($column, ['status', 'contact_type'])) {
                    do_action('fluentcrm_subscriber_' . $column . '_to_' . $value, $subscriber, $oldValue);

                    if ($column == 'status') {
                        /**
                         * Contact's Status has been changed
                         *
                         * @param Subscriber $subscriber Subscriber Model.
                         * @param string $oldStatus Old Status.
                         * @since 1.0
                         *
                         */
                        do_action('fluent_crm/subscriber_status_changed', $subscriber, $oldValue, $value);
                    }

                }
                if ($column == 'avatar') {
                    do_action('fluent_crm/subscriber_avatar_update', $subscriber, $oldValue);
                }
            }
        }

        return $this->sendSuccess([
            'message' => __('Subscribers successfully updated', 'fluent-crm')
        ]);
    }

    public function deleteSubscriber(Request $request, $id)
    {
        $subscriber = Subscriber::findOrFail($id);

        Helper::deleteContacts([$subscriber->id]);

        return $this->sendSuccess([
            'message' => __('Selected Subscriber has been deleted successfully', 'fluent-crm')
        ]);
    }

    public function deleteSubscribers(Request $request)
    {
        $subscriberIds = $request->get('subscribers');

        $this->validate(
            ['subscriber_ids' => $subscriberIds],
            ['subscriber_ids' => 'required']
        );

        Helper::deleteContacts($subscriberIds);

        return $this->sendSuccess([
            'message' => __('Selected Subscribers has been deleted', 'fluent-crm')
        ]);
    }

    /**
     * Tag a subscriber with Tags or Lists.
     */
    public function tagger()
    {
        $model = $this->resolveModel();

        $subscribers = $this->request->get('subscribers');
        $attachments = $this->attachments($model, 'attach');
        $detachments = $this->attachments($model, 'detach');

        $type = $this->request->get('type');

        foreach ($subscribers as $subscriberId) {
            $subscriber = Subscriber::find($subscriberId);
            if ($attachments) {
                if ($type == 'tags') {
                    $subscriber->attachTags($attachments);
                } else {
                    $subscriber->attachLists($attachments);
                }
            }

            if ($detachments) {
                if ($type == 'tags') {
                    $subscriber->detachTags($detachments);
                } else {
                    $subscriber->detachLists($detachments);
                }
            }
        }

        return $this->sendSuccess([
            'message'     => __('Successfully updated the ', 'fluent-crm') . _n('subscriber', 'subscribers', count($subscribers)) . '.',
            'subscribers' => Subscriber::with('tags', 'lists')->whereIn('id', $subscribers)->get()
        ]);
    }

    /**
     * Store a subscriber.
     *
     * @return array
     */
    public function store(Request $request)
    {
        $forceUpdate = $request->get('__force_update') == 'yes';

        if (!$forceUpdate) {
            $data = $this->validate($request->all(), [
                'email'  => 'required|email|unique:fc_subscribers',
                'status' => 'required'
            ], [
                'email.unique' => __('Provided email already assigned to another subscriber.', 'fluent-crm')
            ]);
        } else {
            $data = $this->validate($request->all(), [
                'email'  => 'required|email',
                'status' => 'required'
            ]);
        }

        unset($data['__force_update']);

        $data = Sanitize::contact($data);

        $user = get_user_by('email', $data['email']);

        if ($user) {
            $data['user_id'] = $user->ID;
        } else {
            $data['user_id'] = '';
        }

        if ($this->isNew()) {
            $data['created_at'] = current_time('mysql');

            $contact = FluentCrmApi('contacts')->createOrUpdate($data, false, false);

            /**
             * new Contact has been created
             *
             * @param Subscriber $contact Subscriber Model.
             * @param array $data Original raw subscriber.
             * @since 3.30.2
             */
//            do_action('fluentcrm_contact_created', $contact); // @deprecated since 2.8.0. Use fluent_crm/contact_created instead
//            do_action('fluent_crm/contact_created', $contact);
            // no need these action hooks because those are already in the updateOrCreate method in Subscriber model

            $double_optin = filter_var($request->get('double_optin'), FILTER_VALIDATE_BOOLEAN);

            if ($double_optin) {
                $contact->sendDoubleOptinEmail();
            }

            return [
                'message'     => __('Successfully added the subscriber.', 'fluent-crm'),
                'contact'     => $contact,
                'action_type' => 'created'
            ];

        } else if ($forceUpdate) {
            $contact = FluentCrmApi('contacts')->createOrUpdate($data, false, false);

            if ($contact && $contact->status == 'pending') {
                $contact->sendDoubleOptinEmail();
            }

            return $this->sendSuccess([
                'message'     => __('contact has been successfully updated.', 'fluent-crm'),
                'contact'     => $contact,
                'action_type' => 'updated'
            ]);
        }

        return $this->sendError([
            'message' => __('Sorry contact already exist', 'fluent-crm')
        ], 422);
    }

    public function bulkAddUpdate(Request $request)
    {
        $contacts = $request->getJson('contacts', []);
        $invalids = [];
        $created = [];
        $updated = [];

        $double_optin = filter_var($request->get('double_optin'), FILTER_VALIDATE_BOOLEAN);
        $forceUpdate = filter_var($request->get('force_update'), FILTER_VALIDATE_BOOLEAN);

        foreach ($contacts as $contact) {
            $contactData = Sanitize::contact($contact);
            if (empty($contactData['email']) || !is_email($contactData['email'])) {
                $invalids[] = $contactData;
                continue;
            }

            $contactData['tags'] = Arr::get($contact, 'tags', []);
            $contactData['lists'] = Arr::get($contact, 'lists', []);
            $createdContact = FluentCrmApi('contacts')->createOrUpdate($contactData, $forceUpdate, false);

            if (!$createdContact) {
                $invalids[] = $contactData;
                continue;
            }

            if ($createdContact->status == 'pending' && $double_optin) {
                $createdContact->sendDoubleOptinEmail();
            }

            if ($contact->wasRecentlyCreated) {
                $created[] = [
                    'id'     => $createdContact->id,
                    'email'  => $createdContact->email,
                    'status' => $createdContact->status,
                ];
            } else {
                $updated[] = [
                    'id'     => $createdContact->id,
                    'email'  => $createdContact->email,
                    'status' => $createdContact->status,
                ];
            }
        }

        return [
            'message'  => __('Successfully added/updated the subscribers.', 'fluent-crm'),
            'created'  => $created,
            'updated'  => $updated,
            'invalids' => $invalids
        ];
    }

    public function updateSubscriber(Request $request, $id)
    {
        $subscriber = Subscriber::findOrFail($id);
        $originalData = $request->getJson('subscriber');

        if (!$originalData) {
            $originalData = $request->all();
        }

        $data = [];
        if (isset($originalData['email'])) {
            $data = $this->validate($originalData, [
                'email' => 'required|email|unique:fc_subscribers,email,' . $id,
            ], [
                'email.unique' => __('Provided email already assigned to another subscriber.', 'fluent-crm')
            ]);
        } else {
            $data = $originalData;
        }

        if (isset($data['email'])) {
            // Maybe update user id
            $user = get_user_by('email', $data['email']);
            /**
             * Determine whether to update the WordPress user email on change.
             *
             * This filter allows you to control whether the WordPress user email should be updated
             * when there is a change in the FluentCRM subscriber email.
             *
             * @param bool Whether to update the WordPress user email on change. Default false.
             * @since 2.9.25
             *
             */
            if (!$user && apply_filters('fluentcrm_update_wp_user_email_on_change', false)) {
                $user = get_user_by('ID', $data['user_id']);
            }

            $data['user_id'] = $user ? $user->ID : NULL;
        }

        if (!empty($data['user_id'])) {
            $data['user_id'] = (int)$data['user_id'];
        }

        if (isset($data['date_of_birth']) && empty($data['date_of_birth'])) {
            $data['date_of_birth'] = '0000-00-00';
        }

        $validData = Sanitize::contact($data);

        unset($validData['created_at']);
        unset($validData['last_activity']);
        $customValues = Arr::get($originalData, 'custom_values', []);


        $oldEmail = $subscriber->email;

        $subscriber->fill($validData);

        $dirtyFields = $subscriber->getDirty();


        if ($dirtyFields) {
            $subscriber->save();
        }

        if ($customValues) {
            $subscriber->syncCustomFieldValues($customValues, true);
        }

        if ($tags = Arr::get($originalData, 'attach_tags', [])) {
            $subscriber->attachTags($tags);
        }

        if ($lists = Arr::get($originalData, 'attach_lists', [])) {
            $subscriber->attachLists($lists);
        }

        if ($detachTags = Arr::get($originalData, 'detach_tags', [])) {
            $subscriber->detachTags($detachTags);
        }

        if ($detachLists = Arr::get($originalData, 'detach_lists', [])) {
            $subscriber->detachLists($detachLists);
        }

        if ($dirtyFields) {

            if (isset($dirtyFields['email'])) {
                /**
                 * Contact's Email address has been updated
                 *
                 * @param Subscriber $subscriber Subscriber Model.
                 * @param string $oldEmail Old Email Address.
                 * @since 1.0
                 *
                 */
                do_action('fluent_crm/contact_email_changed', $subscriber, $oldEmail);
            }

            do_action('fluentcrm_contact_updated', $subscriber, $dirtyFields);
            do_action('fluent_crm/contact_updated', $subscriber, $dirtyFields);

        }

        return $this->sendSuccess([
            'message' => __('Subscriber successfully updated', 'fluent-crm'),
            'contact' => $subscriber,
            'isDirty' => !!$dirtyFields,
            'values'  => $customValues
        ], 200);
    }

    /**
     * Resolve the appropriate model e.g. Tag or, Lists
     *
     * @return string
     */
    private function resolveModel()
    {
        $type = $this->request->type;

        return 'FluentCrm\App\Models\\' . ($type === 'tags' ? 'Tag' : 'Lists');
    }

    /**
     * Get the attachment options e.g. attach or, detach
     *
     * @param \FluentCrm\App\Models\Model $model
     * @param string $type
     * @return array
     */
    private function attachments($model, $type = 'attach')
    {
        $attachments = $this->request->get($type, []);
        $findBy = sanitize_text_field($this->request->get('find_by', 'slug'));

        if ($attachments) {
            $items = $model::select('id')->whereIn($findBy, $attachments)->get();

            if (!$items->isEmpty()) {
                return array_map(function ($item) {
                    return $item['id'];
                }, $items->toArray());
            }
        }

        return [];
    }

    /**
     * Handles if subscriber already exist.
     *
     * @return bool
     */
    private function isNew()
    {
        $subscriber = Subscriber::where(
            'email', $this->request->getSafe('email', '', 'sanitize_email')
        )->first();

        if ($subscriber) {
            return false;
        }

        return true;
    }

    public function emails(Request $request, $subscriberId)
    {
        $filter = sanitize_text_field(Arr::get($request->get(), 'filter'));

        $emailsQuery = CampaignEmail::where('subscriber_id', $subscriberId)
            ->orderBy('id', 'DESC');

        // Apply filter if present
        if ($filter == 'open') {
            $emailsQuery->where('is_open', '1');
        } elseif ($filter == 'click') {
            $emailsQuery->whereNotNull('click_counter');
        } elseif ($filter == 'unopened') {
            $emailsQuery->where('is_open', '==', 0);
        }

        $emails = $emailsQuery->paginate();
        $tab = Arr::get($request->all(), 'tab', 'crm');

        if (defined('FLUENTMAIL_PLUGIN_FILE') && $tab == 'fluentsmtp') {
            $getLogsByCurrentUser = Subscriber::where('id', $subscriberId)->pluck('email')->first();

            $emails = [];

            if (!empty($getLogsByCurrentUser)) {
                $emails = fluentMailDb()->table(FLUENT_MAIL_DB_PREFIX . 'email_logs')
                    ->where('to', 'LIKE', '%' . $getLogsByCurrentUser . '%')
                    ->orderBy('id', 'DESC')
                    ->paginate();

                // Format the email log results
                $emails['data'] = $this->formatResult($emails['data']);
            }
        }

        /**
         * Determine and retrieve emails for a subscriber.
         *
         * This filter allows modifying the list of emails associated with a subscriber.
         *
         * @param array {
         *     Array containing the email data.
         *
         * @type array $emails List of email records or paginated results.
         * }
         * @param int $subscriberId The ID of the subscriber.
         *
         * @return array Filtered email data for the subscriber.
         * @since 1.0.0
         *
         */
        return apply_filters('fluentcrm_contact_emails', [
            'emails' => $emails
        ], $subscriberId);
    }

    protected function formatResult($result)
    {
        $result = is_array($result) ? $result : func_get_args();

        foreach ($result as $key => $row) {
            $result[$key] = array_map('maybe_unserialize', (array)$row);
            $result[$key]['id'] = (int)$result[$key]['id'];
            $result[$key]['retries'] = (int)$result[$key]['retries'];
            $result[$key]['from'] = htmlspecialchars($result[$key]['from']);
            $result[$key]['subject'] = wp_kses_post(wp_unslash($result[$key]['subject']));
        }

        return $result;
    }

    public function deleteEmails(Request $request, $subscriberId)
    {
        $emailIds = array_map('intval', $request->get('email_ids'));
        CampaignEmail::where('subscriber_id', $subscriberId)
            ->whereIn('id', $emailIds)
            ->delete();
        return [
            'message' => __('Selected emails has been deleted', 'fluent-crm')
        ];
    }

    public function getNotes()
    {

        $subscriberId = $this->request->get('id');
        $search = $this->request->get('search');

        $notes = SubscriberNote::where('subscriber_id', $subscriberId);

        if (!empty($search)) {
            $notes = $notes->where('title', 'LIKE', '%' . $search . '%');
        }

        $notes = $notes->orderBy('id', 'DESC')
            ->paginate();

        foreach ($notes as $note) {
            $note->added_by = $note->createdBy();
        }
        $fields['fields'] = Helper::getNoteSyncFields();


        return $this->sendSuccess([
            'notes'  => $notes,
            'fields' => $fields
        ]);
    }

    public function addNote(Request $request, $id)
    {
        $subscriber = Subscriber::findOrFail($id);
        $note = $this->validate($request->get('note'), [
            'title'       => 'required',
            'description' => 'required',
            'type'        => 'required',
            'created_at'  => 'sometimes|datetime'
        ]);

        if (empty($note['created_at'])) {
            $note['created_at'] = current_time('mysql');
        }

        /**
         * Parse the Subscriber's Note Description.
         *
         * @param string $note ['description'] The subscriber's note description.
         * @param object $subscriber The subscriber object.
         * @since 2.8.44
         *
         */
        $note['description'] = apply_filters('fluent_crm/parse_campaign_email_text', $note['description'], $subscriber);

        $note['subscriber_id'] = $id;

        $note = Sanitize::contactNote($note);

        $subscriberNote = SubscriberNote::create(wp_unslash($note));

        /**
         * Subscriber's Note Added
         *
         * @param SubscriberNote $subscriberNote Note Model.
         * @param Subscriber $subscriber Contact Model.
         * @param array $note Contact Note Data Array.
         * @since 1.0
         */
        do_action('fluent_crm/note_added', $subscriberNote, $subscriber, $note);

        return $this->sendSuccess([
            'note'    => $subscriberNote,
            'message' => __('Note successfully added', 'fluent-crm')
        ]);
    }

    public function updateNote(Request $request, $id, $noteId)
    {
        $subscriber = Subscriber::findOrFail($id);

        $note = $this->validate($request->get('note'), [
            'title'       => 'required',
            'description' => 'required',
            'type'        => 'required',
            'created_at'  => 'sometimes|datetime'
        ]);

        $note = Arr::only(wp_unslash($note), ['title', 'description', 'type', 'created_at']);

        if (empty($note['created_at'])) {
            unset($note['created_at']);
        }

        /**
         * Parse the campaign email text for Subscriber's Note Description.
         *
         * This filter allows you to modify the campaign email text before it is processed for a Subscriber's Note Description.
         *
         * @param string $note ['description'] Subscriber's Note Description from parsed campaign email text.
         * @param object $subscriber The subscriber object data.
         * @since 2.8.44
         *
         */
        $note['description'] = apply_filters('fluent_crm/parse_campaign_email_text', $note['description'], $subscriber);

        $note = Sanitize::contactNote($note);

        $subsciberNote = SubscriberNote::find($noteId);
        $subsciberNote->fill($note);
        $subsciberNote->save();

        /**
         * Subscriber's Note Updated
         *
         * @param SubscriberNote $subscriberNote Note Model.
         * @param Subscriber $subscriber Contact Model.
         * @param array $note Contact Note Data Array.
         * @since 1.0
         */
        do_action('fluent_crm/note_updated', $subsciberNote, $subscriber, $note);

        return $this->sendSuccess([
            'note'    => $subsciberNote,
            'message' => __('Note successfully updated', 'fluent-crm')
        ]);
    }

    public function deleteNote($id, $noteId)
    {
        $subscriber = Subscriber::findOrFail($id);
        SubscriberNote::where('id', $noteId)->delete();

        /**
         * Subscriber's Note Delete
         *
         * @param SubscriberNote $subscriberNote Note Model.
         * @param Subscriber $subscriber Contact Model.
         * @since 1.0
         */
        do_action('fluent_crm/note_delete', $noteId, $subscriber);

        return $this->sendSuccess([
            'message' => __('Note successfully deleted', 'fluent-crm')
        ]);
    }

    public function getFormSubmissions()
    {
        $provider = $this->request->get('provider');
        $subscriberId = intval($this->request->get('id'));
        $subscriber = Subscriber::where('id', $subscriberId)->first();

        /**
         * Filter the form submissions data for a specific provider.
         *
         * The dynamic portion of the hook name, `$provider`, refers to the form provider.
         *
         * @param array {
         *     An array of form submissions data.
         *
         * @type array $data The form submissions data.
         * @type int $total The total number of form submissions.
         * }
         * @param object $subscriber The subscriber object.
         * @since 2.5.1
         *
         */
        $data = apply_filters('fluentcrm_get_form_submissions_' . $provider, [
            'data'  => [],
            'total' => 0
        ], $subscriber);

        return $this->sendSuccess([
            'submissions' => $data
        ]);
    }

    public function getSupportTickets()
    {
        $provider = $this->request->get('provider');
        $subscriberId = intval($this->request->get('id'));
        $subscriber = Subscriber::where('id', $subscriberId)->first();

        /**
         * Determine the support tickets data for a specific provider and subscriber.
         *
         * The dynamic portion of the hook name, `$provider`, refers to the support ticket provider.
         *
         * @param array {
         *     An array of support tickets data.
         *
         * @type array $data The support tickets data.
         * @type int $total The total number of support tickets.
         * }
         * @param object $subscriber The subscriber object.
         * @since 2.5.1
         *
         */
        $data = apply_filters('fluentcrm-get_support_tickets_' . $provider, [
            'data'  => [],
            'total' => 0
        ], $subscriber);

        $data['columns_config'] = [
            'id'           => [
                'label' => __('ID', 'fluent-crm'),
                'width' => '100px'
            ],
            'status'       => [
                'label' => 'Status',
                'width' => '120px'
            ],
            'Submitted at' => [
                'label' => 'Submitted at',
                'width' => '150px'
            ],
            'action'       => [
                'label' => 'Action',
                'width' => '150px'
            ]
        ];

        return $this->sendSuccess([
            'tickets' => $data
        ]);
    }

    public function sendDoubleOptinEmail(Request $request, $id)
    {
        $subscriber = Subscriber::where('id', $id)->first();

        if ($subscriber == 'subscribed') {
            return $this->sendError([
                'message' => __('Contact Already Subscribed', 'fluent-crm')
            ]);
        }

        $subscriber->sendDoubleOptinEmail();

        return $this->sendSuccess([
            'message' => __('Double OptIn email has been sent', 'fluent-crm')
        ]);
    }

    public function getTemplateMock(Request $request, $id)
    {
        $emailMock = CustomEmailCampaign::getMock();
        $emailMock['title'] = __('Custom Email to Contact', 'fluent-crm');
        return [
            'email_mock' => $emailMock
        ];
    }

    public function sendCustomEmail(Request $request, $contactId)
    {
        $contact = Subscriber::findOrFail($contactId);

        $validStatuses = ['subscribed', 'transactional'];

        if (!in_array($contact->status, $validStatuses)) {
            return $this->sendError([
                'message' => __('Subscriber\'s status need to be subscribed.', 'fluent-crm')
            ]);
        }

        add_action('wp_mail_failed', function ($wpError) {
            return $this->sendError([
                'message' => $wpError->get_error_message()
            ]);
        }, 10, 1);

        $newCampaign = $request->get('campaign');
        unset($newCampaign['id']);

        $newCampaign = Sanitize::campaign($newCampaign);

        $campaign = CustomEmailCampaign::create($newCampaign);

        $campaign->subscribe([$contactId], [
            'status'       => 'scheduled',
            'scheduled_at' => current_time('mysql')
        ]);

        do_action('fluentcrm_process_contact_jobs', $contact);

        return [
            'message' => __('Custom Email has been successfully sent', 'fluent-crm')
        ];
    }

    public function getExternalView(Request $request, $subscriberId)
    {
        $subscriber = Subscriber::findOrFail($subscriberId);
        $sectionId = $request->get('section_provider');

        /**
         * Filter the profile section content for a specific section ID.
         *
         * The dynamic portion of the hook name, `$sectionId`, refers to the ID of the profile section.
         *
         * @param array {
         *     An array of profile section data.
         *
         * @type string $heading The heading of the profile section.
         * @type string $content_html The HTML content of the profile section.
         * }
         * @param object $subscriber The subscriber object.
         * @since 2.5.1
         *
         */
        return apply_filters('fluencrm_profile_section_' . $sectionId, [
            'heading'      => '',
            'content_html' => ''
        ], $subscriber);
    }


    public function saveExternalViewData(Request $request, $subscriberId)
    {
        $subscriber = Subscriber::findOrFail($subscriberId);
        $sectionId = $request->get('section_provider');

        /**
         * Filter the data being saved for a specific profile section.
         *
         * This filter allows modifying the data before saving it for a profile section
         * identified by the `$sectionId`.
         *
         * @param mixed  The data to be saved for the profile section. Defaults to an empty string.
         * @param array  The input data received from the request. Defaults to an empty array.
         * @param object $subscriber The subscriber object for which the profile section is being updated.
         *
         * @return mixed Filtered data to be saved for the profile section.
         * @since 2.8.44
         *
         */
        $response = apply_filters('fluencrm_profile_section_save_' . $sectionId, '', $request->get('data', []), $subscriber);

        if (!$response) {
            return $this->sendError([
                'message' => __('Handled could not be found.', 'fluent-crm')
            ]);
        }

        return $response;
    }

    public function handleBulkActions(Request $request)
    {
        $actionName = sanitize_text_field($request->get('action_name', ''));
        $doingAllBulk = $request->get('is_all') == 'yes';

        if ($doingAllBulk) {

            $contactQuery = $request->get('contact_query', []);

            $filterType = Arr::get($contactQuery, 'filter_type', 'simple');

            $with = [];

            if ($filterType == 'advanced') {

                $rawGroup = json_decode(Arr::get($contactQuery, 'advanced_filters', ''), true);

                if (!$rawGroup || !is_array($rawGroup)) {
                    return $this->sendError([
                        'message' => __('Invalid Advanced Filters', 'fluent-crm')
                    ]);
                }

                $queryArgs = [
                    'with'               => $with,
                    'filter_type'        => 'advanced',
                    'filters_groups_raw' => $rawGroup,
                    'search'             => trim(Arr::get($contactQuery, 'search', '')),
                    'sort_by'            => 'id',
                    'sort_type'          => 'ASC',
                    'has_commerce'       => false,
                    'custom_fields'      => false,
                    'company_ids'        => Arr::get($contactQuery, 'company_ids', []),
                ];
            } else {
                $queryArgs = [
                    'with'          => $with,
                    'filter_type'   => 'simple',
                    'search'        => trim(Arr::get($contactQuery, 'search', '')),
                    'sort_by'       => 'id',
                    'sort_type'     => 'ASC',
                    'has_commerce'  => false,
                    'custom_fields' => false,
                    'tags'          => Arr::get($contactQuery, 'tags', []),
                    'statuses'      => Arr::get($contactQuery, 'statuses', []),
                    'lists'         => Arr::get($contactQuery, 'lists', []),
                    'company_ids'   => Arr::get($contactQuery, 'company_ids', []),
                ];
            }

            $subscribersModel = (new ContactsQuery($queryArgs))->getModel();
            $lastId = $request->get('last_id', 0);

            $subscribersModel = $subscribersModel->select(['id'])
                ->limit(400)
                ->where('id', '>', $lastId)
                ->get();

            if ($subscribersModel->isEmpty()) {
                return [
                    'is_completed'       => true,
                    'completed_contacts' => 0,
                    'message'            => __('All contacts has been processed', 'fluent-crm')
                ];
            }

            $subscriberIds = $subscribersModel->pluck('id')->toArray();

        } else {
            $subscriberIds = array_map('intval', $request->get('subscriber_ids', []));
            $subscriberIds = array_filter($subscriberIds);
        }

        $lastContactId = end($subscriberIds);

        if (!$subscriberIds) {
            return $this->sendError([
                'message' => __('Subscribers selection is required', 'fluent-crm')
            ]);
        }

        if ($actionName == 'delete_contacts') {
            Helper::deleteContacts($subscriberIds);
            return $this->sendSuccess([
                'completed_contacts' => count($subscriberIds),
                'last_contact_id'    => $lastContactId,
                'message'            => __('Selected Contacts has been deleted permanently', 'fluent-crm'),
            ]);
        } elseif ($actionName == 'send_double_optin') {
            Helper::sendDoubleOptin($subscriberIds);
            return $this->sendSuccess([
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => __('Double optin sent to selected contacts', 'fluent-crm'),
            ]);
        } elseif ($actionName == 'add_to_email_sequence') {
            if (!defined('FLUENTCAMPAIGN')) {
                return $this->sendError([
                    'message' => __('This action requires FluentCRM Pro', 'fluent-crm')
                ]);
            }

            $sequenceId = (int)$request->get('new_status', '');

            if (!$sequenceId) {
                return $this->sendError([
                    'message' => __('Invalid Email Sequence ID', 'fluent-crm')
                ]);
            }

            $sequence = \FluentCampaign\App\Models\Sequence::findOrFail($sequenceId);

            $validSubscribers = Subscriber::whereIn('id', $subscriberIds)
                ->whereDoesntHave('sequences', function ($q) use ($sequenceId) {
                    $q->where('fc_campaigns.id', $sequenceId);
                })
                ->where('status', 'subscribed')
                ->get();

            if ($validSubscribers->isEmpty()) {
                if ($doingAllBulk) {
                    return $this->sendSuccess([
                        'last_contact_id'    => $lastContactId,
                        'completed_contacts' => count($subscriberIds),
                        'message'            => __('No valid active subscribers found for this chunk', 'fluent-crm')
                    ]);
                }
                return $this->sendError([
                    'message' => __('No valid active subscribers found for this sequence', 'fluent-crm')
                ]);
            }

            $sequence->subscribe($validSubscribers);
            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => sprintf(__('%d subscribers has been attached to the selected email sequence', 'fluent-crm'), count($validSubscribers))
            ];

        } elseif ($actionName == 'add_to_company') {
            $companyId = (int)$request->get('new_status', '');

            if (!$companyId) {
                return $this->sendError([
                    'message' => __('Invalid Company ID', 'fluent-crm')
                ]);
            }

            $company = Company::findOrFail($companyId);

            $validSubscribers = Subscriber::whereIn('id', $subscriberIds)
                ->whereDoesntHave('companies', function ($q) use ($companyId) {
                    $q->where('fc_companies.id', $companyId);
                })
                ->get();

            if ($validSubscribers->isEmpty()) {

                if ($doingAllBulk) {
                    return $this->sendSuccess([
                        'last_contact_id'    => $lastContactId,
                        'completed_contacts' => count($subscriberIds),
                        'message'            => __('No valid active subscribers found for this chunk', 'fluent-crm')
                    ]);
                }

                return $this->sendError([
                    'message' => __('No valid active subscribers found for this company', 'fluent-crm')
                ]);
            }

            foreach ($validSubscribers as $contact) {
                if ($contact) {
                    $contact->attachCompanies([$company->id]);
                    if (!$contact->company_id) {
                        $contact->company_id = $company->id;
                        $contact->save();
                    }
                }
            }
            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => sprintf(__('%d subscribers has been attached to the selected company', 'fluent-crm'), count($validSubscribers))
            ];

        } elseif ($actionName == 'remove_from_company') {
            $companyId = (int)$request->get('new_status', '');

            if (!$companyId) {
                return $this->sendError([
                    'message' => __('Invalid Company ID', 'fluent-crm')
                ]);
            }

            $company = Company::findOrFail($companyId);

            $validSubscribers = Subscriber::whereIn('id', $subscriberIds)
                ->whereHas('companies', function ($q) use ($companyId) {
                    $q->where('fc_companies.id', $companyId);
                })
                ->get();

            if ($validSubscribers->isEmpty()) {

                if ($doingAllBulk) {
                    return $this->sendSuccess([
                        'last_contact_id'    => $lastContactId,
                        'completed_contacts' => count($subscriberIds),
                        'message'            => __('No valid active subscribers found for this chunk', 'fluent-crm')
                    ]);
                }

                return $this->sendError([
                    'message' => __('No valid active subscribers found for this company', 'fluent-crm')
                ]);
            }

            foreach ($validSubscribers as $contact) {
                if ($contact) {
                    $contact->detachCompanies([$company->id]);
                    if ($contact->company_id == $company->id) {
                        $contact->company_id = null;
                        $contact->save();
                    }
                }
            }
            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => sprintf(__('%d subscribers has been detached from the selected company', 'fluent-crm'), count($validSubscribers))
            ];

        } elseif ($actionName == 'change_contact_status') {
            $newStatus = sanitize_text_field($request->get('new_status', ''));
            if (!$newStatus) {
                return $this->sendError([
                    'message' => __('Please select status', 'fluent-crm')
                ]);
            }

            $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();

            foreach ($subscribers as $subscriber) {
                $oldStatus = $subscriber->status;
                if ($oldStatus != $newStatus) {
                    $subscriber->updateStatus($newStatus);
                }
            }

            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => __('Status has been changed for the selected subscribers', 'fluent-crm')
            ];
        } elseif ($actionName == 'add_to_automation') {
            if (!defined('FLUENTCAMPAIGN')) {
                return $this->sendError([
                    'message' => __('This action requires FluentCRM Pro', 'fluent-crm')
                ]);
            }

            $automationId = (int)$request->get('new_status', '');

            if (!$automationId) {
                return $this->sendError([
                    'message' => __('Invalid Automation Funnel ID', 'fluent-crm')
                ]);
            }

            $automation = Funnel::findOrFail($automationId);

            $validSubscribers = Subscriber::whereIn('id', $subscriberIds)
                ->whereDoesntHave('funnels', function ($q) use ($automationId) {
                    $q->where('fc_funnels.id', $automationId);
                })
                ->get();

            if ($validSubscribers->isEmpty()) {

                if ($doingAllBulk) {
                    return $this->sendSuccess([
                        'last_contact_id'    => $lastContactId,
                        'completed_contacts' => count($subscriberIds),
                        'message'            => __('No valid active subscribers found for this chunk', 'fluent-crm')
                    ]);
                }

                return $this->sendError([
                    'message' => __('No valid active subscribers found for this funnel', 'fluent-crm')
                ]);
            }

            foreach ($validSubscribers as $subscriber) {
                (new FunnelProcessor())->startFunnelSequence($automation, [], [
                    'source_trigger_name' => 'fcrm_manual_attach'
                ], $subscriber);
            }

            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => sprintf(__('%d subscribers has been attached to the selected automation funnel', 'fluent-crm'), count($validSubscribers)),
                'subscribers'        => $validSubscribers
            ];
        } else if ($actionName == 'change_contact_type') {
            $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();
            $newType = sanitize_text_field($request->get('new_status', ''));
            if (!$newType) {
                return $this->sendError([
                    'message' => 'Please select new type'
                ]);
            }
            foreach ($subscribers as $subscriber) {
                $oldType = $subscriber->contact_type;
                if ($oldType != $newType) {
                    $subscriber->contact_type = $newType;
                    $subscriber->save();
                    do_action('fluent_crm/subscriber_contact_type_to_' . $newType, $subscriber, $oldType);
                }
            }

            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => __('Contact Type has been updated for the selected subscribers', 'fluent-crm')
            ];
        } else if ($actionName == 'update_custom_fields') {
            $customField = $request->get('custom_field');
            $customFieldKey = Arr::get($customField, 'key');
            $customFieldValue = Arr::get($customField, 'value');
            $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();

            if (empty($customFieldKey)) {
                return $this->sendError([
                    'message' => __('Please provide a valid custom field key', 'fluent-crm')
                ]);
            }

            foreach ($subscribers as $subscriber) {
                $existField = SubscriberMeta::where('key', $customFieldKey)
                    ->where('subscriber_id', $subscriber->id)
                    ->first();

                // check if exists
                if ($existField) {
                    if ($existField->value == $customFieldValue) {
                        continue;
                    }
                    $existField->fill(['value' => $customFieldValue])->save();
                } else {
                    $customFieldMeta = new SubscriberMeta();
                    $customFieldMeta->fill([
                        'subscriber_id' => $subscriber->id,
                        'object_type'   => 'custom_field',
                        'key'           => $customFieldKey,
                        'value'         => $customFieldValue,
                        'created_by'    => get_current_user_id()
                    ]);
                    $customFieldMeta->save();
                }
            }

            return [
                'last_contact_id'    => $lastContactId,
                'completed_contacts' => count($subscriberIds),
                'message'            => __('Custom Fields has been updated for the selected subscribers', 'fluent-crm')
            ];
        }

        $validActions = [
            'add_to_tags'       => 'attachTags',
            'add_to_lists'      => 'attachLists',
            'remove_from_tags'  => 'detachTags',
            'remove_from_lists' => 'detachLists'
        ];

        if (!isset($validActions[$actionName])) {
            $response = $this->sendError([
                'message' => __('Selected Action is not valid', 'fluent-crm')
            ]);

            /**
             * Filter the result of a bulk action performed on FluentCRM contacts.
             *
             * The dynamic portion of the hook name, `$actionName`, refers to the specific bulk action being performed.
             *
             * @param mixed $response The initial response for the bulk action. Can be modified by the filter.
             * @param array $subscriberIds An array of subscriber IDs targeted by the bulk action.
             * @param array $request ->all()   The full request data as an associative array.
             *
             * @return mixed Filtered response for the bulk action.
             * @since 2.9.0
             *
             */
            $result = apply_filters('fluent_crm/contact_bulk_action_' . $actionName, $response, $subscriberIds, $request->all());

            if (is_array($result)) {
                $result['last_contact_id'] = $lastContactId;
                $result['completed_contacts'] = count($subscriberIds);
            }

            return $result;
        }

        $options = $request->get('action_options', []);

        $options = array_map(function ($id) {
            return intval($id);
        }, $options);

        $options = array_filter($options);

        if (!$options) {
            return $this->sendError([
                'message' => __('Please provide bulk options', 'fluent-crm')
            ]);
        }

        $method = $validActions[$actionName];

        $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();

        foreach ($subscribers as $subscriber) {
            $subscriber->{$method}($options);
        }

        return [
            'last_contact_id'    => $lastContactId,
            'completed_contacts' => count($subscriberIds),
            'message'            => __('Selected bulk action has been successfully completed', 'fluent-crm')
        ];
    }

    public function getPrevNextIds(Request $request)
    {
        $filterType = $this->request->get('filter_type');

        $currentId = (int)$this->request->get('current_id');

        if (!$filterType || !$currentId) {
            return $this->sendError([
                'message' => 'filter_type & current_id is required'
            ]);
        }

        $sortType = sanitize_sql_orderby($this->request->get('sort_type', 'DESC'));
        $prevSortType = ($sortType == 'DESC') ? 'ASC' : 'DESC';

        if ($filterType == 'advanced') {
            $queryArgs = [
                'filter_type'        => 'advanced',
                'filters_groups_raw' => $this->request->getJson('advanced_filters'),
                'search'             => trim(sanitize_text_field($this->request->get('search', ''))),
                'sort_by'            => 'id',
                'sort_type'          => $sortType,
                'with'               => []
            ];
        } else {
            $queryArgs = [
                'filter_type' => 'simple',
                'search'      => trim(sanitize_text_field($this->request->get('search', ''))),
                'sort_by'     => 'id',
                'sort_type'   => $sortType,
                'tags'        => $this->request->get('tags', []),
                'statuses'    => $this->request->get('statuses', []),
                'lists'       => $this->request->get('lists', []),
                'with'        => []
            ];
        }

        $prevQueryArgs = $queryArgs;

        $prevQueryArgs['sort_type'] = ($sortType == 'DESC') ? 'ASC' : 'DESC';

        $prevItems = (new ContactsQuery($prevQueryArgs))
            ->getModel()
            ->select(['id'])
            ->limit(10)
            ->where('id', ($sortType == 'DESC') ? '>' : '<', $currentId)
            ->get();

        $nextItems = (new ContactsQuery($queryArgs))
            ->getModel()
            ->select(['id'])
            ->limit(10)
            ->where('id', ($sortType == 'DESC') ? '<' : '>', $currentId)
            ->get();

        $formattedNext = [];
        foreach ($nextItems as $nextItem) {
            $formattedNext[] = $nextItem->id;
        }

        $formattedPrev = [];
        foreach ($prevItems as $prevItem) {
            $formattedPrev[] = $prevItem->id;
        }

        return [
            'navigation' => [
                'next' => $formattedNext,
                'prev' => $formattedPrev
            ],
            'has_next'   => count($formattedNext) == 10,
            'has_prev'   => count($formattedPrev) == 10
        ];

    }

    public function searchContacts(Request $request)
    {
        $search = trim($request->getSafe('search', ''));

        $contacts = [];

        if ($search) {
            $subscribers = Subscriber::searchBy($search)->limit($request->get('limit', 20))->get();
            foreach ($subscribers as $subscriber) {
                $contacts[$subscriber->id] = [
                    'first_name' => $subscriber->first_name,
                    'last_name'  => $subscriber->last_name,
                    'full_name'  => $subscriber->full_name,
                    'email'      => $subscriber->email,
                    'id'         => (string)$subscriber->id
                ];
            }
        }

        $values = (array)$request->get('values', []);

        if ($values) {
            $pushedIds = array_keys($contacts);
            $includedIds = array_diff($values, $pushedIds);
            if ($includedIds) {
                $subscribers = Subscriber::whereIn('id', $includedIds)->get();
                foreach ($subscribers as $subscriber) {
                    $contacts[$subscriber->id] = [
                        'first_name' => $subscriber->first_name,
                        'last_name'  => $subscriber->last_name,
                        'full_name'  => $subscriber->full_name,
                        'email'      => $subscriber->email,
                        'id'         => (string)$subscriber->id
                    ];
                }
            }
        }

        return [
            'contacts' => (object)$contacts
        ];
    }

    public function getInfoWidgets(Request $request, $subscriber)
    {
        if (is_numeric($subscriber)) {
            $subscriber = Subscriber::findOrFail($subscriber);
        }


        if ($byWidget = $request->get('by_widget')) {
            /**
             * Filter the subscriber info widget.
             *
             * This filter allows modification of the subscriber info widget based on the widget type.
             *
             * @param array The array of widgets.
             * @param object $subscriber The subscriber object data.
             * @since 2.8.40
             *
             */
            $widgets = apply_filters('fluent_crm/subscriber_info_widget_' . $byWidget, [], $subscriber);
            $widgets = array_values($widgets);

            if (isset($widgets[0])) {
                $widget = $widgets[0];
            } else {
                $widget = [
                    'content' => 'No content found'
                ];
            }

            return [
                'widget' => $widget
            ];
        }

        $commerce = (new PurchaseHistory())->getCommerceStatWidget($subscriber);

        /**
         * Filter the top widgets for a subscriber.
         *
         * This filter allows modification of the top widgets displayed for a subscriber.
         *
         * @param array The array of top widgets.
         * @param object $subscriber The subscriber object.
         * @since 2.8.0
         *
         */
        $topWidgets = array_filter(apply_filters('fluent_crm/subscriber_top_widgets', array_filter([$commerce]), $subscriber));

        /**
         * Filter the subscriber info widgets.
         *
         * This filter allows modification of the subscriber info widgets.
         *
         * @param array  An array of existing widgets.
         * @param object $subscriber The subscriber object.
         * @since 2.8.0
         *
         */
        $otherWidgets = apply_filters('fluent_crm/subscriber_info_widgets', [], $subscriber);

        return [
            'widgets' => [
                'top_widgets'   => $topWidgets,
                'other_widgets' => $otherWidgets,
                'widgets_count' => count($topWidgets) + count($otherWidgets)
            ]
        ];
    }

    public function getTrackingEvents(Request $request, $subscriberId)
    {
        if (!Helper::isExperimentalEnabled('event_tracking')) {
            return $this->sendError([
                'message'    => __('Event Tracker is not enabled', 'fluent-crm'),
                'error_code' => 'not_enabled'
            ]);
        }

        $subscriber = Subscriber::findOrFail($subscriberId);
        $events = EventTracker::where('subscriber_id', $subscriber->id)
            ->orderBy('id', 'DESC')
            ->paginate();

        return [
            'events' => $events
        ];
    }

    public function trackEvent(Request $request)
    {
        $data = $request->all();

        $this->validate($data, [
            'event_key' => 'required',
            'title'     => 'required'
        ]);

        $isUnique = $request->get('repeatable', true);
        $result = FluentCrmApi('event_tracker')->track($data, $isUnique);

        if (is_wp_error($result)) {
            return $this->sendError([
                'message'    => $result->get_error_message(),
                'error_code' => $result->get_error_code()
            ]);
        }

        return $this->sendSuccess([
            'message' => __('Event has been tracked', 'fluent-crm'),
            'id'      => $result->id
        ]);
    }

    public function getUrlMetrics(Request $request, $id)
    {
        $sort_by = sanitize_sql_orderby($this->request->get('sort_by', 'id'));
        $sort_type = sanitize_sql_orderby($this->request->get('sort_type', 'DESC'));
        $subscriber = Subscriber::findOrFail($id);

        $urlActivityQuery = CampaignUrlMetric::with('url_stores')
            ->where('subscriber_id', $subscriber->id)
            ->where('type', 'click');

        // Apply custom sorting if provided
        if (!empty($sort_by) && !empty($sort_type)) {
            $urlActivityQuery->orderBy($sort_by, $sort_type);
        } else {
            $urlActivityQuery->orderBy('id', 'DESC');
        }

        $urlActivity = $urlActivityQuery->paginate();

        $urlMetrics = $urlActivity->toArray();

        if (!empty($urlMetrics['data'])) {
            $urlMetrics['data'] = $this->formatUrlActivityData($urlMetrics['data']);
        }

        return [
            'urlMetrics' => $urlMetrics
        ];
    }

    public function formatUrlActivityData($data)
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = [
                'url'   => $item['url_stores']['url'],
                'count' => $item['counter']
            ];
        }
        return $result;
    }
}
