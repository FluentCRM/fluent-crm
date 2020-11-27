<?php

namespace FluentCrm\App\Services;


use FluentCrm\Includes\Helpers\Arr;

class BlockParser
{
    private $subscriber = null;

    public function __construct($subscriber = null)
    {
        static $initiated;

        BlockParserHelper::setSubscriber($subscriber);

        if($initiated) {
            return $this;
        }

        $initiated = true;
        add_filter('render_block', array($this, 'alterBlockContent'), 999, 2);
    }


    public function parse($content)
    {
        $blocks = parse_blocks( $content );
        $output = '';
        foreach ( $blocks as $block ) {
            $block = $this->sanitizeBlock($block);
            $output .= render_block( $block );
        }

        if($this->subscriber) {
            $output .= '<h1>'.$this->subscriber->id.'-'.$this->subscriber->email.'</h1>';
        }

        return $output;
    }

    private function sanitizeBlock($block)
    {
        if(!empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $index => $childBlock) {
                $block['innerBlocks'][$index] = $this->sanitizeBlock($childBlock);
            }
        }

        $blockName = $block['blockName'];
        if($blockName == 'core/columns') {
            $blockCounts = count($block['innerBlocks']);
            $lastContentIndex =  $blockCounts * 2;
            foreach ($block['innerBlocks'] as $blockIndex => $blockItem) {
                $block['innerBlocks'][$blockIndex]['fc_total_blocks'] = $blockCounts;
            }
            $block['innerContent'][0] = $this->getRowOpening($block);
            $block['innerContent'][$lastContentIndex] = $this->getRowClosing($block);
        } else if($blockName == 'core/media-text') {
            $blockCounts = count($block['innerBlocks']);
            $lastContentIndex =  $blockCounts * 2;
            $block['innerContent'][0] = $this->getMediaTextOpening($block);
            $block['innerContent'][$lastContentIndex] = $this->getMediaTextClosing($block);
        } else if($blockName == 'core/buttons') {
            $blockCounts = count($block['innerBlocks']);
            $lastContentIndex =  $blockCounts * 2;
            foreach ($block['innerBlocks'] as $blockIndex => $blockItem) {
                $block['innerBlocks'][$blockIndex]['fc_total_blocks'] = $blockCounts;
            }
            $block['innerContent'][0] = $this->getButtonsOpening($block);
            $block['innerContent'][$lastContentIndex] = $this->getButtonsClosing($block);
        }

        return $block;
    }

    private function getButtonsOpening($block)
    {
        $align = Arr::get($block, 'attrs.align', 'center');
        return '<table valign="middle" align="'.$align.'" class="fce_row fce_buttons_row" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%; width: auto; float:none;"><tbody><tr>';
    }

    private function getButtonsClosing($block)
    {
        return '</tr></tbody></table>';
    }

    private function getMediaTextOpening($block)
    {
        $backgroundColorClass = Arr::get($block, 'attrs.backgroundColor');
        $prevContent = $block['innerContent'][0];
        preg_match('/<figure (.*?)<\/figure>/s', $prevContent, $match);
        $figure = $match[0];
        $mediaWidth = Arr::get($block,'attrs.mediaWidth', 50);
        $contentWidth = 100 - $mediaWidth;
        $MediaAlign = Arr::get($block,'attrs.mediaPosition', 'left');
        $textAlign = ($MediaAlign == 'right') ? 'left' : 'right';

        $imageFill = Arr::get($block, 'attrs.imageFill') ? 'has_bg_image' : 'no_image_fill';

        $background = Arr::get($block, 'attrs.style.color.background');
        $extraCss = '';
        if(!$backgroundColorClass && $background) {
            $extraCss = 'background: '.$background.';background-color:'.$background;
        }
        if($backgroundColorClass) {
            $backgroundColorClass = 'has-'.Helper::kebabCase($backgroundColorClass).'-background-color';
        }
        $html = '<table class="fce_row fc_row_media_text '.$backgroundColorClass.'" border="0" cellpadding="0" cellspacing="0" width="100%" style="'.$extraCss.'table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><tbody><tr>';
        $html .= '<td><table class="fc_media_table" align="'.$MediaAlign.'" style="width: '.$mediaWidth.'%;" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td align="left" class="'.$imageFill.'" valign="middle">'.$figure.'</td></tr></tbody></table>';
        $html .= '<table class="fc_media_text" valign="middle" align="'.$textAlign.'" style="width: '.$contentWidth.'%;" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td style="padding: 30px 20px 10px;" align="left" valign="middle">';
        return $html;
    }

    private function getMediaTextClosing($block)
    {
        return '</td></tr></tbody></table></td></tr></tbody></table>';
    }

    public function alterBlockContent($content, $data)
    {
        if(isset($data['blockName']) && $data['blockName'] == 'fluentcrm/conditional-group') {
            return $this->renderConditionalBlock($content, $data);
        }

        if(empty($data['fc_total_blocks'])) {
            return  $content;
        }

        $blockName = $data['blockName'];
        if($blockName == 'core/column') {
            $content = $this->getColumnOpening($data).$content.$this->getColumnClosing($data);
        } else if($blockName == 'core/button') {
            $content = $this->getButtonWrapper($content, $data);
        }
        return $content;
    }

    private function getRowOpening($block)
    {
        return '<table class="fce_row" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><tbody><tr>';
    }

    private function getRowClosing($block)
    {
        return '</tr></tbody></table>';
    }

    private function getColumnOpening($block)
    {
        $width = Arr::get($block, 'attrs.width');
        if(!$width) {
            $total = !empty($block['fc_total_blocks']) ? $block['fc_total_blocks'] : 1;
            $width = 100 / $total;
        }
        $vAlign = Arr::get($block, 'attrs.verticalAlignment', 'middle');
        return '<td align="center" valign="'.$vAlign.'" width="'.$width.'%" class="fce_column"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td class="fc_column_content">';
    }

    private function getColumnClosing($block)
    {
        return '</td></tr></table></td>';
    }

    private function getButtonWrapper($content, $data)
    {
        $hasCustomBg = Arr::get($data, 'attrs.style.color.background') || Arr::get($data, 'attrs.backgroundColor');
        $hasTextColor = Arr::get($data, 'attrs.style.color.text') || Arr::get($data, 'attrs.textColor');

        $btn_wrapper_class = '';
        if(!$hasCustomBg) {
            $btn_wrapper_class .= 'fc_d_btn_bg ';
        }
        if(!$hasTextColor) {
            $btn_wrapper_class .= 'fc_d_btn_color ';
        }

        return '<td align="center" valign="middle" class="fce_column"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td class="fc_column_content '.$btn_wrapper_class.'">'.$content.'</td></tr></table></td>';
    }

    private function renderConditionalBlock($content, $data)
    {
        $subscriber = BlockParserHelper::getSubscriber();

        if(!$subscriber) {
            return '';
        }

        $tagIds = Arr::get($data, 'attrs.tag_ids');
        if(!$tagIds) {
            return '';
        }

        $checkType = Arr::get($data, 'attrs.condition_type', 'show_if_tag_exist');
        $tagMatched = $subscriber->hasAnyTagId($tagIds);

        if($checkType == 'show_if_tag_exist') {
            if($tagMatched) {
                return $content;
            };
            return '';
        }

        if($checkType == 'show_if_tag_not_exist') {
            if($tagMatched) {
                return '';
            };
            return $content;
        }


        return '';
    }
}
