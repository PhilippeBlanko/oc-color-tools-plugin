<?php namespace Ducharme\ColorTools;

use System\Classes\PluginBase;
use Ducharme\ColorTools\Classes\ColorExtension;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'ducharme.colortools::lang.plugin.name',
            'description' => 'ducharme.colortools::lang.plugin.description',
            'author' => 'Philippe Ducharme',
            'icon' => 'icon-eyedropper',
        ];
    }

    public function registerFormWidgets()
    {
        return [
            \Ducharme\ColorTools\FormWidgets\AdvancedColorPicker::class => 'advancedcolorpicker',
        ];
    }

    public function registerMarkupTags()
    {
        return [
            'filters' => [
                // Conversions de format
                'to_hex' => [ColorExtension::class, 'toHex'],
                'to_rgb' => [ColorExtension::class, 'toRgb'],
                'to_rgba' => [ColorExtension::class, 'toRgba'],
                'to_hsl' => [ColorExtension::class, 'toHsl'],

                // Analyse et Propriétés
                'luminance' => [ColorExtension::class, 'luminance'],
                'best_text_color' => [ColorExtension::class, 'bestTextColor'],
                'contrast_ratio' => [ColorExtension::class, 'contrastRatio'],
            ],
            'functions' => [
                // Logique et Validation
                'color_mix' => [ColorExtension::class, 'colorMix'],
                'color_compose' => [ColorExtension::class, 'colorCompose'],
                'is_contrast_ok' => [ColorExtension::class, 'isContrastOk'],
                'color_random' => [ColorExtension::class, 'colorRandom'],
            ],
        ];
    }
}
