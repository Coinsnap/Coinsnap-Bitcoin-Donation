// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var lastInputCurency = shoutoutsData.currency // Used to detrmine if invoice should be created in by fiat or crypto
    const minAmount = shoutoutsData.minimumShoutoutAmount
    const premiumAmount = shoutoutsData.premiumShoutoutAmount
    const setDefaults = () => {

        const amountField = $('#bitcoin-donation-shoutout-amount');
        amountField.val(shoutoutsData.defaultShoutoutAmount);
        updateValueField(
            shoutoutsData.defaultShoutoutAmount,
            'bitcoin-donation-shoutout-satoshi',
            '/',
            exchangeRates
        )
        const messageField = $('#bitcoin-donation-shoutout-message');
        messageField.val(shoutoutsData.defaultShoutoutMessage);

    }
    if (document.getElementById('bitcoin-donation-shoutout-amount')) {
        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            setDefaults()
        });


        // Event listeners
        $('#bitcoin-donation-shout').on('click', function () {
            const emailField = $('bitcoin-donation-shoutout-email')
            if (emailField.val()) {
                event.preventDefault();
                return
            }
            const amountField = $('#bitcoin-donation-shoutout-amount');
            const satoshiField = $('#bitcoin-donation-shoutout-satoshi');
            const messageField = $('#bitcoin-donation-shoutout-message');
            const nameField = $('#bitcoin-donation-shoutout-name');
            const name = nameField.val() || "Anonymous"
            const satsAmount = parseFloat(satoshiField.val())
            const message = messageField.val()
            const amount = lastInputCurency == 'SATS' ? satsAmount : parseFloat(amountField.val());
            if (amount) {
                createInvoice(amount, message, lastInputCurency, name);
            }
        });

        $('#bitcoin-donation-shoutout-amount').on('input', function () {
            const amount = parseFloat($(this).val());
            lastInputCurency = shoutoutsData.currency
            updateValueField(
                amount,
                'bitcoin-donation-shoutout-satoshi',
                '/',
                exchangeRates
            )
        });

        $('#bitcoin-donation-shoutout-satoshi').on('input', function () {
            const satoshi = parseFloat($(this).val());
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
                exchangeRates
            )

        });
    }

});

