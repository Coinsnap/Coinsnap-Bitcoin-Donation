// js/script.js
jQuery(document).ready(function ($) {
    
    if(!$('#blur-overlay-outer').length){
        $('body').append('<div id="blur-overlay-outer"></div><div id="coinsnap-popup-outer"></div>');        
    }
    
    const multiDonation = document.getElementById('coinsnap-bitcoin-donation-amount-multi');
    const wideMultiDonation = document.getElementById('coinsnap-bitcoin-donation-amount-multi-wide');
    
    if (document.getElementsByClassName('coinsnap-bitcoin-donation-form')?.length > 0) {

        var overlayContainer = $('.blur-overlay.coinsnap-bitcoin-donation').detach();
        $('#blur-overlay-outer').append(overlayContainer);  
        var qrContainer = $('.qr-container.coinsnap-bitcoin-donation').detach();
        $('#coinsnap-popup-outer').append(qrContainer);
    
        var selectedMultiCurrency = coinsnapDonationMultiData.multiCurrency;
        var secondaryMultiCurrency = (selectedMultiCurrency === 'SATS')? 'EUR' : 'SATS';

        if (multiDonation || wideMultiDonation) {

            const updateSecondaryMultiCurrency = (wide, primaryId, secondaryId, originalAmount) => {
                const widePart = wide ? '-wide' : '';
                const currencyFieldName = `coinsnap-bitcoin-donation-swap-multi${widePart}`;
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

            const setMultiDefaults = (wide) => {
                const widePart = wide ? '-wide' : '';
                const currencyFieldName = `coinsnap-bitcoin-donation-swap-multi${widePart}`;
                const primaryFieldName = `coinsnap-bitcoin-donation-amount-multi${widePart}`;
                const secondaryFieldName = `coinsnap-bitcoin-donation-satoshi-multi${widePart}`;

                const messageField = document.getElementById(`coinsnap-bitcoin-donation-message-multi${widePart}`);
                messageField.value = coinsnapDonationMultiData.defaultMultiMessage;

                document.getElementById(currencyFieldName).value = selectedMultiCurrency;
                document.getElementById(primaryFieldName).value = coinsnapDonationMultiData.defaultMultiAmount +  " " + selectedMultiCurrency;

                updateSecondaryMultiCurrency(wide,primaryFieldName,secondaryFieldName,coinsnapDonationMultiData.defaultMultiAmount);

                for (let i = 1; i <= 3; i++) {
                    updateSecondaryMultiCurrency(
                        wide,
                        `coinsnap-bitcoin-donation-pay-multi-snap${i}-primary-wide`,
                        `coinsnap-bitcoin-donation-pay-multi-snap${i}-secondary-wide`,
                        coinsnapDonationMultiData[`snap${i}Amount`]
                    );
                }
                unformatNumericInput(document.getElementById(primaryFieldName));
                formatNumericInput(document.getElementById(primaryFieldName));
            };

            if (multiDonation) {
                    setMultiDefaults(false);
                    addDonationPopupListener('coinsnap-bitcoin-donation-', '-multi', 'Multi Amount Donation', coinsnapDonationMultiData.redirectUrl);
                }
                if (wideMultiDonation) {
                    setMultiDefaults(true);
                    addDonationPopupListener('coinsnap-bitcoin-donation-', '-multi-wide', 'Multi Amount Donation', coinsnapDonationMultiData.redirectUrl);
                }

            const handleMultiAmountInput = (wide) => {
                const widePart = wide ? '-wide' : '';
                const primaryFieldName = `coinsnap-bitcoin-donation-amount-multi${widePart}`;
                const secondaryFieldName = `coinsnap-bitcoin-donation-satoshi-multi${widePart}`;
                const currencyFieldName = `coinsnap-bitcoin-donation-swap-multi${widePart}`;
                const selectedCurrency = document.getElementById(currencyFieldName).value;
                const secondaryCurrency = (selectedCurrency === 'SATS')? 'EUR' : 'SATS';

                let amountValue = document.getElementById(primaryFieldName).value.replace(/[^\d.,]/g, '');
                const decimalSeparator = getThousandSeparator() === "." ? "," : ".";

                if (amountValue[0] === '0' && amountValue[1] !== decimalSeparator && amountValue.length > 1) {
                    amountValue = amountValue.substring(1);
                }
                if (amountValue.trim() !== '') {
                    document.getElementById(primaryFieldName).value = amountValue + ` ${selectedCurrency}`;
                    updateSecondaryMultiCurrency(wide, primaryFieldName, secondaryFieldName, amountValue);
                }
                else {
                    document.getElementById(primaryFieldName).value = '' + ` ${secondaryCurrency}`;
                    document.getElementById(secondaryFieldName).textContent = 0 + " " + secondaryCurrency;
                }
            }

            // Update secondary values
            $('#coinsnap-bitcoin-donation-amount-multi').on('input', () => handleMultiAmountInput(false));
            $('#coinsnap-bitcoin-donation-amount-multi-wide').on('input', () => handleMultiAmountInput(true));

            // Handle thousands separators
            if(multiDonation){
                NumericInput('coinsnap-bitcoin-donation-amount-multi');
            }
            if(wideMultiDonation){
                NumericInput('coinsnap-bitcoin-donation-amount-multi-wide');
            }

            // Limit cursor movement
            if(multiDonation){
                $('#coinsnap-bitcoin-donation-amount-multi').on('click keydown', (e) => { limitCursorMovement(e, selectedMultiCurrency); });
            }
            if(wideMultiDonation){
                $('#coinsnap-bitcoin-donation-amount-multi-wide').on('click keydown', (e) => { limitCursorMovement(e, selectedMultiCurrency); });
            }

            // Update snap buttons
            const snapIds = ['snap1', 'snap2', 'snap3'];

            const widePart = (wideMultiDonation)? '-wide' : '';

            snapIds.forEach(snapId => {

                    const suffix = `${snapId}${widePart}`;
                    const payButtonId = `coinsnap-bitcoin-donation-pay-multi-${suffix}`;
                    const primaryId = `coinsnap-bitcoin-donation-pay-multi-${snapId}-primary${widePart}`;

                    $(`#${payButtonId}`).on('click', () => {
                        const amountField = $(`#coinsnap-bitcoin-donation-amount-multi${widePart}`);
                        const amount = cleanDonationAmount(document.getElementById(primaryId).textContent);
                        amountField.val(`${amount} ${selectedMultiCurrency}`);
                        amountField.trigger('input');
                    });

            });

            const handleMultiCurrencyChange = (wide) => {
                const widePart = wide ? '-wide' : '';
                const primaryFieldName = `coinsnap-bitcoin-donation-amount-multi${widePart}`;
                const secondaryFieldName = `coinsnap-bitcoin-donation-satoshi-multi${widePart}`;
                const currencyFieldName = `coinsnap-bitcoin-donation-swap-multi${widePart}`;
                const selectedCurrency = document.getElementById(currencyFieldName).value;
                
                const selectedCurrencyRate = (selectedCurrency === 'SATS')
                    ? 1/$(`#` + currencyFieldName + ` option[value="EUR"]`).attr('data-rate') 
                    : $(`#` + currencyFieldName + ` option:selected`).attr('data-rate');
                    
                    

                const amountField = $(`#` + primaryFieldName);
                
                const primaryAmount = (selectedCurrency === 'SATS')
                        ? (parseFloat(amountField.val()) / selectedCurrencyRate).toFixed(0)
                        : parseFloat(amountField.val());
                
                const amountValue = primaryAmount || 0;
                amountField.val(`${amountValue} ${selectedCurrency}`);

                updateSecondaryMultiCurrency(wide,primaryFieldName,secondaryFieldName,amountValue);

                const snaps = ['snap1', 'snap2', 'snap3'];

                snaps.forEach(snap => {
                    const primaryId = `coinsnap-bitcoin-donation-pay-multi-${snap}-primary${widePart}`;
                    const secondaryId = `coinsnap-bitcoin-donation-pay-multi-${snap}-secondary${widePart}`;
                    const primaryAmount = (selectedCurrency === 'SATS')
                        ? (coinsnapDonationMultiData[`${snap}Amount`] / selectedCurrencyRate).toFixed(0)
                        : coinsnapDonationMultiData[`${snap}Amount`];
                    document.getElementById(primaryId).textContent = primaryAmount + ' ' + selectedCurrency;
                    updateSecondaryMultiCurrency(wide,primaryId, secondaryId, primaryAmount);
                });

            };

            // Handle currency change
            $('#coinsnap-bitcoin-donation-swap-multi').on('change', () => { handleMultiCurrencyChange(false); });
            $('#coinsnap-bitcoin-donation-swap-multi-wide').on('change', () => { handleMultiCurrencyChange(true); });

        }
    }
});