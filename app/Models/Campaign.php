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
                'template_config'     => Helper::getTemplateConfig($defaultTemplate),
                'sending_type'        => 'instant'
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
        } else {
            Subject::where('object_id', $this->id)
                ->delete();
        }

        return $this->subjects();
    }

    public function duplicateSubjects(Campaign $campaign)
    {

        $subjects = $campaign->subjects;
        if (!$subjects) {
            return;
        }

        $formattedSubjects = [];
        foreach ($subjects as $subject) {
            $formattedSubjects[] = [
                'key'   => $subject->key,
                'value' => $subject['value']
            ];
        }
        if ($formattedSubjects) {
            $this->syncSubjects($formattedSubjects);
        }
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
        $model = $this->getSubscribersModel($settings);

        $totalCount = $model->count();

        if ($limit) {
            $model->limit($limit);
        }

        if ($offset) {
            $model->offset($offset);
        }

        $result = $this->subscribe($model, [], true);

        return [
            'result'           => ($result) ? $result : 0,
            'total_subscribed' => count($result),
            'total_items'      => $totalCount
        ];
    }

    public function getSubscribersModel($settings = false)
    {
        if (!$settings) {
            $settings = $this->settings;
        }

        $filterType = Arr::get($settings, 'sending_filter', 'list_tag');

        if ($filterType == 'list_tag') {
            $subscriberModel = $this->getSubscribeIdsByListModel($settings['subscribers'], 'subscribed');
            if ($excludeItems = Arr::get($settings, 'excludedSubscribers')) {
                $formattedExcludedItems = [];
                foreach ($excludeItems as $item) {
                    if (empty($item['list']) && empty($item['tag'])) {
                        continue;
                    }
                    $formattedExcludedItems[] = $item;
                }

                if ($formattedExcludedItems) {
                    $excludedModel = $this->getSubscribeIdsByListModel($excludeItems, 'subscribed');
                    $excludedModel->select('id');
                    $subscriberModel->whereNotIn('id', $excludedModel->getQuery());
                }
            }

            return $subscriberModel;
        }

        if ($filterType == 'dynamic_segment') {
            $segmentSettings = Arr::get($settings, 'dynamic_segment', []);
            $segmentSettings['offset'] = 0;
            $segmentSettings['limit'] = false;

            $segmentDetails = apply_filters('fluentcrm_dynamic_segment_' . $segmentSettings['slug'], [], $segmentSettings['id'], [
                'model' => true
            ]);

            if (!empty($segmentDetails['model'])) {
                $model = $segmentDetails['model'];
                $model->where('status', 'subscribed');
                return $model;
            }

            return null;
        }

        if ($filterType == 'advanced_filters') {
            $query = new ContactsQuery([
                'with'               => [],
                'filter_type'        => 'advanced',
                'contact_status'     => 'subscribed',
                'filters_groups_raw' => $settings['advanced_filters']
            ]);

            return $query->getModel();
        }

        return null;
    }

    public function getSubscriberIdsBySegmentSettings($settings, $limit = false, $offset = 0)
    {
        $model = $this->getSubscribersModel($settings);

        if (!$model) {
            return [
                'subscriber_ids' => [],
                'total_count'    => 0
            ];
        }

        $totalCount = $model->count();

        if ($limit) {
            $model->limit($limit);
        }

        if ($offset) {
            $model->offset($offset);
        }

        return [
            'subscriber_ids' => $model->get()->pluck('id')->toArray(),
            'total_count'    => $totalCount
        ];
    }

    public function getSubscriberIdsCountBySegmentSettings($settings, $status = 'subscribed')
    {
        $model = $this->getSubscribersModel($settings);
        if ($model) {
            return $model->count();
        }

        return 0;
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
            $query->where(function ($innerQuery) use ($queryGroups) {
                $type = 'where';
                foreach ($queryGroups as $queryGroup) {
                    $innerQuery->{$type}(function ($q) use ($queryGroup, $innerQuery) {
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
            });
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
     * @param bool $isModel if the $subscriberIds is collection or not
     * @return array
     */
    public function subscribe($subscriberIds, $emailArgs = [], $isModel = false)
    {
        $updateIds = [];

        $mailHeaders = Helper::getMailHeadersFromSettings(Arr::get($this->settings, 'mailer_settings', []));

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

            $email['email_subject'] = apply_filters('fluent_crm/parse_campaign_email_text', $emailSubject, $subscriber);;

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

        $emailCount = $this->getEmailCount();
        if ($emailCount != $this->recipients_count) {
            $this->recipients_count = $emailCount;
            $this->save();
        }

        return $updateIds;
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

    public function getHash()
    {
        $hash = fluentcrm_get_campaign_meta($this->id, '_campaign_hash', true);

        if ($hash) {
            return $hash;
        }
        $hash = md5(mt_rand(100, 10000) . '_' . $this->id . '_' . $this->title . '_' . time());
        $hash = str_replace('e', 'd', $hash);
        fluentcrm_update_campaign_meta($this->id, '_campaign_hash', $hash);

        return $hash;
    }

    public function deleteCampaignData()
    {
        CampaignEmail::where('campaign_id', $this->id)->delete();
        CampaignUrlMetric::where('campaign_id', $this->id)->delete();

        Meta::where('object_id', $this->id)
            ->where('object_type', 'FluentCrm\App\Models\Campaign')
            ->delete();

        return $this;
    }

    public function rangedScheduleDates()
    {
        $settings = $this->settings;

        if (Arr::get($settings, 'sending_type') != 'range_schedule') {
            return null;
        }



        $ranges = Arr::get($settings, 'schedule_range', ['', '']);

        if(!$ranges) {
            return null;
        }

        return [
            'start' => date('Y-m-d H:i:s', $ranges[0]),
            'end'   => date('Y-m-d H:i:s', $ranges[1])
        ];
    }

    public function getEmailScheduleAt()
    {
        static $scheduled_at = null;
        if ($scheduled_at) {
            return $scheduled_at;
        }

        $settings = $this->settings;

        if (Arr::get($settings, 'sending_type') != 'range_schedule') {
            $scheduled_at = $this->scheduled_at;
            return $scheduled_at;
        }

        // this is a range selector
        $ranges = Arr::get($settings, 'schedule_range', [$this->scheduled_at, $this->scheduled_at]);

        $timeStamp = random_int($ranges[0], $ranges[1]);

        if ($timeStamp < current_time('timestamp')) {
            $timeStamp = current_time('timestamp') + 60;
        }

        return date('Y-m-d H:i:s', $timeStamp);
    }
}
