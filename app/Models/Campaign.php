<?php

namespace FluentCrm\App\Models;

use FluentCrm\App\Services\ContactsQuery;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\App\Services\Libs\Parser\Parser;

/**
 *  Campaign Model - DB Model for Campaigns
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 1.0.0
 */
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
                        'list' => 'all',
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
                'advanced_filters'    => [[]],
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
     * @return \FluentCrm\Framework\Database\Orm\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(__NAMESPACE__ . '\Template', 'template_id', 'ID');
    }

    /**
     * One2Many: Campaign has many emails
     *
     * @return \FluentCrm\Framework\Database\Orm\Relations\hasMany
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
     * @return \FluentCrm\Framework\Database\Orm\Relations\hasMany
     */
    public function campaign_emails()
    {
        return $this->hasMany(
            __NAMESPACE__ . '\CampaignEmail', 'campaign_id', 'id'
        )->where('email_type', 'campaign');
    }

    /**
     * One2Many: Campaign has many subjects
     * @return \FluentCrm\Framework\Database\Orm\Relations\hasMany
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

        $alreadySliced = false;

        if ($filterType == 'list_tag') {
            $excludeSubscriberIds = [];
            if ($excludeItems = Arr::get($settings, 'excludedSubscribers')) {
                $excludeSubscriberIds = $this->getSubscribeIdsByList($excludeItems, 'subscribed');
            }

            if (!$excludeSubscriberIds) {
                $alreadySliced = true;
                $subscriberIds = $this->getSubscribeIdsByList($settings['subscribers'], 'subscribed', $limit, $offset);
            } else {
                $subscriberIds = $this->getSubscribeIdsByList($settings['subscribers'], 'subscribed');
            }

            if ($excludeSubscriberIds) {
                $subscriberIds = array_diff($subscriberIds, $excludeSubscriberIds);
            }

            $totalItems = count($subscriberIds);
            if ($limit && !$alreadySliced) {
                $subscriberIds = array_slice($subscriberIds, $offset, $limit);
            }

            return [
                'subscriber_ids' => $subscriberIds,
                'total_count'    => $totalItems
            ];

        } else if ($filterType == 'dynamic_segment') {
            $segmentSettings = Arr::get($settings, 'dynamic_segment', []);
            $segmentSettings['offset'] = $offset;
            $segmentSettings['limit'] = $limit;
            $subscribersPaginate = apply_filters('fluentcrm_segment_paginate_contact_ids', [], $segmentSettings);

            $subscriberIds = $subscribersPaginate['subscriber_ids'];
            $totalItems = $subscribersPaginate['total_count'];
            if ($limit) {
                $subscriberIds = array_slice($subscriberIds, 0, $limit);
            }

            return [
                'subscriber_ids' => $subscriberIds,
                'total_count'    => $totalItems
            ];

        } else if ($filterType == 'advanced_filters') {
            $query = new ContactsQuery([
                'with'               => [],
                'filter_type'        => 'advanced',
                'contact_status'     => 'subscribed',
                'limit'              => $limit,
                'offset'             => $offset,
                'filters_groups_raw' => $settings['advanced_filters']
            ]);

            $count = $query->getModel()->count();
            $subscriberIds = $query->get()->pluck('id')->toArray();


            return [
                'subscriber_ids' => $subscriberIds,
                'total_count'    => $count
            ];
        }

        return [
            'subscriber_ids' => [],
            'total_count'    => 0
        ];
    }

    public function getSubscriberIdsCountBySegmentSettings($settings, $status = 'subscribed')
    {
        $filterType = Arr::get($settings, 'sending_filter', 'list_tag');

        if ($filterType == 'list_tag') {
            $excludeSubscriberIds = [];
            if ($excludeItems = Arr::get($settings, 'excludedSubscribers')) {
                $excludeSubscriberIds = $this->getSubscribeIdsByList($excludeItems, $status);
            }

            if (!$excludeSubscriberIds) {
                $model = $this->getSubscribeIdsByListModel($settings['subscribers'], $status);
                return $model->count();
            } else {
                $subscriberIds = $this->getSubscribeIdsByList($settings['subscribers'], $status);
                return count(array_diff($subscriberIds, $excludeSubscriberIds));
            }
        } else if ($filterType == 'dynamic_segment') {
            $segmentSettings = Arr::get($settings, 'dynamic_segment', []);
            $segmentSettings['offset'] = 0;
            $segmentSettings['limit'] = 1;
            $segmentSettings['status'] = $status;
            $subscribersPaginate = apply_filters('fluentcrm_segment_paginate_contact_ids', [], $segmentSettings);
            return $subscribersPaginate['total_count'];
        } else if ($filterType == 'advanced_filters') {
            $query = new ContactsQuery([
                'with'               => [],
                'filter_type'        => 'advanced',
                'contact_status'     => $status,
                'filters_groups_raw' => $settings['advanced_filters'],
            ]);

            return $query->getModel()->count();
        } else {
            return 0;
        }
    }


    /**
     * @param $query
     * @param $ids
     * @param $table
     * @param $objectType
     * @return mixed
     */
    private function getSubQueryForLisTorTagFilter($query, $ids, $table, $objectType)
    {
        $prefix = 'fc_';

        return $query->from($prefix . $table)
            ->join(
                $prefix . 'subscriber_pivot',
                $prefix . 'subscriber_pivot.object_id',
                '=',
                $prefix . $table . '.id'
            )
            ->where($prefix . 'subscriber_pivot.object_type', $objectType)
            ->whereIn($prefix . $table . '.id', $ids)
            ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
            ->select($prefix . 'subscriber_pivot.subscriber_id');
    }

    /**
     * Get subscribers ids to by list with tag filtering
     * @param array $items
     * @param string $status contact status
     * @param int|boolean $limit limit
     * @param int $offset contact offset
     * @return array
     */
    public function getSubscribeIdsByList($items, $status = 'subscribed', $limit = false, $offset = 0)
    {
        $model = $this->getSubscribeIdsByListModel($items, $status, $limit, $offset);
        $results = $model->get();
        $ids = [];

        foreach ($results as $result) {
            $ids[] = $result->id;
        }

        return $ids;
    }

    /**
     * Get subscribers count to by list with tag filtering
     * @param array $items
     * @param string $status contact status
     * @param int|boolean $limit limit
     * @param int $offset contact offset
     * @return int
     */
    public function getSubscribeIdsByListCount($items, $status = 'subscribed', $limit = false, $offset = 0)
    {
        $model = $this->getSubscribeIdsByListModel($items, $status, $limit, $offset);
        return $model->count();
    }

    public function getSubscribeIdsByListModel($items, $status = 'subscribed', $limit = false, $offset = 0)
    {

        $query = Subscriber::where('status', $status);

        $queryGroups = [];

        $willSkip = false;

        $hasListFilter = false;
        $tagIds = [];
        foreach ($items as $item) {
            $listId = $item['list'];
            $tagId = $item['tag'];
            if (!$listId || !$tagId) {
                continue;
            }

            if ($listId == 'all' && $tagId == 'all') {
                $willSkip = true;
            } else if ($listId == 'all') {
                $queryGroups[] = ['tag_id' => $tagId];
                $tagIds[] = $tagId;
            } else if ($tagId == 'all') {
                $hasListFilter = true;
                $queryGroups[] = ['list_id' => $listId];
            } else {
                $hasListFilter = true;
                $tagIds[] = $tagId;
                $queryGroups[] = [
                    'list_id' => $listId,
                    'tag_id'  => $tagId
                ];
            }
        }

        if (!$willSkip && !$hasListFilter && $tagIds) {
            $query->filterByTags($tagIds);
        } else if (!$willSkip && $queryGroups) {
            $type = 'where';
            foreach ($queryGroups as $queryGroup) {
                $query->{$type}(function ($q) use ($queryGroup, $query) {
                    foreach ($queryGroup as $type => $id) {
                        if ($type == 'tag_id') {
                            $q->whereIn('id', function ($query) use ($id) {
                                return $this->getSubQueryForLisTorTagFilter($query, [$id], 'tags', 'FluentCrm\App\Models\Tag');
                            });
                        } else if ($type == 'list_id') {
                            $q->whereIn('id', function ($query) use ($id) {
                                return $this->getSubQueryForLisTorTagFilter($query, [$id], 'lists', 'FluentCrm\App\Models\Lists');
                            });
                        }
                    }
                });

                $type = 'orWhere';
            }
        }

        if ($limit) {
            $query->limit($limit)->offset($offset);
        }

        return $query;

    }

    /**
     * Add one or more subscribers to the campaign
     * @param array $subscriberIds
     * @param array $emailArgs extra campaign_email args
     * @return array
     */
    public function subscribe($subscriberIds, $emailArgs = [], $isModel = false)
    {
        $campaignEmails = [];
        $updateIds = [];

        $mailHeaders = Helper::getMailHeadersFromSettings(Arr::get($this->settings, 'mailer_settings'));

        if ($isModel) {
            $subscribers = $subscriberIds;
        } else {
            $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();
        }

        foreach ($subscribers as $subscriber) {
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
            $emailSubject = $this->email_subject;

            if ($subjectItem && !empty($subjectItem->value)) {
                $emailSubject = $subjectItem->value;
                $email['email_subject_id'] = $subjectItem->id;
            }

            $email['email_subject'] = apply_filters('fluentcrm_parse_campaign_email_text', $emailSubject, $subscriber);;

            $email['email_body'] = $this->email_body;

            if ($emailArgs) {
                $email = wp_parse_args($emailArgs, $email);
            }

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
        $emailCount = $this->getEmailCount();
        if ($emailCount != $this->recipients_count) {
            $this->recipients_count = $emailCount;
            $this->save();
        }

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
        if ($subjects->isEmpty()) {
            return null;
        }

        $priorities = $subjects->pluck('key')->toArray();
        $count = count($priorities);
        $num = mt_rand(0, array_sum($priorities));

        $i = $n = 0;
        while ($i < $count) {
            $n += $priorities[$i];
            if ($n >= $num) break;
            $i++;
        }

        return isset($subjects[$i]) ? $subjects[$i] : null;
    }

    public function getParsedText($text, $subscriber)
    {
        return Parser::parse($text, $subscriber);
    }

    public function filterDuplicateSubscribers($subscriberIds, $subscribers)
    {
        $existingIds = CampaignEmail::where('campaign_id', $this->id)
            ->whereIn('subscriber_id', $subscribers->pluck('id')->toArray())
            ->get()->pluck('subscriber_id')->toArray();

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
            ->distinct()
            ->count('subscriber_id');

        $revenue = fluentcrm_get_campaign_meta($this->id, '_campaign_revenue');

        $stats = [
            'total'         => $totalEmails,
            'sent'          => $totalSent,
            'clicks'        => $clicks,
            'views'         => $views,
            'unsubscribers' => $unSubscribed
        ];

        if ($revenue && $revenue->value) {
            $data = (array)$revenue->value;
            foreach ($data as $currency => $cents) {
                if ($cents) {
                    $stats['revenue'] = [
                        'label'    => __('Revenue', 'fluent-crm') . ' (' . $currency . ')',
                        'total'    => number_format($cents / 100, 2),
                        'currency' => $currency
                    ];
                }
            }
        }

        return $stats;

    }

    public function getEmailCount()
    {
        return fluentCrmDb()->table('fc_campaign_emails')
            ->where('campaign_id', $this->id)
            ->count();
    }


    public function maybeDeleteDuplicates()
    {
        $duplicates = fluentCrmDb()->table('fc_campaign_emails')
            ->where('campaign_id', $this->id)
            ->select(['id', 'subscriber_id', fluentCrmDb()->raw('COUNT(subscriber_id) as count')])
            ->groupBy('subscriber_id')
            ->havingRaw('COUNT(subscriber_id) > ?', [1])
            ->get();

        if (!$duplicates) {
            return $this;
        }

        $subscriberIds = [];
        $exceptIds = [];
        foreach ($duplicates as $duplicate) {
            $subscriberIds[] = $duplicate->subscriber_id;
            $exceptIds[] = $duplicate->id;
        }

        fluentCrmDb()->table('fc_campaign_emails')
            ->where('campaign_id', $this->id)
            ->whereIn('subscriber_id', $subscriberIds)
            ->whereNotIn('id', $exceptIds)
            ->delete();

        $emailCount = $this->getEmailCount();
        if ($emailCount != $this->recipients_count) {
            $this->recipients_count = $emailCount;
            $this->save();
        }

        return $this;
    }
}
