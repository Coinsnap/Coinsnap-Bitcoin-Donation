(function ($) {
    'use strict';

    $(function () {
        var $typeInput = $('#_coinsnap_donation_form_form_type');
        var $cards = $('.donation-form-type-card');
        var $conditionals = $('.donation-form-conditional');
        var $publicDonors = $('#_coinsnap_donation_form_public_donors');
        var $donorFields = $('.donation-form-donor-fields');

        function isExistingPost() {
            // On post.php we are editing an existing post
            return window.location.href.indexOf('post.php') !== -1;
        }

        function toggleConditionalFields(type) {
            $conditionals.each(function () {
                var showFor = $(this).data('show-for');
                if (showFor && showFor.indexOf(type) !== -1) {
                    $(this).addClass('visible');
                } else {
                    $(this).removeClass('visible');
                }
            });

            // Toggle shoutout list shortcode visibility
            var $shoutoutShortcode = $('.donation-form-shortcode-shoutout-list');
            if ($shoutoutShortcode.length) {
                if (type === 'shoutout') {
                    $shoutoutShortcode.show();
                } else {
                    $shoutoutShortcode.hide();
                }
            }
        }

        function toggleDonorFields() {
            if ($publicDonors.is(':checked')) {
                $donorFields.addClass('visible');
            } else {
                $donorFields.removeClass('visible');
            }
        }

        // Card click handler
        $cards.on('click', function () {
            var $card = $(this);
            var newType = $card.data('type');
            var currentType = $typeInput.val();

            if (newType === currentType) {
                return;
            }

            if (isExistingPost() && currentType) {
                if (!confirm('Switching form type will reset type-specific fields. Are you sure?')) {
                    return;
                }
            }

            $typeInput.val(newType);
            $cards.removeClass('active');
            $card.addClass('active');
            toggleConditionalFields(newType);
        });

        // Public donors checkbox toggle
        $publicDonors.on('change', function () {
            toggleDonorFields();
        });

        // Copy shortcode handler
        $(document).on('click', '.csc-shortcode-copy', function () {
            var $btn = $(this);
            var shortcode = $btn.data('shortcode');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode);
            } else {
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(shortcode).select();
                document.execCommand('copy');
                $temp.remove();
            }

            $btn.addClass('is-copied');
            setTimeout(function () {
                $btn.removeClass('is-copied');
            }, 2000);
        });

        // Initialize on page load
        var currentType = $typeInput.val() || 'simple_donation';
        toggleConditionalFields(currentType);
        toggleDonorFields();
    });
})(jQuery);
