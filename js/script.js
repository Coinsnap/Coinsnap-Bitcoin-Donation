// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var lastInputCurency = donationData.currency // Used to detrmine if invoice should be created in by fiat or crypto

    const setDefaults = () => {
        const amountField = $('#bitcoin-donation-amount');
        amountField.val(donationData.defaultAmount);
        updateValueField(
            donationData.defaultAmount,
            'bitcoin-donation-satoshi',
            '/',
            exchangeRates
        )
        const messageField = $('#bitcoin-donation-message');
        messageField.val(donationData.defaultMessage);
    }
    if (document.getElementById('bitcoin-donation-amount')) {

        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            setDefaults()
        });


        // Event listeners
        $('#bitcoin-donation-pay').on('click', function () {
            $(this).prop('disabled', true);
            const emailField = $('bitcoin-donation-email')
            if(emailField.val()){
                event.preventDefault();
                return
            }
            const amountField = $('#bitcoin-donation-amount');
            const satoshiField = $('#bitcoin-donation-satoshi');
            const messageField = $('#bitcoin-donation-message');
            const satsAmount = parseFloat(satoshiField.val())
            const message = messageField.val()
            const amount = lastInputCurency == 'SATS' ? satsAmount : parseFloat(amountField.val());
            if (amount) {
                createInvoice(amount, message, lastInputCurency);
            }
        });

        $('#bitcoin-donation-amount').on('input', function () {
            const amount = parseFloat($(this).val());
            lastInputCurency = donationData.currency
            updateValueField(
                amount,
                'bitcoin-donation-satoshi',
                '/',
                exchangeRates
            )
        });

        $('#bitcoin-donation-satoshi').on('input', function () {
            const satoshi = parseFloat($(this).val());
            lastInputCurency = 'SATS'
            updateValueField(
                satoshi,
                'bitcoin-donation-amount',
                '*',
                exchangeRates
            )
        });
    }
});

