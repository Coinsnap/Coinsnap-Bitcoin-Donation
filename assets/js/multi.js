// js/multi.js — Per-form instance initialization via data-* attributes
jQuery(document).ready(function ($) {

    document.querySelectorAll('.coinsnap-donation-form-instance[data-form-type="multi_amount"]').forEach(function (container) {
        var formId = container.dataset.formId;
        var isWide = container.dataset.layout === 'WIDE';
        var config = {
            currency:       container.dataset.currency,
            defaultAmount:  container.dataset.defaultAmount,
            defaultMessage: container.dataset.defaultMessage,
            redirectUrl:    container.dataset.redirectUrl,
            snap1Amount:    container.dataset.snap1,
            snap2Amount:    container.dataset.snap2,
            snap3Amount:    container.dataset.snap3
        };

        // Narrow: IDs like coinsnap-bitcoin-donation-amount-multi-{formId}
        // Wide:   IDs like coinsnap-bitcoin-donation-amount-multi-wide-{formId}
        var widePart = isWide ? '-wide' : '';
        var idSuffix = '-multi' + widePart + '-' + formId;

        // ---- helpers ----

        var updateSecondaryMultiCurrency = function (primaryId, secondaryId, originalAmount) {
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

        updateSecondaryMultiCurrency(primaryFieldName, secondaryFieldName, config.defaultAmount);

        // Update snap button secondary amounts
        for (var i = 1; i <= 3; i++) {
            var snapPrimaryId   = 'coinsnap-bitcoin-donation-pay-multi-snap' + i + '-primary' + widePart + '-' + formId;
            var snapSecondaryId = 'coinsnap-bitcoin-donation-pay-multi-snap' + i + '-secondary' + widePart + '-' + formId;
            updateSecondaryMultiCurrency(snapPrimaryId, snapSecondaryId, config['snap' + i + 'Amount']);
        }

        var amountInput = document.getElementById(primaryFieldName);
        if (amountInput) {
            unformatNumericInput(amountInput);
            formatNumericInput(amountInput);
        }

        // ---- popup listener ----
        // Modal template uses suffix "-{formId}".
        addDonationPopupListener('coinsnap-bitcoin-donation-', '-' + formId, 'Multi Amount Donation', config.redirectUrl);

        // ---- amount input handler ----

        var handleMultiAmountInput = function () {
            var pField = 'coinsnap-bitcoin-donation-amount' + idSuffix;
            var sField = 'coinsnap-bitcoin-donation-satoshi' + idSuffix;
            var cField = 'coinsnap-bitcoin-donation-swap' + idSuffix;
            var selCur = document.getElementById(cField).value;
            var secCur = (selCur === 'SATS') ? 'EUR' : 'SATS';

            var amountValue = document.getElementById(pField).value.replace(/[^\d.,]/g, '');
            var decimalSeparator = getThousandSeparator() === '.' ? ',' : '.';

            if (amountValue[0] === '0' && amountValue[1] !== decimalSeparator && amountValue.length > 1) {
                amountValue = amountValue.substring(1);
            }
            if (amountValue.trim() !== '') {
                document.getElementById(pField).value = amountValue;
                updateSecondaryMultiCurrency(pField, sField, amountValue);
            } else {
                document.getElementById(pField).value = '';
                document.getElementById(sField).textContent = 0 + ' ' + secCur;
            }
        };

        // ---- bind amount input ----

        $('#' + primaryFieldName).on('input', handleMultiAmountInput);
        NumericInput(primaryFieldName);

        // ---- snap button click handlers ----

        var snapIds = ['snap1', 'snap2', 'snap3'];
        snapIds.forEach(function (snapId) {
            var payButtonId = 'coinsnap-bitcoin-donation-pay-multi-' + snapId + widePart + '-' + formId;
            var primaryId   = 'coinsnap-bitcoin-donation-pay-multi-' + snapId + '-primary' + widePart + '-' + formId;

            $('#' + payButtonId).on('click', function () {
                var amountField = $('#' + primaryFieldName);
                var amount = cleanDonationAmount(document.getElementById(primaryId).textContent);
                amountField.val(amount);
                amountField.trigger('input');
            });
        });

        // ---- currency change handler ----

        var handleMultiCurrencyChange = function () {
            var pField = 'coinsnap-bitcoin-donation-amount' + idSuffix;
            var sField = 'coinsnap-bitcoin-donation-satoshi' + idSuffix;
            var cField = 'coinsnap-bitcoin-donation-swap' + idSuffix;
            var selCur = document.getElementById(cField).value;

            var selectedCurrencyRate = (selCur === 'SATS')
                ? 1 / $('#' + cField + ' option[value="EUR"]').attr('data-rate')
                : $('#' + cField + ' option:selected').attr('data-rate');

            var amountField = $('#' + pField);
            var primaryAmount = (selCur === 'SATS')
                ? (parseFloat(amountField.val()) / selectedCurrencyRate).toFixed(0)
                : parseFloat(amountField.val());

            var amountValue = primaryAmount || 0;
            amountField.val(amountValue);

            var labelId = 'coinsnap-bitcoin-donation-currency-label' + idSuffix;
            var label   = document.getElementById(labelId);
            if (label) label.textContent = selCur;

            updateSecondaryMultiCurrency(pField, sField, amountValue);

            // Update snap buttons
            var snaps = ['snap1', 'snap2', 'snap3'];
            snaps.forEach(function (snap) {
                var snapPrimaryId   = 'coinsnap-bitcoin-donation-pay-multi-' + snap + '-primary' + widePart + '-' + formId;
                var snapSecondaryId = 'coinsnap-bitcoin-donation-pay-multi-' + snap + '-secondary' + widePart + '-' + formId;
                var snapPrimaryAmount = (selCur === 'SATS')
                    ? (config[snap + 'Amount'] / selectedCurrencyRate).toFixed(0)
                    : config[snap + 'Amount'];
                document.getElementById(snapPrimaryId).textContent = snapPrimaryAmount + ' ' + selCur;
                updateSecondaryMultiCurrency(snapPrimaryId, snapSecondaryId, snapPrimaryAmount);
            });
        };

        // ---- bind currency change ----

        $('#' + currencyFieldName).on('change', handleMultiCurrencyChange);
    });

});
