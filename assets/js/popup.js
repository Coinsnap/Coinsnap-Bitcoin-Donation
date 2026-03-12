// js/popup.js
const checkDonationRequiredFieds = (fields) => {
    let valid = true;
    fields.forEach((field) => {
        if (field && field.required && !field.value.trim()) {
            valid = false;
            field.classList.add('error');
            setTimeout(() => {
                field.classList.remove('error');
            }, 3000);
        }
    });
    return valid;
}

const addDonationPopupListener = (prefix, suffix, type, redirect) => {
    let walletHandler = null;

    const resetPopup = (prefix, suffix) => {
        hideDonationElementsById(['qr-container', 'blur-overlay', 'payment-loading', 'payment-popup', 'thank-you-popup'], prefix, suffix);
        showDonationElementById('public-donor-popup', 'flex', prefix, suffix);
        const button = document.getElementById(`${prefix}pay${suffix}`);
        button.disabled = false;
        const payInWalletBtn = document.getElementById(`${prefix}pay-in-wallet${suffix}`);
        if (walletHandler) {
            payInWalletBtn.removeEventListener('click', walletHandler);
            walletHandler = null;
        }
    };

    window.addEventListener("click", function (event) {
        const qrContainer = document.getElementById(`${prefix}qr-container${suffix}`);
        const element = event.target;
        if (qrContainer.style.display === 'flex') {
            if (element.classList.contains('close-popup') || (!qrContainer.contains(event.target) && !element.id.includes('pay'))) {
                resetPopup(prefix, suffix);
            }
        }
    });

    document.getElementById(`${prefix}pay${suffix}`).addEventListener('click', async () => {
        const button = document.getElementById(`${prefix}pay${suffix}`)
        button.disabled = true;
        event.preventDefault();
        const honeypot = document.getElementById(`${prefix}email${suffix}`);
        if (honeypot && honeypot.value) {
            return;
        }
        
        const currency = document.getElementById(`${prefix}swap${suffix}`).value?.toUpperCase();
        const amountValue = document.getElementById(`${prefix}amount${suffix}`)?.value;
        const amountField = document.getElementById(`${prefix}amount${suffix}`);
        
        
        console.log('Amount check');
        if (!amountValue || parseFloat(amountValue) === 0) {
            button.disabled = false;
            addErrorDonationField(amountField,'');
            return;
        }
        
        let data = {
            action: 'coinsnap_bitcoin_donation_amount_check',
            apiNonce: coinsnap_bitcoin_donation_ajax.nonce,
            apiAmount: cleanDonationAmount(amountValue),
            apiCurrency: currency
        };

        const queryData = new URLSearchParams();
        for ( const key in data ) {
            queryData.set( key, data[ key ] );
        }
        
        const amountCheck = await fetch(coinsnap_bitcoin_donation_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cache-Control': 'no-cache'
            },
            body: queryData
        }).catch(error => {
            console.log('Amount check request error' + error);
        });
        
        const responseData = await amountCheck.json();
  
        if(responseData.result === false){
            button.disabled = false;
            addErrorDonationField(amountField,responseData.error);
            return;
        }
        
        const publicDonor = document.getElementById(`${prefix}qr-container${suffix}`).dataset.publicDonors;
        if (!publicDonor) {
            const publicDonorsPay = document.getElementById(`${prefix}public-donors-pay${suffix}`);
            publicDonorsPay.click();
        }
        showDonationElementsById(['blur-overlay', 'qr-container'], 'flex', prefix, suffix);
    });

    document.getElementById(`${prefix}public-donors-pay${suffix}`).addEventListener('click', async () => {
        event.preventDefault();
        const publicDonor = document.getElementById(`${prefix}qr-container${suffix}`).dataset.publicDonors;
        var retryId = '';

        const messageField = document.getElementById(`${prefix}message${suffix}`);
        const message = messageField.value;
        
        const currencyField = document.getElementById(`${prefix}swap${suffix}`);
        const currency = currencyField.value?.toUpperCase();
        const currencyFiat = (currency === 'SATS')? 'EUR' : currency;
        
        const amountField = (currency === 'SATS')? document.getElementById(`${prefix}amount${suffix}`) : document.getElementById(`${prefix}satoshi${suffix}`);
        var amount = (currency === 'SATS')? document.getElementById(`${prefix}amount${suffix}`).value : document.getElementById(`${prefix}satoshi${suffix}`).getAttribute('data-value');
        amount = cleanDonationAmount(amount);
        
        const amountFiatField = (currency === 'SATS')? document.getElementById(`${prefix}satoshi${suffix}`) : document.getElementById(`${prefix}amount${suffix}`);
        var amountFiat =  (currency === 'SATS')? document.getElementById(`${prefix}satoshi${suffix}`).getAttribute('data-value') : document.getElementById(`${prefix}amount${suffix}`).value;
                
        amountFiat = cleanDonationAmount(amountFiat);
        
        
        const firstNameField = document.getElementById(`${prefix}first-name${suffix}`);
        const lastNameField = document.getElementById(`${prefix}last-name${suffix}`);
        const emailField = document.getElementById(`${prefix}donor-email${suffix}`);
        const streetField = document.getElementById(`${prefix}street${suffix}`);
        const houseNumberField = document.getElementById(`${prefix}house-number${suffix}`);
        const postalCodeField = document.getElementById(`${prefix}postal${suffix}`);
        const cityField = document.getElementById(`${prefix}town${suffix}`);
        const countryField = document.getElementById(`${prefix}country${suffix}`);
        const address = `${streetField?.value ?? ''} ${houseNumberField?.value ?? ''}, ${postalCodeField?.value ?? ''} ${cityField?.value ?? ''}, ${countryField?.value ?? ''}`;
        const customField = document.getElementById(`${prefix}custom${suffix}`);
        const customNameField = document.getElementById(`${prefix}custom-name${suffix}`);
        const customContent = customNameField?.textContent && customField?.value ? `${customNameField.textContent}: ${customField.value}` : '';
        const validForm = !publicDonor || checkDonationRequiredFieds([firstNameField, lastNameField, emailField, streetField, houseNumberField, postalCodeField, cityField, countryField, customField]);
        
        const metadata = {
            donorName: `${firstNameField.value} ${lastNameField?.value ?? ''}`,
            donorEmail: emailField?.value,
            donorAddress: address !== ' ,  , ' ? address : '',
            donorMessage: message,
            donorCustom: customContent,
            formType: type,
            amount: `${amount}`,
            amountFiat: `${amountFiat} ${currencyFiat}`,
            publicDonor: publicDonor || 0,
            modal: true,
            orderNumber: "Bitcoin Donation",
        }
        if (!validForm) return;

        showDonationElementById('payment-loading', 'flex', prefix, suffix);
        hideDonationElementById('public-donor-popup', prefix, suffix);

        var name = undefined;
        if (type === "Bitcoin Shoutout") {
            const nameField = document.getElementById(`${prefix}name${suffix}`);
            name = nameField?.value || "Anonymous";
        }

        const res = await createDonationInvoice(amount, message, name, type, false, metadata);

        if (res) {
            
            console.log(res);
            
            // Update addresses 
            const qrLightning = res.lightningInvoice;
            const qrBitcoin = res.onchainAddress;

            if (qrBitcoin) {
                showDonationElementsById(['btc-wrapper', 'qr-btc-container'], 'flex', prefix, suffix)
            }

            // Hide spinner and show qr code stuff
            showDonationElementsById(['qrCode', 'lightning-wrapper', 'qr-fiat', 'qrCodeBtc'], 'block', prefix, suffix)
            showDonationElementsById(['qr-summary', 'qr-lightning-container', 'pay-in-wallet'], 'flex', prefix, suffix)
            hideDonationElementById('payment-loading', prefix, suffix)
            showDonationElementById('payment-popup', 'flex', prefix, suffix)
            // Update actuall data
            document.getElementById(`${prefix}qrCode${suffix}`).src = res.qrCodes.lightningQR;
            document.getElementById(`${prefix}qr-lightning${suffix}`).textContent = `${qrLightning.substring(0, 20)}...${qrLightning.slice(-15)}`;
            document.getElementById(`${prefix}qr-btc${suffix}`).textContent = `${qrBitcoin.substring(0, 20)}...${qrBitcoin.slice(-15)}`;
            document.getElementById(`${prefix}qr-amount${suffix}`).textContent = `Amount: ${res.amount} sats`;

            // Copy address functionallity 
            const copyLightning = document.querySelector(`#${prefix}qr-lightning-container${suffix} .qr-copy-icon`);
            const copyBtc = document.querySelector(`#${prefix}qr-btc-container${suffix} .qr-copy-icon`);
            copyLightning.addEventListener('click', () => { navigator.clipboard.writeText(qrLightning); });
            copyBtc.addEventListener('click', () => { navigator.clipboard.writeText(qrBitcoin); });
            document.getElementById(`${prefix}qr-fiat${suffix}`).textContent = `≈ ${amountFiat} ${currencyFiat}`;
            document.getElementById(`${prefix}pay-in-wallet${suffix}`).setAttribute('href', `lightning:${qrLightning}`);

            // Reset retry counter
            var retryNum = 0;
            retryId = res.id;

            const checkPaymentStatus = () => {
                fetch(`/wp-json/coinsnap-bitcoin-donation/v1/check-payment-status/${res.id}`)
                    .then(response => response.json())
                    .then(data => {
                        const qrContainer = document.getElementById(`${prefix}qr-container${suffix}`);

                        if (data.status === 'completed') {
                            showDonationElementById('thank-you-popup', 'flex', prefix, suffix)
                            hideDonationElementById('payment-popup', prefix, suffix)
                            setTimeout(() => {
                                resetPopup(prefix, suffix);
                                if (redirect) {
                                    window.location.href = redirect;
                                } else {
                                    window.location.reload();
                                }
                            }, 2000);

                        } else if (qrContainer.style.display != 'flex') {
                            retryId = '';
                        }
                        else if (retryNum < 180 && retryId == res.id) {
                            retryNum++;
                            checkPaymentStatus();
                        } else {
                            //TODO Invoice expired
                        }
                    })
                    .catch(error => {
                        console.error('Error checking payment status:', error);
                        retryNum++;
                        if (retryId == res.id) {
                            setTimeout(checkPaymentStatus, 5000);
                        }
                    });
            }
            checkPaymentStatus()

        }
        else {
            console.error('Error creating invoice')
        }

    });

}
