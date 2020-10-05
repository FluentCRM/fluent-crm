<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\CampaignEmail;
use FluentCrm\Includes\Request\Request;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Models\SubscriberPivot;

class SubscriberController extends Controller
{
    public function index()
    {
        $subscribers = Subscriber::with('tags', 'lists')
            ->when($this->request->has('tags'), function ($query) {
                $query->filterByTags($this->request->get('tags'));
            })
            ->when($this->request->has('statuses'), function ($query) {
                $query->filterByStatues($this->request->get('statuses'));
            })
            ->when($this->request->has('lists'), function ($query) {
                $query->filterByLists($this->request->get('lists'));
            })
            ->when($this->request->has('search'), function ($query) {
                $query->searchBy($this->request->get('search'));
            })
            ->when($this->request->has('sort_by'), function ($query) {
                $query->orderBy($this->request->get('sort_by'), $this->request->get('sort_type'));
            });

        return $this->sendSuccess([
            'subscribers' => $subscribers->paginate()
        ]);
    }

    /**
     * Find a subscriber by id
     *
     * @return \WP_REST_Response $object
     */
    public function show()
    {
        $subscriber = Subscriber::with('tags', 'lists')->find($this->request->get('id'));

        if ($subscriber->user_id) {
            $subscriber->user_edit_url = get_edit_user_link($subscriber->user_id);
        }

        $with = $this->request->get('with', []);

        if (in_array('stats', $with)) {
            $subscriber->stats = $subscriber->stats();
        }

        if (in_array('subscriber.custom_values', $with)) {
            $subscriber->custom_values = (object)$subscriber->custom_fields();
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
        $column = sanitize_text_field($this->request->get('property'));
        $value = sanitize_text_field($this->request->get('value'));
        $subscriberIds = $this->request->get('subscribers');

        $validColumns = ['status', 'contact_type', 'avatar'];
        $subscriberStatuses = fluentcrm_subscriber_statuses();
        $leadStatuses = fluentcrm_contact_types();

        $this->validate([
            'column'         => $column,
            'value'          => $value,
            'subscriber_ids' => $subscriberIds
        ], [
            'column'         => 'required',
            'value'          => 'required',
            'subscriber_ids' => 'required'
        ]);

        if (!in_array($column, $validColumns)) {
            return $this->sendError([
                'message' => 'Column is not valid'
            ]);
        }

        if ($column == 'status' && !in_array($value, $subscriberStatuses)) {
            return $this->sendError([
                'message' => 'Value is not valid'
            ]);
        } else if ($column == 'contact_type' && !in_array($value, $leadStatuses)) {
            return $this->sendError([
                'message' => 'Value is not valid'
            ]);
        }

        $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();

        foreach ($subscribers as $subscriber) {
            $oldValue = $subscriber->{$column};
            if ($oldValue != $value) {
                $subscriber->{$column} = $value;
                $subscriber->save();
                if(in_array($column, ['status', 'contact_type'])) {
                    do_action('fluentcrm_subscriber_' . $column . '_to_' . $value, $subscriber, $oldValue);
                }
            }
        }

        Subscriber::whereIn('id', $subscriberIds)->update([$column => $value]);

        return $this->sendSuccess([
            'message' => 'Subscribers successfully updated'
        ]);
    }

    public function deleteSubscribers(Request $request)
    {
        $subscriberIds = $request->get('subscribers');

        $this->validate(
            ['subscriber_ids' => $subscriberIds],
            ['subscriber_ids' => 'required']
        );

        do_action('fluentcrm_before_subscribers_deleted', $subscriberIds);

        Subscriber::whereIn('id', $subscriberIds)->delete();

        do_action('fluentcrm_after_subscribers_deleted', $subscriberIds);

        return $this->sendSuccess([
            'message' => 'Selected Subscribers has been deleted'
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

        foreach ($subscribers as $subscriber) {
            SubscriberPivot::attach($attachments, $subscriber, $model);
            SubscriberPivot::detach($detachments, $subscriber, $model);
        }

        return $this->sendSuccess([
            'message'     => 'Successfully updated the ' . _n('subscriber', 'subscribers', count($subscribers)) . '.',
            'subscribers' => Subscriber::with('tags', 'lists')->whereIn('id', $subscribers)->get()
        ]);
    }

    /**
     * Store a subscriber.
     *
     * @return void
     */
    public function store(Request $request)
    {
        $data = $this->validate($request->all(), [
            'email'  => 'required|email|unique:fc_subscribers',
            'status' => 'required'
        ], [
            'email.unique' => __('Provided email already assigned to another subscriber.')
        ]);

        if ($this->isNew()) {
            Subscriber::store($data);
            return $this->sendSuccess([
                'message' => 'Successfully added the subscriber.'
            ]);
        }
    }

    public function updateSubscriber(Request $request)
    {
        $subscriber = Subscriber::find($id = $request->get('id'));

        $data = $this->validate($request->getJson('subscriber'), [
            'email'  => 'required|email|unique:fc_subscribers,email,'.$id,
        ], [
            'email.unique' => __('Provided email already assigned to another subscriber.')
        ]);

        // Maybe update user id
        if ($user = get_user_by('email', $data['email'])) {
            $data['user_id'] = $user->ID;
        }

        $subscriber->fill($data);
        $subscriber->save();

        do_action('fluentcrm_contact_updated', $subscriber, $data);

        if ($customValues = Arr::get($data, 'custom_values')) {
            $subscriber->syncCustomFieldValues($customValues);
        }

        return $this->sendSuccess([
            'message' => __('Subscriber successfully updated')
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

        $model = 'FluentCrm\App\Models\\' . ($type === 'tags' ? 'Tag' : 'Lists');

        return $model;
    }

    /**
     * Get the attachment options e.g. attach or, detach
     *
     * @param \FluentCrm\App\Models\Base\Model $model
     * @param string $type
     * @return array
     */
    private function attachments($model, $type = 'attach')
    {
        $attachments = $this->request->get($type, []);

        if ($attachments) {
            $attachments = $model::select('id')->whereIn('slug', $attachments)->get();

            if ($attachments) {
                $attachments = array_map(function ($item) {
                    return $item['id'];
                }, $attachments->toArray());
            }
        }

        return $attachments;
    }

    /**
     * Handles if subscriber already exist.
     *
     * @return bool
     */
    private function isNew()
    {
        $subscriber = Subscriber::where(
            'email', $this->request->get('email')
        )->first();

        if ($subscriber) {
            return $this->sendSuccess([
                'subscriber' => $subscriber
            ], 422);
        }

        return true;
    }

    public function emails()
    {
        $subscriberId = $this->request->get('id');
        $emails = CampaignEmail::where('subscriber_id', $subscriberId)
            ->orderBy('id', 'DESC')
            ->paginate();

        return apply_filters('fluentcrm_contact_emails', [
            'emails' => $emails
        ], $subscriberId);
    }

    public function addNote(Request $request, $id)
    {
        $note = $this->validate($request->get('note'), [
            'title'       => 'required',
            'description' => 'required',
            'type'        => 'required'
        ]);

        $subsciberNote = SubscriberNote::create(array_merge(
            wp_unslash($note), ['subscriber_id' => $id]
        ));

        return $this->sendSuccess([
            'note'    => $subsciberNote,
            'message' => __('Note successfully added')
        ]);
    }

    public function updateNote(Request $request, $id, $noteId)
    {
        $note = $this->validate($request->get('note'), [
            'title'       => 'required',
            'description' => 'required',
            'type'        => 'required'
        ]);

        $note = Arr::only(wp_unslash($note), ['title', 'description', 'type']);
        $subsciberNote = SubscriberNote::find($noteId);
        $subsciberNote->fill($note);
        $subsciberNote->save();

        return $this->sendSuccess([
            'note'    => $subsciberNote,
            'message' => __('Note successfully updated')
        ]);
    }

    public function deleteNote($id, $noteId)
    {
        SubscriberNote::where('id', $noteId)->delete();

        return $this->sendSuccess([
            'message' => __('Note successfully deleted', 'fluentcrm')
        ]);
    }

    public function getNotes()
    {
        $subscriberId = $this->request->get('id');

        $notes = SubscriberNote::where('subscriber_id', $subscriberId)
            ->with('added_by')
            ->orderBy('id', 'DESC')
            ->paginate();

        return $this->sendSuccess([
            'notes' => $notes
        ]);
    }

    public function getFormSubmissions()
    {
        $provider = $this->request->get('provider');
        $subscriberId = intval($this->request->get('id'));
        $subscriber = Subscriber::where('id', $subscriberId)->first();

        $data = $this->app->applyCustomFilters('get_form_submissions_' . $provider, [
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

        $data = $this->app->applyCustomFilters('get_support_tickets_' . $provider, [
            'data'  => [],
            'total' => 0
        ], $subscriber);

        return $this->sendSuccess([
            'tickets' => $data
        ]);
    }

    public function sendDoubleOptinEmail(Request $request, $id)
    {
        $subscriber = Subscriber::where('id', $id)->first();

        if ($subscriber == 'subscribed') {
            return $this->sendError([
                'message' => 'Contact Already Subscribed'
            ]);
        }

        $subscriber->sendDoubleOptinEmail();

        return $this->sendSuccess([
            'message' => 'Double OptIn email has been sent'
        ]);
    }
}
