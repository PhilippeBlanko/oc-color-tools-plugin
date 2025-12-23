<?php namespace Ducharme\ColorTools\Tests;

use Ducharme\ColorTools\Classes\ColorExtension;
use Ducharme\ColorTools\Classes\ColorHelper;
use PHPUnit\Framework\TestCase;

/**
 * ColorExtensionTest
 *
 * Suite de tests unitaires pour ColorExtension (filtres et fonctions Twig).
 * Lancer les tests : vendor/bin/phpunit plugins/ducharme/colortools/tests/ColorExtensionTest.php
 */
class ColorExtensionTest extends TestCase
{
    /* =====================================
     * Tests des Filtres de Conversion
     * ===================================== */

    /**
     * Teste le filtre to_hex
     *
     * @test
     */
    public function testToHex()
    {
        // RGB vers hex
        $this->assertEquals('#ff0000', ColorExtension::toHex('rgb(255, 0, 0)'));
        $this->assertEquals('#ffffff', ColorExtension::toHex('rgb(255, 255, 255)'));
        $this->assertEquals('#000000', ColorExtension::toHex('rgb(0, 0, 0)'));

        // HSL vers hex
        $this->assertEquals('#ff0000', ColorExtension::toHex('hsl(0, 100%, 50%)'));

        // Hex déjà normalisé
        $this->assertEquals('#ff0000', ColorExtension::toHex('#ff0000'));
        $this->assertEquals('#ff0000', ColorExtension::toHex('#f00'));

        // Invalide
        $this->assertNull(ColorExtension::toHex('invalid'));
        $this->assertNull(ColorExtension::toHex(''));
    }

    /**
     * Teste le filtre to_rgb
     *
     * @test
     */
    public function testToRgb()
    {
        // Hex vers RGB
        $this->assertEquals('rgb(255, 0, 0)', ColorExtension::toRgb('#ff0000'));
        $this->assertEquals('rgb(255, 0, 0)', ColorExtension::toRgb('#f00'));
        $this->assertEquals('rgb(255, 255, 255)', ColorExtension::toRgb('#ffffff'));
        $this->assertEquals('rgb(0, 0, 0)', ColorExtension::toRgb('#000000'));

        // HSL vers RGB
        $result = ColorExtension::toRgb('hsl(0, 100%, 50%)');
        $this->assertStringContainsString('rgb(255, 0,', $result);

        // Invalide
        $this->assertNull(ColorExtension::toRgb('invalid'));
    }

    /**
     * Teste le filtre to_rgba
     *
     * @test
     */
    public function testToRgba()
    {
        // Sans alpha spécifié (défaut 1.0 ou alpha d'origine)
        $this->assertEquals('rgba(255, 0, 0, 1.00)', ColorExtension::toRgba('#ff0000'));

        // Préservation de l'alpha d'origine (si aucun paramètre fourni)
        $this->assertEquals('rgba(255, 0, 0, 0.67)', ColorExtension::toRgba('#ff0000aa'));
        $this->assertEquals('rgba(0, 255, 0, 0.30)', ColorExtension::toRgba('rgba(0, 255, 0, 0.3)'));

        // Avec alpha spécifié (doit écraser l'alpha d'origine)
        $this->assertEquals('rgba(255, 0, 0, 0.50)', ColorExtension::toRgba('#ff0000', 0.5));
        $this->assertEquals('rgba(255, 0, 0, 0.50)', ColorExtension::toRgba('#ff0000aa', 0.5));
        $this->assertEquals('rgba(255, 0, 0, 0.75)', ColorExtension::toRgba('#ff0000', 0.75));

        // Clamping alpha
        $this->assertEquals('rgba(255, 0, 0, 0.00)', ColorExtension::toRgba('#ff0000', -0.5));
        $this->assertEquals('rgba(255, 0, 0, 1.00)', ColorExtension::toRgba('#ff0000', 2.0));

        // Invalide
        $this->assertNull(ColorExtension::toRgba('invalid'));
    }

    /**
     * Teste le filtre to_hsl
     *
     * @test
     */
    public function testToHsl()
    {
        // Rouge pur
        $this->assertEquals('hsl(0, 100%, 50%)', ColorExtension::toHsl('#ff0000'));

        // Blanc
        $this->assertEquals('hsl(0, 0%, 100%)', ColorExtension::toHsl('#ffffff'));

        // Noir
        $this->assertEquals('hsl(0, 0%, 0%)', ColorExtension::toHsl('#000000'));

        // Gris
        $result = ColorExtension::toHsl('#808080');
        $this->assertStringContainsString('hsl(0, 0%', $result);

        // Invalide
        $this->assertNull(ColorExtension::toHsl('invalid'));
    }

    /* =====================================
     * Tests des Filtres d'Analyse
     * ===================================== */

    /**
     * Teste le filtre luminance
     *
     * @test
     */
    public function testLuminance()
    {
        // Blanc (luminance max)
        $this->assertEquals(1.0, ColorExtension::luminance('#ffffff'));

        // Noir (luminance min)
        $this->assertEquals(0.0, ColorExtension::luminance('#000000'));

        // Gris moyen
        $result = ColorExtension::luminance('#808080');
        $this->assertGreaterThan(0.2, $result);
        $this->assertLessThan(0.3, $result);

        // Rouge
        $result = ColorExtension::luminance('#ff0000');
        $this->assertGreaterThan(0, $result);
        $this->assertLessThan(0.3, $result);

        // Invalide
        $this->assertNull(ColorExtension::luminance('invalid'));
    }

    /**
     * Teste le filtre contrast_ratio
     *
     * @test
     */
    public function testContrastRatio()
    {
        // Noir sur blanc (max)
        $this->assertEquals(21.0, ColorExtension::contrastRatio('#000000', '#ffffff'));

        // Blanc sur noir (même résultat)
        $this->assertEquals(21.0, ColorExtension::contrastRatio('#ffffff', '#000000'));

        // Même couleur (min)
        $this->assertEquals(1.0, ColorExtension::contrastRatio('#ff0000', '#ff0000'));

        // Gris sur blanc
        $ratio = ColorExtension::contrastRatio('#808080', '#ffffff');
        $this->assertGreaterThan(3.0, $ratio);
        $this->assertLessThan(4.0, $ratio);

        // Avec alpha (composition automatique)
        $ratio = ColorExtension::contrastRatio('#00000080', '#ffffff');
        $this->assertGreaterThan(1.0, $ratio);
        $this->assertLessThan(21.0, $ratio);

        // Invalide
        $this->assertNull(ColorExtension::contrastRatio('invalid', '#ffffff'));
    }

    /**
     * Teste le filtre best_text_color
     *
     * @test
     */
    public function testBestTextColor()
    {
        // Fond blanc → texte noir
        $this->assertEquals('#000000', ColorExtension::bestTextColor('#ffffff'));

        // Fond noir → texte blanc
        $this->assertEquals('#ffffff', ColorExtension::bestTextColor('#000000'));

        // Fond gris clair → texte noir
        $this->assertEquals('#000000', ColorExtension::bestTextColor('#cccccc'));

        // Fond gris foncé → texte blanc
        $this->assertEquals('#ffffff', ColorExtension::bestTextColor('#333333'));

        // Fond jaune → texte noir
        $this->assertEquals('#000000', ColorExtension::bestTextColor('#ffff00'));

        // Fond bleu foncé → texte blanc
        $this->assertEquals('#ffffff', ColorExtension::bestTextColor('#0000ff'));

        // Avec alpha et underlying
        $result = ColorExtension::bestTextColor('#00000080', '#ffffff');
        $this->assertContains($result, ['#000000', '#ffffff']);

        // Invalide
        $this->assertNull(ColorExtension::bestTextColor('invalid'));
    }

    /* =====================================
     * Tests des Fonctions Avancées
     * ===================================== */

    /**
     * Teste la fonction color_mix
     *
     * @test
     */
    public function testColorMix()
    {
        // Mélange 50/50 rouge et bleu → violet
        $result = ColorExtension::colorMix('#ff0000', '#0000ff', 0.5);
        $this->assertNotNull($result);
        $c = ColorHelper::parseColor($result);
        $this->assertGreaterThan(100, $c['r']);
        $this->assertGreaterThan(100, $c['b']);

        // 100% première couleur
        $result = ColorExtension::colorMix('#ff0000', '#0000ff', 0.0);
        $this->assertEquals('#ff0000', $result);

        // 100% deuxième couleur
        $result = ColorExtension::colorMix('#ff0000', '#0000ff', 1.0);
        $this->assertEquals('#0000ff', $result);

        // Mélange 25/75
        $result = ColorExtension::colorMix('#000000', '#ffffff', 0.75);
        $this->assertNotNull($result);

        // Invalide
        $this->assertNull(ColorExtension::colorMix('invalid', '#ffffff'));
        $this->assertNull(ColorExtension::colorMix('#ffffff', 'invalid'));
    }

    /**
     * Teste la fonction color_compose
     *
     * @test
     */
    public function testColorCompose()
    {
        // Rouge semi-transparent sur blanc
        $result = ColorExtension::colorCompose('#ff000080', '#ffffff');
        $this->assertNotNull($result);
        $c = ColorHelper::parseColor($result);
        $this->assertGreaterThan(200, $c['r']); // Plus clair que rouge pur

        // Noir transparent sur blanc
        $result = ColorExtension::colorCompose('#00000080', '#ffffff');
        $this->assertNotNull($result);

        // Couleur opaque (pas de changement)
        $result = ColorExtension::colorCompose('#ff0000', '#ffffff');
        $this->assertEquals('#ff0000', $result);

        // Invalide
        $this->assertNull(ColorExtension::colorCompose('invalid', '#ffffff'));
    }

    /**
     * Teste la fonction is_contrast_ok
     *
     * @test
     */
    public function testIsContrastOk()
    {
        // Noir sur blanc : AA ✓
        $this->assertTrue(ColorExtension::isContrastOk('#000000', '#ffffff', 'AA', 'normal'));

        // Gris clair sur blanc : AA ✗
        $this->assertFalse(ColorExtension::isContrastOk('#cccccc', '#ffffff', 'AA', 'normal'));

        // Gris moyen sur blanc : AA ✓
        $this->assertTrue(ColorExtension::isContrastOk('#757575', '#ffffff', 'AA', 'normal'));

        // Noir sur blanc : AAA ✓
        $this->assertTrue(ColorExtension::isContrastOk('#000000', '#ffffff', 'AAA', 'normal'));

        // Large text (seuil plus bas)
        $this->assertTrue(ColorExtension::isContrastOk('#959595', '#ffffff', 'AA', 'large'));

        // UI elements
        $this->assertTrue(ColorExtension::isContrastOk('#808080', '#ffffff', 'AA', 'ui'));

        // Avec alpha
        $this->assertFalse(ColorExtension::isContrastOk('#00000040', '#ffffff', 'AA', 'normal'));

        // Invalide
        $this->assertNull(ColorExtension::isContrastOk('invalid', '#ffffff'));
    }

    /**
     * Teste la fonction color_random
     *
     * @test
     */
    public function testColorRandom()
    {
        // Test mode par défaut (Chaos)
        $color = ColorExtension::colorRandom();
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);

        // Test mode Fancy
        $fancyColor = ColorExtension::colorRandom(true);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $fancyColor);

        // Vérification que la couleur générée est valide pour les autres filtres
        $luminance = ColorExtension::luminance($fancyColor);
        $this->assertIsFloat($luminance);
        $this->assertGreaterThanOrEqual(0, $luminance);
        $this->assertLessThanOrEqual(1, $luminance);
    }

    /* =====================================
     * Tests de Cas Réels d'Utilisation
     * ===================================== */

    /**
     * Teste un workflow complet de génération de palette
     *
     * @test
     */
    public function testPaletteWorkflow()
    {
        // Couleur de base
        $base = '#3498db';

        // Mélanger avec une autre couleur
        $mixed = ColorExtension::colorMix($base, '#e74c3c', 0.5);
        $this->assertNotNull($mixed);

        // Composer avec alpha
        $composed = ColorExtension::colorCompose('#3498db80', '#ffffff');
        $this->assertNotNull($composed);
    }

    /**
     * Teste un cas d'accessibilité complet
     *
     * @test
     */
    public function testAccessibilityWorkflow()
    {
        $bgColor = '#f8f9fa';

        // Trouver la meilleure couleur de texte
        $textColor = ColorExtension::bestTextColor($bgColor);
        $this->assertEquals('#000000', $textColor);

        // Vérifier le contraste
        $ratio = ColorExtension::contrastRatio($textColor, $bgColor);
        $this->assertGreaterThan(4.5, $ratio);

        // Valider WCAG
        $this->assertTrue(ColorExtension::isContrastOk($textColor, $bgColor, 'AA', 'normal'));
    }

    /**
     * Teste des conversions en chaîne
     *
     * @test
     */
    public function testChainedConversions()
    {
        // RGB → HSL → Manipulation → HEX
        $rgb = 'rgb(255, 0, 0)';
        $hsl = ColorExtension::toHsl($rgb);
        $this->assertStringStartsWith('hsl(', $hsl);

        $hex = ColorExtension::toHex($hsl);
        $this->assertEquals('#ff0000', $hex);
    }
}
