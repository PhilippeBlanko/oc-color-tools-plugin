<?php namespace Ducharme\ColorTools\Tests;

use Ducharme\ColorTools\Classes\ColorHelper;
use PHPUnit\Framework\TestCase;

/**
 * ColorHelperTest
 *
 * Suite de tests unitaires complète pour la classe ColorHelper.
 * Teste toutes les fonctionnalités de parsing, conversion et validation WCAG.
 * Lancer les tests : vendor/bin/phpunit plugins/ducharme/colortools/tests/ColorHelperTest.php
 */
class ColorHelperTest extends TestCase
{
    /* =====================================
     * Tests de parsing de couleurs
     * ===================================== */

    /**
     * Teste le parsing de couleurs hexadécimales
     *
     * @test
     */
    public function testParseColorHex()
    {
        // Hex court #rgb
        $result = ColorHelper::parseColor('#fff');
        $this->assertEquals(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 1.0], $result);

        $result = ColorHelper::parseColor('#f00');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0], $result);

        // Hex complet #rrggbb
        $result = ColorHelper::parseColor('#ffffff');
        $this->assertEquals(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 1.0], $result);

        $result = ColorHelper::parseColor('#123456');
        $this->assertEquals(['r' => 18, 'g' => 52, 'b' => 86, 'a' => 1.0], $result);

        // Hex avec alpha #rrggbbaa
        $result = ColorHelper::parseColor('#ff0000ff');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0], $result);

        $result = ColorHelper::parseColor('#ff000080');
        $this->assertEqualsWithDelta(0.5, $result['a'], 0.01);
    }

    /**
     * Teste le parsing de couleurs RGB/RGBA
     *
     * @test
     */
    public function testParseColorRgb()
    {
        // RGB basique
        $result = ColorHelper::parseColor('rgb(255, 0, 0)');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0], $result);

        $result = ColorHelper::parseColor('rgb(128,128,128)');
        $this->assertEquals(['r' => 128, 'g' => 128, 'b' => 128, 'a' => 1.0], $result);

        // RGBA avec alpha
        $result = ColorHelper::parseColor('rgba(255, 0, 0, 0.5)');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 0.5], $result);

        $result = ColorHelper::parseColor('rgba(0, 0, 0, 0)');
        $this->assertEquals(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0.0], $result);

        // RGB avec pourcentages
        $result = ColorHelper::parseColor('rgb(100%, 0%, 0%)');
        $this->assertEquals(255, $result['r']);
        $this->assertEquals(0, $result['g']);
    }

    /**
     * Teste le parsing de couleurs HSL/HSLA
     *
     *  @test
     */
    public function testParseColorHsl()
    {
        // Rouge pur en HSL
        $result = ColorHelper::parseColor('hsl(0, 100%, 50%)');
        $this->assertEquals(255, $result['r']);
        $this->assertEquals(0, $result['g']);
        $this->assertLessThan(10, $result['b']); // Proche de 0

        // Vert pur
        $result = ColorHelper::parseColor('hsl(120, 100%, 50%)');
        $this->assertLessThan(10, $result['r']);
        $this->assertEquals(255, $result['g']);

        // Bleu pur
        $result = ColorHelper::parseColor('hsl(240, 100%, 50%)');
        $this->assertLessThan(10, $result['r']);
        $this->assertLessThan(10, $result['g']);
        $this->assertEquals(255, $result['b']);

        // HSLA avec alpha
        $result = ColorHelper::parseColor('hsla(0, 100%, 50%, 0.5)');
        $this->assertEquals(0.5, $result['a']);
    }

    /**
     * Teste le parsing de valeurs spéciales
     *
     * @test
     */
    public function testParseColorSpecial()
    {
        // Transparent
        $result = ColorHelper::parseColor('transparent');
        $this->assertEquals(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0.0], $result);

        // Valeurs invalides
        $this->assertNull(ColorHelper::parseColor('invalid'));
        $this->assertNull(ColorHelper::parseColor(''));
        $this->assertNull(ColorHelper::parseColor(null));
        $this->assertNull(ColorHelper::parseColor(123));
    }

    /* =====================================
     * Tests de conversion de couleurs
     * ===================================== */

    /**
     * Teste la conversion HEX vers RGB
     *
     * @test
     */
    public function testHexToRgb()
    {
        $result = ColorHelper::hexToRgb('#ffffff');
        $this->assertEquals(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 1.0], $result);

        $result = ColorHelper::hexToRgb('#000000');
        $this->assertEquals(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 1.0], $result);

        $result = ColorHelper::hexToRgb('#ff0000');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0], $result);

        // Shorthand
        $result = ColorHelper::hexToRgb('#f00');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0, 'a' => 1.0], $result);
    }

    /**
     * Teste la conversion RGB vers HEX
     *
     * @test
     */
    public function testRgbToHex()
    {
        // Array
        $result = ColorHelper::rgbToHex(['r' => 255, 'g' => 255, 'b' => 255]);
        $this->assertEquals('#ffffff', $result);

        $result = ColorHelper::rgbToHex(['r' => 0, 'g' => 0, 'b' => 0]);
        $this->assertEquals('#000000', $result);

        $result = ColorHelper::rgbToHex(['r' => 255, 'g' => 0, 'b' => 0]);
        $this->assertEquals('#ff0000', $result);

        // String
        $result = ColorHelper::rgbToHex('rgb(255, 0, 0)');
        $this->assertEquals('#ff0000', $result);

        // Invalide
        $this->assertNull(ColorHelper::rgbToHex('invalid'));
        $this->assertNull(ColorHelper::rgbToHex([]));
    }

    /**
     * Teste la conversion RGB vers HSL
     *
     * @test
     */
    public function testRgbToHsl()
    {
        // Rouge pur
        $result = ColorHelper::rgbToHsl(255, 0, 0);
        $this->assertEquals(0, $result['h']);
        $this->assertEquals(100, $result['s']);
        $this->assertEquals(50, $result['l']);

        // Vert pur
        $result = ColorHelper::rgbToHsl(0, 255, 0);
        $this->assertEquals(120, $result['h']);

        // Bleu pur
        $result = ColorHelper::rgbToHsl(0, 0, 255);
        $this->assertEquals(240, $result['h']);

        // Gris (achromatique)
        $result = ColorHelper::rgbToHsl(128, 128, 128);
        $this->assertEquals(0, $result['h']);
        $this->assertEquals(0, $result['s']);
        $this->assertEqualsWithDelta(50.2, $result['l'], 0.5);

        // Blanc
        $result = ColorHelper::rgbToHsl(255, 255, 255);
        $this->assertEquals(0, $result['s']);
        $this->assertEquals(100, $result['l']);

        // Noir
        $result = ColorHelper::rgbToHsl(0, 0, 0);
        $this->assertEquals(0, $result['l']);
    }

    /**
     * Teste la normalisation de couleurs
     *
     * @test
     */
    public function testNormalizeColor()
    {
        // Hex déjà normalisé
        $result = ColorHelper::normalizeColor('#ff0000');
        $this->assertEquals('#ff0000', $result);

        // Hex court
        $result = ColorHelper::normalizeColor('#f00');
        $this->assertEquals('#ff0000', $result);

        // RGB
        $result = ColorHelper::normalizeColor('rgb(255, 0, 0)');
        $this->assertEquals('#ff0000', $result);

        // HSL
        $result = ColorHelper::normalizeColor('hsl(0, 100%, 50%)');
        $this->assertEquals('#ff0000', $result);

        // Avec alpha
        $result = ColorHelper::normalizeColor('rgba(255, 0, 0, 0.5)', true);
        $this->assertStringContainsString('#ff0000', $result);

        // Invalide
        $this->assertNull(ColorHelper::normalizeColor('invalid'));
    }

    /* =====================================
     * Tests de luminance et contraste WCAG
     * ===================================== */

    /**
     * Teste le calcul de luminance relative
     *
     * @test
     */
    public function testRelativeLuminance()
    {
        // Blanc (luminance maximale)
        $result = ColorHelper::relativeLuminance('#ffffff');
        $this->assertEquals(1.0, $result);

        // Noir (luminance minimale)
        $result = ColorHelper::relativeLuminance('#000000');
        $this->assertEquals(0.0, $result);

        // Rouge pur
        $result = ColorHelper::relativeLuminance('#ff0000');
        $this->assertGreaterThan(0, $result);
        $this->assertLessThan(0.3, $result);

        // Vert pur (plus lumineux que rouge)
        $resultGreen = ColorHelper::relativeLuminance('#00ff00');
        $resultRed = ColorHelper::relativeLuminance('#ff0000');
        $this->assertGreaterThan($resultRed, $resultGreen);

        // Gris moyen
        $result = ColorHelper::relativeLuminance('#808080');
        $this->assertGreaterThan(0.2, $result);
        $this->assertLessThan(0.3, $result);

        // Avec array
        $result = ColorHelper::relativeLuminance(['r' => 255, 'g' => 255, 'b' => 255]);
        $this->assertEquals(1.0, $result);

        // Invalide
        $this->assertNull(ColorHelper::relativeLuminance('invalid'));
    }

    /**
     * Teste le calcul du ratio de contraste
     *
     * @test
     */
    public function testContrastRatio()
    {
        // Noir sur blanc (contraste maximal)
        $ratio = ColorHelper::contrastRatio('#000000', '#ffffff');
        $this->assertEquals(21.0, $ratio);

        // Blanc sur noir (même résultat)
        $ratio = ColorHelper::contrastRatio('#ffffff', '#000000');
        $this->assertEquals(21.0, $ratio);

        // Même couleur (contraste minimal)
        $ratio = ColorHelper::contrastRatio('#ff0000', '#ff0000');
        $this->assertEquals(1.0, $ratio);

        // Gris sur blanc
        $ratio = ColorHelper::contrastRatio('#808080', '#ffffff');
        $this->assertGreaterThan(3.0, $ratio);
        $this->assertLessThan(4.0, $ratio);

        // Rouge sur blanc
        $ratio = ColorHelper::contrastRatio('#ff0000', '#ffffff');
        $this->assertGreaterThan(3.5, $ratio);
        $this->assertLessThan(4.5, $ratio);

        // Couleurs invalides
        $this->assertNull(ColorHelper::contrastRatio('invalid', '#ffffff'));
        $this->assertNull(ColorHelper::contrastRatio('#ffffff', 'invalid'));
    }

    /**
     * Teste la validation de contraste WCAG AA
     *
     * @test
     */
    public function testIsContrastOkAA()
    {
        // Noir sur blanc : AA normal (4.5:1) ✓
        $this->assertTrue(ColorHelper::isContrastOk('#000000', '#ffffff', 'AA', 'normal'));

        // Noir sur blanc : AA large (3:1) ✓
        $this->assertTrue(ColorHelper::isContrastOk('#000000', '#ffffff', 'AA', 'large'));

        // Gris clair sur blanc : AA normal ✗
        $this->assertFalse(ColorHelper::isContrastOk('#cccccc', '#ffffff', 'AA', 'normal'));

        // Gris moyen sur blanc : AA normal ✓
        $this->assertTrue(ColorHelper::isContrastOk('#757575', '#ffffff', 'AA', 'normal'));

        // Gris moyen sur blanc : AA large ✓
        $this->assertTrue(ColorHelper::isContrastOk('#959595', '#ffffff', 'AA', 'large'));

        // UI elements (3:1)
        $this->assertTrue(ColorHelper::isContrastOk('#808080', '#ffffff', 'AA', 'ui'));
    }

    /**
     * Teste la validation de contraste WCAG AAA
     *
     * @test
     */
    public function testIsContrastOkAAA()
    {
        // Noir sur blanc : AAA normal (7:1) ✓
        $this->assertTrue(ColorHelper::isContrastOk('#000000', '#ffffff', 'AAA', 'normal'));

        // Gris foncé sur blanc : AAA normal ✓
        $this->assertTrue(ColorHelper::isContrastOk('#595959', '#ffffff', 'AAA', 'normal'));

        // Gris moyen sur blanc : AAA normal ✗
        $this->assertFalse(ColorHelper::isContrastOk('#808080', '#ffffff', 'AAA', 'normal'));

        // Gris moyen sur blanc : AAA large (4.5:1) ✓
        $this->assertTrue(ColorHelper::isContrastOk('#767676', '#ffffff', 'AAA', 'large'));

        // Invalide
        $this->assertNull(ColorHelper::isContrastOk('invalid', '#ffffff', 'AAA'));
    }

    /**
     * Teste les cas réels de combinaisons de couleurs
     *
     * @test
     */
    public function testRealWorldColorCombinations()
    {
        // Bleu Bootstrap primary sur blanc
        $ratio = ColorHelper::contrastRatio('#0d6efd', '#ffffff');
        $this->assertGreaterThanOrEqual(4.5, $ratio); // Devrait passer AA normal

        // Texte gris Bootstrap sur blanc
        $ratio = ColorHelper::contrastRatio('#6c757d', '#ffffff');
        $this->assertGreaterThanOrEqual(4.5, $ratio);

        // Danger Bootstrap sur blanc
        $ratio = ColorHelper::contrastRatio('#dc3545', '#ffffff');
        $this->assertGreaterThanOrEqual(3.5, $ratio);
    }

    /* =====================================
     * Tests de fonctionnalités avancées
     * ===================================== */

    /**
     * Teste la sélection de la meilleure couleur de texte
     *
     * @test
     */
    public function testBestTextColor()
    {
        // Fond blanc → texte noir
        $result = ColorHelper::bestTextColor('#ffffff');
        $this->assertEquals('#000000', $result);

        // Fond noir → texte blanc
        $result = ColorHelper::bestTextColor('#000000');
        $this->assertEquals('#ffffff', $result);

        // Fond gris clair → texte noir
        $result = ColorHelper::bestTextColor('#cccccc');
        $this->assertEquals('#000000', $result);

        // Fond gris foncé → texte blanc
        $result = ColorHelper::bestTextColor('#333333');
        $this->assertEquals('#ffffff', $result);

        // Fond jaune vif → texte noir
        $result = ColorHelper::bestTextColor('#ffff00');
        $this->assertEquals('#000000', $result);

        // Fond bleu foncé → texte blanc
        $result = ColorHelper::bestTextColor('#0000ff');
        $this->assertEquals('#ffffff', $result);

        // Invalide
        $this->assertNull(ColorHelper::bestTextColor('invalid'));
    }

    /**
     * Teste le mélange de couleurs
     *
     * @test
     */
    public function testMixColors()
    {
        // Mélange 50/50 rouge et bleu
        $result = ColorHelper::mixColors('#ff0000', '#0000ff', 0.5);
        $this->assertEqualsWithDelta(127, $result['r'], 1); // Tolérance de ±1
        $this->assertEquals(0, $result['g']);
        $this->assertEqualsWithDelta(127, $result['b'], 1); // Tolérance de ±1

        // 100% première couleur
        $result = ColorHelper::mixColors('#ff0000', '#0000ff', 0.0);
        $this->assertEquals(255, $result['r']);
        $this->assertEquals(0, $result['g']);
        $this->assertEquals(0, $result['b']);

        // 100% deuxième couleur
        $result = ColorHelper::mixColors('#ff0000', '#0000ff', 1.0);
        $this->assertEquals(0, $result['r']);
        $this->assertEquals(0, $result['g']);
        $this->assertEquals(255, $result['b']);

        // Mélange 75/25
        $result = ColorHelper::mixColors('#000000', '#ffffff', 0.75);
        $this->assertGreaterThan(150, $result['r']);
        $this->assertGreaterThan(150, $result['g']);
        $this->assertGreaterThan(150, $result['b']);

        // Mélange avec alpha
        $result = ColorHelper::mixColors('rgba(255,0,0,1)', 'rgba(0,0,255,0)', 0.5);
        $this->assertEquals(0.5, $result['a']);

        // Invalide
        $this->assertNull(ColorHelper::mixColors('invalid', '#ffffff'));
        $this->assertNull(ColorHelper::mixColors('#ffffff', 'invalid'));
    }

    /* =====================================
     * Tests de cas limites et robustesse
     * ===================================== */

    /**
     * Teste les valeurs limites (clamping)
     *
     * @test
     */
    public function testClampingValues()
    {
        // RGB au-dessus de 255
        $result = ColorHelper::parseColor('rgb(300, 300, 300)');
        $this->assertEquals(255, $result['r']);
        $this->assertEquals(255, $result['g']);
        $this->assertEquals(255, $result['b']);

        // RGB négatif
        $result = ColorHelper::parseColor('rgb(-10, -10, -10)');
        $this->assertEquals(0, $result['r']);
        $this->assertEquals(0, $result['g']);
        $this->assertEquals(0, $result['b']);
    }

    /**
     * @test
     *
     * Teste la gestion des espaces et de la casse
     */
    public function testWhitespaceAndCase()
    {
        // Espaces variés
        $result = ColorHelper::parseColor('  #ffffff  ');
        $this->assertNotNull($result);

        $result = ColorHelper::parseColor('rgb( 255 , 0 , 0 )');
        $this->assertEquals(255, $result['r']);

        // Casse mixte
        $result = ColorHelper::parseColor('#FFFFFF');
        $this->assertEquals(255, $result['r']);

        $result = ColorHelper::parseColor('RGB(255, 0, 0)');
        $this->assertEquals(255, $result['r']);

        $result = ColorHelper::parseColor('TRANSPARENT');
        $this->assertEquals(0.0, $result['a']);
    }

    /**
     * Teste les conversions circulaires (aller-retour)
     *
     * @test
     */
    public function testRoundTripConversions()
    {
        // HEX → RGB → HEX
        $original = '#ff0000';
        $rgb = ColorHelper::hexToRgb($original);
        $result = ColorHelper::rgbToHex($rgb);
        $this->assertEquals($original, $result);

        // RGB → HSL → RGB (approximatif)
        $rgb = ColorHelper::parseColor('rgb(255, 0, 0)');
        $hsl = ColorHelper::rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
        // Reconvertir HSL en RGB devrait donner un résultat proche
        $this->assertEquals(0, $hsl['h']);
        $this->assertEquals(100, $hsl['s']);
    }

    /**
     * Teste la composition de couleurs avec alpha
     *
     * @test
     */
    public function testComposeColor()
    {
        // Rouge semi-transparent (50%) sur blanc
        $result = ColorHelper::composeColor('#ff000080', '#ffffff');
        $this->assertEqualsWithDelta(255, $result['r'], 1);
        $this->assertEqualsWithDelta(127, $result['g'], 1);
        $this->assertEqualsWithDelta(127, $result['b'], 1);
        $this->assertEquals(1.0, $result['a']); // Résultat toujours opaque

        // Noir semi-transparent (50%) sur blanc
        $result = ColorHelper::composeColor('#00000080', '#ffffff');
        $this->assertEqualsWithDelta(127, $result['r'], 1);
        $this->assertEqualsWithDelta(127, $result['g'], 1);
        $this->assertEqualsWithDelta(127, $result['b'], 1);

        // Couleur totalement transparente sur rouge
        $result = ColorHelper::composeColor('#0000ff00', '#ff0000');
        $this->assertEquals(255, $result['r']);
        $this->assertEquals(0, $result['g']);
        $this->assertEquals(0, $result['b']);

        // Couleur opaque (pas de changement)
        $result = ColorHelper::composeColor('#ff0000', '#ffffff');
        $this->assertEquals(255, $result['r']);
        $this->assertEquals(0, $result['g']);
        $this->assertEquals(0, $result['b']);

        // Avec format rgba()
        $result = ColorHelper::composeColor('rgba(0, 0, 255, 0.5)', '#ff0000');
        $this->assertEqualsWithDelta(127, $result['r'], 1);
        $this->assertEquals(0, $result['g']);
        $this->assertEqualsWithDelta(127, $result['b'], 1);

        // Invalide
        $this->assertNull(ColorHelper::composeColor('invalid', '#ffffff'));
        $this->assertNull(ColorHelper::composeColor('#ffffff', 'invalid'));
    }

    /**
     * Teste le ratio de contraste avec alpha
     *
     * @test
     */
    public function testContrastRatioWithAlpha()
    {
        // Rouge semi-transparent (50%) sur blanc = rose
        // Devrait avoir moins de contraste que rouge pur sur blanc
        $ratioFull = ColorHelper::contrastRatio('#ff0000', '#ffffff');
        $ratioAlpha = ColorHelper::contrastRatioWithAlpha('#ff000080', '#ffffff');

        $this->assertLessThan($ratioFull, $ratioAlpha);
        $this->assertGreaterThan(2.0, $ratioAlpha);

        // Noir à 50% sur blanc = gris moyen
        $ratio = ColorHelper::contrastRatioWithAlpha('#00000080', '#ffffff');
        $this->assertGreaterThan(3.0, $ratio);
        $this->assertLessThan(4.5, $ratio);

        // Couleur opaque (même résultat qu'avant)
        $ratio1 = ColorHelper::contrastRatio('#ff0000', '#ffffff');
        $ratio2 = ColorHelper::contrastRatioWithAlpha('#ff0000', '#ffffff');
        $this->assertEquals($ratio1, $ratio2);

        // Invalide
        $this->assertNull(ColorHelper::contrastRatioWithAlpha('invalid', '#ffffff'));
    }

    /**
     * Teste la validation WCAG avec alpha
     *
     * @test
     */
    public function testIsContrastOkWithAlpha()
    {
        // Noir opaque sur blanc : AA ✓
        $this->assertTrue(
            ColorHelper::isContrastOkWithAlpha('#000000', '#ffffff', 'AA', 'normal')
        );

        // Noir semi-transparent sur blanc : peut échouer AA selon l'alpha
        $result = ColorHelper::isContrastOkWithAlpha('#00000060', '#ffffff', 'AA', 'normal');
        $this->assertFalse($result); // Trop transparent pour AA

        // Noir à 90% sur blanc : devrait passer AA
        $result = ColorHelper::isContrastOkWithAlpha('#000000e6', '#ffffff', 'AA', 'normal');
        $this->assertTrue($result);

        // Rouge semi-transparent sur blanc
        $result = ColorHelper::isContrastOkWithAlpha('#ff000080', '#ffffff', 'AA', 'normal');
        $this->assertFalse($result); // Rose = contraste insuffisant

        // Bleu foncé avec alpha sur blanc
        $result = ColorHelper::isContrastOkWithAlpha('#0000ffcc', '#ffffff', 'AA', 'large');
        $this->assertTrue($result); // Devrait passer pour large
    }

    /**
     * Teste bestTextColor avec alpha
     *
     * @test
     */
    public function testBestTextColorWithAlpha()
    {
        // Fond blanc semi-transparent sur blanc = reste clair → texte noir
        $result = ColorHelper::bestTextColorWithAlpha('#ffffff80', '#ffffff');
        $this->assertEquals('#000000', $result);

        // Fond noir semi-transparent sur blanc = gris → dépend de l'alpha
        $result = ColorHelper::bestTextColorWithAlpha('#00000080', '#ffffff');
        $this->assertEquals('#000000', $result);

        // Fond noir très transparent sur blanc = presque blanc → texte noir
        $result = ColorHelper::bestTextColorWithAlpha('#00000020', '#ffffff');
        $this->assertEquals('#000000', $result);

        // Fond noir opaque sur n'importe quoi → texte blanc
        $result = ColorHelper::bestTextColorWithAlpha('#000000', '#ffffff');
        $this->assertEquals('#ffffff', $result);
    }

    /**
     * Teste un cas réel : October CMS avec alpha
     *
     * @test
     */
    public function testOctoberCmsAlphaFormat()
    {
        // Format October : #9a2f2fa2 (rouge foncé à ~63% d'opacité)
        $parsed = ColorHelper::parseColor('#9a2f2fa2');
        $this->assertEquals(154, $parsed['r']);
        $this->assertEquals(47, $parsed['g']);
        $this->assertEquals(47, $parsed['b']);
        $this->assertEqualsWithDelta(0.635, $parsed['a'], 0.01);

        // Composer sur fond blanc
        $composed = ColorHelper::composeColor('#9a2f2fa2', '#ffffff');
        $this->assertGreaterThan(154, $composed['r']); // Plus clair que l'original
        $this->assertEquals(1.0, $composed['a']); // Résultat opaque

        // Vérifier le contraste
        $ratio = ColorHelper::contrastRatioWithAlpha('#9a2f2fa2', '#ffffff');
        $this->assertGreaterThan(2.0, $ratio);
    }

    /* =====================================
     * Tests de Génération
     * ===================================== */

    /**
     * Teste la génération de couleurs aléatoires
     *
     * @test
     */
    public function testRandomColor()
    {
        // Test du mode standard (Chaos)
        $color = ColorHelper::randomColor();

        // Vérifie que c'est bien un format hexadécimal valide (#RRGGBB)
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);

        // Test du mode "Fancy"
        $fancyColor = ColorHelper::randomColor(true);

        // Vérifie le format
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $fancyColor);

        // Vérifie que la couleur est bien parsable par notre propre helper
        $parsed = ColorHelper::parseColor($fancyColor);
        $this->assertNotNull($parsed);

        // Test de variabilité
        // On génère deux couleurs pour s'assurer qu'elles ne sont pas identiques
        $color1 = ColorHelper::randomColor();
        $color2 = ColorHelper::randomColor();
        $this->assertNotEquals($color1, $color2);
    }
}
