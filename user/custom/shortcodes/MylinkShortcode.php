<?php
namespace Grav\Plugin\Shortcodes;

use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Grav\Common\Utils;

class MylinkShortcode extends Shortcode {
    public function init() {
        $this->shortcode->getHandlers()->add('a', function (ShortcodeInterface $sc) {
            // 1. Récupération et nettoyage des attributs
            $url = $sc->getParameter('url', $sc->getParameter('href'));
            if ($url === null) {
                $url = $sc->getBbCode() ?: $sc->getParameter(0, '#');
            }
            $url = trim((string) $url);

            // Conversion du "@" anti-spam en "@" s'il est écrit en entité dans le Markdown
            $url = str_replace(['@', '&amp;#64;', '&#64;'], '@', $url);

            $alt = Utils::getNonce($sc->getParameter('alt', '')); 
            $class = trim((string) $sc->getParameter('class', ''));
            $target = trim((string) $sc->getParameter('target', ''));
            $forced_type = trim((string) $sc->getParameter('data-type', ''));

            // 2. Récupération du texte entre [a] et [/a]
            $content = $sc->getContent();
            // Nettoyage anti-spam du contenu également
            $content = str_replace(['@', '&amp;#64;', '&#64;'], '@', $content);

            // --- Détection des fichiers ---
            $file_extensions = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'mp3', 'mp4', 'jpg', 'png', 'avif', 'webm'];
            $url_path = parse_url($url, PHP_URL_PATH);
            $extension = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
            $is_file = in_array($extension, $file_extensions);

            // --- Détection des protocoles spéciaux ---
            $is_mailto = (strpos(strtolower($url), 'mailto:') === 0);
            $is_tel = (strpos(strtolower($url), 'tel:') === 0);

            // 3. Détection automatique et dynamique
            if ($forced_type !== '') {
                $data_type = $forced_type;
            } else {
                if ($is_mailto) {
                    $data_type = 'mailto'; 
                } elseif ($is_tel) {
                    $data_type = 'tel';
                } elseif ($is_file) {
                    $data_type = 'file'; 
                } else {
                    $data_type = 'internal'; 
                    
                    if (preg_match('/^https?:\/\//i', $url)) {
                        $url_host = parse_url($url, PHP_URL_HOST);
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
            
            if (($target === '_blank' || $is_external || $is_file_type) && !$is_mailto) {
                $target_attr = " target='_blank' rel='noopener noreferrer'";
            }

            // 4. Construction des attributs de la balise
            $attributes = [];
            
            // PROTECTION ANTI-SPAM (Base64 + HTML Entités)
            if ($is_mailto) {
                // Utilisation de rawurlencode pour s'assurer que les caractères spéciaux (accents) passent sans encombre en Base64
                $secure_encoded = base64_encode(rawurlencode($url));
                $unique_id = 'ml-' . uniqid();
                
                $attributes[] = "id='" . $unique_id . "'";
                $attributes[] = "href='#'"; 
                $attributes[] = "data-secure='" . $secure_encoded . "'";
                $attributes[] = "style='cursor:pointer;'";
                
                // AUTOMATISATION : On injecte automatiquement la classe de bouton
                $class = trim($class . ' btn-mailto');

                // Si le contenu visible ressemble à un email, on le convertit en entités HTML (compatible UTF-8)
                if (filter_var(trim($content), FILTER_VALIDATE_EMAIL) || strpos($content, '@') !== false) {
                    $masked = '';
                    $chars = preg_split('//u', $content, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($chars as $char) {
                        $code = mb_ord($char, 'UTF-8');
                        $r = rand(0, 100);
                        if ($r > 60) {
                            $masked .= '&#' . $code . ';';
                        } elseif ($r > 20) {
                            $masked .= '&#x' . dechex($code) . ';';
                        } else {
                            $masked .= $char;
                        }
                    }
                    $content = $masked;
                }
            } else {
                $attributes[] = "href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "'";
            }

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

            // --- SCRIPT JS DE DÉCODAGE ---
            if ($is_mailto) {
                $output .= "<script>
                (function() {
                    var el = document.getElementById('" . $unique_id . "');
                    if (el) {
                        var encoded = el.getAttribute('data-secure');
                        var binaryString = window.atob(encoded);
                        var bytes = new Uint8Array(binaryString.length);
                        for (var i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }
                        var decodedDecoder = new TextDecoder('utf-8');
                        var fullUrl = decodeURIComponent(decodedDecoder.decode(bytes));
                        
                        var decodeAndGo = function(e) {
                            e.preventDefault();
                            window.location.href = fullUrl;
                        };
                        el.addEventListener('pointerover', function() { el.setAttribute('href', fullUrl); }, {once: true});
                        el.addEventListener('click', decodeAndGo);
                    }
                })();
                </script>";
            }

            // Pour éviter l'auto-échappement HTML agressif de Grav 1.7+ uniquement sur ce shortcode,
            // on demande à Grav de traiter le retour via Twig en mode brut (Filtre |raw interne automatique)
            return $output;
        });
    }
}