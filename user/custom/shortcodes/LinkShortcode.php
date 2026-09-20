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
            
            // Récupération d'un éventuel data-type forcé par l'utilisateur
            $forced_type = trim((string) $sc->getParameter('data-type', ''));

            // 2. Récupération du texte entre [a] et [/a]
            $content = $sc->getContent();

            // --- Détection des fichiers ---
            // Liste des extensions de téléchargement courantes
            $file_extensions = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'mp3', 'mp4', 'jpg', 'png'];
            $url_path = parse_url($url, PHP_URL_PATH);
            $extension = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
            $is_file = in_array($extension, $file_extensions);

            // 3. Détection automatique et dynamique (Interne vs Externe vs Fichier)
            if ($forced_type !== '') {
                // Si l'utilisateur a spécifié un type, on l'utilise directement (ex: internal, external, file)
                $data_type = $forced_type;
            } else {
                if ($is_file) {
                    $data_type = 'file'; // Détection automatique d'un fichier
                } else {
                    $data_type = 'internal'; // Par défaut
                    
                    // Vérification du domaine
                    $url_host = parse_url($url, PHP_URL_HOST);
                    if ($url_host) {
                        $current_host = $this->grav['uri']->host();
                        if (strcasecmp($url_host, $current_host) !== 0) {
                            $data_type = 'external';
                        }
                    }
                }
            }

            // Détermination du comportement d'ouverture (target)
            $is_external = ($data_type === 'external');
            $is_file_type = ($data_type === 'file');
            $target_attr = '';
            
            // On ouvre dans un nouvel onglet si c'est externe, si c'est un fichier, ou si forcé
            if ($target === '_blank' || $is_external || $is_file_type) {
                $target_attr = " target='_blank' rel='noopener noreferrer'";
            }

            // 4. Construction des attributs de la balise
            $attributes = [];
            $attributes[] = "href='" . self::escAttr($url) . "'";
            $attributes[] = "data-type='" . $data_type . "'";
            
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

            // 6. Gestion automatique des spans graphiques additionnels
            if ($is_external) {
                $output .= '<span class="lien-e-seul"></span>';
            } elseif ($is_file_type) {
                // Ajout d'une classe spécifique pour les icônes de téléchargement/fichier
                $output .= '<span class="lien-f-seul"></span>'; 
            } elseif (strpos($url, '/contact') !== false || $class === 'lien-interne') {
                $output .= '<span class="lien-i-seul"></span>';
            }

            return $output;
        });
    }
}