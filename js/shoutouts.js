// js/shoutouts.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var lastInputCurency = shoutoutsData.currency
    const minAmount = shoutoutsData.minimumShoutoutAmount
    const premiumAmount = shoutoutsData.premiumShoutoutAmount

    const setDefaults = () => {
        const amountField = $('#bitcoin-donation-shoutout-amount');
        amountField.val(shoutoutsData.defaultShoutoutAmount);
        updateValueField(
            shoutoutsData.defaultShoutoutAmount,
            'bitcoin-donation-shoutout-satoshi',
            '/',
            exchangeRates,
            shoutoutsData.currency
        )
        const messageField = $('#bitcoin-donation-shoutout-message');
        messageField.val(shoutoutsData.defaultShoutoutMessage);
    }

    if (document.getElementById('bitcoin-donation-shoutout-amount')) {
        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            setDefaults()
        });

        $('#bitcoin-donation-shout').on('click', () => {
            const nameField = $('#bitcoin-donation-shoutout-name');
            const name = nameField.val() || "Anonymous"

            handleButtonClick(
                'bitcoin-donation-shout',
                'bitcoin-donation-shoutout-email',
                'bitcoin-donation-shoutout-amount',
                'bitcoin-donation-shoutout-satoshi',
                'bitcoin-donation-shoutout-message',
                lastInputCurency,
                name
            )
        });

        const updateShoutoutInfo = (fieldName) => {
            const field = document.getElementById(fieldName);
            const amount = cleanAmount(field.value);

            const shoutButton = document.getElementById('bitcoin-donation-shout');
            const helpMinimum = document.getElementById('bitcoin-donation-shoutout-help-minimum');
            const helpPremium = document.getElementById('bitcoin-donation-shoutout-help-premium');
            const helpInfo = document.getElementById('bitcoin-donation-shoutout-help-info');

            if (amount < minAmount) {
                field.style.color = '#e55e65';
                shoutButton.disabled = true;
                helpMinimum.style.display = 'block';
                helpPremium.style.display = 'none';
                helpInfo.style.display = 'none';
            } else if (amount >= premiumAmount) {
                field.style.color = '#f7931a';
                shoutButton.disabled = false;
                helpMinimum.style.display = 'none';
                helpPremium.style.display = 'block';
                helpInfo.style.display = 'none';
            } else {
                field.style.color = '';
                shoutButton.disabled = false;
                helpMinimum.style.display = 'none';
                helpPremium.style.display = 'none';
                helpInfo.style.display = 'block';
            }
        };

        $('#bitcoin-donation-shoutout-amount').on('input', function () {
            const amount = cleanAmount($(this).val());
            lastInputCurency = shoutoutsData.currency
            updateValueField(
                amount,
                'bitcoin-donation-shoutout-satoshi',
                '/',
                exchangeRates,
                shoutoutsData.currency
            )
            updateShoutoutInfo('bitcoin-donation-shoutout-satoshi')
        });
        NumericInput('bitcoin-donation-shoutout-amount')

        $('#bitcoin-donation-shoutout-satoshi').on('input', function () {
            const satoshi = cleanAmount($(this).val());
            lastInputCurency = 'SATS'
            updateValueField(
                satoshi,
                'bitcoin-donation-shoutout-amount',
                '*',
                exchangeRates,
                shoutoutsData.currency
            )
            updateShoutoutInfo('bitcoin-donation-shoutout-satoshi')
        });
        NumericInput('bitcoin-donation-shoutout-satoshi')

    }

});
