/**
 * IBD Vertretungen - Admin JavaScript
 *
 * @package IBD_Vertretungen
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color picker
        if ($.fn.wpColorPicker) {
            $('.ibd-color-picker').wpColorPicker();
        }

        // Range slider value display
        $('input[type="range"]').on('input', function() {
            $(this).next('span').text(this.value);
        });
    });

})(jQuery);
