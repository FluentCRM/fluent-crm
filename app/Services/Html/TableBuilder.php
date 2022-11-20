<?php

namespace FluentCrm\App\Services\Html;

use FluentCrm\Framework\Support\Arr;

class TableBuilder
{
    private $header = [];

    private $rows = [];

    public $tableClass = 'fc_horizontal_table';

    public function setHeader($header)
    {
        $this->header = $header;
    }

    public function addRow($row)
    {
        $this->rows[] = $row;
    }

    public function getHtml()
    {
        if(!$this->header) {
            return '';
        }
        $table = '<table class="'.esc_html($this->tableClass).'"><thead><tr>';
        foreach ($this->header as $key => $heading) {
            $table .= '<th class="fc_head_'.esc_attr($key).'">'.wp_kses_post($heading).'</th>';
        }
        $table .= '</tr></thead><tbody>';

        foreach ($this->rows as $row) {
            $table .= '<tr>';
            foreach ($this->header as $key => $heading) {
                $table .= '<td>'.Arr::get($row, $key).'</td>';
            }
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';

        return $table;

    }

    public function printHtml()
    {
        echo $this->getHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function reset()
    {
        $this->header = [];
        $this->rows = [];
        $this->tableClass = 'fc_horizontal_table';
    }

}
