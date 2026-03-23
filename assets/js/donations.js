// js/donations.js — Per-form instance initialization via data-* attributes
jQuery(document).ready(function ($) {

    document.querySelectorAll('.coinsnap-donation-form-instance[data-form-type="simple_donation"]').forEach(function (container) {
        var formId = container.dataset.formId;
        var isWide = container.dataset.layout === 'WIDE';
        var config = {
            currency:       container.dataset.currency,
            defaultAmount:  container.dataset.defaultAmount,
            defaultMessage: container.dataset.defaultMessage,
            redirectUrl:    container.dataset.redirectUrl
        };

        // Wide templates embed "-wide" before the form ID in element IDs.
        var widePart = isWide ? '-wide' : '';
        // Suffix used for all element-ID lookups inside this form.
        var idSuffix = widePart + '-' + formId;

        // ---- helpers ----

        var updateSecondaryDonationCurrency = function (primaryId, secondaryId, originalAmount) {
            var currencyFieldName = 'coinsnap-bitcoin-donation-swap' + idSuffix;
            var currency = document.getElementById(currencyFieldName).value;

            var currencyRate = (currency === 'SATS')
                ? 1 / $('#' + currencyFieldName + ' option[value="EUR"]').attr('data-rate')
                : $('#' + currencyFieldName + ' option:selected').attr('data-rate');

            var converted = (currency === 'SATS')
                ? (originalAmount * currencyRate).toFixed(2)
                : (originalAmount * currencyRate).toFixed(0);
            var secCur = (currency === 'SATS') ? 'EUR' : 'SATS';

            var withSeparators = addNumSeparators(converted);
            document.getElementById(secondaryId).textContent = '\u2248 ' + withSeparators + ' ' + secCur;
            $('#' + secondaryId).attr('data-value', converted);
        };

        // ---- set defaults ----

        var currencyFieldName   = 'coinsnap-bitcoin-donation-swap' + idSuffix;
        var primaryFieldName    = 'coinsnap-bitcoin-donation-amount' + idSuffix;
        var secondaryFieldName  = 'coinsnap-bitcoin-donation-satoshi' + idSuffix;

        var messageField = document.getElementById('coinsnap-bitcoin-donation-message' + idSuffix);
        if (messageField) messageField.value = config.defaultMessage;

        var swapEl = document.getElementById(currencyFieldName);
        if (swapEl) swapEl.value = config.currency;

        var amountEl = document.getElementById(primaryFieldName);
        if (amountEl) amountEl.value = config.defaultAmount;

        updateSecondaryDonationCurrency(primaryFieldName, secondaryFieldName, config.defaultAmount);

        // ---- popup listener ----
        // idSuffix includes -wide- for wide layout, matching both form elements and modal template
        addDonationPopupListener('coinsnap-bitcoin-donation-', idSuffix, 'Bitcoin Donation', config.redirectUrl);

        // ---- amount input handler ----

        var handleDonationAmountInput = function () {
            var pField   = 'coinsnap-bitcoin-donation-amount' + idSuffix;
            var sField   = 'coinsnap-bitcoin-donation-satoshi' + idSuffix;
            var cField   = 'coinsnap-bitcoin-donation-swap' + idSuffix;
            var selCur   = document.getElementById(cField).value;
            var secCur   = (selCur === 'SATS') ? 'EUR' : 'SATS';

            var amountValue = document.getElementById(pField).value.replace(/[^\d.,]/g, '');
            var decimalSeparator = getThousandSeparator() === '.' ? ',' : '.';

            if (amountValue[0] === '0' && amountValue[1] !== decimalSeparator && amountValue.length > 1) {
                amountValue = amountValue.substring(1);
            }
            if (amountValue.trim() !== '') {
                document.getElementById(pField).value = amountValue;
                updateSecondaryDonationCurrency(pField, sField, amountValue);
            } else {
                document.getElementById(pField).value = '';
                document.getElementById(sField).textContent = 0 + ' ' + secCur;
            }
        };

        // ---- currency change handler ----

        var handleDonationCurrencyChange = function () {
            var pField = 'coinsnap-bitcoin-donation-amount' + idSuffix;
            var sField = 'coinsnap-bitcoin-donation-satoshi' + idSuffix;
            var cField = 'coinsnap-bitcoin-donation-swap' + idSuffix;
            var selCur = document.getElementById(cField).value;

            var amountField = $('#' + pField);
            var amountValue = cleanDonationAmount(amountField.val()) || 0;
            amountField.val(amountValue);

            var labelId = 'coinsnap-bitcoin-donation-currency-label' + idSuffix;
            var label   = document.getElementById(labelId);
            if (label) label.textContent = selCur;

            updateSecondaryDonationCurrency(pField, sField, amountValue);
        };

        // ---- bind events ----

        $('#' + primaryFieldName).on('input', handleDonationAmountInput);
        NumericInput(primaryFieldName);
        $('#' + currencyFieldName).on('change', handleDonationCurrencyChange);
    });

});
