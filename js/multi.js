// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var multiPrimaryCurrency = multiData.multiPrimary == 'SATS' ? 'sats' : multiData.multiFiat
    var multiSecondaryCurrency = multiData.multiPrimary == 'SATS' ? multiData.multiFiat : 'sats'

    const multiDonation = document.getElementById('bitcoin-donation-amount-multi')
    const wideMultiDonation = document.getElementById('bitcoin-donation-amount-multi-wide')

    if (multiDonation || wideMultiDonation) {

        const multiDefaults = (wide) => {
            const widePart = wide ? '-wide' : ''
            const satoshiFieldName = `bitcoin-donation-satoshi-multi${widePart}`
            document.getElementById(`bitcoin-donation-swap-multi${widePart}`).value = multiPrimaryCurrency
            const operation = multiPrimaryCurrency == 'sats' ? '*' : '/';
            const currency = multiPrimaryCurrency == 'sats' ? multiSecondaryCurrency : multiPrimaryCurrency
            const amountField = document.getElementById(`bitcoin-donation-amount-multi${widePart}`);
            amountField.value = multiData.defaultMultiAmount
            updateValueField(
                multiData.defaultMultiAmount,
                satoshiFieldName,
                operation,
                exchangeRates,
                currency,
                true, // mutli needed?
            )
            const messageField = document.getElementById(`bitcoin-donation-message-multi${widePart}`);
            messageField.value = multiData.defaultMultiMessage;
            const secondaryField = document.getElementById(satoshiFieldName)
            secondaryField.textContent = "≈ " + secondaryField.textContent + " " + multiSecondaryCurrency
            amountField.value += " " + multiPrimaryCurrency
            for (let i = 1; i <= 3; i++) {
                updateSecondaryCurrency(
                    `bitcoin-donation-pay-multi-snap${i}-primary${widePart}`,
                    `bitcoin-donation-pay-multi-snap${i}-secondary${widePart}`,
                    multiData[`snap${i}Amount`]
                )
            }
        }

        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            if (multiDonation) {
                multiDefaults(false)
                addPopupListener('bitcoin-donation-', '-multi', 'Multi Amount Donation', exchangeRates)
            }
            if (wideMultiDonation) {
                multiDefaults(true)
                addPopupListener('bitcoin-donation-', '-multi-wide', 'Multi Amount Donation', exchangeRates)
            }
        });

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

        const handleAmountInput = (wide) => {
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

        // Update secondary values
        $('#bitcoin-donation-amount-multi').on('input', () => { handleAmountInput(false) });
        $('#bitcoin-donation-amount-multi-wide').on('input', () => { handleAmountInput(true) });

        // Handle thousands separators
        NumericInput('bitcoin-donation-amount-multi')
        NumericInput('bitcoin-donation-amount-multi-wide')

        // Limit cursor movement
        $('#bitcoin-donation-amount-multi').on('click keydown', (e) => { limitCursorMovement(e, multiPrimaryCurrency); });
        $('#bitcoin-donation-amount-multi-wide').on('click keydown', (e) => { limitCursorMovement(e, multiPrimaryCurrency); });

        // Update snap buttons
        const snapIds = ['snap1', 'snap2', 'snap3'];
        const variants = ['', '-wide'];
        snapIds.forEach(snapId => {
            variants.forEach(variant => {
                const suffix = variant ? `${snapId}${variant}` : snapId;
                const payButtonId = `bitcoin-donation-pay-multi-${suffix}`;
                const primaryId = `bitcoin-donation-pay-multi-${snapId}-primary${variant}`;

                $(`#${payButtonId}`).on('click', () => {
                    const amountField = $(`#bitcoin-donation-amount-multi${variant}`);
                    const amount = cleanAmount(document.getElementById(primaryId).textContent)
                    amountField.val(`${amount} ${multiPrimaryCurrency}`);
                    amountField.trigger('input');
                });
            });
        });

        const handleMultiChangeCurrency = (wide) => {
            const widePart = wide ? '-wide' : ''
            const newCurrency = $(`#bitcoin-donation-swap-multi${widePart}`).val();
            multiPrimaryCurrency = newCurrency;
            multiSecondaryCurrency = (newCurrency === 'sats') ? multiData.multiFiat : 'sats';

            const amountField = $(`#bitcoin-donation-amount-multi${widePart}`);
            const amountValue = cleanAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${multiPrimaryCurrency}`);

            updateSecondaryCurrency(`bitcoin-donation-amount-multi${widePart}`, `bitcoin-donation-satoshi-multi${widePart}`);
            const snaps = ['snap1', 'snap2', 'snap3'];

            snaps.forEach(snap => {
                const primaryId = `bitcoin-donation-pay-multi-${snap}-primary${widePart}`;
                const secondaryId = `bitcoin-donation-pay-multi-${snap}-secondary${widePart}`;
                if (newCurrency !== 'sats') {
                    updateSecondaryCurrency(primaryId, secondaryId, multiData[`${snap}Amount`]);
                } else {
                    swapSnapCurrency(primaryId, secondaryId);
                }
            });

        }

        // Handle currency change
        $('#bitcoin-donation-swap-multi').on('change', () => { handleMultiChangeCurrency(false); });
        $('#bitcoin-donation-swap-multi-wide').on('change', () => { handleMultiChangeCurrency(true); });

    }

});