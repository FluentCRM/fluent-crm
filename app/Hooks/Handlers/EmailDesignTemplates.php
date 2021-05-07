<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\Includes\Emogrifier\Emogrifier;

class EmailDesignTemplates
{
    public function addPlainTemplate($emailBody, $templateData, $campaign)
    {
        $view = FluentCrm('view');
        $emailBody = $view->make('emails.plain.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return $emogrifier->emogrify();
    }

    public function addSimpleTemplate($emailBody, $templateData, $campaign)
    {
        if(empty($templateData['config']['body_bg_color'])) {
            $templateData['config']['body_bg_color'] = '#FAFAFA';
        }

        if(empty($templateData['config']['content_bg_color'])) {
            $templateData['config']['content_bg_color'] = '#ffffff';
        }

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.simple.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return $emogrifier->emogrify();
    }

    public function addClassicTemplate($emailBody, $templateData, $campaign)
    {
        if(empty($templateData['config']['content_bg_color'])) {
            $templateData['config']['content_bg_color'] = '#ffffff';
        }

        $view = FluentCrm('view');
        $emailBody = $view->make('emails.classic.Template', $templateData);
        $emailBody = $emailBody->__toString();

        $emogrifier = new Emogrifier($emailBody);
        $emogrifier->disableInvisibleNodeRemoval();
        return  $emogrifier->emogrify();
    }

}
