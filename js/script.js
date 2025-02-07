// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var lastInputCurency = donationData.currency

    const setDefaults = (amountField, satoshiFieldName, messageFieldName) => {
        amountField.val(donationData.defaultAmount);
        updateValueField(
            donationData.defaultAmount,
            satoshiFieldName,
            '/',
            exchangeRates
        )
        const messageField = $(messageFieldName);
        messageField.val(donationData.defaultMessage);
    }

    const simpleDonation = document.getElementById('bitcoin-donation-amount')
    const wideDonation = document.getElementById('bitcoin-donation-amount-wide')

    if (simpleDonation || wideDonation) {

        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            if (simpleDonation) {
                setDefaults($('#bitcoin-donation-amount'), 'bitcoin-donation-satoshi', '#bitcoin-donation-message')
            }
            if (wideDonation) {
                setDefaults($('#bitcoin-donation-amount-wide'), 'bitcoin-donation-satoshi-wide', '#bitcoin-donation-message-wide')

            }
        });

        // Event listeners
        const fieldUpdateListener = (field1, field2, operator, currency) => {
            const amount = document.getElementById(field1).value
            lastInputCurency = currency
            updateValueField(amount, field2, operator, exchangeRates)
        }

        $('#bitcoin-donation-pay-wide').on('click', () =>
            handleButtonClick(
                'bitcoin-donation-pay-wide',
                'bitcoin-donation-email-wide',
                'bitcoin-donation-amount-wide',
                'bitcoin-donation-satoshi-wide',
                'bitcoin-donation-message-wide',
                lastInputCurency
            ));

        $('#bitcoin-donation-pay').on('click', () =>
            handleButtonClick(
                'bitcoin-donation-pay',
                'bitcoin-donation-email',
                'bitcoin-donation-amount',
                'bitcoin-donation-satoshi',
                'bitcoin-donation-message',
                lastInputCurency
            ));

        $('#bitcoin-donation-amount').on('input', () => fieldUpdateListener('bitcoin-donation-amount', 'bitcoin-donation-satoshi', '/', donationData.currency));
        $('#bitcoin-donation-satoshi').on('input', () => fieldUpdateListener('bitcoin-donation-satoshi', 'bitcoin-donation-amount', '*', 'SATS'));
        $('#bitcoin-donation-amount-wide').on('input', () => fieldUpdateListener('bitcoin-donation-amount-wide', 'bitcoin-donation-satoshi-wide', '/', donationData.currency));
        $('#bitcoin-donation-satoshi-wide').on('input', () => fieldUpdateListener('bitcoin-donation-satoshi-wide', 'bitcoin-donation-amount-wide', '*', 'SATS'));
    }
});
