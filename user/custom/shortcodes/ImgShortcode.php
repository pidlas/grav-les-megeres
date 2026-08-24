<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImgShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('img', function (ShortcodeInterface $sc) {
            $name = trim((string) $sc->getParameter('name'));
            if ($name === '') {
                return '';
            }

            $page = $this->grav['page'] ?? null;
            $alt = self::escAttr($sc->getParameter('alt', ''));
            $class = self::escAttr($sc->getParameter('class', ''));
            $loading = self::escAttr($sc->getParameter('loading') ?: 'lazy');

            if (!$page || !method_exists($page, 'media')) {
                return '';
            }

            $media = method_exists($page, 'media') ? $page->media() : null;
            if (!$media || !is_array($media->all()) && !($media instanceof \ArrayAccess)) {
                return '';
            }

            $image = null;
            if (is_array($media)) {
                $image = $media[$name] ?? null;
            } else {
                $image = $media[$name] ?? null;
            }

            if (!$image) {
                return '';
            }

            try {
                $url = method_exists($image, 'url') ? (string) $image->url() : '';
                if ($url === '') {
                    return '';
                }

                $attributes = [];
                $attributes[] = "src='" . self::escAttr($url) . "'";
                $attributes[] = "alt='" . $alt . "'";
                $attributes[] = "loading='" . $loading . "'";

                if ($class !== '') {
                    $attributes[] = "class='" . $class . "'";
                }

                return '<img ' . implode(' ', $attributes) . '>';
            } catch (\Throwable $e) {
                return '';
            }
        });
    }
}
