<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class LinkShortcode extends Shortcode
{
    public function init()
    {
        // Enregistrement explicite du handler 'a'
        $this->shortcode->getHandlers()->add('a', function (ShortcodeInterface $sc) {
            
            // 1. Récupération et nettoyage des attributs
            $url = trim((string) $sc->getParameter('url', $sc->getParameter('href', '#')));
            $alt = self::escAttr($sc->getParameter('alt', ''));
            $class = self::escAttr($sc->getParameter('class', ''));
            $target = trim((string) $sc->getParameter('target', ''));
            
            // 2. Récupération du texte entre [a] et [/a]
            $content = $sc->getContent();

            // 3. Détection automatique si le lien est externe ou interne
            $is_external = (strpos($url, 'http') === 0);

            $target_attr = '';
            $data_type = 'internal'; // Par défaut, le lien est interne

            if ($target === '_blank' || $is_external) {
                $target_attr = " target='_blank' rel='noopener noreferrer'";
                $data_type = 'external'; // Si c'est http ou _blank, il devient externe
            }

            // 4. Construction des attributs de la balise (avec injection du data-type)
            $attributes = [];
            $attributes[] = "href='" . self::escAttr($url) . "'";
            $attributes[] = "data-type='" . $data_type . "'"; // <--- Injection automatique ici
            
            if ($class !== '') {
                $attributes[] = "class='" . $class . "'";
            }
            if ($alt !== '') {
                $attributes[] = "title='" . $alt . "' alt='" . $alt . "'";
            }

            // 5. Génération du HTML de la balise <a>
            $output = '<a ' . implode(' ', $attributes) . $target_attr . '>';
            $output .= $content;
            $output .= '</a>';

            // 6. Gestion automatique des spans graphiques additionnels (si nécessaires en CSS)
            if ($is_external) {
                $output .= '<span class="lien-e-seul"></span>';
            } elseif (strpos($url, '/contact') !== false || $class === 'lien-interne') {
                $output .= '<span class="lien-i-seul"></span>';
            }

            return $output;
        });
    }
}