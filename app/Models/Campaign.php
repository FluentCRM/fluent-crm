<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Parser\Parser;

class Campaign extends Model
{
    protected $table = 'fc_campaigns';

    protected $guarded = ['id'];

    protected static $type = 'campaign';

    public static function boot()
    {
        static::creating(function ($model) {
            $defaultTemplate = $model->design_template ? $model->design_template : Helper::getDefaultEmailTemplate();
            $model->email_body = $model->email_body ?: '';
            $model->status = $model->status ?: 'draft';
            $model->type = static::$type;
            $model->design_template = $defaultTemplate;
            $model->slug = $model->slug ?: sanitize_title($model->title, '', 'preview');
            $model->created_by = $model->created_by ?: get_current_user_id();
            $model->settings = $model->settings ?: [
                'mailer_settings'     => [
                    'from_name'      => '',
                    'from_email'     => '',
                    'reply_to_name'  => '',
                    'reply_to_email' => '',
                    'is_custom'      => 'no'
                ],
                'subscribers'         => [
                    [
                        'list' => null,
                        'tag'  => 'all'
                    ]
                ],
                'excludedSubscribers' => [
                    [
                        'list' => null,
                        'tag'  => null
                    ]
                ],
                'sending_filter'      => 'list_tag',
                'dynamic_segment'     => [
                    'id'   => '',
                    'slug' => ''
                ],
                'template_config'     => Helper::getTemplateConfig($defaultTemplate)
            ];
        });

        static::addGlobalScope('type', function ($builder) {
            $builder->where('type', '=', static::$type);
        });
    }

    public function setSlugAttribute($slug)
    {
        $this->attributes['slug'] = \sanitize_title($slug, '', 'preview');
    }

    public function setSettingsAttribute($settings)
    {
        $this->attributes['settings'] = \maybe_serialize($settings);
    }

    public function getSettingsAttribute($settings)
    {
        return \maybe_unserialize($settings);
    }

    public function getRecipientsCountAttribute($recipientsCount)
    {
        return (int)$recipientsCount;
    }

    public function getRenderedBodyAttribute()
    {
        return (new Template)->render($this->body);
    }

    // Now using a single subject, get the first one
    public function getSubjectAttribute()
    {
        if ($firstSubject = $this->subjects()->first()) {
            return $firstSubject->value;
        }
        return $this->email_subject;
    }

    public function syncSubjects($subjects)
    {
        $validSubjectIds = [];
        foreach ($subjects as $subject) {
            if (empty($subject['value']) || empty($subject['key'])) {
                continue;
            }
            if (empty($subject['id'])) {
                $data = Arr::only($subject, ['key', 'value']);
                $data['object_id'] = $this->id;
                $inserted = Subject::create($data);
                $validSubjectIds[] = $inserted->id;
            } else {
                Subject::where('id', $subject['id'])->update(Arr::only($subject, ['key', 'value']));
                $validSubjectIds[] = $subject['id'];
            }
        }

        if ($validSubjectIds) {
            // remove old subjects
            Subject::whereNotIn('id', $validSubjectIds)
                ->where('object_id', $this->id)
                ->delete();
        }

        return $this->subjects();
    }

    public function scopeOfType($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 0);
    }

    /**
     * One2One: Campaign belongs to one template
     * @return Model
     */
    public function template()
    {
        return $this->belongsTo(__NAMESPACE__ . '\Template', 'template_id', 'ID');
    }

    /**
     * TODO: emails should be filtered by status (draft, queue e.t.c.)
     * One2Many: Campaign has many emails
     * @return Model Collection
     */
    public function emails()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\CampaignEmail', 'campaign_id', 'id'
        );
    }

    /**
     * TODO: emails should be filtered by status (draft, queue e.t.c.)
     * One2Many: Campaign has many emails
     * @return Model Collection
     */
    public function campaign_emails()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\CampaignEmail', 'campaign_id', 'id'
        )->where('email_type', 'campaign');
    }

    /**
     * One2Many: Campaign has many subjects
     * @return Model Collection
     */
    public function subjects()
    {
        return $this->hasMany(__NAMESPACE__ . '\Subject', 'object_id', 'id');
    }

    /**
     * Add one or more subscribers to the campaign by list with filtering
     * @param $settings
     * @param bool $limit
     * @param int $offset
     * @return array
     */
    public function subscribeBySegment($settings, $limit = false, $offset = 0)
    {
        $data = $this->getSubscriberIdsBySegmentSettings($settings, $limit, $offset);
        $subscriberIds = $data['subscriber_ids'];
        $totalItems = $data['total_count'];

        if ($subscriberIds) {
            $result = $this->subscribe($subscriberIds);
        } else {
            $result = 0;
        }

        return [
            'result'           => $result,
            'total_subscribed' => count($subscriberIds),
            'total_items'      => $totalItems
        ];
    }

    public function getSubscriberIdsBySegmentSettings($settings, $limit = false, $offset = 0)
    {
        $filterType = Arr::get($settings, 'sending_filter', 'list_tag');

        if ($filterType == 'list_tag') {
            $subscriberIds = $this->getSubscribeIdsByList($settings['subscribers'], 'subscribed');

            if ($excludeItems = Arr::get($settings, 'excludedSubscribers')) {
                if ($excludeSubscriberIds = $this->getSubscribeIdsByList($excludeItems, 'subscribed')) {
                    $subscriberIds = array_diff($subscriberIds, $excludeSubscriberIds);
                }
            }
            $totalItems = count($subscriberIds);
        } else {
            $segmentSettings = Arr::get($settings, 'dynamic_segment', []);
            $segmentSettings['offset'] = $offset;
            $segmentSettings['limit'] = $limit;
            $subscribersPaginate = apply_filters('fluentcrm_segment_paginate_contact_ids', [], $segmentSettings);
            $subscriberIds = $subscribersPaginate['subscriber_ids'];
            $totalItems = $subscribersPaginate['total_count'];
        }

        if ($limit) {
            $subscriberIds = array_slice($subscriberIds, $offset, $limit);
        }

        return [
            'subscriber_ids' => $subscriberIds,
            'total_count'    => $totalItems
        ];
    }

    /**
     * Get subscribers ids to by list with tag filtering
     * @param array $listTags
     * @return array
     */
    public function getSubscribeIdsByList($items, $status = false)
    {
        foreach ($items as $item) {
            $list = $item['list'];

            $segment = $item['tag'];

            if ($list == 'all') {

                $segmentQuery = Subscriber::where(function ($query) use ($status) {
                    $status && $query->where('fc_subscribers.status', $status);
                });

                if ($segment && !in_array($segment, ['all', 'group'])) {
                    $segmentQuery->filterByTags([$segment], 'slug');
                }

                $subscriberIds[] = $segmentQuery->get()->pluck('id');
            } else {
                $subscriberIds[] = Lists::with(['subscribers' => function ($query) use ($segment, $status) {

                    if ($segment && !in_array($segment, ['all', 'group'])) {
                        $query->whereHas('tags', function ($query) use ($segment) {
                            $query->where('fc_tags.slug', $segment);
                        });
                    }

                    $status && $query->where('fc_subscribers.status', $status);

                }])->find($list)->subscribers->pluck('id');
            }
        }

        $selectedIds = [];

        if (isset($subscriberIds)) {
            foreach ($subscriberIds as $ids) {
                $selectedIds = array_merge($selectedIds, $ids);
            }
        }

        return array_unique($selectedIds);
    }

    /**
     * Add one or more subscribers to the campaign
     * @param array $subscriberIds
     * @param array $emailArgs extra campaign_email args
     * @return array
     */
    public function subscribe($subscriberIds, $emailArgs = [])
    {
        $campaignEmails = [];
        $updateIds = [];

        $mailHeaders = Helper::getMailHeadersFromSettings($this->settings['mailer_settings']);

        foreach (Subscriber::whereIn('id', $subscriberIds)->get() as $subscriber) {
            if ($subscriber->status != 'subscribed') {
                continue; // We don't want to send emails to non-subscribed members
            }

            $time = fluentCrmTimestamp();
            $email = [
                'campaign_id'   => $this->id,
                'status'        => $this->status,
                'subscriber_id' => $subscriber->id,
                'email_address' => $subscriber->email,
                'email_headers' => $mailHeaders,
                'created_at'    => $time,
                'updated_at'    => $time
            ];

            $subjectItem = $this->guessEmailSubject();
            if ($subjectItem) {
                $emailSubject = $subjectItem->value;
                $email['email_subject_id'] = $subjectItem->id;
            } else {
                $emailSubject = $this->email_subject;
            }

            $email['email_subject'] = apply_filters('fluentcrm-parse_campaign_email_text', $emailSubject, $subscriber);;

            if ($emailArgs) {
                $email = wp_parse_args($emailArgs, $email);
            }

            $email['email_body'] = $this->email_body;

            $inserted = CampaignEmail::create($email);

            $subscriber->campaign_id = $this->id;
            $subscriber->email_id = $inserted->id;

            $emailHash = Helper::generateEmailHash($inserted->id);

            CampaignEmail::where('id', $inserted->id)
                ->update([
                    'email_hash' => $emailHash
                ]);

            $updateIds[] = $inserted->id;

        }

        $result = $campaignEmails ? CampaignEmail::insert($campaignEmails) : [];
        $this->recipients_count = $this->emails()->count();
        $this->save();

        return array_merge($result, $updateIds);
    }

    /**
     * Remove one or more subscribers from the campaign
     * @param array $subscriberIds
     * @return bool
     */
    public function unsubscribe($subscriberIds)
    {
        $result = $this->emails()->whereIn('subscriber_id', $subscriberIds)->delete();

        $this->recipients_count = $this->emails()->count();

        $this->save();

        return $result;
    }

    /**
     * Guess the subject by probability formula
     * @return Model Object or null
     */
    public function guessEmailSubject()
    {
        $subjects = $this->subjects()->get();
        if ($subjects->empty()) {
            return null;
        }

        $priorities = $subjects->pluck('key');
        $count = count($priorities);
        $num = mt_rand(0, array_sum($priorities));

        $i = $n = 0;
        while ($i < $count) {
            $n += $priorities[$i];
            if ($n >= $num) break;
            $i++;
        }

        return $subjects[$i] ? $subjects[$i] : null;
    }

    public function getParsedText($text, $subscriber)
    {
        return Parser::parse($text, $subscriber->toArray());
    }

    public function filterDuplicateSubscribers($subscriberIds, $subscribers)
    {
        $existingIds = CampaignEmail::where('campaign_id', $this->id)
            ->whereIn('subscriber_id', $subscribers->pluck('id'))
            ->get()->pluck('subscriber_id');

        return $subscribers->filter(function ($subscriber) use ($existingIds) {
            return !in_array($subscriber->id, $existingIds);
        });
    }

    public function archive()
    {
        $this->status = 0;
        $this->save();
        return $this;
    }

    public function getUtmParams()
    {
        if ($this->utm_status) {
            return array_filter([
                'utm_source'   => $this->utm_source,
                'utm_medium'   => $this->utm_medium,
                'utm_campaign' => $this->utm_campaign,
                'utm_term'     => $this->utm_term,
                'utm_content'  => $this->utm_content
            ]);
        }

        return [];
    }

    public function stats()
    {
        $totalEmails = CampaignEmail::where('campaign_id', $this->id)
            ->count();

        $totalSent = CampaignEmail::where('campaign_id', $this->id)
            ->where('status', 'sent')
            ->count();

        $clicks = CampaignUrlMetric::where('campaign_id', $this->id)
            ->where('type', 'click')
            ->count();

        $views = CampaignUrlMetric::where('campaign_id', $this->id)
            ->where('type', 'open')
            ->count();

        $unSubscribed = CampaignUrlMetric::where('campaign_id', $this->id)
            ->where('type', 'unsubscribe')
            ->groupBy('subscriber_id')
            ->count();

        return [
            'total'         => $totalEmails,
            'sent'          => $totalSent,
            'clicks'        => $clicks,
            'views'         => $views,
            'unsubscribers' => $unSubscribed
        ];

    }

}