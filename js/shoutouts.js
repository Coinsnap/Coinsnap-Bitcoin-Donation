// js/script.js
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
        });
        NumericInput('bitcoin-donation-shoutout-amount')

        $('#bitcoin-donation-shoutout-satoshi').on('input', function () {
            const satoshi = cleanAmount($(this).val());
            if (satoshi < minAmount) {
                $(this).css('color', '#e55e65');
                $('#bitcoin-donation-shout').prop('disabled', true);
                $('#bitcoin-donation-shoutout-help-minimum').css('display', 'block');
                $('#bitcoin-donation-shoutout-help-premium').css('display', 'none');
                $('#bitcoin-donation-shoutout-help-info').css('display', 'none');
            } else if (satoshi >= premiumAmount) {
                $(this).css('color', '#f7931a');
                $('#bitcoin-donation-shout').prop('disabled', false);
                $('#bitcoin-donation-shoutout-help-minimum').css('display', 'none');
                $('#bitcoin-donation-shoutout-help-premium').css('display', 'block');
                $('#bitcoin-donation-shoutout-help-info').css('display', 'none');
            } else {
                $(this).css('color', '');
                $('#bitcoin-donation-shout').prop('disabled', false);
                $('#bitcoin-donation-shoutout-help-minimum').css('display', 'none');
                $('#bitcoin-donation-shoutout-help-premium').css('display', 'none');
                $('#bitcoin-donation-shoutout-help-info').css('display', 'block');
            }

            lastInputCurency = 'SATS'
            updateValueField(
                satoshi,
                'bitcoin-donation-shoutout-amount',
                '*',
                exchangeRates,
                shoutoutsData.currency
            )

        });
        NumericInput('bitcoin-donation-shoutout-satoshi')

    }


});
