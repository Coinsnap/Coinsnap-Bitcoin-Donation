(function ($) {
    $(document).ready(function () {

        // Tab navigation is handled by coinsnap-core admin.js (csc-tab / csc-tab-content)

        // --- Forms page toggle logic ---

        function togglePublicDonorFields(section, force) {
            section = section.replace(/-/g, '_');
            const element = document.getElementById(section + '_public_donors');
            var isChecked = element?.checked;
            if (force !== undefined) isChecked = force;
            $('.public-donor-field.' + section.replace(/_/g, '-')).closest('tr').toggle(isChecked);
        }

        function toggleShoutoutFields(section) {
            var isChecked = $('#' + section + '_donation_active').is(':checked');
            section = section.replace(/_/g, '-');
            if (section === 'shoutout') {
                $('#' + section + '-donation table tr').not(':first').toggle(isChecked);
            } else {
                $('#' + section + '-donation table tbody tr').not(':first').toggle(isChecked);
            }
            if (!isChecked) {
                togglePublicDonorFields(section, false);
            } else {
                togglePublicDonorFields(section);
            }
        }

        function toggleShortcode(value, section) {
            const regular = document.getElementById(`shortcode_${section}`);
            const wide = document.getElementById(`shortcode_${section}_wide`);
            if (!regular || !wide) return;
            if (value === 'WIDE') {
                regular.style.display = 'none';
                wide.classList.remove('hiddenRow');
                wide.style.display = 'table-row!important';
            } else {
                regular.style.display = 'table-row';
                wide.classList.add('hiddenRow');
            }
        }

        if ($('#form_type')?.val) {
            toggleShortcode($('#form_type').val(), 'coinsnap_bitcoin_donation');
        }
        if ($('#multi_amount_form_type')?.val) {
            toggleShortcode($('#multi_amount_form_type').val(), 'multi_amount_donation');
        }

        togglePublicDonorFields('simple_donation');
        togglePublicDonorFields('shoutout');
        togglePublicDonorFields('multi_amount');
        toggleShoutoutFields('shoutout');
        toggleShoutoutFields('multi_amount');

        $('#simple_donation_public_donors').change(function () { togglePublicDonorFields('simple_donation'); });
        $('#shoutout_public_donors').change(function () { togglePublicDonorFields('shoutout'); });
        $('#multi_amount_public_donors').change(function () { togglePublicDonorFields('multi_amount'); });
        $('#shoutout_donation_active').change(function () { toggleShoutoutFields('shoutout'); });
        $('#multi_amount_donation_active').change(function () { toggleShoutoutFields('multi_amount'); });
        $('#form_type').change(function () { toggleShortcode($(this).val(), 'coinsnap_bitcoin_donation'); });
        $('#multi_amount_form_type').change(function () { toggleShortcode($(this).val(), 'multi_amount_donation'); });

        // Save toast cleanup is handled by coinsnap-core admin.js

        // --- Shortcode click-to-copy ---
        $(document).on('click', '.csc-shortcode-copy', function (e) {
            e.preventDefault();
            var btn = $(this);
            var shortcode = btn.data('shortcode');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shortcode).then(function () {
                    showCopied(btn);
                });
            } else {
                var temp = $('<textarea>').val(shortcode).appendTo('body').select();
                document.execCommand('copy');
                temp.remove();
                showCopied(btn);
            }
        });

        function showCopied(btn) {
            btn.addClass('is-copied');
            setTimeout(function () {
                btn.removeClass('is-copied');
            }, 1500);
        }
    });
})(jQuery);
