<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Services\Libs\Emogrifier\Emogrifier;
use FluentCrm\Framework\Support\Arr;

/**
 *  EmailDesignTemplates Class
 *
 * For handling email design templates
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */

class EmailDesignTemplates
{
    /**
     * @param string $emailBody
     * @param array $templateData
     * @param \FluentCrm\App\Models\Campaign $campaign
     * @return string
     */
    public function addPlainTemplate($emailBody, $templateData, $campaign)
    {
        $templateData = $this->filterTemplateData($templateData);

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.plain.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return $emogrifier->emogrify();
    }

    /**
     * @param string $emailBody
     * @param array $templateData
     * @param \FluentCrm\App\Models\Campaign $campaign
     * @return string
     */
    public function addSimpleTemplate($emailBody, $templateData, $campaign)
    {
        if(empty($templateData['config']['body_bg_color'])) {
            $templateData['config']['body_bg_color'] = '#FAFAFA';
        }

        if(empty($templateData['config']['content_bg_color'])) {
            $templateData['config']['content_bg_color'] = '#ffffff';
        }

        $templateData = $this->filterTemplateData($templateData);

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.simple.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return $emogrifier->emogrify();
    }

    /**
     * @param string $emailBody
     * @param array $templateData
     * @param \FluentCrm\App\Models\Campaign $campaign
     * @return string
     */
    public function addClassicTemplate($emailBody, $templateData, $campaign)
    {
        if(empty($templateData['config']['content_bg_color'])) {
            $templateData['config']['content_bg_color'] = '#ffffff';
        }

        $templateData = $this->filterTemplateData($templateData);

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.classic.Template', $templateData);
        $emailBody = $emailBody->__toString();

        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return  $emogrifier->emogrify();
    }

    /**
     * @param string $emailBody
     * @param array $templateData
     * @param \FluentCrm\App\Models\Campaign $campaign
     * @return string
     */
    public function addRawClassicTemplate($emailBody, $templateData, $campaign)
    {
        $templateData = $this->filterTemplateData($templateData);

        $configDefault = [
            'content_width' => '',
            'headings_font_family' => '',
            'text_color' => '',
            'headings_color' => '',
            'link_color' => '',
            'body_bg_color' => '',
            'content_bg_color' => '',
            'footer_text_color' => '',
            'content_font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
        ];

        $templateData['config'] = wp_parse_args($templateData['config'], $configDefault);

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.raw_classic.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return $emogrifier->emogrify();
    }

    public function addWebPreviewTemplate($emailBody, $templateData, $campaign)
    {
        $templateData = $this->filterTemplateData($templateData);

        $configDefault = [
            'content_width' => '',
            'headings_font_family' => '',
            'text_color' => '',
            'headings_color' => '',
            'link_color' => '',
            'body_bg_color' => '',
            'content_bg_color' => '',
            'footer_text_color' => '',
            'content_font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
        ];

        $templateData['config'] = wp_parse_args($templateData['config'], $configDefault);

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.web_preview.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return $emogrifier->emogrify();
    }

    private function filterTemplateData($templateData)
    {
        if(Arr::get($templateData, 'config.disable_footer') == 'yes') {
            $templateData['footer_text'] = '';
        }

        return $templateData;
    }

}
