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
            // ✅ AJOUT : Récupère l'option lazy (vrai par défaut)
            $lazy = $sc->getParameter('lazy', 'true');
            
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

            // Sécurisation et préparation des attributs de classe
            $classAttr = $class ? " class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";
            $pictureClassAttr = $class ? " class='wrapper-" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";
            
            // ✅ AJOUT : Détermination dynamique de l'attribut loading
            $loading = ($lazy === 'false' || $lazy === false) ? 'eager' : 'lazy';
            
            $altEscaped = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
            
            // ✅ CORRECTION : Injection de l'attribut {$loading} dynamique
            return "<picture{$pictureClassAttr}>
                <source type='image/avif' srcset='{$avifSrcset}' sizes='(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw'>
                <source type='image/webp' srcset='{$webpSrcset}' sizes='(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw'>
                <source type='image/jpeg' srcset='{$jpegSrcset}' sizes='(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw'>
                <img src='{$fallbackUrl}' alt='{$altEscaped}'{$classAttr} loading='{$loading}'>
            </picture>";

        });
    }
}