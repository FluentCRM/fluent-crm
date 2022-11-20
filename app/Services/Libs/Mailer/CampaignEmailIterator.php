<?php

namespace FluentCrm\App\Services\Libs\Mailer;

use FluentCrm\App\Models\CampaignEmail;

class CampaignEmailIterator implements \Iterator
{
    protected $key = 0;
    protected $limit = 0;
    protected $offset = 0;
    protected $emails = null;
    protected $campaignId = null;

    public function __construct($campaignId = null, $limit = 10)
    {
        $this->campaignId = $campaignId;
        $this->limit = $limit ? $limit : 10;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->emails;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->key++;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->offset = $this->offset;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->offset = 0;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        $currentTime = current_time('mysql');

        $emails = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->when($this->campaignId, function ($query) {
                return $query->where('campaign_id', $this->campaignId);
            })
            ->where('scheduled_at', '<=', $currentTime)
            ->whereNotNull('scheduled_at')
            ->with('campaign', 'subscriber')
            ->orderBy('scheduled_at', 'ASC')
            ->offset($this->offset)
            ->limit($this->limit)
            ->get();

        $ids = $emails->pluck('id')->toArray();

        if ($ids) {
            CampaignEmail::whereIn('id', $ids)
                ->update([
                    'status'       => 'processing',
                    'updated_at'   => $currentTime,
                    'scheduled_at' => $currentTime
                ]);
        }

        $this->emails = $emails;

        return !$this->emails->isEmpty();
    }
}
