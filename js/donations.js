// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var selectedCurrency = formData.currency
    var secondaryCurrency = 'sats'
    var selectedCurrencyWide = formData.currency
    var secondaryCurrencyWide = 'sats'

    const simpleDonation = document.getElementById('bitcoin-donation-amount')
    const wideDonation = document.getElementById('bitcoin-donation-amount-wide')

    if (simpleDonation || wideDonation) {

        const setDefaults = (wide) => {
            const widePart = wide ? '-wide' : ''
            const satoshiFieldName = `bitcoin-donation-satoshi${widePart}`
            const selCurrency = wide ? selectedCurrencyWide : selectedCurrency
            const secCurrency = wide ? secondaryCurrencyWide : secondaryCurrency

            document.getElementById(`bitcoin-donation-swap${widePart}`).value = selCurrency
            const operation = selCurrency == 'sats' ? '*' : '/';
            const fiatCurrency = formData.currency
            const amountField = document.getElementById(`bitcoin-donation-amount${widePart}`);

            amountField.value = formData.defaultAmount
            updateValueField(
                formData.defaultAmount,
                satoshiFieldName,
                operation,
                exchangeRates,
                fiatCurrency,
                true // TODO check
            )
            const messageField = document.getElementById(`bitcoin-donation-message${widePart}`);
            messageField.value = formData.defaultMessage;

            const secondaryField = document.getElementById(satoshiFieldName)
            secondaryField.textContent = "≈ " + secondaryField.textContent + " " + secCurrency
            amountField.value += " " + selCurrency

        }

        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            if (simpleDonation) {
                setDefaults(false)
                addPopupListener('bitcoin-donation-', '', 'Bitcoin Donation', exchangeRates, formData.redirectUrl)
            }
            if (wideDonation) {
                setDefaults(true)
                addPopupListener('bitcoin-donation-', '-wide', 'Bitcoin Donation', exchangeRates, formData.redirectUrl)
            }
        });

        const updateSecondaryCurrency = (wide, primaryId, secondaryId) => {
            const selCurrency = wide ? selectedCurrencyWide : selectedCurrency
            const secCurrency = wide ? secondaryCurrencyWide : secondaryCurrency

            const currency = selCurrency == 'sats' ? secCurrency : selCurrency
            const currencyRate = exchangeRates[currency];
            const primaryField = document.getElementById(primaryId)
            amount = cleanAmount(primaryField.value)
            const converted = selCurrency == 'sats' ? (amount * currencyRate).toFixed(8) : (amount / currencyRate).toFixed(0)
            const withSeparators = addNumSeparators(converted)
            document.getElementById(secondaryId).textContent = `≈ ${withSeparators} ${secCurrency}`
        }

        const handleAmountInput = (wide) => {
            const widePart = wide ? '-wide' : ''
            const selCurrency = wide ? selectedCurrencyWide : selectedCurrency
            const secCurrency = wide ? secondaryCurrencyWide : secondaryCurrency
            const field = document.getElementById(`bitcoin-donation-amount${widePart}`)
            const field2 = document.getElementById(`bitcoin-donation-satoshi${widePart}`)
            let value = field.value.replace(` ${selCurrency}`, '');
            if (value.trim() !== '') {
                field.value = value + ` ${selCurrency}`;
                updateSecondaryCurrency(wide, `bitcoin-donation-amount${widePart}`, `bitcoin-donation-satoshi${widePart}`)
            } else {
                field.value = '' + ` ${selCurrency}`;
                field2.textContent = 0 + " " + secCurrency
            }
        }

        const handleChangeCurrency = (wide) => {
            const widePart = wide ? '-wide' : ''
            const newCurrency = $(`#bitcoin-donation-swap${widePart}`).val();
            if (wide) {
                selectedCurrencyWide = newCurrency;
                secondaryCurrencyWide = (newCurrency === 'sats') ? formData.currency : 'sats';
            } else {
                selectedCurrency = newCurrency;
                secondaryCurrency = (newCurrency === 'sats') ? formData.currency : 'sats';
            }
            const amountField = $(`#bitcoin-donation-amount${widePart}`);
            const amountValue = cleanAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${wide ? selectedCurrencyWide : selectedCurrency}`);
            updateSecondaryCurrency(wide, `bitcoin-donation-amount${widePart}`, `bitcoin-donation-satoshi${widePart}`);
        }
        // Update secondary values
        $('#bitcoin-donation-amount').on('input', () => handleAmountInput(false));
        $('#bitcoin-donation-amount-wide').on('input', () => handleAmountInput(true));

        // Handle thousands separators
        NumericInput('bitcoin-donation-amount')
        NumericInput('bitcoin-donation-amount-wide')

        // Limit cursor movement
        $('#bitcoin-donation-amount').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrency); });
        $('#bitcoin-donation-amount-wide').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrencyWide); });

        // Handle currency change
        $('#bitcoin-donation-swap').on('change', () => { handleChangeCurrency(false); });
        $('#bitcoin-donation-swap-wide').on('change', () => { handleChangeCurrency(true); });

    }

});