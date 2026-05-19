/**
 * color-picker.js
 *
 * Initialise les WP Color Pickers présents dans l'admin du plugin.
 * Reste en script jQuery classique (et non ES module) car wp-color-picker
 * dépend de jQuery et doit être bootstrappé au DOM ready.
 */
(function ($) {
    'use strict';

    function init() {
        $('.cf7mw-color-input').each(function () {
            const $input = $(this);
            if ($input.data('cf7mw-init')) return;
            $input.data('cf7mw-init', true);

            $input.wpColorPicker({
                defaultColor: $input.data('default') || '',
                change: function (event, ui) {
                    const color = ui.color.toString();
                    $input.val(color).trigger('cf7mw:color-change', [color]);
                },
                clear: function () {
                    $input.trigger('cf7mw:color-change', ['']);
                },
            });
        });
    }

    $(document).ready(init);

    // Permettre la ré-initialisation après un ajout dynamique.
    $(document).on('cf7mw:refresh-color-pickers', init);
})(jQuery);
