/**
 * Coinsnap Bitcoin Donation - Iframe Modal & Payment Submission
 */

const checkDonationRequiredFieds = (fields) => {
    let valid = true;
    fields.forEach((field) => {
        if (field && field.required && !field.value.trim()) {
            valid = false;
            field.classList.add('error');
            setTimeout(() => { field.classList.remove('error'); }, 3000);
        }
    });
    return valid;
};

function createDonationModal() {
    var backdrop = document.createElement('div');
    backdrop.className = 'coinsnap-donation-modal-backdrop';

    var modal = document.createElement('div');
    modal.className = 'coinsnap-donation-modal';

    var iframe = document.createElement('iframe');
    iframe.className = 'coinsnap-donation-payment-iframe';
    iframe.style.cssText = 'width:100%;height:100%;border:none;background:#fff;';

    modal.appendChild(iframe);
    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);

    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
            backdrop.pollingActive = false;
            backdrop.style.display = 'none';
        }
    });

    return { backdrop: backdrop, frame: iframe };
}

function startDonationPaymentPolling(invoiceId, modal, redirectUrl) {
    var tries = 0;
    var maxTries = 120;

    function step() {
        if (!modal.backdrop.pollingActive) return;
        tries++;

        jQuery.ajax({
            url: coinsnapDonationSharedData.restUrl + 'status/' + encodeURIComponent(invoiceId),
            method: 'GET',
            headers: { 'X-WP-Nonce': coinsnapDonationSharedData.nonce }
        })
        .done(function (response) {
            if (response && response.success && response.data && response.data.paid) {
                modal.backdrop.style.display = 'none';
                modal.backdrop.pollingActive = false;
                setTimeout(function () {
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        window.location.reload();
                    }
                }, 1500);
                return;
            }
            if (tries < maxTries) {
                setTimeout(step, 1000);
            }
        })
        .fail(function () {
            if (tries < maxTries) {
                setTimeout(step, 1500);
            }
        });
    }

    modal.backdrop.pollingActive = true;
    step();
}

function submitDonationPayment(amount, currency, message, formType, redirectUrl, metadata) {
    var requestData = {
        amount: amount,
        currency: currency,
        message: message,
        formType: formType,
        redirectUrl: redirectUrl,
        metadata: metadata
    };

    jQuery.ajax({
        url: coinsnapDonationSharedData.restUrl + 'payment/create',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(requestData),
        headers: { 'X-WP-Nonce': coinsnapDonationSharedData.nonce }
    })
    .done(function (response) {
        if (response.success) {
            // Hide the donor form popup and overlay before showing iframe modal
            jQuery('.qr-container').hide();
            jQuery('.blur-overlay').hide();

            var modal = createDonationModal();
            modal.frame.src = response.payment_url;
            modal.backdrop.style.display = 'flex';
            startDonationPaymentPolling(response.invoice_id, modal, response.redirect_url);
        } else {
            alert(response.message || 'Payment creation failed.');
        }
    })
    .fail(function (xhr) {
        var message = 'An error occurred. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }
        alert(message);
    });
}

const addDonationPopupListener = (prefix, suffix, type, redirect) => {

    const resetPopup = (prefix, suffix) => {
        hideDonationElementsById(['qr-container', 'blur-overlay', 'payment-loading'], prefix, suffix);
        showDonationElementById('public-donor-popup', 'flex', prefix, suffix);
        const button = document.getElementById(`${prefix}pay${suffix}`);
        if (button) button.disabled = false;
    };

    window.addEventListener("click", function (event) {
        const qrContainer = document.getElementById(`${prefix}qr-container${suffix}`);
        if (!qrContainer) return;
        const element = event.target;
        if (qrContainer.style.display === 'flex') {
            if (element.classList.contains('close-popup') || (!qrContainer.contains(event.target) && !element.id.includes('pay'))) {
                resetPopup(prefix, suffix);
            }
        }
    });

    document.getElementById(`${prefix}pay${suffix}`).addEventListener('click', function (e) {
        const button = document.getElementById(`${prefix}pay${suffix}`);
        button.disabled = true;
        e.preventDefault();

        const honeypot = document.getElementById(`${prefix}email${suffix}`);
        if (honeypot && honeypot.value) return;

        const currency = document.getElementById(`${prefix}swap${suffix}`).value?.toUpperCase();
        const amountValue = document.getElementById(`${prefix}amount${suffix}`)?.value;
        const amountField = document.getElementById(`${prefix}amount${suffix}`);

        if (!amountValue || parseFloat(amountValue) === 0) {
            button.disabled = false;
            addErrorDonationField(amountField, '');
            return;
        }

        const publicDonor = document.getElementById(`${prefix}qr-container${suffix}`).dataset.publicDonors;
        if (!publicDonor) {
            const publicDonorsPay = document.getElementById(`${prefix}public-donors-pay${suffix}`);
            publicDonorsPay.click();
        }
        showDonationElementsById(['blur-overlay', 'qr-container'], 'flex', prefix, suffix);
    });

    document.getElementById(`${prefix}public-donors-pay${suffix}`).addEventListener('click', function (e) {
        e.preventDefault();
        const publicDonor = document.getElementById(`${prefix}qr-container${suffix}`).dataset.publicDonors;

        const messageField = document.getElementById(`${prefix}message${suffix}`);
        const message = messageField.value;

        const currencyField = document.getElementById(`${prefix}swap${suffix}`);
        const currency = currencyField.value?.toUpperCase();
        const currencyFiat = (currency === 'SATS') ? 'EUR' : currency;

        // Always read the primary amount from the amount input field
        var amount = cleanDonationAmount(document.getElementById(`${prefix}amount${suffix}`).value);

        // Secondary amount is the conversion shown below the input
        var amountFiat = cleanDonationAmount(
            (currency === 'SATS')
                ? document.getElementById(`${prefix}satoshi${suffix}`).getAttribute('data-value')
                : document.getElementById(`${prefix}amount${suffix}`).value
        );

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

        if (!validForm) return;

        showDonationElementById('payment-loading', 'flex', prefix, suffix);
        hideDonationElementById('public-donor-popup', prefix, suffix);

        var name = undefined;
        if (type === "Bitcoin Shoutout") {
            const nameField = document.getElementById(`${prefix}name${suffix}`);
            name = nameField?.value || "Anonymous";
        }

        const metadata = {
            donorName: `${firstNameField?.value ?? ''} ${lastNameField?.value ?? ''}`.trim(),
            donorEmail: emailField?.value || '',
            donorAddress: address !== ' ,  , ' ? address : '',
            donorMessage: message,
            donorCustom: customContent,
            donorOptOut: '0',
            formType: type,
            type: type,
            amount: `${amount}`,
            amountFiat: `${amountFiat} ${currencyFiat}`,
            publicDonor: publicDonor || '0',
            satsAmount: (currency === 'SATS') ? `${amount}` : '',
        };

        submitDonationPayment(amount, currency, message, type, redirect || window.location.href, metadata);
    });
};
