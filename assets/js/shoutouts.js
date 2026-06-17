// js/shoutouts.js — Per-form instance initialization via data-* attributes
jQuery(document).ready(function ($) {

    document.querySelectorAll('.coinsnap-donation-form-instance[data-form-type="shoutout"]').forEach(function (container) {
        var formId = container.dataset.formId;
        var config = {
            currency:       container.dataset.currency,
            defaultAmount:  container.dataset.defaultAmount,
            defaultMessage: container.dataset.defaultMessage,
            redirectUrl:    container.dataset.redirectUrl,
            minimumAmount:  parseFloat(container.dataset.minimumAmount),
            premiumAmount:  parseFloat(container.dataset.premiumAmount)
        };

        // All shoutout element IDs follow: coinsnap-bitcoin-donation-shoutout-{element}-{formId}
        var idSuffix = '-' + formId;
        var minAmount     = config.minimumAmount;
        var premiumAmount = config.premiumAmount;

        // ---- helpers ----

        var updateSecondaryShoutoutCurrency = function (primaryId, secondaryId, originalAmount) {
            var currencyFieldName = 'coinsnap-bitcoin-donation-shoutout-swap' + idSuffix;
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

        var updateShoutoutInfo = function () {
            var fieldName = 'coinsnap-bitcoin-donation-shoutout-amount' + idSuffix;
            var field = document.getElementById(fieldName);
            var value = cleanDonationAmount(field.value);

            var currencyFieldName = 'coinsnap-bitcoin-donation-shoutout-swap' + idSuffix;
            var selectedCurrency = document.getElementById(currencyFieldName).value;
            var currencySatsRate = (selectedCurrency === 'SATS') ? 1 : jQuery('#' + currencyFieldName + ' option:selected').attr('data-rate');
            var amount = (selectedCurrency === 'SATS') ? value : value * currencySatsRate;

            var shoutButton = document.getElementById('coinsnap-bitcoin-donation-shoutout-pay' + idSuffix);
            var helpMinimum = document.getElementById('coinsnap-bitcoin-donation-shoutout-help-minimum' + idSuffix);
            var helpPremium = document.getElementById('coinsnap-bitcoin-donation-shoutout-help-premium' + idSuffix);
            var helpInfo    = document.getElementById('coinsnap-bitcoin-donation-shoutout-help-info' + idSuffix);

            if (amount < minAmount) {
                field.style.color = '#e55e65';
                if (shoutButton) shoutButton.disabled = true;
                if (helpMinimum) helpMinimum.style.display = 'block';
                if (helpPremium) helpPremium.style.display = 'none';
                if (helpInfo)    helpInfo.style.display = 'none';
            } else if (amount >= premiumAmount) {
                field.style.color = '#f7931a';
                if (shoutButton) shoutButton.disabled = false;
                if (helpMinimum) helpMinimum.style.display = 'none';
                if (helpPremium) helpPremium.style.display = 'block';
                if (helpInfo)    helpInfo.style.display = 'none';
            } else {
                field.style.color = '';
                if (shoutButton) shoutButton.disabled = false;
                if (helpMinimum) helpMinimum.style.display = 'none';
                if (helpPremium) helpPremium.style.display = 'none';
                if (helpInfo)    helpInfo.style.display = 'block';
            }
        };

        // ---- set defaults ----

        var currencyFieldName   = 'coinsnap-bitcoin-donation-shoutout-swap' + idSuffix;
        var primaryFieldName    = 'coinsnap-bitcoin-donation-shoutout-amount' + idSuffix;
        var secondaryFieldName  = 'coinsnap-bitcoin-donation-shoutout-satoshi' + idSuffix;

        var messageField = document.getElementById('coinsnap-bitcoin-donation-shoutout-message' + idSuffix);
        if (messageField) messageField.value = config.defaultMessage;

        var swapEl = document.getElementById(currencyFieldName);
        if (swapEl) swapEl.value = config.currency;

        var amountEl = document.getElementById(primaryFieldName);
        if (amountEl) amountEl.value = config.defaultAmount;

        updateSecondaryShoutoutCurrency(primaryFieldName, secondaryFieldName, config.defaultAmount);

        // ---- popup listener ----

        addDonationPopupListener('coinsnap-bitcoin-donation-shoutout-', idSuffix, 'Bitcoin Shoutout', config.redirectUrl);

        // ---- amount input handler ----

        var handleShoutoutsAmountInput = function () {
            var pField = 'coinsnap-bitcoin-donation-shoutout-amount' + idSuffix;
            var sField = 'coinsnap-bitcoin-donation-shoutout-satoshi' + idSuffix;
            var cField = 'coinsnap-bitcoin-donation-shoutout-swap' + idSuffix;
            var selCur = document.getElementById(cField).value;
            var secCur = (selCur === 'SATS') ? 'EUR' : 'SATS';

            var amountValue = document.getElementById(pField).value.replace(/[^\d.,]/g, '');
            var decimalSeparator = getThousandSeparator() === '.' ? ',' : '.';

            if (amountValue[0] === '0' && amountValue[1] !== decimalSeparator && amountValue.length > 1) {
                amountValue = amountValue.substring(1);
            }
            if (amountValue.trim() !== '') {
                document.getElementById(pField).value = amountValue;
                updateSecondaryShoutoutCurrency(pField, sField, amountValue);
            } else {
                document.getElementById(pField).value = '';
                document.getElementById(sField).textContent = 0 + ' ' + secCur;
            }

            updateShoutoutInfo();
        };

        // ---- currency change handler ----

        var handleShoutoutCurrencyChange = function () {
            var pField = 'coinsnap-bitcoin-donation-shoutout-amount' + idSuffix;
            var sField = 'coinsnap-bitcoin-donation-shoutout-satoshi' + idSuffix;
            var cField = 'coinsnap-bitcoin-donation-shoutout-swap' + idSuffix;
            var selCur = document.getElementById(cField).value;

            var amountField = $('#' + pField);
            var amountValue = cleanDonationAmount(amountField.val()) || 0;
            amountField.val(amountValue);

            var label = document.getElementById('coinsnap-bitcoin-donation-shoutout-currency-label' + idSuffix);
            if (label) label.textContent = selCur;

            var currencySatsRate = (selCur === 'SATS') ? 1 : $('#' + cField + ' option:selected').attr('data-rate');

            var displayMinAmount = (selCur === 'SATS' || selCur === 'RUB' || selCur === 'JPY')
                ? (minAmount / currencySatsRate).toFixed(0)
                : ((selCur === 'BTC') ? (minAmount / currencySatsRate).toFixed(8) : (minAmount / currencySatsRate).toFixed(2));

            var displayPremiumAmount = (selCur === 'SATS' || selCur === 'RUB' || selCur === 'JPY')
                ? (premiumAmount / currencySatsRate).toFixed(0)
                : ((selCur === 'BTC') ? (premiumAmount / currencySatsRate).toFixed(7) : (premiumAmount / currencySatsRate).toFixed(2));

            $('#coinsnap-bitcoin-donation-shoutout-help-minimum-amount' + idSuffix).text(displayMinAmount + ' ' + selCur);
            $('#coinsnap-bitcoin-donation-shoutout-help-premium-amount' + idSuffix).text(displayPremiumAmount + ' ' + selCur);

            updateSecondaryShoutoutCurrency(pField, sField, amountValue);
        };

        // ---- bind events ----

        $('#' + primaryFieldName).on('input', handleShoutoutsAmountInput);
        $('#' + currencyFieldName).on('change', handleShoutoutCurrencyChange);
        NumericInput(primaryFieldName);
    });

});
