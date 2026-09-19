<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class PictureShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('picture', function(ShortcodeInterface $sc) {
            $filename = $sc->getParameter('name') ?? $sc->getParameter('src');
            $alt = $sc->getParameter('alt', $this->grav['page']->title());
            $class = $sc->getParameter('class', ''); // ✅ AJOUT : Récupération de la classe
            
            $page = $this->grav['page'] ?? null;
            if (!$page || !method_exists($page, 'media')) {
                return '';
            }

            $image = $page->media()[$filename] ?? null;

            if (!$image) {
                return "<!-- Image [picture] manquante dans le dossier : $filename -->";
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $derivatives = $image->derivatives(400, 1200, 400);

            if ($extension === 'avif') {
                $avifSrcset = $derivatives->srcset();
                try {
                    $webpSrcset = $derivatives->format('webp')->srcset();
                    $jpegSrcset = $derivatives->format('jpg')->srcset(); 
                    $fallbackUrl = $image->resize(1200)->format('jpg')->url();
                } catch (\Exception $e) {
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

            // ✅ AJOUT : Sécurisation et préparation des attributs de classe
            $classAttr = $class ? " class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";
            $pictureClassAttr = $class ? " class='wrapper-" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";

            $altEscaped = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

            // ✅ CORRECTION : Injection des variables de classe dans le HTML
            return "<picture{$pictureClassAttr}>
                <source type='image/avif' srcset='{$avifSrcset}' sizes='(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw'>
                <source type='image/webp' srcset='{$webpSrcset}' sizes='(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw'>
                <source type='image/jpeg' srcset='{$jpegSrcset}' sizes='(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw'>
                <img src='{$fallbackUrl}' alt='{$altEscaped}'{$classAttr} loading='lazy'>
            </picture>";

        });
    }
}