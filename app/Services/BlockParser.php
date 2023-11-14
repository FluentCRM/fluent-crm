<?php

namespace FluentCrm\App\Services;

use FluentCrm\App\Services\BlockRender\WooProduct;
use FluentCrm\Framework\Support\Arr;

class BlockParser
{
    public function __construct($subscriber = null)
    {
        BlockParserHelper::setSubscriber($subscriber);

        if (!fluentCrmRunTimeCache('fluentcrm_block_parser_initiated')) {
            fluentCrmRunTimeCache('fluentcrm_block_parser_initiated', 'yes');
            add_filter('render_block', array($this, 'alterBlockContent'), 999, 2);
        }
    }

    public function parse($content)
    {
        $blocks = parse_blocks($content);
        $output = '';
        foreach ($blocks as $block) {
            $block = $this->sanitizeBlock($block);
            $output .= render_block($block);
        }

        return $output;
    }

    private function sanitizeBlock($block)
    {
        if (!empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $index => $childBlock) {
                $block['innerBlocks'][$index] = $this->sanitizeBlock($childBlock);
            }
        }

        $blockName = $block['blockName'];

        if ($blockName == 'core/columns') {
            $blockCounts = count($block['innerBlocks']);
            $lastContentIndex = $blockCounts * 2;
            foreach ($block['innerBlocks'] as $blockIndex => $blockItem) {
                $block['innerBlocks'][$blockIndex]['fc_total_blocks'] = $blockCounts;
            }
            $block['innerContent'][0] = $this->getRowOpening($block);
            $block['innerContent'][$lastContentIndex] = $this->getRowClosing($block);
        } else if ($blockName == 'core/media-text') {
            $blockCounts = count($block['innerBlocks']);
            $lastContentIndex = $blockCounts * 2;
            $block['innerContent'][0] = $this->getMediaTextOpening($block);
            $block['innerContent'][$lastContentIndex] = $this->getMediaTextClosing($block);
        } else if ($blockName == 'core/buttons') {
            $blockCounts = count($block['innerBlocks']);
            $lastContentIndex = $blockCounts * 2;
            foreach ($block['innerBlocks'] as $blockIndex => $blockItem) {
                $block['innerBlocks'][$blockIndex]['fc_total_blocks'] = $blockCounts;
                $block['innerBlocks'][$blockIndex]['parent_attrs'] = $block['attrs'];
            }
            $block['innerContent'][0] = $this->getButtonsOpening($block);
            $block['innerContent'][$lastContentIndex] = $this->getButtonsClosing($block);
        } else if ($blockName == 'core/image') {
            $block['innerContent'][0] = $this->getImageBlockHtml($block);
        } else if ($blockName == 'core/latest-posts') {
            $block['blockName'] = 'fluent-crm/core-posts';
            $block['fc_total_blocks'] = 1;
        } else if ($blockName == 'fluentcrm/woo-product') {
            $block['innerContent'][0] = '';
            $block['innerContent'][2] = '';
            $block['fc_total_blocks'] = 1;
        } else if ($blockName == 'fluent-crm/latest-posts') {
            $block['innerContent'][0] = '';
            $block['innerContent'][2] = '';
            $block['fc_total_blocks'] = 1;
        } else if ($blockName == 'fluent-crm/products') {
            $block['innerContent'][0] = '';
            $block['innerContent'][2] = '';
            $block['fc_total_blocks'] = 1;
        }

        return $block;
    }

    public function alterBlockContent($content, $data)
    {
        if (isset($data['blockName']) && $data['blockName'] == 'fluentcrm/conditional-group') {
            return $this->renderConditionalBlock($content, $data);
        }

        if (empty($data['fc_total_blocks'])) {
            return $content;
        }

        $blockName = $data['blockName'];

        if ($blockName == 'core/column') {
            $content = $this->getColumnOpening($data) . $content . $this->getColumnClosing($data);
        } else if ($blockName == 'core/button') {
            $content = $this->getButtonWrapper($content, $data);
        } else if ($blockName == 'fluent-crm/core-posts') {
            $content = $this->renderLatestPosts($data);
        } else if ($blockName == 'fluentcrm/woo-product') {
            $content = WooProduct::renderProduct($content, $data);
        } else if ($blockName == 'fluent-crm/latest-posts') {

            $content = '';
            if (class_exists('\FluentCampaign\App\Services\PostParser\LatestPost')) {
                $content = \FluentCampaign\App\Services\PostParser\LatestPost::renderPosts($content, $data);
            }
        } else if ($blockName == 'fluent-crm/products') {
            $content = '';
            if (class_exists('\FluentCampaign\App\Services\PostParser\LatestProductsBlock')) {
                $content = \FluentCampaign\App\Services\PostParser\LatestProductsBlock::renderProducts($content, $data);
            }
        }

        return $content;
    }

    private function getMediaTextOpening($block)
    {
        $backgroundColorClass = Arr::get($block, 'attrs.backgroundColor');
        $prevContent = $block['innerContent'][0];
        preg_match('/<figure (.*?)<\/figure>/s', $prevContent, $match);
        $figure = $match[0];
        $mediaWidth = Arr::get($block, 'attrs.mediaWidth', 50);
        $contentWidth = 100 - $mediaWidth;
        $MediaAlign = Arr::get($block, 'attrs.mediaPosition', 'left');
        $textAlign = ($MediaAlign == 'right') ? 'left' : 'right';

        $imageFill = Arr::get($block, 'attrs.imageFill') ? 'has_bg_image' : 'no_image_fill';

        $background = Arr::get($block, 'attrs.style.color.background');
        $extraCss = '';
        if (!$backgroundColorClass && $background) {
            $extraCss = 'background: ' . $background . ';background-color:' . $background . ';';
        }
        if ($backgroundColorClass) {
            $backgroundColorClass = 'has-' . Helper::kebabCase($backgroundColorClass) . '-background-color';
        }
        $html = '<table class="fce_row fc_row_media_text ' . $backgroundColorClass . '" border="0" cellpadding="0" cellspacing="0" width="100%" style="' . $extraCss . 'table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;"><tbody><tr>';
        $html .= '<td><table class="fc_media_table" align="' . $MediaAlign . '" style="width: ' . $mediaWidth . '%;" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td align="left" class="' . $imageFill . '" valign="middle">' . $figure . '</td></tr></tbody></table>';
        $html .= '<table class="fc_media_text" valign="middle" align="' . $textAlign . '" style="width: ' . $contentWidth . '%;" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td style="padding: 30px 20px 10px;" align="left" valign="middle">';
        return $html;
    }

    private function getMediaTextClosing($block)
    {
        return '</td></tr></tbody></table></td></tr></tbody></table>';
    }

    private function getRowOpening($block)
    {
        $isStackOnMobile = Arr::get($block, 'attrs.isStackedOnMobile', true);

        $background = Arr::get($block, 'attrs.style.color.background');
        $defaultBackground = Arr::get($block, 'attrs.backgroundColor');

        $style = 'margin-bottom: 10px;';
        if ($background) {
            $style .= 'background-color:' . $background . ';';
        } else if ($defaultBackground) {
            $defaultBackground = 'has-' . Helper::kebabCase($defaultBackground) . '-background-color';
        }

        $class = 'fce_row';

        if ($isStackOnMobile) {
            $class = 'fce_row fce_stacked';
        }

        return '<table class="' . esc_attr($class) . ' ' . esc_attr($defaultBackground) . '" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;' . $style . '"><tbody><tr>';
    }

    private function getRowClosing($block)
    {
        return '</tr></tbody></table>';
    }

    private function getColumnOpening($block)
    {

        $width = Arr::get($block, 'attrs.width');
        if (!$width) {
            $total = !empty($block['fc_total_blocks']) ? $block['fc_total_blocks'] : 1;
            $width = 100 / $total;
        }
        $vAlign = Arr::get($block, 'attrs.verticalAlignment', 'middle');
        return '<td align="center" valign="' . $vAlign . '" width="' . $width . '%" class="fce_column"><table border="0" cellpadding="10" cellspacing="0" width="100%"><tr><td class="fc_column_content">';
    }

    private function getColumnClosing($block)
    {
        return '</td></tr></table></td>';
    }

    private function getButtonsOpening($block)
    {
        $alignment = Arr::get($block, 'innerBlocks.0.attrs.align', '');
        $align = Arr::get($block, 'attrs.layout.justifyContent', 'left');
        $tableCssClass = 'fce_row fce_buttons_row';

        $width = 'auto';
        if ($align == 'right' || $alignment == 'center') {
            $width = '100%';
        } else if (Arr::get($block, 'innerBlocks.0.attrs.width') == 100) {
            $width = '100%';
        }

        $tableCssClass .= ' tb_btn_' . $alignment;

        if ($definedWidth = Arr::get($block, 'innerBlocks.0.attrs.width')) {
            $tableCssClass .= ' wp-block-button__width-' . $definedWidth;
        }

        $btnCount = count(Arr::get($block, 'innerBlocks'));

        if ($btnCount > 1) {
            $tableCssClass .= ' fc_btn_multiple fc_btn_count_' . $btnCount;
        }

        $extraStyle = '';
        if ($spacings = Arr::get($block, 'attrs.style.spacing.margin', [])) {

            if(!empty($spacings['top'])) {
                $extraStyle .= 'margin-top:' . $spacings['top'] . ';';
            }

            if(!empty($spacings['bottom'])) {
                $extraStyle .= 'margin-bottom:' . $spacings['bottom'] . ';';
            }
        }

        return '<table valign="middle" align="' . $align . '" class="' . $tableCssClass . '" border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%; width: ' . $width . '; float:none;' . $extraStyle . '"><tbody><tr>';
    }

    private function getButtonWrapper($content, $data)
    {
        $defaultClass = Arr::get($data, 'attrs.className', '');
        $backgroundColor = Arr::get($data, 'attrs.style.color.background');
        if (!$backgroundColor) {
            $bgClass = Arr::get($data, 'attrs.backgroundColor');
            $backgroundColor = Helper::getColorSchemeValue($bgClass);
        }

        $hasTextColor = Arr::get($data, 'attrs.style.color.text') || Arr::get($data, 'attrs.textColor');

        $btn_wrapper_class = $defaultClass . ' ';
        if (!$backgroundColor && $defaultClass != 'is-style-outline') {
            $btn_wrapper_class .= 'fc_d_btn_bg ';
            $backgroundColor = '#32373c';
        }

        if (!$hasTextColor) {
            $btn_wrapper_class .= 'fc_d_btn_color ';
        }

        $additionalStyle = '';
        if ($defaultClass == 'is-style-outline') {
            if (!$backgroundColor) {
                $backgroundColor = 'white';
            }
            $textColor = Arr::get($data, 'attrs.style.color.text');
            if (!$textColor) {
                $textColorName = Arr::get($data, 'attrs.textColor');
                $textColor = Helper::getColorSchemeValue($textColorName);
            }

            if (!$textColor) {
                $textColor = '#000000';
            }

            $additionalStyle = 'border: 1px solid ' . $textColor;
        }

        $borderRadius = Arr::get($data, 'attrs.style.border.radius', '0px');

        $content = trim(preg_replace("/<\/?div[^>]*\>/i", "", $content));

        $td = '<td class="fc_btn ' . trim($btn_wrapper_class) . '" align="center" style="border-radius: ' . $borderRadius . '; ' . $additionalStyle . '" bgcolor="' . $backgroundColor . '">';

        $align = Arr::get($data, 'parent_attrs.parent_attrs.layout.justifyContent', 'center');

        $alignment = $align == 'center' ? 'text-align: -webkit-center' : ' ';

        return '<td style="padding-right: 10px;' . $alignment . '" align="' . $align . '" valign="middle" class="fce_column"><table style="margin-bottom: 4px; margin-top: 4px;" border="0" cellspacing="0" cellpadding="0"><tr>' . $td . $content . '</td></tr></table></td>';
    }

    private function getButtonsClosing($block)
    {
        return '</tr></tbody></table>';
    }

    private function renderConditionalBlock($content, $data)
    {
        $subscriber = BlockParserHelper::getSubscriber();

        if (!$subscriber) {
            return $content;
        }

        $tagIds = Arr::get($data, 'attrs.tag_ids');
        if (!$tagIds) {
            return '';
        }

        $checkType = Arr::get($data, 'attrs.condition_type', 'show_if_tag_exist');
        $tagMatched = $subscriber->hasAnyTagId($tagIds);

        if ($checkType == 'show_if_tag_exist') {
            if ($tagMatched) {
                return $content;
            };
            return '';
        }

        if ($checkType == 'show_if_tag_not_exist') {
            if ($tagMatched) {
                return '';
            };
            return $content;
        }

        return '';
    }

    private function getImageBlockHtml($block)
    {
        $classNames = implode(' ', array_filter([
            Arr::get($block, 'attrs.className'),
            'wp-block-image size-' . Arr::get($block, 'attrs.sizeSlug'),
            'align' . Arr::get($block, 'attrs.align', 'left')
        ]));
        $radius = Arr::get($block, 'attrs.style.border.radius', '0px');
        $marginTop  = $this->getSpacing('attrs.marginTop', $block);
        $marginBottom  = $this->getSpacing('attrs.marginBottom', $block);
        $marginLeft  = $this->getSpacing('attrs.marginLeft', $block);
        $marginRight  = $this->getSpacing('attrs.marginRight', $block);

        $paddingTop  = $this->getSpacing('attrs.paddingTop', $block);
        $paddingBottom  = $this->getSpacing('attrs.paddingBottom', $block);
        $paddingLeft  = $this->getSpacing('attrs.paddingLeft', $block);
        $paddingRight  = $this->getSpacing('attrs.paddingRight', $block);

        $margin  = ''.$marginTop.'px '.$marginRight.'px '.$marginBottom.'px '.$marginLeft.'px';
        $padding = ''.$paddingTop.'px '.$paddingRight.'px '.$paddingBottom.'px '.$paddingLeft.'px';


        $content = $block['innerContent'][0];
        $html = strip_tags($content, '<a><figcaption><img>');
        $html = str_replace(['<figcaption', 'figcaption/>'], ['<p', '/p>'], $html);
        $html = '<div class="' . $classNames .'" style="border-radius: ' . $radius . '; margin: '. $margin . '; padding: '.$padding.'">'. $html . '</div>';
        return $html;
    }

    private function renderLatestPosts($attributes)
    {
        return '';
    }

    private  function getSpacing($key, $block)
    {
        $data  = Arr::get($block, $key, '0');
        if (empty($data)) {
            $data = '0';
        }
        return $data;
    }
}
