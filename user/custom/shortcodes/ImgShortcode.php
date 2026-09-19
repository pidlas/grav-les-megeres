<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ImgShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('img', function (ShortcodeInterface $sc) {
            $filename = $sc->getParameter('name') ?? $sc->getParameter('src');
            $alt = $sc->getParameter('alt', $this->grav['page']->title());
            $class = $sc->getParameter('class', '');
            
            $page = $this->grav['page'] ?? null;
            if (!$page || !method_exists($page, 'media')) {
                return '';
            }

            $image = $page->media()[$filename] ?? null;

            if (!$image) {
                return "<!-- Image [img] manquante dans le dossier : $filename -->";
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            // .derivatives(min, max, step)
            $derivatives = $image->derivatives(400, 1200, 400);

            if ($extension === 'avif') {
                $avifSrcset = $derivatives->srcset();
                try {
                    $webpSrcset = $derivatives->format('webp')->srcset();
                    $jpegSrcset = $derivatives->format('jpg')->srcset();
                    $fallbackUrl = $image->resize(1200)->format('jpg')->url();
                } catch (\Exception $e) {
                    // Si la conversion échoue à la volée, on utilise l'AVIF natif partout
                    $webpSrcset = $avifSrcset;
                    $jpegSrcset = $avifSrcset;
                    $fallbackUrl = $image->resize(1200)->url();
                }
            } else {
                $avifSrcset = $derivatives->format('avif')->srcset();
                $webpSrcset = $derivatives->format('webp')->srcset();
                $jpegSrcset = $derivatives->srcset();
                $fallbackUrl = $image->resize(1200)->url();
            }

            $classAttr = $class ? " class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";
            $altEscaped = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
            
            return "<picture>
                <source type='image/avif' srcset='{$avifSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <source type='image/webp' srcset='{$webpSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <source type='image/jpeg' srcset='{$jpegSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <img src='{$fallbackUrl}' alt='{$altEscaped}'{$classAttr} loading='lazy'>
            </picture>";
        });
    }
}