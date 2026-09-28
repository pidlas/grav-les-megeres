<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Grav\Common\Utils;

class MylinkShortcode extends Shortcode
{
    public function init()
    {
        $this->shortcode->getHandlers()->add('a', function (ShortcodeInterface $sc) {
            
            // 1. Récupération et nettoyage des attributs
            $url = trim((string) $sc->getParameter('url', $sc->getParameter('href', '#')));
            $alt = Utils::getNonce($sc->getParameter('alt', '')); // Nettoyage sécurisé natif Grav
            $class = trim((string) $sc->getParameter('class', ''));
            $target = trim((string) $sc->getParameter('target', ''));
            $forced_type = trim((string) $sc->getParameter('data-type', ''));

            // 2. Récupération du texte entre [a] et [/a]
            $content = $sc->getContent();

            // --- Détection des fichiers ---
            $file_extensions = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'mp3', 'mp4', 'jpg', 'png', 'avif', 'webm'];
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
                    
                    // Vérification robuste du domaine si l'URL commence par http/https
                    if (preg_match('/^https?:\/\//i', $url)) {
                        $url_host = parse_url($url, PHP_URL_HOST);
                        
                        // Sécurité absolue : utilisation de la superglobale pour éviter le crash de l'objet URI
                        $current_host = $_SERVER['HTTP_HOST'] ?? '';
                        
                        if ($url_host && $current_host && strcasecmp($url_host, $current_host) !== 0) {
                            $data_type = 'external';
                        }
                    }
                }
            }

            // Détermination du comportement d'ouverture (target)
            $is_external = ($data_type === 'external');
            $is_file_type = ($data_type === 'file');
            $target_attr = '';
            
            if ($target === '_blank' || $is_external || $is_file_type) {
                $target_attr = " target='_blank' rel='noopener noreferrer'";
            }

            // 4. Construction des attributs de la balise
            $attributes = [];
            $attributes[] = "href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'";
            $attributes[] = "data-type='" . $data_type . "'";
            
            if ($class !== '') {
                $attributes[] = "class='" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "'";
            }
            if ($alt !== '') {
                $attributes[] = "title='" . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . "' alt='" . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . "'";
            }

            // 5. Génération du HTML de la balise <a>
            $output = '<a ' . implode(' ', $attributes) . $target_attr . '>';
            $output .= $content;
            $output .= '</a>';

            // 6. Gestion automatique des spans graphiques additionnels
            if ($is_external) {
                $output .= '<span class="lien-e-seul"></span>';
            } elseif ($is_file_type) {
                $output .= '<span class="lien-f-seul"></span>'; 
            } elseif (strpos($url, '/contact') !== false || $class === 'lien-interne') {
                $output .= '<span class="lien-i-seul"></span>';
            }

            return $output;
        });
    }
}