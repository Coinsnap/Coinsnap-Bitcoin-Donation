// js/shoutouts.js
jQuery(document).ready(function ($) {
    
    if(!$('#blur-overlay-outer').length){
        $('body').append('<div id="blur-overlay-outer"></div><div id="coinsnap-popup-outer"></div>');
    }
    
    const shoutoutAmount = document.getElementById('coinsnap-bitcoin-donation-shoutout-amount');
    
    if (document.getElementsByClassName('coinsnap-bitcoin-donation-form')?.length > 0) {
        
        var overlayContainer = $('.blur-overlay.coinsnap-bitcoin-donation').detach();
        $('#blur-overlay-outer').append(overlayContainer);  
        var qrContainer = $('.qr-container.coinsnap-bitcoin-donation').detach();
        $('#coinsnap-popup-outer').append(qrContainer);
    
        const minAmount = coinsnapDonationShoutoutsData.minimumShoutoutAmount;
        const premiumAmount = coinsnapDonationShoutoutsData.premiumShoutoutAmount;
        var selectedCurrency = coinsnapDonationShoutoutsData.currency;
        var secondaryCurrency = (selectedCurrency === 'SATS')? 'EUR' : 'SATS';

        const setDonationShoutoutDefaults = () => {
            
            const currencyFieldName = `coinsnap-bitcoin-donation-shoutout-swap`;
            const primaryFieldName = `coinsnap-bitcoin-donation-shoutout-amount`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-shoutout-satoshi`;
            
            const messageField = document.getElementById(`coinsnap-bitcoin-donation-shoutout-message`);
            messageField.value = coinsnapDonationShoutoutsData.defaultShoutoutMessage;

            document.getElementById(currencyFieldName).value = selectedCurrency;
            document.getElementById(primaryFieldName).value = coinsnapDonationShoutoutsData.defaultShoutoutAmount +  " " + selectedCurrency;
            
            console.log(coinsnapDonationShoutoutsData.defaultShoutoutAmount);
            
            updateSecondaryShoutoutCurrency(primaryFieldName,secondaryFieldName,coinsnapDonationShoutoutsData.defaultShoutoutAmount);
            
            /*
            const satoshiFieldName = `coinsnap-bitcoin-donation-shoutout-satoshi`;
            const selCurrency = selectedCurrency;
            const secCurrency = secondaryCurrency;

            document.getElementById(`coinsnap-bitcoin-donation-shoutout-swap`).value = selCurrency
            const operation = selCurrency == 'sats' ? '*' : '/';
            const fiatCurrency = coinsnapDonationShoutoutsData.currency
            const amountField = document.getElementById(`coinsnap-bitcoin-donation-shoutout-amount`);

            amountField.value = coinsnapDonationShoutoutsData.defaultShoutoutAmount;
            updateDonationValueField(
                coinsnapDonationShoutoutsData.defaultShoutoutAmount,
                satoshiFieldName,
                operation,
                fiatCurrency,
                true
            )
            const messageField = document.getElementById(`coinsnap-bitcoin-donation-shoutout-message`);
            messageField.value = coinsnapDonationShoutoutsData.defaultShoutoutMessage;

            const secondaryField = document.getElementById(satoshiFieldName);
            secondaryField.textContent = "≈ " + secondaryField.textContent + " " + secCurrency;
            amountField.value += " " + selCurrency;*/
        }
        
        const updateSecondaryShoutoutCurrency = (primaryId, secondaryId, originalAmount) => {
            const currencyFieldName = `coinsnap-bitcoin-donation-shoutout-swap`;
            const currency = document.getElementById(currencyFieldName).value;
            
            const currencyRate = (currency === 'SATS')
                ? 1/$(`#` + currencyFieldName + ` option[value="EUR"]`).attr('data-rate') 
                : $(`#` + currencyFieldName + ` option:selected`).attr('data-rate');
            
            const converted = (currency === 'SATS')? (originalAmount * currencyRate).toFixed(2) : (originalAmount * currencyRate).toFixed(0);
            const secondaryCurrency = (currency === 'SATS')? 'EUR' : 'SATS';
            
            const withSeparators = addNumSeparators(converted);
            document.getElementById(secondaryId).textContent = `≈ ${withSeparators} ${secondaryCurrency}`;
            $('#'+secondaryId).attr('data-value',converted);
            
            /*
            
            
            const selCurrency = selectedCurrency
            const secCurrency = secondaryCurrency

            const currency = selCurrency == 'sats' ? secCurrency : selCurrency
            const currencyRate = exchangeRates[currency];
            const primaryField = document.getElementById(primaryId)
            amount = cleanDonationAmount(primaryField.value)
            const converted = selCurrency == 'sats' ? (amount * currencyRate).toFixed(8) : (amount / currencyRate).toFixed(0)
            const withSeparators = addNumSeparators(converted)
            document.getElementById(secondaryId).textContent = `≈ ${withSeparators} ${secCurrency}`*/
        }
        
        setDonationShoutoutDefaults();
        addDonationPopupListener('coinsnap-bitcoin-donation-shoutout-', '', 'Bitcoin Shoutout', coinsnapDonationShoutoutsData.redirectUrl);
        
        const handleShoutoutsAmountInput = () => {
            
            const currencyFieldName = `coinsnap-bitcoin-donation-shoutout-swap`;
            const primaryFieldName = `coinsnap-bitcoin-donation-shoutout-amount`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-shoutout-satoshi`;
            const selectedCurrency = coinsnapDonationShoutoutsData.currency;
            const secondaryCurrency = (selectedCurrency === 'SATS')? 'EUR' : 'SATS';
            
            let amountValue = document.getElementById(primaryFieldName).value.replace(/[^\d.,]/g, '');
            const decimalSeparator = getThousandSeparator() === "." ? "," : ".";
            
            if (amountValue[0] === '0' && amountValue[1] !== decimalSeparator && amountValue.length > 1) {
                amountValue = amountValue.substring(1);
            }
            if (amountValue.trim() !== '') {
                document.getElementById(primaryFieldName).value = amountValue + ` ${selectedCurrency}`;
                updateSecondaryShoutoutCurrency(primaryFieldName, secondaryFieldName, amountValue);
            }
            else {
                document.getElementById(primaryFieldName).value = '' + ` ${secondaryCurrency}`;
                document.getElementById(secondaryFieldName).textContent = 0 + " " + secondaryCurrency;
            }
            
            /*
            const selCurrency = selectedCurrency
            const secCurrency = secondaryCurrency
            const field = document.getElementById(`coinsnap-bitcoin-donation-shoutout-amount`)
            const field2 = document.getElementById(`coinsnap-bitcoin-donation-shoutout-satoshi`)
            let value = field.value.replace(/[^\d.,]/g, '');
            const decimalSeparator = getThousandSeparator() == "." ? "," : ".";
            if (value[0] == '0' && value[1] != decimalSeparator && value.length > 1) {
                value = value.substring(1);
            }
            if (value.trim() !== '') {
                field.value = value + ` ${selCurrency}`;
                updateSecondaryDonationCurrency(`coinsnap-bitcoin-donation-shoutout-amount`, `coinsnap-bitcoin-donation-shoutout-satoshi`)
            } else {
                field.value = '' + ` ${selCurrency}`;
                field2.textContent = 0 + " " + secCurrency
            }*/
            
            updateShoutoutInfo('coinsnap-bitcoin-donation-shoutout-amount');

        }
        
        const handleShoutoutCurrencyChange = () => {
            
            const primaryFieldName = `coinsnap-bitcoin-donation-shoutout-amount`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-shoutout-satoshi`;
            const currencyFieldName = `coinsnap-bitcoin-donation-shoutout-swap`;
            const selectedCurrency = document.getElementById(currencyFieldName).value;
            
            
            const amountField = $(`#` + primaryFieldName);
            const amountValue = cleanDonationAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${selectedCurrency}`);
            
            const currencySatsRate = (selectedCurrency === 'SATS')? 1 : $(`#` + currencyFieldName + ` option:selected`).attr('data-rate');
            
            const displayMinAmount = (selectedCurrency === 'SATS' || selectedCurrency === 'RUB' || selectedCurrency === 'JPY')? 
                (minAmount/currencySatsRate).toFixed(0) : ((selectedCurrency === 'BTC')? (minAmount/currencySatsRate).toFixed(8) : (minAmount/currencySatsRate).toFixed(2));
            
            const displayPremiumAmount = (selectedCurrency === 'SATS' || selectedCurrency === 'RUB' || selectedCurrency === 'JPY')? 
                (premiumAmount/currencySatsRate).toFixed(0) : ((selectedCurrency === 'BTC')? (premiumAmount/currencySatsRate).toFixed(7) : (premiumAmount/currencySatsRate).toFixed(2));
            
            
            $('#coinsnap-bitcoin-donation-shoutout-help-minimum-amount').text(displayMinAmount + ' ' + selectedCurrency);
            $('#coinsnap-bitcoin-donation-shoutout-help-premium-amount').text(displayPremiumAmount + ' ' + selectedCurrency);
            
            updateSecondaryShoutoutCurrency(primaryFieldName, secondaryFieldName, amountValue);
            /*
            const newCurrency = $(`#coinsnap-bitcoin-donation-shoutout-swap`).val();

            selectedCurrency = newCurrency;
            secondaryCurrency = (newCurrency === 'sats') ? coinsnapDonationShoutoutsData.currency : 'sats';

            const amountField = $(`#coinsnap-bitcoin-donation-shoutout-amount`);
            const amountValue = cleanDonationAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${selectedCurrency}`);
            updateShoutoutInfo('coinsnap-bitcoin-donation-shoutout-amount');
            updateSecondaryDonationCurrency(`coinsnap-bitcoin-donation-shoutout-amount`, `coinsnap-bitcoin-donation-shoutout-satoshi`);*/
        }

        

        const updateShoutoutInfo = (fieldName) => {
            const field = document.getElementById(fieldName);
            const value = cleanDonationAmount(field.value);
            const amount = (selectedCurrency === 'sats')? value : value / exchangeRates[selectedCurrency];
            const shoutButton = document.getElementById('coinsnap-bitcoin-donation-shoutout-pay');
            const helpMinimum = document.getElementById('coinsnap-bitcoin-donation-shoutout-help-minimum');
            const helpPremium = document.getElementById('coinsnap-bitcoin-donation-shoutout-help-premium');
            const helpInfo = document.getElementById('coinsnap-bitcoin-donation-shoutout-help-info');

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

        

        

        $('#coinsnap-bitcoin-donation-shoutout-amount').on('input', () => handleShoutoutsAmountInput(false));
        $('#coinsnap-bitcoin-donation-shoutout-amount').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrency); });
        $('#coinsnap-bitcoin-donation-shoutout-swap').on('change', () => { handleShoutoutCurrencyChange(false); });
        NumericInput('coinsnap-bitcoin-donation-shoutout-amount');

    }

});
