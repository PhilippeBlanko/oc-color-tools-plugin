<div class="contrast-info"
     data-role="<?= $role ?>"
     data-compare-to="<?= $compareTo ?>"
     data-contrast-with="<?= $contrastWith ?>"
     data-contrast-level="<?= $contrastLevel ?>"
     data-contrast-size="<?= $contrastSize ?>"
     data-contrast-required="<?= $contrastRequired ? 'true' : 'false' ?>"
     data-lang-target-ratio="<?= e(trans('ducharme.colortools::lang.advanced_color_picker.target_ratio')) ?>"
     data-lang-linked-field-fallback="<?= e(trans('ducharme.colortools::lang.advanced_color_picker.linked_field_fallback')) ?>">

    <div class="contrast-badges">
        <!-- Badge Level + Size -->
        <span class="contrast-badge">
            <i class="icon-universal-access"></i>
            <?= strtoupper($contrastLevel) ?> · <?= ucfirst($contrastSize) ?>
            <span class="contrast-ratio-target" data-target-ratio=""></span>
        </span>

        <?php if ($contrastWith): ?>
            <!-- Badge champ lié (cliquable) -->
            <button class="contrast-badge contrast-badge-linked"
                    data-linked-field="<?= $contrastWith ?>"
                    type="button"
                    title="<?= e(trans('ducharme.colortools::lang.advanced_color_picker.linked_field_title')) ?>">
                <i class="icon-link"></i>
                <span class="linked-field-label"></span>
            </button>
        <?php endif ?>

        <!-- Badge couleur comparée -->
        <span class="contrast-badge">
            <i class="icon-exchange"></i>
            <span class="color-swatch" data-compare-color=""></span>
        </span>

        <!-- Badge statut du contraste -->
        <span class="contrast-badge contrast-badge-status"
              data-current-ratio=""
              style="display: none;">
            <i class="icon-check-circle"></i>
            <span class="status-text"></span>
        </span>
    </div>
</div>
