<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class PictureShortcode extends Shortcode
{
    public function init()
    {
        // 👇 CORRECTION : On utilise getHandlers()->add() pour éviter le plantage de méthode indéfinie
        $this->shortcode->getHandlers()->add('picture', function(ShortcodeInterface $sc) {
            $filename = $sc->getParameter('name');
            $alt = $sc->getParameter('alt', $this->grav['page']->title());
            
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

            // Gestion intelligente si l'image d'origine est déjà un .avif
            if ($extension === 'avif') {
                $avifSrcset = $derivatives->srcset();
                try {
                    $webpSrcset = $derivatives->format('webp')->srcset();
                    $fallbackUrl = $image->resize(1200)->format('jpg')->url();
                } catch (\Exception $e) {
                    $webpSrcset = $avifSrcset;
                    $fallbackUrl = $image->resize(1200)->url();
                }
            } else {
                $avifSrcset = $derivatives->format('avif')->srcset();
                $webpSrcset = $derivatives->format('webp')->srcset();
                $fallbackUrl = $image->resize(1200)->url();
            }

            return "<picture>
                <source type='image/avif' srcset='{$avifSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <source type='image/webp' srcset='{$webpSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <source type='image/jpeg' srcset='{$jpegSrcset}' sizes='(max-width:400px) 100vw, (max-width:800px) 50vw, 33vw'>
                <img src='{$fallbackUrl}' alt='{$alt}' loading='lazy'>
            </picture>";
        });
    }
}