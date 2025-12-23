# Plugin Color Tools pour October CMS

**Color Tools** est un plugin pour October CMS qui fournit des outils avancés pour la gestion et la manipulation des couleurs. Il propose un **FormWidget ColorPicker amélioré** avec validation WCAG du contraste, ainsi qu'une bibliothèque complète de **filtres et fonctions Twig** pour convertir, analyser et manipuler les couleurs directement dans vos templates.

## Fonctionnalités principales

- **FormWidget AdvancedColorPicker** : extension du ColorPicker natif avec validation automatique du contraste WCAG
- **Filtres Twig de conversion** : conversion entre formats hex, RGB, RGBA et HSL
- **Filtres Twig d'analyse** : calcul de luminance, ratio de contraste et détection de la meilleure couleur de texte
- **Fonctions Twig avancées** : mélange de couleurs, composition alpha, validation WCAG et génération aléatoire
- **Interface intuitive** : badges visuels et indicateurs en temps réel pour le contraste

## Installation

Vous pouvez installer ce plugin depuis le **Marketplace October CMS** ou en utilisant **Composer**.

### Via Marketplace

1. Allez dans le backend d’October CMS : **Settings > System > Plugins**.
2. Recherchez le plugin **Color Tools**.
3. Cliquez sur le plugin pour l’installer.

### Via Composer

Ouvrez votre terminal, placez-vous à la racine de votre projet October CMS et exécutez la commande suivante :

```bash
php artisan plugin:install Ducharme.ColorTools
```

## Utilisation

Le plugin **Color Tools** propose deux fonctionnalités principales : un FormWidget avancé pour le backend et une collection de filtres/fonctions Twig pour le frontend.

### 1. FormWidget AdvancedColorPicker

Le FormWidget **AdvancedColorPicker** étend le **ColorPicker** natif d'October CMS en ajoutant la validation automatique du contraste selon les normes WCAG 2.0.

#### Options disponibles

Le widget hérite de toutes les options du [ColorPicker natif](https://docs.octobercms.com/4.x/element/form/widget-colorpicker.html) et ajoute les options suivantes :

| Option             | Type    | Description                                                      | Défaut   |
|--------------------|---------|------------------------------------------------------------------|----------|
| `role`             | String  | Rôle de la couleur : `foreground` (texte) ou `background` (fond) | -        |
| `compareTo`        | String  | Couleur fixe de comparaison (ex: `#ffffff`)                      | -        |
| `contrastWith`     | String  | Nom du champ à comparer dynamiquement                            | -        |
| `contrastLevel`    | String  | Niveau WCAG : `AA` ou `AAA`                                      | `AA`     |
| `contrastSize`     | String  | Taille d'élément : `normal`, `large` ou `ui`                     | `normal` |
| `contrastRequired` | Boolean | Bloque la sauvegarde si contraste insuffisant                    | `false`  |

**Notes importantes :**

- Le champ comparatif (`contrastWith`) peut être associé à un champ de type `colorpicker` ou `advancedcolorpicker`
- Pour les couleurs `foreground` avec alpha : le contraste est calculé en composant la couleur sur le fond
- Pour les couleurs `background` avec alpha : le contraste est calculé en ignorant la transparence (comme si la couleur était opaque)

#### Interface visuelle

Le FormWidget affiche des badges informatifs en temps réel :

- **Badge Level + Size** : niveau WCAG (AA/AAA) et taille (normal/large/ui) avec le ratio cible
- **Badge champ lié** : nom du champ de comparaison (cliquable)
- **Badge couleur comparée** : aperçu visuel de la couleur de comparaison
- **Badge statut** : conformité du contraste avec le ratio actuel

#### Exemples d'utilisation

```yaml
# Texte sur fond fixe
text_color:
    label: "Couleur du texte"
    type: advancedcolorpicker
    role: foreground
    compareTo: '#ffffff'
    contrastLevel: AA
    contrastRequired: true

# Validation croisée entre deux champs
background_color:
    label: "Couleur de fond"
    type: colorpicker

text_color:
    label: "Couleur du texte"
    type: advancedcolorpicker
    role: foreground
    contrastWith: background_color
    contrastLevel: AAA
    contrastRequired: true
```

### 2. Filtres et fonctions Twig

Le plugin enregistre automatiquement des filtres et fonctions Twig pour manipuler les couleurs dans vos templates.

#### Filtres disponibles

| Filtre             | Syntaxe                                  | Retour  | Description                                                   |
|--------------------|------------------------------------------|---------|---------------------------------------------------------------|
| `to_hex`           | `{{ color\|to_hex }}`                    | String  | Convertit en hexadécimal (#rrggbb)                            |
| `to_rgb`           | `{{ color\|to_rgb }}`                    | String  | Convertit en RGB (rgb(r, g, b))                               |
| `to_rgba`          | `{{ color\|to_rgba(alpha?) }}`           | String  | Convertit en RGBA. Si alpha omis, conserve l'alpha d'origine  |
| `to_hsl`           | `{{ color\|to_hsl }}`                    | String  | Convertit en HSL (hsl(h, s%, l%))                             |
| `luminance`        | `{{ color\|luminance }}`                 | Float   | Retourne la luminance relative (0.0 à 1.0)                    |
| `contrast_ratio`   | `{{ fg\|contrast_ratio(bg) }}`           | Float   | Ratio de contraste WCAG (1.0 à 21.0)                          |
| `best_text_color`  | `{{ bg\|best_text_color(underlying?) }}` | String  | Retourne #000000 ou #ffffff. underlying par défaut : #ffffff  |

#### Fonctions disponibles

| Fonction          | Syntaxe                                        | Retour  | Description                                                       |
|-------------------|------------------------------------------------|---------|-------------------------------------------------------------------|
| `color_mix`       | `{{ color_mix(c1, c2, ratio?) }}`              | String  | Mélange deux couleurs. ratio par défaut : 0.5 (0=c1, 1=c2)        |
| `color_compose`   | `{{ color_compose(fg, bg) }}`                  | String  | Compose une couleur avec alpha sur un fond                        |
| `is_contrast_ok`  | `{{ is_contrast_ok(fg, bg, level?, size?) }}`  | Boolean | Vérifie conformité WCAG. Défauts : level='AA', size='normal'      |
| `color_random`    | `{{ color_random(fancy?) }}`                   | String  | Génère une couleur. fancy=true donne une couleur vive équilibrée  |

#### Exemples d'utilisation

```twig
{# Conversions de formats #}
{{ 'rgb(255,0,0)'|to_hex }}           {# #ff0000 #}
{{ '#3498db'|to_rgba(0.7) }}          {# rgba(52, 152, 219, 0.70) #}
{{ 'rgba(255, 0, 0, 0.5)'|to_rgba }}  {# rgba(255, 0, 0, 0.50) - conserve l'alpha #}

{# Texte adaptatif selon le fond #}
{% set bgColor = settings.background_color %}
<div style="background: {{ bgColor }}; color: {{ bgColor|best_text_color }};">
    Texte avec contraste optimal
</div>

{# Validation WCAG avec affichage conditionnel #}
{% set ratio = textColor|contrast_ratio(bgColor) %}
{% if is_contrast_ok(textColor, bgColor, 'AA') %}
    <p style="color: {{ textColor }}; background: {{ bgColor }};">
        Contraste conforme ({{ ratio }}:1)
    </p>
{% else %}
    <div class="alert alert-warning">
        Contraste insuffisant : {{ ratio }}:1 (minimum AA : 4.5:1)
    </div>
{% endif %}

{# Génération de palette de couleurs #}
{% set baseColor = '#3498db' %}
<div class="palette">
    <div style="background: {{ baseColor }}">Couleur de base</div>
    <div style="background: {{ color_mix(baseColor, '#ffffff', 0.3) }}">Teinte claire</div>
    <div style="background: {{ color_mix(baseColor, '#000000', 0.3) }}">Teinte foncée</div>
</div>

{# Composition d'overlay semi-transparent #}
{% set overlayColor = 'rgba(0, 0, 0, 0.7)' %}
{% set underlyingBg = '#ffffff' %}
{% set composedColor = color_compose(overlayColor, underlyingBg) %}
<div class="overlay" style="background: {{ overlayColor }}; color: {{ composedColor|best_text_color }};">
    Overlay avec texte optimal
</div>
```

### 3. Normes WCAG

Le plugin respecte les directives WCAG 2.0 pour l'accessibilité :

| Niveau   | Taille                | Ratio minimum  |
|----------|-----------------------|----------------|
| AA       | Normal (< 24px)       | 4.5:1          |
| AA       | Large (≥ 24px)        | 3.0:1          |
| AA       | UI (icônes, bordures) | 3.0:1          |
| AAA      | Normal (< 24px)       | 7.0:1          |
| AAA      | Large (≥ 24px)        | 4.5:1          |
| AAA      | UI (icônes, bordures) | 3.0:1          |

**Note :** Les tailles en pixels sont données à titre indicatif, conformément à l'équivalence WCAG (18 pt ≈ 24 px).

## Contribuer

Les contributions sont les bienvenues !

- Forkez le projet et créez votre branche pour les améliorations ou corrections.
- Vérifiez que les tests passent avant de soumettre votre Pull Request : `vendor/bin/phpunit plugins/ducharme/colortools/tests`
- Soumettez une [Pull Request](https://github.com/PhilippeBlanko/oc-color-tools-plugin/pulls) avec une description claire des changements.
- Signalez les bugs ou problèmes via les [Issues](https://github.com/PhilippeBlanko/oc-color-tools-plugin/issues).

Merci de respecter les bonnes pratiques de contribution et de documenter vos modifications.

## Licence

Ce plugin est distribué sous licence **MIT**.  
Le texte complet de la licence MIT est disponible ici : [MIT License](https://github.com/PhilippeBlanko/oc-color-tools-plugin/blob/main/LICENCE)

## Documentation

Cette documentation a été générée en partie avec l'aide d'une intelligence artificielle.  
La version anglaise de ce README est disponible ici : [README.md](README.md)
