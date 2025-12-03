/**
 * CheckoutFields Module - Admin JavaScript
 */

(function ($) {
    'use strict';

    const CheckoutFieldsAdmin = {
        /**
         * Initialize
         */
        init: function () {
            this.initFieldControls();
            this.bindEvents();
        },

        /**
         * Initialize field controls
         */
        initFieldControls: function () {
            // Add visual feedback for toggle changes
            $('.nt-field-control .nt-toggle input').on('change', function () {
                const $row = $(this).closest('.nt-field-row');
                const isEnabled = $row.find('input[name*="[enabled]"]').is(':checked');

                if (!isEnabled) {
                    $row.css('opacity', '0.6');
                } else {
                    $row.css('opacity', '1');
                }
            }).trigger('change');
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            // Form submission confirmation
            $('.nt-form').on('submit', function (e) {
                const hasChanges = $(this).find('input, select, textarea').toArray().some(field => {
                    return $(field).val() !== $(field).data('original-value');
                });

                if (hasChanges) {
                    return confirm('Save changes to checkout fields?');
                }
            });

            // Store original values for change detection
            $('.nt-form input, .nt-form select, .nt-form textarea').each(function () {
                $(this).data('original-value', $(this).val());
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function () {
        CheckoutFieldsAdmin.init();
    });

})(jQuery);
