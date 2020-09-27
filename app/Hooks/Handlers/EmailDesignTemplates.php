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
        return $emogrifier->emogrify();
    }

    public function addSimpleTemplate($emailBody, $templateData, $campaign)
    {
        $view = FluentCrm('view');
        $emailBody = $view->make('emails.simple.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        return $emogrifier->emogrify();
    }

    public function addClassicTemplate($emailBody, $templateData, $campaign)
    {
        $view = FluentCrm('view');
        $emailBody = $view->make('emails.classic.Template', $templateData);
        $emailBody = $emailBody->__toString();
        $emogrifier = new Emogrifier($emailBody);
        return $emogrifier->emogrify();
    }

    public function addRawHtmlTemplate($emailBody, $templateData, $campaign)
    {
        $emogrifier = new Emogrifier($emailBody);
        return $emogrifier->emogrify();
    }
}
