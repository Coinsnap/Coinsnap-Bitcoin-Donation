// js/script.js
jQuery(document).ready(function ($) {
    
    if(!$('#blur-overlay-outer').length){
        $('body').append('<div id="blur-overlay-outer"></div><div id="coinsnap-popup-outer"></div>');        
    }
    
    const simpleDonation = document.getElementById('coinsnap-bitcoin-donation-amount');
    const wideDonation = document.getElementById('coinsnap-bitcoin-donation-amount-wide');

    if (document.getElementsByClassName('coinsnap-bitcoin-donation-form')?.length > 0) {

        var overlayContainer = $('.blur-overlay.coinsnap-bitcoin-donation').detach();
        $('#blur-overlay-outer').append(overlayContainer);  
        var qrContainer = $('.qr-container.coinsnap-bitcoin-donation').detach();
        $('#coinsnap-popup-outer').append(qrContainer);
        
        var selectedCurrency = coinsnapDonationFormData.currency;
        var secondaryCurrency = (selectedCurrency === 'SATS')? 'EUR' : 'SATS';
        
        const setDonationDefaults = (wide) => {
            const widePart = wide ? '-wide' : '';
            const currencyFieldName = `coinsnap-bitcoin-donation-swap${widePart}`;
            const primaryFieldName = `coinsnap-bitcoin-donation-amount${widePart}`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-satoshi${widePart}`;
            
            const messageField = document.getElementById(`coinsnap-bitcoin-donation-message${widePart}`);
            messageField.value = coinsnapDonationFormData.defaultMessage;

            document.getElementById(currencyFieldName).value = selectedCurrency;
            document.getElementById(primaryFieldName).value = coinsnapDonationFormData.defaultAmount +  " " + selectedCurrency;
            
            updateSecondaryDonationCurrency(wide,primaryFieldName,secondaryFieldName,coinsnapDonationFormData.defaultAmount);

        };
        
        const updateSecondaryDonationCurrency = (wide, primaryId, secondaryId, originalAmount) => {
            const widePart = wide ? '-wide' : '';
            const currencyFieldName = `coinsnap-bitcoin-donation-swap${widePart}`;
            const currency = document.getElementById(currencyFieldName).value;
            
            const currencyRate = (currency === 'SATS')
                ? 1/$(`#` + currencyFieldName + ` option[value="EUR"]`).attr('data-rate') 
                : $(`#` + currencyFieldName + ` option:selected`).attr('data-rate');
            
            const converted = (currency === 'SATS')? (originalAmount * currencyRate).toFixed(2) : (originalAmount * currencyRate).toFixed(0);
            const secondaryCurrency = (currency === 'SATS')? 'EUR' : 'SATS';
            
            const withSeparators = addNumSeparators(converted);
            document.getElementById(secondaryId).textContent = `≈ ${withSeparators} ${secondaryCurrency}`;
            $('#'+secondaryId).attr('data-value',converted);
        };

        if (simpleDonation) {
            setDonationDefaults(false);
            addDonationPopupListener('coinsnap-bitcoin-donation-', '', 'Bitcoin Donation', coinsnapDonationFormData.redirectUrl);
        }
        
        if (wideDonation) {
            setDonationDefaults(true);
            addDonationPopupListener('coinsnap-bitcoin-donation-', '-wide', 'Bitcoin Donation', coinsnapDonationFormData.redirectUrl);
        }

        const handleDonationAmountInput = (wide) => {
            const widePart = wide ? '-wide' : '';
            const primaryFieldName = `coinsnap-bitcoin-donation-amount${widePart}`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-satoshi${widePart}`;
            const currencyFieldName = `coinsnap-bitcoin-donation-swap${widePart}`;
            const selectedCurrency = document.getElementById(currencyFieldName).value;
            const secondaryCurrency = (selectedCurrency === 'SATS')? 'EUR' : 'SATS';
            
            let amountValue = document.getElementById(primaryFieldName).value.replace(/[^\d.,]/g, '');
            const decimalSeparator = getThousandSeparator() === "." ? "," : ".";
            
            if (amountValue[0] === '0' && amountValue[1] !== decimalSeparator && amountValue.length > 1) {
                amountValue = amountValue.substring(1);
            }
            if (amountValue.trim() !== '') {
                document.getElementById(primaryFieldName).value = amountValue + ` ${selectedCurrency}`;
                updateSecondaryDonationCurrency(wide, primaryFieldName, secondaryFieldName, amountValue);
            }
            else {
                document.getElementById(primaryFieldName).value = '' + ` ${secondaryCurrency}`;
                document.getElementById(secondaryFieldName).textContent = 0 + " " + secondaryCurrency;
            }
        }

        const handleDonationCurrencyChange = (wide) => {
            const widePart = wide ? '-wide' : '';
            const primaryFieldName = `coinsnap-bitcoin-donation-amount${widePart}`;
            const secondaryFieldName = `coinsnap-bitcoin-donation-satoshi${widePart}`;
            const currencyFieldName = `coinsnap-bitcoin-donation-swap${widePart}`;
            const selectedCurrency = document.getElementById(currencyFieldName).value;
            
            const amountField = $(`#` + primaryFieldName);
            const amountValue = cleanDonationAmount(amountField.val()) || 0;
            amountField.val(`${amountValue} ${selectedCurrency}`);
            
            updateSecondaryDonationCurrency(wide,primaryFieldName,secondaryFieldName,amountValue);
        };
        
        // Update secondary values
        $('#coinsnap-bitcoin-donation-amount').on('input', () => handleDonationAmountInput(false));
        $('#coinsnap-bitcoin-donation-amount-wide').on('input', () => handleDonationAmountInput(true));

        // Handle thousands separators
        NumericInput('coinsnap-bitcoin-donation-amount');
        NumericInput('coinsnap-bitcoin-donation-amount-wide');

        // Limit cursor movement
        $('#coinsnap-bitcoin-donation-amount').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrency); });
        $('#coinsnap-bitcoin-donation-amount-wide').on('click keydown', (e) => { limitCursorMovement(e, selectedCurrency); });

        // Handle currency change
        $('#coinsnap-bitcoin-donation-swap').on('change', () => { handleDonationCurrencyChange(false); });
        $('#coinsnap-bitcoin-donation-swap-wide').on('change', () => { handleDonationCurrencyChange(true); });

    }

});