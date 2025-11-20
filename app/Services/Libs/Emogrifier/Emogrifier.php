<?php

namespace FluentCrm\App\Services\Libs\Emogrifier;


class Emogrifier
{
    private $html = '';

    private $disableInvisibleNode = false;

    public function __construct($html)
    {
        $this->html = (string) $html;
    }

    public function disableInvisibleNodeRemoval()
    {
        $this->disableInvisibleNode = true;
        return $this;
    }

    public function emogrify()
    {
        // check if php version is less than 8.1
        if (version_compare(phpversion(), '8.1', '<')) {
            return $this->handleLegacy();
        }

        if (!class_exists('\FluentEmogrifier\Vendor\Pelago\Emogrifier\CssInliner')) {
            require_once __DIR__ . '/scoped-vendor/autoload.php';
        }

        if (!class_exists('\FluentEmogrifier\Vendor\Pelago\Emogrifier\CssInliner')) {
            return $this->handleLegacy();
        }

        // check if css inlines is available or not
        return \FluentEmogrifier\Vendor\Pelago\Emogrifier\CssInliner::fromHtml($this->html)
            ->inlineCss()
            ->render();
    }

    private function handleLegacy()
    {
        $emogrifier = new EmogrifierPhp7($this->html);
        if ($this->disableInvisibleNode) {
            $emogrifier->disableInvisibleNodeRemoval();
        }

        return $emogrifier->emogrify();
    }

}
