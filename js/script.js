// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var lastInputCurency = donationData.currency
    var multiPrimaryCurrency = donationData.multiPrimary == 'SATS' ? 'sats' : donationData.multiFiat
    var multiSecondaryCurrency = donationData.multiPrimary == 'SATS' ? donationData.multiFiat : 'sats'

    const setDefaults = (amountField, amount, satoshiFieldName, messageFieldName, operator, currency, jQuery = true) => {
        if (jQuery) {
            amountField.val(amount);
        } else {
            amountField.value = amount
        }
        if (satoshiFieldName) {
            updateValueField(
                amount,
                satoshiFieldName,
                operator,
                exchangeRates,
                currency
            )
        }
        const messageField = $(messageFieldName);
        messageField.val(donationData.defaultMessage);
    }

    const multiDefaults = (wide) => {
        const widePart = wide ? '-wide' : ''
        const satoshiFieldName = `bitcoin-donation-satoshi-multi${widePart}`
        document.getElementById(`bitcoin-donation-multi-swap${widePart}`).value = multiPrimaryCurrency
        const operation = multiPrimaryCurrency == 'sats' ? '*' : '/';
        const currency = multiPrimaryCurrency == 'sats' ? multiSecondaryCurrency : multiPrimaryCurrency
        const amountField = document.getElementById(`bitcoin-donation-amount-multi${widePart}`);
        setDefaults(amountField, donationData.defaultMultiAmount, satoshiFieldName, `#bitcoin-donation-message-multi${widePart}`, operation, currency, false)
        const secondaryField = document.getElementById(satoshiFieldName)
        secondaryField.textContent = "≈ " + secondaryField.textContent + " " + multiSecondaryCurrency
        amountField.value += " " + multiPrimaryCurrency
        for (let i = 1; i <= 3; i++) {
            updateSecondaryCurrency(
                `bitcoin-donation-pay-multi-snap${i}-primary${widePart}`,
                `bitcoin-donation-pay-multi-snap${i}-secondary${widePart}`,
                donationData[`snap${i}Amount`]
            )

        }
        lastInputCurency = donationData.multiPrimary

    }

    const updateSecondaryCurrency = (primaryId, secondaryId, originalAmount) => {
        const currency = multiSecondaryCurrency == 'sats' ? multiPrimaryCurrency : multiSecondaryCurrency
        const currencyRate = exchangeRates[currency];
        const primaryField = document.getElementById(primaryId)
        var amount = cleanAmount(originalAmount)
        if (primaryId.includes("snap")) {
            primaryField.textContent = `${amount} ${multiPrimaryCurrency}`
        } else {
            amount = cleanAmount(primaryField.value)
        }
        const converted = multiPrimaryCurrency == 'sats' ? (amount * currencyRate).toFixed(8) : (amount / currencyRate).toFixed(0)
        const withSeparators = addNumSeparators(converted)
        document.getElementById(secondaryId).textContent = `≈ ${withSeparators} ${multiSecondaryCurrency}`
    }

    const handleMultiInput = (wide) => {
        const widePart = wide ? '-wide' : ''
        const field = document.getElementById(`bitcoin-donation-amount-multi${widePart}`)
        const field2 = document.getElementById(`bitcoin-donation-satoshi-multi${widePart}`)
        let value = field.value.replace(` ${multiPrimaryCurrency}`, '');
        if (value.trim() !== '') {
            field.value = value + ` ${multiPrimaryCurrency}`;
            updateSecondaryCurrency(`bitcoin-donation-amount-multi${widePart}`, `bitcoin-donation-satoshi-multi${widePart}`, value)
        } else {
            field.value = 0;
            field2.textContent = 0 + " " + multiSecondaryCurrency
        }
        // field.setSelectionRange(value.length, value.length);
    }

    const swapSnapCurrency = (primaryId, secondaryId) => {
        const currency = multiSecondaryCurrency == 'sats' ? multiPrimaryCurrency : multiSecondaryCurrency
        const currencyRate = exchangeRates[currency];
        const primaryField = document.getElementById(primaryId)
        const primaryAmount = cleanAmount(primaryField.textContent)
        const secondaryField = document.getElementById(secondaryId)
        const convertedPrimary = (primaryAmount / currencyRate).toFixed(0)
        primaryField.textContent = `${convertedPrimary} ${multiPrimaryCurrency}`
        secondaryField.textContent = `≈ ${primaryAmount} ${multiSecondaryCurrency}`

    }

    const simpleDonation = document.getElementById('bitcoin-donation-amount')
    const wideDonation = document.getElementById('bitcoin-donation-amount-wide')
    const multiDonation = document.getElementById('bitcoin-donation-amount-multi')
    const wideMultiDonation = document.getElementById('bitcoin-donation-amount-multi-wide')


    if (simpleDonation || wideDonation || multiDonation || wideMultiDonation) {

        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            if (simpleDonation) {
                setDefaults($('#bitcoin-donation-amount'), donationData.defaultAmount, 'bitcoin-donation-satoshi', '#bitcoin-donation-message', '/', donationData.currency)
            }
            if (wideDonation) {
                setDefaults($('#bitcoin-donation-amount-wide'), donationData.defaultAmount, 'bitcoin-donation-satoshi-wide', '#bitcoin-donation-message-wide', '/', donationData.currency)
            }
            if (multiDonation) {
                multiDefaults(false)
            }
            if (wideMultiDonation) {
                multiDefaults(true)

            }
        });

        // Event listeners
        const fieldUpdateListener = (field1, field2, operator, currency) => {
            const amount = document.getElementById(field1).value
            lastInputCurency = currency
            updateValueField(amount, field2, operator, exchangeRates, donationData.currency)
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

        $('#bitcoin-donation-pay-multi').on('click', () =>
            handleButtonClickMulti(
                'bitcoin-donation-pay-multi',
                'bitcoin-donation-email-multi',
                'bitcoin-donation-amount-multi',
                'bitcoin-donation-message-multi',
                multiPrimaryCurrency
            ));

        $('#bitcoin-donation-pay-multi-wide').on('click', () =>
            handleButtonClickMulti(
                'bitcoin-donation-pay-multi-wide',
                'bitcoin-donation-email-multi-wide',
                'bitcoin-donation-amount-multi-wide',
                'bitcoin-donation-message-multi-wide',
                multiPrimaryCurrency
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
        NumericInput('bitcoin-donation-amount')
        $('#bitcoin-donation-satoshi').on('input', () => fieldUpdateListener('bitcoin-donation-satoshi', 'bitcoin-donation-amount', '*', 'SATS'));
        NumericInput('bitcoin-donation-satoshi')
        $('#bitcoin-donation-amount-wide').on('input', () => fieldUpdateListener('bitcoin-donation-amount-wide', 'bitcoin-donation-satoshi-wide', '/', donationData.currency));
        NumericInput('bitcoin-donation-amount-wide')
        $('#bitcoin-donation-satoshi-wide').on('input', () => fieldUpdateListener('bitcoin-donation-satoshi-wide', 'bitcoin-donation-amount-wide', '*', 'SATS'));
        NumericInput('bitcoin-donation-satoshi-wide')
        $('#bitcoin-donation-amount-multi').on('input', () => { handleMultiInput(false) });
        NumericInput('bitcoin-donation-amount-multi')
        $('#bitcoin-donation-amount-multi-wide').on('input', () => { handleMultiInput(true) });
        NumericInput('bitcoin-donation-amount-multi-wide')
        $('#bitcoin-donation-amount-multi').on('click keydown', (e) => {
            const field = e.target;
            const position = field.selectionStart;
            const satsOffset = multiPrimaryCurrency === 'sats' ? 5 : 4;
            const satsStart = field.value.length - satsOffset;

            if (field.value.includes(multiPrimaryCurrency)) {
                if (e.type === 'click' && position >= satsStart) {
                    let value = field.value.replace(` ${multiPrimaryCurrency}`, '');
                    field.setSelectionRange(value.length, value.length);
                }

                if (e.type === 'keydown' && (e.key === 'ArrowRight' || e.key === 'End') && position >= satsStart) {
                    e.preventDefault();
                    field.setSelectionRange(satsStart, satsStart);
                }
            }
        });

        $('#bitcoin-donation-amount-multi-wide').on('click keydown', (e) => {
            const field = e.target;
            const position = field.selectionStart;
            const satsOffset = multiPrimaryCurrency === 'sats' ? 5 : 4;
            const satsStart = field.value.length - satsOffset;

            if (field.value.includes(multiPrimaryCurrency)) {
                if (e.type === 'click' && position >= satsStart) {
                    let value = field.value.replace(` ${multiPrimaryCurrency}`, '');
                    field.setSelectionRange(value.length, value.length);
                }

                if (e.type === 'keydown' && (e.key === 'ArrowRight' || e.key === 'End') && position >= satsStart) {
                    e.preventDefault();
                    field.setSelectionRange(satsStart, satsStart);
                }
            }
        });



        const snapIds = ['snap1', 'snap2', 'snap3'];
        const variants = ['', '-wide'];

        snapIds.forEach(snapId => {
            variants.forEach(variant => {
                const suffix = variant ? `${snapId}${variant}` : snapId;
                const payButtonId = `bitcoin-donation-pay-multi-${suffix}`;
                const emailId = `bitcoin-donation-email-multi${variant}`;
                const primaryId = `bitcoin-donation-pay-multi-${snapId}-primary${variant}`;
                const messageId = `bitcoin-donation-message-multi${variant}`;

                $(`#${payButtonId}`).on('click', () => {
                    handleSnapClick(payButtonId, emailId, primaryId, messageId, multiPrimaryCurrency);
                });
            });
        });

        $('#bitcoin-donation-multi-swap').on('change', () => {
            const newCurrency = $('#bitcoin-donation-multi-swap').val();
            multiPrimaryCurrency = newCurrency;
            multiSecondaryCurrency = (newCurrency === 'sats') ? donationData.multiFiat : 'sats';

            const amountField = $('#bitcoin-donation-amount-multi');
            const amountValue = cleanAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${multiPrimaryCurrency}`);

            updateSecondaryCurrency('bitcoin-donation-amount-multi', 'bitcoin-donation-satoshi-multi');

            const snaps = ['snap1', 'snap2', 'snap3'];

            snaps.forEach(snap => {
                const primaryId = `bitcoin-donation-pay-multi-${snap}-primary`;
                const secondaryId = `bitcoin-donation-pay-multi-${snap}-secondary`;

                if (newCurrency !== 'sats') {
                    updateSecondaryCurrency(primaryId, secondaryId, donationData[`${snap}Amount`]);
                } else {
                    swapSnapCurrency(primaryId, secondaryId);
                }
            });

            lastInputCurency = (multiPrimaryCurrency === 'sats') ? 'SATS' : multiPrimaryCurrency;
        });

        $('#bitcoin-donation-multi-swap-wide').on('change', () => {
            const newCurrency = $('#bitcoin-donation-multi-swap-wide').val();
            multiPrimaryCurrency = newCurrency;
            multiSecondaryCurrency = (newCurrency === 'sats') ? donationData.multiFiat : 'sats';

            const amountField = $('#bitcoin-donation-amount-multi-wide');
            const amountValue = cleanAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${multiPrimaryCurrency}`);

            updateSecondaryCurrency('bitcoin-donation-amount-multi-wide', 'bitcoin-donation-satoshi-multi-wide');

            const snaps = ['snap1', 'snap2', 'snap3'];

            snaps.forEach(snap => {
                const primaryId = `bitcoin-donation-pay-multi-${snap}-primary-wide`;
                const secondaryId = `bitcoin-donation-pay-multi-${snap}-secondary-wide`;

                if (newCurrency !== 'sats') {
                    updateSecondaryCurrency(primaryId, secondaryId, donationData[`${snap}Amount`]);
                } else {
                    swapSnapCurrency(primaryId, secondaryId);
                }
            });

            lastInputCurency = (multiPrimaryCurrency === 'sats') ? 'SATS' : multiPrimaryCurrency;
        });


    }

});
