// js/script.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    var selectedCurrency = formData.currency
    var secondaryCurrency = 'sats'
    var selectedCurrencyWide = formData.currency
    var secondaryCurrencyWide = 'sats'

    const simpleDonation = document.getElementById('coinsnap-bitcoin-donation-amount')
    const wideDonation = document.getElementById('coinsnap-bitcoin-donation-amount-wide')

    if (simpleDonation || wideDonation) {

        const setDefaults = (wide) => {
            const widePart = wide ? '-wide' : ''
            const satoshiFieldName = `coinsnap-bitcoin-donation-satoshi${widePart}`
            const selCurrency = wide ? selectedCurrencyWide : selectedCurrency
            const secCurrency = wide ? secondaryCurrencyWide : secondaryCurrency

            document.getElementById(`coinsnap-bitcoin-donation-swap${widePart}`).value = selCurrency
            const operation = selCurrency == 'sats' ? '*' : '/';
            const fiatCurrency = formData.currency
            const amountField = document.getElementById(`coinsnap-bitcoin-donation-amount${widePart}`);

            amountField.value = formData.defaultAmount
            updateValueField(
                formData.defaultAmount,
                satoshiFieldName,
                operation,
                exchangeRates,
                fiatCurrency,
                true
            )
            const messageField = document.getElementById(`coinsnap-bitcoin-donation-message${widePart}`);
            messageField.value = formData.defaultMessage;

            const secondaryField = document.getElementById(satoshiFieldName)
            secondaryField.textContent = "≈ " + secondaryField.textContent + " " + secCurrency
            amountField.value += " " + selCurrency

        }

        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            if (simpleDonation) {
                setDefaults(false)
                addPopupListener('coinsnap-bitcoin-donation-', '', 'Bitcoin Donation', exchangeRates, formData.redirectUrl)
            }
            if (wideDonation) {
                setDefaults(true)
                addPopupListener('coinsnap-bitcoin-donation-', '-wide', 'Bitcoin Donation', exchangeRates, formData.redirectUrl)
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
            const field = document.getElementById(`coinsnap-bitcoin-donation-amount${widePart}`)
            const field2 = document.getElementById(`coinsnap-bitcoin-donation-satoshi${widePart}`)
            let value = field.value.replace(/[^\d.,]/g, '');
            const decimalSeparator = getThousandSeparator() == "." ? "," : ".";
            if (value[0] == '0' && value[1] != decimalSeparator) {
                value = value.substring(1);
            }
            if (value.trim() !== '') {
                field.value = value + ` ${selCurrency}`;
                updateSecondaryCurrency(wide, `coinsnap-bitcoin-donation-amount${widePart}`, `coinsnap-bitcoin-donation-satoshi${widePart}`)
            } else {
                field.value = '' + ` ${selCurrency}`;
                field2.textContent = 0 + " " + secCurrency
            }
        }

        const handleChangeCurrency = (wide) => {
            const widePart = wide ? '-wide' : ''
            const newCurrency = $(`#coinsnap-bitcoin-donation-swap${widePart}`).val();
            if (wide) {
                selectedCurrencyWide = newCurrency;
                secondaryCurrencyWide = (newCurrency === 'sats') ? formData.currency : 'sats';
            } else {
                selectedCurrency = newCurrency;
                secondaryCurrency = (newCurrency === 'sats') ? formData.currency : 'sats';
            }
            const amountField = $(`#coinsnap-bitcoin-donation-amount${widePart}`);
            const amountValue = cleanAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${wide ? selectedCurrencyWide : selectedCurrency}`);
            updateSecondaryCurrency(wide, `coinsnap-bitcoin-donation-amount${widePart}`, `coinsnap-bitcoin-donation-satoshi${widePart}`);
        }
        // Update secondary values
        $('#coinsnap-bitcoin-donation-amount').on('input', () => handleAmountInput(false));
        $('#coinsnap-bitcoin-donation-amount-wide').on('input', () => handleAmountInput(true));

        // Handle thousands separators
        NumericInput('coinsnap-bitcoin-donation-amount')
        NumericInput('coinsnap-bitcoin-donation-amount-wide')

        // Limit cursor movement
        $('#coinsnap-bitcoin-donation-amount').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrency); });
        $('#coinsnap-bitcoin-donation-amount-wide').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrencyWide); });

        // Handle currency change
        $('#coinsnap-bitcoin-donation-swap').on('change', () => { handleChangeCurrency(false); });
        $('#coinsnap-bitcoin-donation-swap-wide').on('change', () => { handleChangeCurrency(true); });

    }

});