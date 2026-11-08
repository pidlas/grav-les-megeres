<?php
namespace Grav\Theme;

use Grav\Common\Theme;
use RocketTheme\Toolbox\Event\Event;

class Quark2Child extends Theme
{
    public static function getSubscribedEvents()
    {
        return [
            /*'onThemeInitialized'  => ['onThemeInitialized', 0],*/
            'onShortcodeHandlers' => ['onShortcodeHandlers', 0],
            'onAssetsInitialized' => ['onAssetsInitialized', 0]
        ];
    }

    public function onThemeInitialized()
    {
        // Force Grav à charger et prioriser les templates et assets du thème enfant
    }

    public function onShortcodeHandlers(Event $e)
    {
        $this->grav['shortcode']->registerAllShortcodes(__DIR__ . '/shortcodes');
    }

    /* COMMENTAIRE : Gestion centralisée et hiérarchisée de l'ensemble de vos ressources CSS */
    public function onAssetsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        $assets = $this->grav['assets'];

        // --- NIVEAU 1 : FONDATIONS & POLICES (PRIORITÉ ABSOLUE) ---
        // Justification : Écrase immédiatement la charte graphique par défaut de Spectre
        $assets->addCss('theme://css/all.min.css', ['priority' => 110]);
        $assets->addCss('theme://css/_variables.css', ['priority' => 105]);

        // --- NIVEAU 2 : SQUELETTE STRUCTUREL ---
        // Justification : Déploie vos conteneurs, votre .wrapper et vos requêtes média ouvertes
        $assets->addCss('theme://css/_layout.css', ['priority' => 100]);

        // --- NIVEAU 3 : COMPOSANTS INTERACTIFS STANDARDS ---
        // Justification : Applique les styles de votre menu, accordéon et flux de base
        $assets->addCss('theme://css/_components.css', ['priority' => 95]);

        // --- NIVEAU 4 : COMPOSANTS SPÉCIFIQUES MÉDIAS ET PAGES ---
        // Justification : Chargés après les composants standards pour enrichir les mises en page dédiées
        $assets->addCss('theme://css/video.css', ['priority' => 90]);
        $assets->addCss('theme://css/audio.css', ['priority' => 90]);
        $assets->addCss('theme://css/spectacle-opression.css', ['priority' => 85]);

        // --- NIVEAU 5 : AJUSTEMENTS DE DERNIÈRE MINUTE ---
        // Justification : Le custom.css clôture la cascade pour d'éventuelles corrections volantes
        $assets->addCss('theme://css/custom.css', ['priority' => 80]);
    }
}