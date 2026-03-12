function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/;';
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function setCookie(name, value, minutes) {
    const d = new Date();
    d.setTime(d.getTime() + (minutes * 60 * 1000));
    const expires = "expires=" + d.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

const addErrorDonationField = (field,message) => {
    field.classList.add('error');
    
    if(message !== ''){
        if(jQuery(field).next('.donation-field-error').length){
            jQuery(field).next('.donation-field-error').html(message);
        }
        else {
            jQuery(field).after('<span class="donation-field-error">'+message+'</span>');
        }
        
    }
    removeDonationBorderOnFocus(field, field);
    
    
    field.css('border', '1px solid red');
    removeBorderOnFocus(field, field)
}

const removeDonationBorderOnFocus = (field1, field2) => {
    field1.addEventListener('focus', function () {
        field2.classList.remove('error');
        jQuery(field1).next('.crowdfunding-field-error').remove();
        jQuery(field2).next('.crowdfunding-field-error').remove();
    });
};

const removeBorderOnFocus = (field1, field2) => {
    field1.on('focus', function () {
        field2.css('border', '');
    })

}

handleSnapClick = (buttonId, honeypotId, amountId, messageId, currency) => {
    const button = document.getElementById(buttonId)
    button.disabled = true;
    event.preventDefault();
    const honeypot = document.getElementById(honeypotId)
    if (honeypot && honeypot.value) {
        event.preventDefault();
        return
    }
    const amount = cleanDonationAmount(document.getElementById(amountId).textContent)
    const messageField = document.getElementById(messageId)
    const message = messageField ? messageField.value : ""
    createDonationInvoice(amount, message, undefined, 'Multi Amount Donation');
}

const handleDonationButtonClick = (buttonId, honeypotId, amountId, satoshiId, messageId, lastInputCurrency, name) => {
    event.preventDefault();

    const button = document.getElementById(buttonId);
    button.disabled = true;

    const honeypot = document.getElementById(honeypotId);
    if (honeypot && honeypot.value) {
        return;
    }

    const amountField = document.getElementById(amountId);
    const fiatAmount = cleanDonationAmount(amountField.value);

    let satoshiField = null;
    let satsAmount = null;

    if (satoshiId) {
        satoshiField = document.getElementById(satoshiId);
        satsAmount = cleanDonationAmount(satoshiField.value);
    }

    if (isNaN(fiatAmount) && isNaN(satsAmount)) {
        button.disabled = false;
        addErrorDonationField(amountField);
        if (satoshiField) {
            addErrorDonationField(satoshiField);
            removeBorderOnFocus(satoshiField, amountField);
            removeBorderOnFocus(amountField, satoshiField);
        }
        return;
    }

    const messageField = document.getElementById(messageId);
    const message = messageField ? messageField.value : "";

    const currency = lastInputCurrency.toUpperCase();
    const amount = currency === 'SATS' ? satsAmount : fiatAmount;
    if (!isNaN(amount) && amount > 0) {
        const type = name ? 'Shoutout Donation' : 'Donation Button';
        createDonationInvoice(amount, message, name, type);
    } else {
        button.disabled = false;
    }
};

const handleDonationButtonClickMulti = (buttonId, honeypotId, amountId, messageId, lastInputCurrency, name) => {
    const button = document.getElementById(buttonId);
    button.disabled = true;
    event.preventDefault();
    const honeypot = document.getElementById(honeypotId);
    if (honeypot && honeypot.value) {
        event.preventDefault();
        return;
    }
    const amountField = document.getElementById(amountId);
    const fiatAmount = cleanDonationAmount(amountField.value);

    if (!fiatAmount) {
        button.disabled = false;
        addErrorDonationField(amountField);
        event.preventDefault();
        return;
    }

    const messageField = document.getElementById(messageId);
    const message = messageField ? messageField.value : "";
    const currency = lastInputCurrency.toUpperCase();
    const amount = fiatAmount;
    if (amount) {
        createDonationInvoice(amount, message, name, 'Multi Amount Donation');
    }
};

const updateDonationValueField = (amount, fieldName, operation, exchangeRates, currency, multi = false) => {
    const currencyRate = 1;//exchangeRates[currency?.toUpperCase()];
    const field = document.getElementById(fieldName);

    if (!field) {
        console.error(`Field with ID "${fieldName}" not found.`);
        return;
    }
    if (multi && !isNaN(amount) && currencyRate) {
        const value = operation === '*' ? amount * currencyRate : amount / currencyRate;
        const decimals = value > 1000 ? 4 : 8;
        const valueDecimal = value.toFixed(operation === '*' ? decimals : 0);
        const newVal = addNumSeparators(valueDecimal);
        field.textContent = newVal;
        return;
    }
    if (!isNaN(amount) && currencyRate) {
        const value = operation === '*' ? amount * currencyRate : amount / currencyRate;
        const valueDecimal = value.toFixed(operation === '*' ? 8 : 0);
        const newVal = addNumSeparators(valueDecimal);
        field.value = newVal;
    } else {
        field.value = '';
    }
};

async function generateQRCodeDataURL(text) {
    try {
        console.log('Invoice URL: ' + encodeURIComponent(text));
        const response = await fetch(`https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(text)}`);
        const blob = await response.blob();
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    } catch (error) {
        console.error('Error generating QR code:', error);
        return null;
    }
};

const createActualDonationInvoice = async (amount, message, lastInputCurrency, name, coinsnap, type, redirect, metadata) => {
    deleteCookie('coinsnap_invoice_donation');
    
    var provider = (coinsnap)? 'coinsnap' : 'btcpay';
    var orderId = 'DNTN_' + (Date.now()).toString(36);
    
    if(provider === 'btcpay'){
        metadata.orderId = orderId;
    }

    const requestData = {
        amount: amount,
        currency: lastInputCurrency,
        buyerEmail: metadata.donorEmail,
        redirectAutomatically: true,
        checkout: {
            redirectAutomatically: true
        },
        orderId: orderId,
        walletMessage: message,
        metadata: {
            orderNumber: message,
            type: type,
            name: name,
            ...metadata
        }
    };
    
    requestData.provider = requestData.metadata.provider = provider;

    if (type === 'Bitcoin Donation') {
        requestData.redirectUrl = coinsnapDonationSharedData?.redirectUrl || window.location.href;
    }
    else if (type === 'Bitcoin Shoutout') {
        requestData.redirectUrl = coinsnapDonationSharedData?.shoutoutRedirectUrl || window.location.href;
    } 
    else if (type === 'Multi Amount Donation') {
        requestData.redirectUrl = coinsnapDonationSharedData?.multiRedirectUrl || window.location.href;
    }

    if (window.location.href.includes("localhost")) {
        requestData.redirectUrl = "https://coinsnap.io";
    }

    if (coinsnap) {
        requestData.referralCode = 'D19833';
    }

    const url = (provider === 'coinsnap')
        ? `https://app.coinsnap.io/api/v1/stores/${coinsnapDonationSharedData?.coinsnapStoreId}/invoices`
        : `${coinsnapDonationSharedData?.btcpayUrl}/api/v1/stores/${coinsnapDonationSharedData?.btcpayStoreId}/invoices`;

    const headers = (provider === 'coinsnap')
        ? {
            'x-api-key': coinsnapDonationSharedData?.coinsnapApiKey,
            'Content-Type': 'application/json'
        }
        : {
            'Authorization': `token ${coinsnapDonationSharedData?.btcpayApiKey}`,
            'Content-Type': 'application/json'
        };

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(requestData)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const responseData = await response.json();

        const invoiceCookieData = {
            id: responseData.id,
            amount: amount,
            currency: lastInputCurrency,
            checkoutLink: responseData.checkoutLink,
            message: message,
            name: name
        };

        setCookie('coinsnap_invoice_donation', JSON.stringify(invoiceCookieData), 15);
        
        if (!coinsnap) {
            const url = `${coinsnapDonationSharedData?.btcpayUrl}/api/v1/stores/${coinsnapDonationSharedData?.btcpayStoreId}/invoices/${responseData.id}/payment-methods`;
            const response2 = await fetch(url, {
                method: 'GET',
                headers: headers
            });
            const responseData2 = await response2.json();
            const paymentLink = responseData2[0].paymentLink;
            console.log('Payment Link:', paymentLink);
            responseData.lightningInvoice = paymentLink?.replace('lightning:', '');
            responseData.onchainAddress = '';

            // Generate QR code image from lightning invoice
            const qrCodeImage = await generateQRCodeDataURL(paymentLink);
            responseData.qrCodes = {
                lightningQR: qrCodeImage || paymentLink
            };
        }

        if (redirect) {
            window.location.href = responseData.checkoutLink;
        }

        return responseData;
    } catch (error) {
        console.error('Error creating invoice:', error);
        return null;
    }
};

const checkInvoiceStatus = async (invoiceId, amount, message, lastInputCurrency, name, coinsnap, type, redirect, metadata) => {

    const url = coinsnap
        ? `https://app.coinsnap.io/api/v1/stores/${coinsnapDonationSharedData.coinsnapStoreId}/invoices/${invoiceId}`
        : `${coinsnapDonationSharedData.btcpayUrl}/api/v1/stores/${coinsnapDonationSharedData.btcpayStoreId}/invoices/${invoiceId}`;

    const headers = coinsnap
        ? {
            'x-api-key': coinsnapDonationSharedData.coinsnapApiKey,
            'Content-Type': 'application/json'

        }
        : {
            'Authorization': `token ${coinsnapDonationSharedData.btcpayApiKey}`,
            'Content-Type': 'application/json'
        };

    try {
        const response = await fetch(url, {
            method: 'GET',
            headers: headers
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const responseData = await response.json();
        
        if (!coinsnap) {
            const url = `${coinsnapDonationSharedData?.btcpayUrl}/api/v1/stores/${coinsnapDonationSharedData?.btcpayStoreId}/invoices/${responseData.id}/payment-methods`;
            const response2 = await fetch(url, {
                method: 'GET',
                headers: headers
            });
            const responseData2 = await response2.json();
            const paymentLink = responseData2[0].paymentLink;
            console.log('Payment Link:', paymentLink);
            responseData.lightningInvoice = paymentLink?.replace('lightning:', '');
            responseData.onchainAddress = '';

            // Generate QR code image from lightning invoice
            const qrCodeImage = await generateQRCodeDataURL(paymentLink);
            responseData.qrCodes = {
                lightningQR: qrCodeImage || paymentLink
            }
        }

        if (responseData?.status === 'Settled') {
            return await createActualDonationInvoice(amount, message, lastInputCurrency, name, coinsnap, type, redirect, metadata);
        } else if (responseData?.status === 'New') {
            if (redirect) {
                window.location.href = responseData.checkoutLink;
            }
            return responseData;
        }

    } catch (error) {
        console.error('Error creating invoice:', error);
        return null;
    }
};

//  Invoice creation
const createDonationInvoice = async (amount, message, name, type, redirect = true, metadata) => {
    existingInvoice = getCookie('coinsnap_invoice_donation');
    lastInputCurrency = 'SATS';
    if (existingInvoice) {
        invoiceJson = JSON.parse(existingInvoice)
        if (
            invoiceJson.id &&
            invoiceJson.checkoutLink &&
            invoiceJson.amount == amount &&
            invoiceJson.currency == lastInputCurrency &&
            invoiceJson.message == message &&
            invoiceJson.name == name
        ) {
            const cs = await checkInvoiceStatus(
                invoiceJson.id,
                amount,
                message,
                lastInputCurrency,
                name,
                coinsnapDonationSharedData.provider === 'coinsnap',
                type,
                redirect,
                metadata
            )
            return cs
        }
        else {
            return await createActualDonationInvoice(
                amount,
                message,
                lastInputCurrency,
                name,
                coinsnapDonationSharedData.provider === 'coinsnap',
                type,
                redirect,
                metadata
            )
        }
    } else {
        return await createActualDonationInvoice(
            amount,
            message,
            lastInputCurrency,
            name,
            coinsnapDonationSharedData.provider === 'coinsnap',
            type,
            redirect,
            metadata
        )
    }
}

const addNumSeparators = (amount) => {
    var tmp = removeThousandSeparator(amount)
    var val = Number(tmp).toLocaleString("en-GB");

    if (tmp === '') {
        return '';
    } else {
        return val;
    }

}

const getThousandSeparator = () => {
    return (1000).toLocaleString("en-GB").replace(/\d/g, '')[0];
}

const removeThousandSeparator = (amount) => {
    const sep = getThousandSeparator()
    return amount?.replace(new RegExp(`\\${sep}`, 'g'), '');

}

const cleanDonationAmount = (amount) => {
    return parseFloat(removeThousandSeparator(amount))

}

const formatNumericInput = (target) => {
    var tmp = removeThousandSeparator(target.value);
    var original = tmp;
    tmp = parseFloat(tmp);
    original = original.replace(tmp, "");
    var val = Number(tmp).toLocaleString("en-GB");
    if (isNaN(tmp) || tmp === '') {
        target.value = '';
    } else {
        target.value = `${val}${original}`;
    }
}

const unformatNumericInput = (target) => {
    var val = removeThousandSeparator(target.value);
    target.value = val;
}

const NumericInput = (inputFieldName) => {
    const inp = document.getElementById(inputFieldName)
    if (inp) {
        const sep = (getThousandSeparator() === ".")? "," : ".";
        var numericKeys = `0123456789${sep}`;

        inp.addEventListener('keypress', function (e) {
            var event = e || window.event;
            var target = event.target;

            if (event.charCode === 0) {
                return;
            }
            if (`${inp.value}`.includes(sep) && event.key === sep) {
                event.preventDefault();
                return;
            }
            if (-1 === numericKeys.indexOf(event.key)) {
                event.preventDefault();
                return;
            }
        });

        inp.addEventListener('blur', function (e) {
            var event = e || window.event;
            formatNumericInput(event.target);
        });

        inp.addEventListener('focus', function (e) {
            var event = e || window.event;
            unformatNumericInput(event.target);
        });
        unformatNumericInput(inp);
        formatNumericInput(inp);
    }
}

const limitCursorMovement = (e, primaryCurrency) => {
    const field = e.target;
    //console.log('Amount field: ' + field.id);
    const position = field.selectionStart;
    const satsOffset = primaryCurrency === 'sats' ? 5 : 4;
    const satsStart = field.value.length - satsOffset;

    if (field.value.includes(primaryCurrency)) {
        // Handle click events
        if (e.type === 'click' && position >= satsStart) {
            let value = field.value.replace(` ${primaryCurrency}`, '');
            field.setSelectionRange(value.length, value.length);
        }

        // Handle keydown events
        if (e.type === 'keydown') {
            // For regular or Command/Ctrl + right arrow
            if ((e.key === 'ArrowRight' || e.key === 'End') &&
                (position >= satsStart || (field.selectionEnd > field.selectionStart))) {
                e.preventDefault();
                field.setSelectionRange(satsStart, satsStart);
            }

            // Specifically for Command/Ctrl + right arrow
            if ((e.metaKey || e.ctrlKey) && e.key === 'ArrowRight') {
                e.preventDefault();
                field.setSelectionRange(satsStart, satsStart);
            }
        }
    }
}

const hideDonationElementById = (id, prefix = '', sufix = '') => {
    document.getElementById(`${prefix}${id}${sufix}`).style.display = 'none';
};

const hideDonationElementsById = (ids, prefix = '', sufix = '') => {
    ids.forEach(id => {
        hideDonationElementById(id, prefix, sufix);
    });
};

const showDonationElementById = (id, display, prefix = '', sufix = '') => {
    document.getElementById(`${prefix}${id}${sufix}`).style.display = display;
};

const showDonationElementsById = (ids, display, prefix = '', sufix = '') => {
    ids.forEach(id => {
        showDonationElementById(id, display, prefix, sufix);
    });
};