// js/shoutouts.js
jQuery(document).ready(function ($) {
    
    const shoutoutAmount = document.getElementById('coinsnap-bitcoin-donation-shoutout-amount');
    
    if (shoutoutAmount) {
        
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
            document.getElementById(primaryFieldName).value = coinsnapDonationShoutoutsData.defaultShoutoutAmount;

            updateSecondaryShoutoutCurrency(primaryFieldName,secondaryFieldName,coinsnapDonationShoutoutsData.defaultShoutoutAmount);
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
                document.getElementById(primaryFieldName).value = amountValue;
                updateSecondaryShoutoutCurrency(primaryFieldName, secondaryFieldName, amountValue);
            }
            else {
                document.getElementById(primaryFieldName).value = '';
                document.getElementById(secondaryFieldName).textContent = 0 + " " + secondaryCurrency;
            }
            
            updateShoutoutInfo('coinsnap-bitcoin-donation-shoutout-amount');

        }
        
        const handleShoutoutCurrencyChange = () => {
            
            const primaryFieldName = `coinsnap-bitcoin-donation-shoutout-amount`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-shoutout-satoshi`;
            const currencyFieldName = `coinsnap-bitcoin-donation-shoutout-swap`;
            const selectedCurrency = document.getElementById(currencyFieldName).value;
            
            
            const amountField = $(`#` + primaryFieldName);
            const amountValue = cleanDonationAmount(amountField.val()) || 0;
            amountField.val(amountValue);
            var label = document.getElementById('coinsnap-bitcoin-donation-shoutout-currency-label');
            if (label) label.textContent = selectedCurrency;

            const currencySatsRate = (selectedCurrency === 'SATS')? 1 : $(`#` + currencyFieldName + ` option:selected`).attr('data-rate');
            
            const displayMinAmount = (selectedCurrency === 'SATS' || selectedCurrency === 'RUB' || selectedCurrency === 'JPY')? 
                (minAmount/currencySatsRate).toFixed(0) : ((selectedCurrency === 'BTC')? (minAmount/currencySatsRate).toFixed(8) : (minAmount/currencySatsRate).toFixed(2));
            
            const displayPremiumAmount = (selectedCurrency === 'SATS' || selectedCurrency === 'RUB' || selectedCurrency === 'JPY')? 
                (premiumAmount/currencySatsRate).toFixed(0) : ((selectedCurrency === 'BTC')? (premiumAmount/currencySatsRate).toFixed(7) : (premiumAmount/currencySatsRate).toFixed(2));
            
            
            $('#coinsnap-bitcoin-donation-shoutout-help-minimum-amount').text(displayMinAmount + ' ' + selectedCurrency);
            $('#coinsnap-bitcoin-donation-shoutout-help-premium-amount').text(displayPremiumAmount + ' ' + selectedCurrency);
            
            updateSecondaryShoutoutCurrency(primaryFieldName, secondaryFieldName, amountValue);
        }

        

        const updateShoutoutInfo = (fieldName) => {
            const field = document.getElementById(fieldName);
            const value = cleanDonationAmount(field.value);

            const currencyFieldName = 'coinsnap-bitcoin-donation-shoutout-swap';
            const selectedCurrency = document.getElementById(currencyFieldName).value;
            const currencySatsRate = (selectedCurrency === 'SATS') ? 1 : jQuery('#' + currencyFieldName + ' option:selected').attr('data-rate');
            const amount = (selectedCurrency === 'SATS') ? value : value * currencySatsRate;

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
        $('#coinsnap-bitcoin-donation-shoutout-swap').on('change', () => { handleShoutoutCurrencyChange(false); });
        NumericInput('coinsnap-bitcoin-donation-shoutout-amount');

    }

});
