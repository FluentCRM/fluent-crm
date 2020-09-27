<?php

namespace FluentCrm\Includes\Mailer;

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

    public function current()
    {
        return $this->emails;
    }

    public function key()
    {
        return $this->key++;
    }

    public function next()
    {
        $this->offset = $this->offset;
    }

    public function rewind()
    {
        $this->offset = 0;
    }

    public function valid()
    {
        $this->emails = CampaignEmail::whereIn('status', ['pending', 'scheduled'])
            ->when($this->campaignId, function($query) {
                $query->where('campaign_id', $this->campaignId);
            })
            ->where('scheduled_at', '<=', current_time('mysql'))
            ->whereNotNull('scheduled_at')
            ->with('campaign', 'subscriber')
            ->offset($this->offset)
            ->limit($this->limit)
            ->get();

        return !$this->emails->empty();
    }
}
