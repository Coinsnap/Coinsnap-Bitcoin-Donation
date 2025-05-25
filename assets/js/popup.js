// js/popup.js
const checkRequiredFieds = (fields) => {
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

const addDonationPopupListener = (prefix, sufix, type, exchangeRates, redirect) => {
    let walletHandler = null;

    const resetPopup = (prefix, sufix) => {
        hideElementsById(['qr-container', 'blur-overlay', 'payment-loading', 'payment-popup', 'thank-you-popup'], prefix, sufix)
        showElementById('public-donor-popup', 'flex', prefix, sufix)
        const button = document.getElementById(`${prefix}pay${sufix}`)
        button.disabled = false;
        const payInWalletBtn = document.getElementById(`${prefix}pay-in-wallet${sufix}`);
        if (walletHandler) {
            payInWalletBtn.removeEventListener('click', walletHandler);
            walletHandler = null;
        }
    }


    window.addEventListener("click", function (event) {
        const qrContainer = document.getElementById(`${prefix}qr-container${sufix}`);
        const element = event.target;
        if (qrContainer.style.display == 'flex') {
            if (element.classList.contains('close-popup') || (!qrContainer.contains(event.target) && !element.id.includes('pay'))) {
                resetPopup(prefix, sufix)
            }
        }
    });

    document.getElementById(`${prefix}pay${sufix}`).addEventListener('click', async () => {
        const button = document.getElementById(`${prefix}pay${sufix}`)
        button.disabled = true;
        event.preventDefault();
        const honeypot = document.getElementById(`${prefix}email${sufix}`);
        if (honeypot && honeypot.value) {
            return
        }
        const amountValue = document.getElementById(`${prefix}amount${sufix}`)?.value
        if (!amountValue) {
            button.disabled = false;
            addErrorField(amountField)
            return
        }
        const publicDonor = document.getElementById(`${prefix}qr-container${sufix}`).dataset.publicDonors;
        if (!publicDonor) {
            const publicDonorsPay = document.getElementById(`${prefix}public-donors-pay${sufix}`)
            publicDonorsPay.click()
        }
        showElementsById(['blur-overlay', 'qr-container'], 'flex', prefix, sufix)
    });

    document.getElementById(`${prefix}public-donors-pay${sufix}`).addEventListener('click', async () => {
        event.preventDefault();
        const publicDonor = document.getElementById(`${prefix}qr-container${sufix}`).dataset.publicDonors;
        var retryId = '';

        const amountField = document.getElementById(`${prefix}amount${sufix}`);
        const amount = cleanAmount(amountField.value);
        const messageField = document.getElementById(`${prefix}message${sufix}`);
        const message = messageField.value;
        const currencyField = document.getElementById(`${prefix}swap${sufix}`);
        const currency = currencyField.value?.toUpperCase();

        const firstNameField = document.getElementById(`${prefix}first-name${sufix}`);
        const lastNameField = document.getElementById(`${prefix}last-name${sufix}`);
        const emailField = document.getElementById(`${prefix}donor-email${sufix}`);
        const streetField = document.getElementById(`${prefix}street${sufix}`);
        const houseNumberField = document.getElementById(`${prefix}house-number${sufix}`);
        const postalCodeField = document.getElementById(`${prefix}postal${sufix}`);
        const cityField = document.getElementById(`${prefix}town${sufix}`);
        const countryField = document.getElementById(`${prefix}country${sufix}`);
        const address = `${streetField?.value ?? ''} ${houseNumberField?.value ?? ''}, ${postalCodeField?.value ?? ''} ${cityField?.value ?? ''}, ${countryField?.value ?? ''}`;
        // const optOutField = document.getElementById(`${prefix}opt-out${sufix}`);
        const customField = document.getElementById(`${prefix}custom${sufix}`);
        const customNameField = document.getElementById(`${prefix}custom-name${sufix}`);
        const customContent = customNameField?.textContent && customField?.value ? `${customNameField.textContent}: ${customField.value}` : ''
        const validForm = !publicDonor || checkRequiredFieds([firstNameField, lastNameField, emailField, streetField, houseNumberField, postalCodeField, cityField, countryField, customField]);
        const satsAmount = currency == 'SATS' ? amount : (amount / exchangeRates[currency]).toFixed(0);
        const metadata = {
            donorName: `${firstNameField.value} ${lastNameField?.value ?? ''}`,
            donorEmail: emailField?.value,
            donorAddress: address != ' ,  , ' ? address : '',
            donorMessage: message,
            donorCustom: customContent,
            formType: type,
            amount: `${amount} ${currency}`,
            publicDonor: publicDonor || 0,
            modal: true,
            satsAmount: satsAmount,
        }
        if (!validForm) return;

        showElementById('payment-loading', 'flex', prefix, sufix)
        hideElementById('public-donor-popup', prefix, sufix)

        var name = undefined;
        if (type == "Bitcoin Shoutout") {
            const nameField = document.getElementById(`${prefix}name${sufix}`);
            name = nameField?.value || "Anonymous";
        }

        const res = await createInvoice(amount, message, currency, name, type, false, metadata)

        if (res) {
            // Update addresses 
            const qrLightning = res.lightningInvoice
            const qrBitcoin = res.onchainAddress

            if (qrBitcoin) {
                showElementsById(['btc-wrapper', 'qr-btc-container'], 'flex', prefix, sufix)
            }

            // Hide spinner and show qr code stuff
            showElementsById(['qrCode', 'lightning-wrapper', 'qr-fiat', 'qrCodeBtc'], 'block', prefix, sufix)
            showElementsById(['qr-summary', 'qr-lightning-container', 'pay-in-wallet'], 'flex', prefix, sufix)
            hideElementById('payment-loading', prefix, sufix)
            showElementById('payment-popup', 'flex', prefix, sufix)
            // Update actuall data
            document.getElementById(`${prefix}qrCode${sufix}`).src = res.qrCodes.lightningQR;
            document.getElementById(`${prefix}qr-lightning${sufix}`).textContent = `${qrLightning.substring(0, 20)}...${qrLightning.slice(-15)}`;
            document.getElementById(`${prefix}qr-btc${sufix}`).textContent = `${qrBitcoin.substring(0, 20)}...${qrBitcoin.slice(-15)}`;
            document.getElementById(`${prefix}qr-amount${sufix}`).textContent = `Amount: ${res.amount} sats`;

            // Copy address functionallity 
            const copyLightning = document.querySelector(`#${prefix}qr-lightning-container${sufix} .qr-copy-icon`);
            const copyBtc = document.querySelector(`#${prefix}qr-btc-container${sufix} .qr-copy-icon`);
            copyLightning.addEventListener('click', () => { navigator.clipboard.writeText(qrLightning); });
            copyBtc.addEventListener('click', () => { navigator.clipboard.writeText(qrBitcoin); });

            // Add fiat amount
            if (exchangeRates['EUR']) {
                document.getElementById(`${prefix}qr-fiat${sufix}`).textContent = `≈ ${(res.amount * exchangeRates['EUR'])?.toFixed(3)} EUR`;
                document.getElementById(`${prefix}pay-in-wallet${sufix}`).setAttribute('href', `lightning:${qrLightning}`);

                //  Browser doesn't know how to redirect to unknown protocol
                //  Store the handler function when adding the listener
                //  walletHandler = function () {
                //      window.location.replace(`lightning:${qrLightning}`);
                //  };
                //document.getElementById(`${prefix}pay-in-wallet${sufix}`).addEventListener('click', walletHandler);

            }

            // Reset retry counter
            var retryNum = 0;
            retryId = res.id;

            const checkPaymentStatus = () => {
                fetch(`/wp-json/coinsnap-bitcoin-donation/v1/check-payment-status/${res.id}`)
                    .then(response => response.json())
                    .then(data => {
                        const qrContainer = document.getElementById(`${prefix}qr-container${sufix}`);

                        if (data.status === 'completed') {
                            showElementById('thank-you-popup', 'flex', prefix, sufix)
                            hideElementById('payment-popup', prefix, sufix)
                            setTimeout(() => {
                                resetPopup(prefix, sufix);
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
