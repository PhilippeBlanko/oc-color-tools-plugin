<?php namespace Ducharme\ColorTools\Classes;

/**
 * ColorExtension
 *
 * Extension Twig fournissant des filtres et fonctions pour manipuler
 * les couleurs côté frontend (conversion, contraste, accessibilité).
 */
class ColorExtension
{
    /* =====================================
     * Filtres de Conversion
     * ===================================== */

    /**
     * Convertit une couleur en format hexadécimal
     *
     * Usage Twig : {{ 'rgb(255,0,0)'|to_hex }}
     *              {{ 'hsl(0,100%,50%)'|to_hex }}
     *
     * @param string $color Couleur à convertir
     * @return string|null Couleur hex (#rrggbb) ou null si invalide
     */
    public static function toHex($color)
    {
        return ColorHelper::normalizeColor($color, false);
    }

    /**
     * Convertit une couleur en format RGB
     *
     * Usage Twig : {{ '#ff0000'|to_rgb }}
     *
     * @param string $color Couleur à convertir
     * @return string|null Chaîne 'rgb(r, g, b)' ou null si invalide
     */
    public static function toRgb($color)
    {
        $c = ColorHelper::parseColor($color);
        if (!$c) {
            return null;
        }

        return sprintf('rgb(%d, %d, %d)', $c['r'], $c['g'], $c['b']);
    }

    /**
     * Convertit une couleur en format RGBA avec alpha spécifié
     * Si l'alpha n'est pas précisé, conserve l'alpha d'origine de la couleur
     *
     * Usage Twig : {{ '#ff0000'|to_rgba(0.7) }}
     *              {{ 'blue'|to_rgba(0.5) }}
     *
     * @param string $color Couleur à convertir
     * @param float|null $alpha Valeur alpha (0-1), défaut null (conserve l'original)
     * @return string|null Chaîne 'rgba(r, g, b, a)' ou null si invalide
     */
    public static function toRgba($color, $alpha = null)
    {
        $c = ColorHelper::parseColor($color);
        if (!$c) {
            return null;
        }

        // On utilise l'alpha fourni, sinon celui détecté dans la couleur source
        $a = ($alpha !== null) ? max(0.0, min(1.0, floatval($alpha))) : $c['a'];

        return sprintf('rgba(%d, %d, %d, %.2f)', $c['r'], $c['g'], $c['b'], $a);
    }

    /**
     * Convertit une couleur en format HSL
     *
     * Usage Twig : {{ '#ff0000'|to_hsl }}
     *
     * @param string $color Couleur à convertir
     * @return string|null Chaîne 'hsl(h, s%, l%)' ou null si invalide
     */
    public static function toHsl($color)
    {
        $c = ColorHelper::parseColor($color);
        if (!$c) {
            return null;
        }

        $hsl = ColorHelper::rgbToHsl($c['r'], $c['g'], $c['b']);
        return sprintf('hsl(%.0f, %.0f%%, %.0f%%)', $hsl['h'], $hsl['s'], $hsl['l']);
    }

    /* =====================================
     * Filtres d'Analyse et Propriétés
     * ===================================== */

    /**
     * Calcule la luminance relative d'une couleur (0-1)
     *
     * Usage Twig : {{ '#ffffff'|luminance }}
     *
     * @param string $color Couleur à analyser
     * @return float|null Luminance (0-1) ou null si invalide
     */
    public static function luminance($color)
    {
        return ColorHelper::relativeLuminance($color);
    }

    /**
     * Calcule le ratio de contraste WCAG entre deux couleurs
     *
     * Usage Twig : {{ myTextColor|contrast_ratio(myBgColor) }}
     *
     * @param string $foreground Couleur de premier plan
     * @param string $background Couleur d'arrière-plan
     * @return float|null Ratio (1-21) ou null si invalide
     */
    public static function contrastRatio($foreground, $background)
    {
        return ColorHelper::contrastRatioWithAlpha($foreground, $background);
    }

    /**
     * Détermine la meilleure couleur de texte (noir ou blanc) pour un fond
     *
     * Usage Twig : {{ myBgColor|best_text_color }}
     *
     * @param string $background Couleur d'arrière-plan
     * @param string $underlying Couleur sous-jacente si alpha (défaut: blanc)
     * @return string|null '#000000' ou '#ffffff', ou null si erreur
     */
    public static function bestTextColor($background, $underlying = '#ffffff')
    {
        return ColorHelper::bestTextColorWithAlpha($background, $underlying);
    }

    /* =====================================
     * Fonctions de Manipulation Avancée
     * ===================================== */

    /**
     * Mélange deux couleurs selon un ratio
     *
     * Usage Twig : {{ color_mix('#ff0000', '#0000ff', 0.5) }}
     *              {{ color_mix(primaryColor, secondaryColor, 0.3) }}
     *
     * @param string $color1 Première couleur
     * @param string $color2 Deuxième couleur
     * @param float $ratio Ratio de mélange (0-1), défaut 0.5
     * @return string|null Couleur hex mélangée ou null si invalide
     */
    public static function colorMix($color1, $color2, $ratio = 0.5)
    {
        $mixed = ColorHelper::mixColors($color1, $color2, $ratio);
        if (!$mixed) {
            return null;
        }

        return ColorHelper::rgbToHex($mixed);
    }

    /**
     * Compose une couleur avec alpha sur un arrière-plan
     *
     * Usage Twig : {{ color_compose('rgba(255,0,0,0.5)', '#ffffff') }}
     *
     * @param string $foreground Couleur de premier plan (avec alpha)
     * @param string $background Couleur d'arrière-plan
     * @return string|null Couleur hex composée ou null si invalide
     */
    public static function colorCompose($foreground, $background)
    {
        $composed = ColorHelper::composeColor($foreground, $background);
        return $composed ? ColorHelper::rgbToHex($composed) : null;
    }

    /**
     * Vérifie si le contraste respecte les normes WCAG
     *
     * Usage Twig : {% if is_contrast_ok(textColor, bgColor, 'AA', 'normal') %}
     *              {% if is_contrast_ok(myColor, '#ffffff') %}
     *
     * @param string $foreground Couleur de premier plan
     * @param string $background Couleur d'arrière-plan
     * @param string $level Niveau WCAG : 'AA' ou 'AAA' (défaut: 'AA')
     * @param string $size Taille : 'normal', 'large' ou 'ui' (défaut: 'normal')
     * @return bool|null True si conforme, false sinon, null si erreur
     */
    public static function isContrastOk($foreground, $background, $level = 'AA', $size = 'normal')
    {
        return ColorHelper::isContrastOkWithAlpha($foreground, $background, $level, $size);
    }

    /**
     * Génère une couleur aléatoire
     *
     * Usage Twig : {{ color_random() }}
     *              {{ color_random(true) }}
     *
     * @param bool $fancy Si true, génère une jolie couleur vive (défaut: false)
     * @return string Couleur hexadécimale (#rrggbb)
     */
    public static function colorRandom($fancy = false)
    {
        return ColorHelper::randomColor($fancy);
    }
}
