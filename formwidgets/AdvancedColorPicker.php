<?php namespace Ducharme\ColorTools\FormWidgets;

use Backend\FormWidgets\ColorPicker;
use Ducharme\ColorTools\Classes\ColorHelper;
use Flash;
use ValidationException;

class AdvancedColorPicker extends ColorPicker
{
    /**
     * @var string defaultAlias Alias par défaut du widget
     */
    protected $defaultAlias = 'advancedcolorpicker';

    /**
     * @var string role Role de la couleur (foreground ou background)
     */
    public $role;

    /**
     * @var string compareTo Couleur fixe de comparaison
     */
    public $compareTo;

    /**
     * @var string contrastWith Nom du champ lié pour validation
     */
    public $contrastWith;

    /**
     * @var string contrastLevel Niveau WCAG (AA ou AAA)
     */
    public $contrastLevel = 'AA';

    /**
     * @var string contrastSize Taille/Type d'élément (normal, large ou ui)
     */
    public $contrastSize = 'normal';

    /**
     * @var bool contrastRequired Le contraste WCAG est obligatoire
     */
    public $contrastRequired = false;

    /**
     * Initialise le widget
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Charger nos configurations supplémentaires
        $this->fillFromConfig([
            'role',
            'compareTo',
            'contrastWith',
            'contrastLevel',
            'contrastSize',
            'contrastRequired',
        ]);
    }

    /**
     * Prépare les variables pour la vue
     *
     * @return void
     */
    public function prepareVars()
    {
        parent::prepareVars();

        // Variables de configuration supplémentaires
        $this->vars['role'] = $this->role;
        $this->vars['compareTo'] = $this->compareTo;
        $this->vars['contrastWith'] = $this->contrastWith ? $this->getRelativeFieldName($this->contrastWith) : null;
        $this->vars['contrastLevel'] = $this->contrastLevel;
        $this->vars['contrastSize'] = $this->contrastSize;
        $this->vars['contrastRequired'] = $this->contrastRequired;
    }

    /**
     * Récupère le nom HTML complet d'un autre champ pour le contraste
     *
     * @param string $targetFieldName Nom du champ cible
     * @return string Nom HTML complet du champ cible
     */
    protected function getRelativeFieldName($targetFieldName)
    {
        $currentName = $this->getFieldName();
        $currentFieldConfig = $this->formField->fieldName;

        // Si c’est exactement le nom de la config
        if ($currentName === $currentFieldConfig) {
            return $targetFieldName;
        }

        // Si c’est un tableau comme Settings[name]
        $suffix = '[' . $currentFieldConfig . ']';
        if (str_ends_with($currentName, $suffix)) {
            return substr($currentName, 0, -strlen($suffix)) . '[' . $targetFieldName . ']';
        }

        return $targetFieldName;
    }

    /**
     * Ajoute des fichiers de ressources spécifiques au widget
     */
    protected function loadAssets()
    {
        parent::loadAssets();

        // Fichiers de ressources supplémentaires
        $this->addCss('css/contrastinfo.css');
        $this->addJs('js/contrastinfo.js');
    }

    /**
     * Traite la valeur avant la sauvegarde
     *
     * @param mixed $value
     * @return mixed
     */
    public function getSaveValue($value)
    {
        $processedValue = parent::getSaveValue($value);

        // Validation du contraste
        if ($this->shouldCalculateContrast()) {
            $this->validateContrast($processedValue);
        }

        return $processedValue;
    }

    /**
     * Valide le contraste selon les paramètres configurés
     *
     * @param string $value
     * @return void
     * @throws ValidationException
     */
    protected function validateContrast($value)
    {
        $compareColor = $this->getCompareColor();

        if (!$compareColor) {
            return;
        }

        // Déterminer quelle méthode utiliser selon le rôle
        $isValid = false;
        $ratio = null;

        if ($this->role === 'foreground') {
            // Le texte (foreground) supporte toujours le calcul avec opacité
            $isValid = ColorHelper::isContrastOkWithAlpha($value, $compareColor, $this->contrastLevel, $this->contrastSize);
            $ratio = ColorHelper::contrastRatioWithAlpha($value, $compareColor);
        } else {
            // Le background est traité comme opaque (on ignore l'alpha)
            $isValid = ColorHelper::isContrastOk($value, $compareColor, $this->contrastLevel, $this->contrastSize);
            $ratio = ColorHelper::contrastRatio($value, $compareColor);
        }

        if (!$isValid && $ratio !== null) {
            // Utilisation de la clé de traduction avec les paramètres dynamiques
            $message = trans('ducharme.colortools::lang.advanced_color_picker.contrast_insufficient', ['label' => trans($this->formField->label), 'ratio' => $ratio]);

            // Affiche un message Flash et bloque la sauvegarde
            if ($this->contrastRequired) {
                throw new ValidationException([$this->getFieldName() => $message]);
            }

            // Affiche un avertissement
            Flash::warning($message);
        }
    }

    /**
     * Détermine si le calcul du contraste doit être effectué
     *
     * @return bool
     */
    protected function shouldCalculateContrast()
    {
        return $this->role !== null && ($this->compareTo !== null || $this->contrastWith !== null);
    }

    /**
     * Récupère la couleur de comparaison
     *
     * @return string|null
     */
    protected function getCompareColor()
    {
        if ($this->compareTo) {
            return $this->compareTo;
        }

        if ($this->contrastWith) {
            $allData = post();

            // Découpe le nom du champ cible en parties pour naviguer dans le tableau POST
            $parts = explode('[', str_replace(']', '', $this->getRelativeFieldName($this->contrastWith)));

            $compareColor = $allData;
            foreach ($parts as $part) {
                if (isset($compareColor[$part])) {
                    $compareColor = $compareColor[$part];
                } else {
                    $compareColor = null;
                    break;
                }
            }

            // fallback sur le modèle si pas dans le POST
            if (!$compareColor && $this->model) {
                $compareColor = $this->model->{$this->contrastWith} ?? null;
            }

            // fallback par rôle
            if (!$compareColor) {
                $compareColor = $this->role === 'foreground' ? '#ffffff' : '#000000';
            }

            return $compareColor;
        }

        return null;
    }
}
