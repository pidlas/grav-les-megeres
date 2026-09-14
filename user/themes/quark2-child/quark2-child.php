<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use RocketTheme\Toolbox\Event\Event;

class Quark2Child extends Theme
{
    public static function getSubscribedEvents()
    {
        return [
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0]
        ];
    }

    public function onShortcodeHandlers(Event $e)
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }
}