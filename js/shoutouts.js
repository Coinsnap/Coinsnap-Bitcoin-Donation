// js/shoutouts.js
jQuery(document).ready(function ($) {
    var exchangeRates = {};
    const minAmount = shoutoutsData.minimumShoutoutAmount
    const premiumAmount = shoutoutsData.premiumShoutoutAmount
    var selectedCurrency = shoutoutsData.currency
    var secondaryCurrency = 'sats'

    const setDefaults = () => {
        const satoshiFieldName = `bitcoin-donation-shoutout-satoshi`
        const selCurrency = selectedCurrency
        const secCurrency = secondaryCurrency

        document.getElementById(`bitcoin-donation-shoutout-swap`).value = selCurrency
        const operation = selCurrency == 'sats' ? '*' : '/';
        const fiatCurrency = shoutoutsData.currency
        const amountField = document.getElementById(`bitcoin-donation-shoutout-amount`);

        amountField.value = shoutoutsData.defaultShoutoutAmount
        updateValueField(
            shoutoutsData.defaultShoutoutAmount,
            satoshiFieldName,
            operation,
            exchangeRates,
            fiatCurrency,
            true // TODO check
        )
        const messageField = document.getElementById(`bitcoin-donation-shoutout-message`);
        messageField.value = shoutoutsData.defaultShoutoutMessage;

        const secondaryField = document.getElementById(satoshiFieldName)
        secondaryField.textContent = "≈ " + secondaryField.textContent + " " + secCurrency
        amountField.value += " " + selCurrency

    }


    if (document.getElementById('bitcoin-donation-shoutout-amount')) {
        fetchCoinsnapExchangeRates().then(rates => {
            exchangeRates = rates
            setDefaults()
            addPopupListener('bitcoin-donation-shoutout-', '', 'Bitcoin Shoutout', exchangeRates, shoutoutsData.redirectUrl)
        });

        const updateShoutoutInfo = (fieldName) => {
            const field = document.getElementById(fieldName);
            const value = cleanAmount(field.value);
            const amount = selectedCurrency == 'sats' ? value : value / exchangeRates[selectedCurrency];
            const shoutButton = document.getElementById('bitcoin-donation-shoutout-pay');
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

        const updateSecondaryCurrency = (primaryId, secondaryId) => {
            const selCurrency = selectedCurrency
            const secCurrency = secondaryCurrency

            const currency = selCurrency == 'sats' ? secCurrency : selCurrency
            const currencyRate = exchangeRates[currency];
            const primaryField = document.getElementById(primaryId)
            amount = cleanAmount(primaryField.value)
            const converted = selCurrency == 'sats' ? (amount * currencyRate).toFixed(8) : (amount / currencyRate).toFixed(0)
            const withSeparators = addNumSeparators(converted)
            document.getElementById(secondaryId).textContent = `≈ ${withSeparators} ${secCurrency}`
        }

        const handleAmountInput = () => {
            const selCurrency = selectedCurrency
            const secCurrency = secondaryCurrency
            const field = document.getElementById(`bitcoin-donation-shoutout-amount`)
            const field2 = document.getElementById(`bitcoin-donation-shoutout-satoshi`)
            let value = field.value.replace(` ${selCurrency}`, '');
            if (value.trim() !== '') {
                field.value = value + ` ${selCurrency}`;
                updateSecondaryCurrency(`bitcoin-donation-shoutout-amount`, `bitcoin-donation-shoutout-satoshi`)
            } else {
                field.value = '' + ` ${selCurrency}`;
                field2.textContent = 0 + " " + secCurrency
            }
            updateShoutoutInfo('bitcoin-donation-shoutout-amount');

        }

        const handleChangeCurrency = () => {
            const newCurrency = $(`#bitcoin-donation-shoutout-swap`).val();

            selectedCurrency = newCurrency;
            secondaryCurrency = (newCurrency === 'sats') ? shoutoutsData.currency : 'sats';

            const amountField = $(`#bitcoin-donation-shoutout-amount`);
            const amountValue = cleanAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${selectedCurrency}`);
            updateShoutoutInfo('bitcoin-donation-shoutout-amount');
            updateSecondaryCurrency(`bitcoin-donation-shoutout-amount`, `bitcoin-donation-shoutout-satoshi`);
        }

        $('#bitcoin-donation-shoutout-amount').on('input', () => handleAmountInput(false));
        $('#bitcoin-donation-shoutout-amount').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrency); });
        $('#bitcoin-donation-shoutout-swap').on('change', () => { handleChangeCurrency(false); });
        NumericInput('bitcoin-donation-shoutout-amount')

    }

});
