+function ($) { "use strict";

  /**
   * Constructeur de l'information sur le contraste.
   * @param {HTMLElement} element L'élément conteneur du plugin.
   * @param {Object} options Options de configuration du plugin.
   */
  var ContrastInfo = function (element, options) {
    this.$el = $(element);
    this.$info = this.$el.find('.contrast-info');
    this.$input = this.$el.find('input[type="text"], input[type="hidden"]');
    this.$statusBadge = this.$info.find('.contrast-badge-status');

    this.options = {
      role: this.$info.data('role'),
      compareTo: this.$info.data('compare-to'),
      contrastWith: this.$info.data('contrast-with'),
      contrastLevel: this.$info.data('contrast-level') || 'AA',
      contrastSize: this.$info.data('contrast-size') || 'normal',
      contrastRequired: this.$info.data('contrast-required') === true,
      langTargetRatio: this.$info.data('lang-target-ratio'),
      langLinkedFieldFallback: this.$info.data('lang-linked-field-fallback'),
    };

    this.thresholds = this.getThresholds();
    this.init();
  };

  /**
   * Initialise les événements et l'état visuel initial.
   */
  ContrastInfo.prototype.init = function () {
    var self = this;

    // Mettre à jour le ratio cible dans le badge
    this.$info.find('.contrast-ratio-target')
      .attr('data-target-ratio', this.thresholds.required)
      .text(this.options.langTargetRatio.replace(':ratio', this.thresholds.required));

    // Gérer le clic sur le badge du champ lié
    this.$info.find('.contrast-badge-linked').on('click', function () {
      self.focusLinkedField();
    });

    // Mettre à jour le label du champ lié
    this.updateLinkedFieldLabel();

    // Initialiser le color swatch avec la bonne couleur
    var initialCompareColor = this.getCompareColor();
    if (initialCompareColor) {
      this.updateCompareColor(initialCompareColor);
    }

    // Écouter les changements sur ce champ
    this.$input.on('change.oc.contrastinfo', function () {
      self.validate();
    });

    // Si contrastWith: écouter les changements sur l'autre champ
    if (this.options.contrastWith) {
      var $linkedField = $('[name="' + this.options.contrastWith + '"]');
      $linkedField.on('change.oc.contrastinfo', function () {
        self.updateCompareColor($(this).val());
        self.validate();
      });
    }

    // Validation initiale
    this.validate();
  };

  /**
   * Définit le seuil de contraste requis selon les standards WCAG.
   * @returns {{required: number}} Le ratio minimum (ex: 3.0, 4.5, 7.0).
   */
  ContrastInfo.prototype.getThresholds = function () {
    var level = this.options.contrastLevel.toUpperCase();
    var size = this.options.contrastSize.toLowerCase();

    var thresholds = {
      'AA-normal': 4.5,
      'AA-large': 3.0,
      'AA-ui': 3.0,
      'AAA-normal': 7.0,
      'AAA-large': 4.5,
      'AAA-ui': 3.0
    };

    return {
      required: thresholds[level + '-' + size] || 4.5,
    };
  };

  /**
   * Valide le contraste actuel et met à jour l'interface.
   */
  ContrastInfo.prototype.validate = function () {
    var currentColor = this.$input.val();
    var compareColor = this.getCompareColor();

    if (!currentColor || !compareColor) {
      this.hideStatus();
      return;
    }

    // Calculer le ratio
    var ratio = this.calculateContrastRatio(currentColor, compareColor);

    if (ratio === null) {
      this.hideStatus();
      return;
    }

    // Afficher le statut
    this.updateStatus(ratio);
  };

  /**
   * Calcule le ratio de contraste entre deux couleurs.
   * Gère la composition alpha si le rôle est 'foreground'.
   * @param {string} color1 - Première couleur (hex).
   * @param {string} color2 - Deuxième couleur (hex).
   * @returns {number|null} Ratio (ex: 4.5) ou null en cas d'erreur.
   */
  ContrastInfo.prototype.calculateContrastRatio = function (color1, color2) {
    try {
      var c1 = this.parseColor(color1);
      var c2 = this.parseColor(color2);

      if (!c1 || !c2) return null;

      // Si foreground avec alpha, composer d'abord
      if (this.options.role === 'foreground' && c1.a < 1.0) {
        c1 = this.composeColor(c1, c2);
      }

      var l1 = this.relativeLuminance(c1);
      var l2 = this.relativeLuminance(c2);

      var lighter = Math.max(l1, l2);
      var darker = Math.min(l1, l2);

      return parseFloat(((lighter + 0.05) / (darker + 0.05)).toFixed(2));
    } catch (e) {
      console.error('Erreur calcul contraste:', e);
      return null;
    }
  };

  /**
   * Convertit une chaîne Hex en objet RGBA.
   * @param {string} value - Couleur au format #RGB, #RRGGBB ou #RRGGBBAA.
   * @returns {Object|null} {r, g, b, a}
   */
  ContrastInfo.prototype.parseColor = function (value) {
    if (!value || typeof value !== 'string') return null;

    value = value.trim().toLowerCase();

    // Hex: #rrggbb ou #rrggbbaa
    var hexMatch = value.match(/^#([0-9a-f]{6})([0-9a-f]{2})?$/);
    if (hexMatch) {
      var r = parseInt(hexMatch[1].substr(0, 2), 16);
      var g = parseInt(hexMatch[1].substr(2, 2), 16);
      var b = parseInt(hexMatch[1].substr(4, 2), 16);
      var a = hexMatch[2] ? parseInt(hexMatch[2], 16) / 255 : 1.0;
      return { r: r, g: g, b: b, a: a };
    }

    // Hex court: #rgb
    hexMatch = value.match(/^#([0-9a-f]{3})$/);
    if (hexMatch) {
      var hex = hexMatch[1];
      return {
        r: parseInt(hex[0] + hex[0], 16),
        g: parseInt(hex[1] + hex[1], 16),
        b: parseInt(hex[2] + hex[2], 16),
        a: 1.0
      };
    }

    return null;
  };

  /**
   * Simule la couleur résultante d'une couleur semi-transparente sur un fond opaque.
   * @param {Object} fg - Foreground {r, g, b, a}
   * @param {Object} bg - Background {r, g, b, a}
   * @returns {Object} Couleur composée.
   */
  ContrastInfo.prototype.composeColor = function (fg, bg) {
    var alpha = fg.a;
    return {
      r: Math.round(fg.r * alpha + bg.r * (1 - alpha)),
      g: Math.round(fg.g * alpha + bg.g * (1 - alpha)),
      b: Math.round(fg.b * alpha + bg.b * (1 - alpha)),
      a: 1.0
    };
  };

  /**
   * Calcule la luminance relative selon la formule WCAG.
   * @see https://www.w3.org/TR/WCAG20/#relativeluminancedef
   * @param {Object} color - {r, g, b}
   * @returns {number} Luminance entre 0 et 1.
   */
  ContrastInfo.prototype.relativeLuminance = function (color) {
    var r = color.r / 255;
    var g = color.g / 255;
    var b = color.b / 255;

    r = (r <= 0.03928) ? (r / 12.92) : Math.pow((r + 0.055) / 1.055, 2.4);
    g = (g <= 0.03928) ? (g / 12.92) : Math.pow((g + 0.055) / 1.055, 2.4);
    b = (b <= 0.03928) ? (b / 12.92) : Math.pow((b + 0.055) / 1.055, 2.4);

    return (0.2126 * r) + (0.7152 * g) + (0.0722 * b);
  };

  /**
   * Met à jour l'affichage du badge de statut (Succès/Échec/Avertissement).
   * @param {number} ratio - Le ratio calculé.
   */
  ContrastInfo.prototype.updateStatus = function (ratio) {
    var statusClass, icon;

    if (ratio >= this.thresholds.required) {
      statusClass = 'status-pass';
      icon = 'icon-check-circle';
    } else {
      // Si requis = erreur (fail), sinon simple avertissement (warning)
      statusClass = this.options.contrastRequired ? 'status-fail' : 'status-warning';
      icon = this.options.contrastRequired ? 'icon-times-circle' : 'icon-exclamation-circle';
    }

    // Mettre à jour le badge
    this.$statusBadge
      .removeClass('status-pass status-fail status-warning')
      .addClass(statusClass)
      .attr('data-current-ratio', ratio)
      .html('<i class="' + icon + '"></i> <span class="status-text">' + ratio + ':1</span>')
      .show();
  };

  /**
   * Masque les éléments de statut de contraste.
   */
  ContrastInfo.prototype.hideStatus = function () {
    this.$statusBadge.hide();
    this.$message.hide();
  };

  /**
   * Récupère la couleur de comparaison (soit fixe, soit depuis un autre champ).
   * @returns {string|null} Couleur Hex.
   */
  ContrastInfo.prototype.getCompareColor = function () {
    if (this.options.compareTo) {
      return this.options.compareTo;
    }

    if (this.options.contrastWith) {
      var $linkedField = $('[name="' + this.options.contrastWith + '"]');
      return $linkedField.val() || (this.options.role === 'foreground' ? '#ffffff' : '#000000');
    }

    return null;
  };

  /**
   * Met à jour l'aperçu visuel (swatch) de la couleur de comparaison.
   * @param {string} color - Couleur Hex (peut inclure l'alpha).
   */
  ContrastInfo.prototype.updateCompareColor = function (color) {
    var $swatch = this.$info.find('.color-swatch[data-compare-color]');
    if (!$swatch.length) return;

    var displayColor = color;

    // Si rôle foreground, on force l'affichage sans alpha pour le swatch de background
    if (this.options.role === 'foreground') {
      var parsed = this.parseColor(color);
      if (parsed) {
        // Reconstruit en format #RRGGBB (ignore parsed.a)
        displayColor = '#' +
          ((1 << 24) + (parsed.r << 16) + (parsed.g << 8) + parsed.b)
            .toString(16).slice(1);
      }
    }

    $swatch.css('background-color', displayColor).attr('data-compare-color', color);
  };

  /**
   * Récupère et affiche le label du champ de formulaire lié.
   */
  ContrastInfo.prototype.updateLinkedFieldLabel = function () {
    if (!this.options.contrastWith) return;

    var $linkedField = $('[name="' + this.options.contrastWith + '"]');
    var $formGroup = $linkedField.closest('.form-group');
    var label = $formGroup.find('label').first().text().trim() || this.options.langLinkedFieldFallback;

    this.$info.find('.linked-field-label').text(label);
  };

  /**
   * Scrolle la page jusqu'au champ lié et applique une animation de mise en évidence.
   */
  ContrastInfo.prototype.focusLinkedField = function () {
    if (!this.options.contrastWith) return;

    var $linkedField = $('[name="' + this.options.contrastWith + '"]');
    var $formGroup = $linkedField.closest('.form-group');

    if ($formGroup.length) {
      $('html, body').animate({
        scrollTop: $formGroup.offset().top - 100
      }, 300);

      // Highlight temporaire
      $formGroup.addClass('highlight-field');
      setTimeout(function () {
        $formGroup.removeClass('highlight-field');
      }, 2000);
    }
  };

  // Plugin jQuery
  var old = $.fn.contrastInfo;

  $.fn.contrastInfo = function (option) {
    return this.each(function () {
      var $this = $(this);
      var data = $this.data('oc.contrastinfo');

      if (!data) {
        $this.data('oc.contrastinfo', (data = new ContrastInfo(this, option)));
      }
    });
  };

  $.fn.contrastInfo.Constructor = ContrastInfo;

  // No conflict
  $.fn.contrastInfo.noConflict = function () {
    $.fn.contrastInfo = old;
    return this;
  };

  // Auto-init
  $(document).render(function () {
    $('[data-control="colorpicker"]').has('.contrast-info').contrastInfo();
  });

}(window.jQuery);
