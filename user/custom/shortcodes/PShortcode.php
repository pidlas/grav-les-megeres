<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class PShortcode extends Shortcode {
    public function init() {
        $this->shortcode->getHandlers()->add('p', function (ShortcodeInterface $sc) {
            // 1. Récupération et nettoyage de l'attribut class
            $class = trim((string) $sc->getParameter('class', ''));

            // 2. Récupération du contenu textuel brut situé entre [p] et [/p]
            $content = $sc->getContent();

            // 3. Construction des attributs HTML de la balise
            $attributes = [];
            if ($class !== '') {
                $attributes[] = "class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'";
            }

            // 4. Génération de la structure HTML sémantique
            $output = '<p';
            if (!empty($attributes)) {
                $output .= ' ' . implode(' ', $attributes);
            }
            $output .= '>';
            
            // On y ajoute le contenu brut. Il sera interprété par le filtre Markdown plus tard.
            $output .= $content;
            $output .= '</p>';

            return $output;
        });
    }
}