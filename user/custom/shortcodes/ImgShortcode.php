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
            $lazy = $sc->getParameter('lazy', 'true');
            
            // Récupération de la page courante par défaut
            $page = $this->grav['page'] ?? null;
            if (!$page || !method_exists($page, 'media')) {
                return '';
            }

            // --- AJOUT : Gestion du dossier parent si préfixé par parent:// ---
            if (strpos($filename, 'parent://') === 0) {
                $filename = str_replace('parent://', '', $filename); // On retire le préfixe pour avoir le nom propre
                $page = $page->parent(); // On bascule sur la page parente
            }

            // Recherche de l'image dans la page sélectionnée (courante ou parente)
            $image = $page->media()[$filename] ?? null;

            if (!$image) {
                return "<!-- Image [img] manquante dans le dossier : $filename -->";
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $derivatives = $image->derivatives(400, 1200, 400);

            if ($extension === 'avif') {
                $avifSrcset = $derivatives->srcset();
                try {
                    // On garde le WebP puisque Grav arrive à le générer depuis l'AVIF !
                    $webpSrcset = $derivatives->format('webp')->srcset();
                    // Pour le JPEG, on lui attribue l'AVIF (ou le WebP) pour éviter le plantage
                    $jpegSrcset = $avifSrcset;
                    // Le fallback devient l'AVIF d'origine redimensionné de manière brute (sans JPG fantôme)
                    $fallbackUrl = $image->resize(1200)->url(false);
                } catch (\Exception $e) {
                    // Filet de sécurité ultime si même le WebP échoue
                    $webpSrcset = $avifSrcset;
                    $jpegSrcset = $avifSrcset;
                    $fallbackUrl = $image->resize(1200)->url(false);
                }
            } else {
                $avifSrcset = $derivatives->format('avif')->srcset();
                $webpSrcset = $derivatives->format('webp')->srcset();
                $jpegSrcset = $derivatives->srcset();
                $fallbackUrl = $image->resize(1200)->url();
            }


            $classAttr = $class ? " class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";
            $pictureClassAttr = $class ? " class='wrapper-" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'" : "";
            
            $loading = ($lazy === 'false' || $lazy === false) ? 'eager' : 'lazy';
            $altEscaped = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
            
            return "<picture{$pictureClassAttr}>
                <source type=\"image/avif\" srcset=\"{$avifSrcset}\" sizes=\"(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw\">
                <source type=\"image/webp\" srcset=\"{$webpSrcset}\" sizes=\"(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw\">
                <source type=\"image/jpeg\" srcset=\"{$jpegSrcset}\" sizes=\"(max-width:400px) 33vw, (max-width:800px) 55vw, 100vw\">
                <img src=\"{$fallbackUrl}\" alt=\"{$altEscaped}\"{$classAttr} loading=\"{$loading}\">
            </picture>";

        });
    }
}