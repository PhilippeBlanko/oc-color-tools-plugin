<?php namespace Ducharme\ColorTools\Classes;

/**
 * ColorHelper
 *
 * Classe utilitaire centrale pour parser, convertir et analyser les couleurs.
 * Supporte les formats : hex (#rgb, #rrggbb, #rrggbbaa), rgb(a), hsl(a), transparent
 */
class ColorHelper
{
    /* =====================================
     * Parsing et Normalisation
     * ===================================== */

    /**
     * Parse une chaîne de couleur et retourne un tableau normalisé
     *
     * Supporte les formats :
     * - "#rgb" ou "#rrggbb" ou "#rrggbbaa" (hex)
     * - "rgb(r,g,b)" ou "rgba(r,g,b,a)"
     * - "hsl(h,s%,l%)" ou "hsla(h,s%,l%,a)"
     * - "transparent"
     *
     * @param string $value Chaîne de couleur à parser
     * @return array|null ['r' => 0-255, 'g' => 0-255, 'b' => 0-255, 'a' => 0.0-1.0] ou null si invalide
     */
    public static function parseColor($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim(mb_strtolower($value));

        // Cas spécial : transparent
        if ($v === 'transparent') {
            return ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0.0];
        }

        // Format HEX : #rgb, #rgba, #rrggbb, #rrggbbaa
        if (preg_match('/^#([0-9a-f]{3,8})$/i', $v, $m)) {
            $hex = $m[1];
            $len = strlen($hex);

            // Shorthand #rgb
            if ($len === 3) {
                $r = hexdec(str_repeat($hex[0], 2));
                $g = hexdec(str_repeat($hex[1], 2));
                $b = hexdec(str_repeat($hex[2], 2));
                return ['r' => $r, 'g' => $g, 'b' => $b, 'a' => 1.0];
            }

            // Shorthand #rgba
            if ($len === 4) {
                $r = hexdec(str_repeat($hex[0], 2));
                $g = hexdec(str_repeat($hex[1], 2));
                $b = hexdec(str_repeat($hex[2], 2));
                $a = hexdec(str_repeat($hex[3], 2)) / 255;
                return ['r' => $r, 'g' => $g, 'b' => $b, 'a' => self::clamp01($a)];
            }

            // Full #rrggbb
            if ($len === 6) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                return ['r' => $r, 'g' => $g, 'b' => $b, 'a' => 1.0];
            }

            // Full #rrggbbaa
            if ($len === 8) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $a = hexdec(substr($hex, 6, 2)) / 255;
                return ['r' => $r, 'g' => $g, 'b' => $b, 'a' => self::clamp01($a)];
            }
        }

        // Format RGB(A) : rgb(...) ou rgba(...)
        if (preg_match('/^rgba?\s*\(\s*([^\)]+)\s*\)$/i', $v, $m)) {
            $parts = preg_split('/\s*,\s*/', trim($m[1]));
            if (count($parts) >= 3) {
                $r = self::parseRgbComponent($parts[0]);
                $g = self::parseRgbComponent($parts[1]);
                $b = self::parseRgbComponent($parts[2]);
                $a = isset($parts[3]) ? floatval($parts[3]) : 1.0;

                return [
                    'r' => self::clamp255($r),
                    'g' => self::clamp255($g),
                    'b' => self::clamp255($b),
                    'a' => self::clamp01($a)
                ];
            }
        }

        // Format HSL(A) : hsl(...) ou hsla(...)
        if (preg_match('/^hsla?\s*\(\s*([^\)]+)\s*\)$/i', $v, $m)) {
            $parts = preg_split('/\s*,\s*/', trim($m[1]));
            if (count($parts) >= 3) {
                $h = floatval($parts[0]);
                $s = self::parsePercentage($parts[1]);
                $l = self::parsePercentage($parts[2]);
                $a = isset($parts[3]) ? floatval($parts[3]) : 1.0;

                $rgb = self::hslToRgb($h, $s, $l);
                return [
                    'r' => round($rgb['r']),
                    'g' => round($rgb['g']),
                    'b' => round($rgb['b']),
                    'a' => self::clamp01($a)
                ];
            }
        }

        // Format non reconnu
        return null;
    }

    /**
     * Normalise une couleur en format hex (#rrggbb ou #rrggbbaa)
     *
     * @param string $value Chaîne de couleur à normaliser
     * @param bool $withAlpha Inclure le canal alpha si < 1
     * @return string|null Couleur hex normalisée ou null si invalide
     */
    public static function normalizeColor($value, $withAlpha = false)
    {
        $c = self::parseColor($value);
        if (!$c) {
            return null;
        }

        $r = sprintf('%02x', self::clamp255($c['r']));
        $g = sprintf('%02x', self::clamp255($c['g']));
        $b = sprintf('%02x', self::clamp255($c['b']));
        $hex = '#' . $r . $g . $b;

        if ($withAlpha && isset($c['a']) && $c['a'] < 1.0) {
            $a = (int) round(self::clamp01($c['a']) * 255);
            $hex .= sprintf('%02x', $a);
        }

        return $hex;
    }

    /* =====================================
     * Conversions de Formats
     * ===================================== */

    /**
     * Convertit une couleur hex en tableau RGB
     *
     * @param string $hex Couleur hex (#rgb, #rrggbb, #rrggbbaa)
     * @return array|null ['r', 'g', 'b', 'a'] ou null si invalide
     */
    public static function hexToRgb($hex)
    {
        return self::parseColor($hex);
    }

    /**
     * Convertit RGB en hex
     *
     * @param mixed $rgb Tableau ['r','g','b'] ou chaîne 'rgb(...)'
     * @return string|null Couleur hex ou null si invalide
     */
    public static function rgbToHex($rgb)
    {
        if (is_string($rgb)) {
            $c = self::parseColor($rgb);
        } elseif (is_array($rgb) && isset($rgb['r'])) {
            $c = [
                'r' => $rgb['r'] ?? 0,
                'g' => $rgb['g'] ?? 0,
                'b' => $rgb['b'] ?? 0,
                'a' => $rgb['a'] ?? 1.0
            ];
        } else {
            return null;
        }

        if (!$c) return null;

        $r = sprintf('%02x', self::clamp255($c['r']));
        $g = sprintf('%02x', self::clamp255($c['g']));
        $b = sprintf('%02x', self::clamp255($c['b']));

        return '#' . $r . $g . $b;
    }

    /**
     * Convertit RGB en HSL
     *
     * @param int $r Rouge (0-255)
     * @param int $g Vert (0-255)
     * @param int $b Bleu (0-255)
     * @return array ['h' => 0-360, 's' => 0-100, 'l' => 0-100]
     */
    public static function rgbToHsl($r, $g, $b)
    {
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = $s = $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0; // Achromatique
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h = $h * 60;
        }

        return [
            'h' => round($h, 2),
            's' => round($s * 100, 2),
            'l' => round($l * 100, 2)
        ];
    }

    /* =====================================
     * Analyse WCAG et Contraste
     * ===================================== */

    /**
     * Calcule la luminance relative d'une couleur selon WCAG 2.0
     *
     * @param mixed $color Chaîne de couleur ou tableau parseColor
     * @return float|null Luminance (0-1) ou null si invalide
     */
    public static function relativeLuminance($color)
    {
        if (is_string($color)) {
            $c = self::parseColor($color);
        } elseif (is_array($color) && isset($color['r'])) {
            $c = $color;
        } else {
            return null;
        }

        if (!$c) return null;

        // Convertir en sRGB (0..1)
        $r = self::clamp01($c['r'] / 255);
        $g = self::clamp01($c['g'] / 255);
        $b = self::clamp01($c['b'] / 255);

        // Linéariser selon la formule WCAG
        $r = ($r <= 0.03928) ? ($r / 12.92) : pow(($r + 0.055) / 1.055, 2.4);
        $g = ($g <= 0.03928) ? ($g / 12.92) : pow(($g + 0.055) / 1.055, 2.4);
        $b = ($b <= 0.03928) ? ($b / 12.92) : pow(($b + 0.055) / 1.055, 2.4);

        // Calculer la luminance
        return (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
    }

    /**
     * Calcule le ratio de contraste WCAG entre deux couleurs
     *
     * @param string|array $color1 Première couleur
     * @param string|array $color2 Deuxième couleur
     * @return float|null Ratio (1-21) ou null si invalide
     */
    public static function contrastRatio($color1, $color2)
    {
        $l1 = self::relativeLuminance($color1);
        $l2 = self::relativeLuminance($color2);

        if ($l1 === null || $l2 === null) {
            return null;
        }

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round((($lighter + 0.05) / ($darker + 0.05)), 2);
    }

    /**
     * Vérifie si le contraste respecte les normes WCAG
     *
     * @param string|array $color1 Couleur de premier plan
     * @param string|array $color2 Couleur d'arrière-plan
     * @param string $level Niveau WCAG : 'AA' ou 'AAA'
     * @param string $size Taille : 'normal', 'large' ou 'ui'
     * @return bool|null True si conforme, false sinon, null si erreur
     */
    public static function isContrastOk($color1, $color2, $level = 'AA', $size = 'normal')
    {
        $ratio = self::contrastRatio($color1, $color2);
        if ($ratio === null) {
            return null;
        }

        $level = strtoupper($level);
        $size = strtolower($size);

        // Déterminer le seuil selon le niveau et la taille
        $threshold = 4.5; // AA normal par défaut

        if ($size === 'large') {
            $threshold = 3.0; // AA large
        } elseif ($size === 'ui') {
            $threshold = 3.0; // Éléments graphiques / UI
        }

        if ($level === 'AAA') {
            if ($size === 'normal') {
                $threshold = 7.0;
            } elseif ($size === 'large') {
                $threshold = 4.5;
            } elseif ($size === 'ui') {
                $threshold = 3.0; // AAA n'est pas requis pour UI
            }
        }

        return $ratio >= $threshold;
    }

    /**
     * Détermine la meilleure couleur de texte (noir ou blanc) pour un fond donné
     *
     * @param string|array $background Couleur d'arrière-plan
     * @return string|null '#000000' ou '#ffffff', ou null si erreur
     */
    public static function bestTextColor($background)
    {
        $black = '#000000';
        $white = '#ffffff';

        $ratioBlack = self::contrastRatio($background, $black);
        $ratioWhite = self::contrastRatio($background, $white);

        if ($ratioBlack === null || $ratioWhite === null) {
            return null;
        }

        return ($ratioBlack >= $ratioWhite) ? $black : $white;
    }

    /* =====================================
     * Manipulation et Algorithmes
     * ===================================== */

    /**
     * Mélange deux couleurs par interpolation linéaire dans l'espace RGB
     *
     * @param string|array $color1 Première couleur
     * @param string|array $color2 Deuxième couleur
     * @param float $ratio Ratio de mélange (0 = color1, 1 = color2)
     * @return array|null ['r','g','b','a'] ou null si erreur
     */
    public static function mixColors($color1, $color2, $ratio = 0.5)
    {
        $c1 = is_string($color1) ? self::parseColor($color1) : $color1;
        $c2 = is_string($color2) ? self::parseColor($color2) : $color2;

        if (!$c1 || !$c2) {
            return null;
        }

        $r = round(self::lerp($c1['r'], $c2['r'], $ratio));
        $g = round(self::lerp($c1['g'], $c2['g'], $ratio));
        $b = round(self::lerp($c1['b'], $c2['b'], $ratio));
        $a = self::clamp01(self::lerp($c1['a'] ?? 1.0, $c2['a'] ?? 1.0, $ratio));

        return ['r' => $r, 'g' => $g, 'b' => $b, 'a' => $a];
    }

    /**
     * Compose une couleur avec alpha sur un arrière-plan (alpha blending)
     *
     * Simule le rendu visuel d'une couleur semi-transparente sur un fond opaque.
     * Utilise la formule standard de composition alpha : C = Ca*α + Cb*(1-α)
     *
     * @param string|array $foreground Couleur de premier plan (peut avoir alpha)
     * @param string|array $background Couleur d'arrière-plan (doit être opaque)
     * @return array|null ['r', 'g', 'b', 'a' => 1.0] couleur composée opaque, ou null si erreur
     */
    public static function composeColor($foreground, $background)
    {
        $fg = is_string($foreground) ? self::parseColor($foreground) : $foreground;
        $bg = is_string($background) ? self::parseColor($background) : $background;

        if (!$fg || !$bg) {
            return null;
        }

        $alpha = $fg['a'] ?? 1.0;

        // Si déjà opaque, retourner tel quel
        if ($alpha >= 1.0) {
            return $fg;
        }

        // Composition alpha : C = Ca*α + Cb*(1-α)
        $r = round($fg['r'] * $alpha + $bg['r'] * (1 - $alpha));
        $g = round($fg['g'] * $alpha + $bg['g'] * (1 - $alpha));
        $b = round($fg['b'] * $alpha + $bg['b'] * (1 - $alpha));

        return [
            'r' => self::clamp255($r),
            'g' => self::clamp255($g),
            'b' => self::clamp255($b),
            'a' => 1.0
        ];
    }

    /**
     * Calcule le ratio de contraste WCAG entre deux couleurs (avec support alpha)
     *
     * Si la couleur de premier plan a de la transparence, elle est d'abord composée
     * sur l'arrière-plan avant le calcul du contraste.
     *
     * @param string|array $foreground Couleur de premier plan
     * @param string|array $background Couleur d'arrière-plan
     * @return float|null Ratio (1-21) ou null si invalide
     */
    public static function contrastRatioWithAlpha($foreground, $background)
    {
        $fg = is_string($foreground) ? self::parseColor($foreground) : $foreground;
        $bg = is_string($background) ? self::parseColor($background) : $background;

        if (!$fg || !$bg) {
            return null;
        }

        // Si le foreground a de la transparence, composer d'abord
        if (isset($fg['a']) && $fg['a'] < 1.0) {
            $fg = self::composeColor($fg, $bg);
            if (!$fg) {
                return null;
            }
        }

        // Calculer la luminance des deux couleurs
        $l1 = self::relativeLuminance($fg);
        $l2 = self::relativeLuminance($bg);

        if ($l1 === null || $l2 === null) {
            return null;
        }

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return round((($lighter + 0.05) / ($darker + 0.05)), 2);
    }

    /**
     * Vérifie si le contraste respecte les normes WCAG (avec support alpha)
     *
     * @param string|array $foreground Couleur de premier plan
     * @param string|array $background Couleur d'arrière-plan
     * @param string $level Niveau WCAG : 'AA' ou 'AAA'
     * @param string $size Taille : 'normal', 'large' ou 'ui'
     * @return bool|null True si conforme, false sinon, null si erreur
     */
    public static function isContrastOkWithAlpha($foreground, $background, $level = 'AA', $size = 'normal')
    {
        $ratio = self::contrastRatioWithAlpha($foreground, $background);
        if ($ratio === null) {
            return null;
        }

        $level = strtoupper($level);
        $size = strtolower($size);

        // Déterminer le seuil selon le niveau et la taille
        $threshold = 4.5; // AA normal par défaut

        if ($size === 'large') {
            $threshold = 3.0; // AA large
        } elseif ($size === 'ui') {
            $threshold = 3.0; // Éléments graphiques / UI
        }

        if ($level === 'AAA') {
            if ($size === 'normal') {
                $threshold = 7.0;
            } elseif ($size === 'large') {
                $threshold = 4.5;
            } elseif ($size === 'ui') {
                $threshold = 3.0; // AAA n'est pas requis pour UI
            }
        }

        return $ratio >= $threshold;
    }

    /**
     * Détermine la meilleure couleur de texte pour un fond donné (avec support alpha)
     *
     * Si le fond a de la transparence, il est d'abord composé sur blanc.
     *
     * @param string|array $background Couleur d'arrière-plan
     * @param string|array $underlyingColor Couleur sous-jacente (défaut: blanc)
     * @return string|null '#000000' ou '#ffffff', ou null si erreur
     */
    public static function bestTextColorWithAlpha($background, $underlyingColor = '#ffffff')
    {
        $bg = is_string($background) ? self::parseColor($background) : $background;

        if (!$bg) {
            return null;
        }

        // Si le background a de la transparence, composer d'abord
        if (isset($bg['a']) && $bg['a'] < 1.0) {
            $bg = self::composeColor($bg, $underlyingColor);
            if (!$bg) {
                return null;
            }
        }

        $black = '#000000';
        $white = '#ffffff';

        $ratioBlack = self::contrastRatio($bg, $black);
        $ratioWhite = self::contrastRatio($bg, $white);

        if ($ratioBlack === null || $ratioWhite === null) {
            return null;
        }

        return ($ratioBlack >= $ratioWhite) ? $black : $white;
    }

    /* =====================================
     * Génération de Couleurs
     * ===================================== */

    /**
     * Génère une couleur aléatoire en format hexadécimal
     *
     * @param bool $fancy Si true, génère une couleur vive et équilibrée, sinon une couleur 100% aléatoire
     * @return string Couleur hexadécimale (ex: #3498db)
     */
    public static function randomColor($fancy = false)
    {
        if ($fancy) {
            // Teinte aléatoire, mais saturation et lumière fixées pour un look "pro"
            $h = mt_rand(0, 360);
            $rgb = self::hslToRgb($h, 0.7, 0.6);
        } else {
            // Vrai aléatoire : peut inclure du noir, du blanc, du gris, etc.
            $rgb = [
                'r' => mt_rand(0, 255),
                'g' => mt_rand(0, 255),
                'b' => mt_rand(0, 255)
            ];
        }

        return self::rgbToHex($rgb);
    }

    /* =====================================
     * Méthodes utilitaires internes
     * ===================================== */

    /**
     * Parse un composant RGB (gère les pourcentages)
     *
     * @param string $v Valeur à parser
     * @return int Valeur 0-255
     */
    protected static function parseRgbComponent($v)
    {
        $v = trim($v);
        // Pourcentage "50%"
        if (strpos($v, '%') !== false) {
            return round(floatval(str_replace('%', '', $v)) * 2.55);
        }
        return intval($v);
    }

    /**
     * Parse un pourcentage en valeur 0-1
     *
     * @param string $v Valeur avec ou sans %
     * @return float Valeur 0-1
     */
    protected static function parsePercentage($v)
    {
        $v = trim($v);
        if (strpos($v, '%') !== false) {
            return floatval(str_replace('%', '', $v)) / 100;
        }
        return floatval($v);
    }

    /**
     * Convertit HSL en RGB
     *
     * @param float $h Teinte (0-360)
     * @param float $s Saturation (0-1 ou 0-100)
     * @param float $l Luminosité (0-1 ou 0-100)
     * @return array ['r', 'g', 'b']
     */
    protected static function hslToRgb($h, $s, $l)
    {
        // Normaliser h en 0-360
        $h = fmod(floatval($h), 360);
        if ($h < 0) $h += 360;

        $s = floatval($s);
        $l = floatval($l);

        // S'assurer que s/l sont en 0..1
        if ($s > 1) $s = $s / 100;
        if ($l > 1) $l = $l / 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60.0, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            $r1 = $c; $g1 = $x; $b1 = 0;
        } elseif ($h < 120) {
            $r1 = $x; $g1 = $c; $b1 = 0;
        } elseif ($h < 180) {
            $r1 = 0; $g1 = $c; $b1 = $x;
        } elseif ($h < 240) {
            $r1 = 0; $g1 = $x; $b1 = $c;
        } elseif ($h < 300) {
            $r1 = $x; $g1 = 0; $b1 = $c;
        } else {
            $r1 = $c; $g1 = 0; $b1 = $x;
        }

        $r = ($r1 + $m) * 255;
        $g = ($g1 + $m) * 255;
        $b = ($b1 + $m) * 255;

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }

    /**
     * Limite une valeur entre 0 et 1
     *
     * @param float $v Valeur à limiter
     * @return float Valeur limitée
     */
    protected static function clamp01($v)
    {
        $v = floatval($v);
        if ($v < 0) return 0.0;
        if ($v > 1) return 1.0;
        return $v;
    }

    /**
     * Limite une valeur entre 0 et 255
     *
     * @param int $v Valeur à limiter
     * @return int Valeur limitée
     */
    protected static function clamp255($v)
    {
        $v = intval(round($v));
        if ($v < 0) return 0;
        if ($v > 255) return 255;
        return $v;
    }

    /**
     * Interpolation linéaire entre deux valeurs
     *
     * @param float $a Valeur de départ
     * @param float $b Valeur d'arrivée
     * @param float $t Ratio (0-1)
     * @return float Valeur interpolée
     */
    protected static function lerp($a, $b, $t)
    {
        return $a + ($b - $a) * $t;
    }
}
