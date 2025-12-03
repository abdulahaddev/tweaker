/**
 * Tweaker Admin JavaScript
 * Shared utilities for admin interface
 */

(function($) {
    'use strict';

    const TweakerAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.initToggles();
            this.initFormValidation();
        },

        /**
         * Initialize toggle switches
         */
        initToggles: function() {
            $('.nt-toggle input[type="checkbox"]').on('change', function() {
                const $toggle = $(this);
                const value = $toggle.is(':checked');
                
                // Trigger custom event for modules to hook into
                $(document).trigger('nt-toggle-changed', {
                    element: this,
                    value: value
                });
            });
        },

        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            $('.nt-form').on('submit', function(e) {
                const $form = $(this);
                const requiredFields = $form.find('[required]');
                let isValid = true;

                requiredFields.each(function() {
                    const $field = $(this);
                    if (!$field.val()) {
                        $field.addClass('error');
                        isValid = false;
                    } else {
                        $field.removeClass('error');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    TweakerAdmin.showNotice('Please fill in all required fields.', 'error');
                }
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'success') {
            const $notice = $('<div>', {
                class: 'nt-notice nt-notice-' + type,
                text: message
            });

            $('.wrap h1').after($notice);

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Confirm action
         */
        confirm: function(message, callback) {
            if (window.confirm(message)) {
                callback();
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        TweakerAdmin.init();
    });

    // Make globally available
    window.TweakerAdmin = TweakerAdmin;

})(jQuery);
