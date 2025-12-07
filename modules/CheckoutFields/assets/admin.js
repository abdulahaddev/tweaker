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
            // Handle individual field "Enabled" toggles
            const $fieldRows = $('.wp-list-table tbody tr');

            $fieldRows.each(function () {
                const $row = $(this);
                const $enableToggle = $row.find('input[name*="[enabled]"]');
                const $parentSection = $row.closest('[class*="nt-field-table-"]');

                // Function to update row state
                function updateRowState() {
                    const isEnabled = $enableToggle.is(':checked');
                    const isSectionDisabled = $parentSection.hasClass('nt-disabled');

                    // Only apply field-level disabled class if section is NOT disabled
                    // to prevent double styling
                    if (!isEnabled && !isSectionDisabled) {
                        // Disable all inputs/selects/textareas EXCEPT the enable toggle
                        $row.find('input:not([name*="[enabled]"]):not([name*="[priority]"]), select, textarea').prop('readonly', true);
                        $row.find('input[name*="[required]"]').prop('disabled', true);

                        // Add disabled visual class to the row
                        $row.addClass('nt-field-disabled');
                    } else if (isEnabled || isSectionDisabled) {
                        // Enable all inputs (unless section is disabled, which handles everything)
                        if (!isSectionDisabled) {
                            $row.find('input, select, textarea').prop('readonly', false).prop('disabled', false);
                        }

                        // Remove disabled visual class
                        $row.removeClass('nt-field-disabled');
                    }
                }

                // Initial state
                updateRowState();

                // Listen for changes on enable toggle
                $enableToggle.on('change', updateRowState);

                // Listen for section toggle changes to update field states
                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.attributeName === 'class') {
                            updateRowState();
                        }
                    });
                });

                observer.observe($parentSection[0], { attributes: true });
            });
        },

        /**
         * Bind events
         */
        bindEvents: function () {
            // Store original values for change detection (reserved for future use)
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
