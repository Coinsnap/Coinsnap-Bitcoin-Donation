
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

async function createCPT(amount, message, name, invoiceId, provider) {
    const nonce = sharedData.nonce;
    const data = {
        title: `Shoutout from ${name}`,
        status: "pending",
        name: name,
        _bitcoin_donation_shoutouts_name: name,
        meta: {
            _bitcoin_donation_shoutouts_name: name,
            _bitcoin_donation_shoutouts_amount: amount,
            _bitcoin_donation_shoutouts_invoice_id: invoiceId,
            _bitcoin_donation_shoutouts_message: message,
            _bitcoin_donation_shoutouts_provider: provider,
        }
    };

    try {
        const response = await fetch('/wp-json/wp/v2/bitcoin-shoutouts', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": nonce,
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        await response.json();
    } catch (error) {
        console.error("Error creating shoutout:", error);
    }
}

const addErrorField = (field) => {
    field.css('border', '1px solid red');
    removeBorderOnFocus(field, field)
}

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
    const amount = cleanAmount(document.getElementById(amountId).textContent)
    const messageField = document.getElementById(messageId)
    const message = messageField ? messageField.value : ""
    createInvoice(amount, message, currency.toUpperCase(), undefined, 'Multi Amount Donation');
}

const handleButtonClick = (buttonId, honeypotId, amountId, satoshiId, messageId, lastInputCurrency, name) => {
    event.preventDefault();

    const button = document.getElementById(buttonId);
    button.disabled = true;

    const honeypot = document.getElementById(honeypotId);
    if (honeypot && honeypot.value) {
        return;
    }

    const amountField = document.getElementById(amountId);
    const fiatAmount = cleanAmount(amountField.value);

    let satoshiField = null;
    let satsAmount = null;

    if (satoshiId) {
        satoshiField = document.getElementById(satoshiId);
        satsAmount = cleanAmount(satoshiField.value);
    }

    if (isNaN(fiatAmount) && isNaN(satsAmount)) {
        button.disabled = false;
        addErrorField(amountField);
        if (satoshiField) {
            addErrorField(satoshiField);
            removeBorderOnFocus(satoshiField, amountField);
            removeBorderOnFocus(amountField, satoshiField);
        }
        return;
    }

    const messageField = document.getElementById(messageId);
    const message = messageField ? messageField.value : "";

    const currency = lastInputCurrency.toUpperCase();
    const amount = currency === 'SATS' ? satsAmount : fiatAmount;
    console.log(satsAmount, fiatAmount)

    if (!isNaN(amount) && amount > 0) {
        const type = name ? 'Shoutout Donation' : 'Donation Button';
        createInvoice(amount, message, lastInputCurrency, name, type);
    } else {
        button.disabled = false;
    }
};

const handleButtonClickMulti = (buttonId, honeypotId, amountId, messageId, lastInputCurrency, name) => {
    const button = document.getElementById(buttonId)
    button.disabled = true;
    event.preventDefault();
    const honeypot = document.getElementById(honeypotId)
    if (honeypot && honeypot.value) {
        event.preventDefault();
        return
    }
    const amountField = document.getElementById(amountId)
    const fiatAmount = cleanAmount(amountField.value)

    if (!fiatAmount) {
        button.disabled = false;
        addErrorField(amountField)
        event.preventDefault();
        return
    }

    const messageField = document.getElementById(messageId)
    const message = messageField ? messageField.value : ""
    const currency = lastInputCurrency.toUpperCase()
    const amount = fiatAmount;
    if (amount) {
        createInvoice(amount, message, currency, name, 'Multi Amount Donation');
    }
}

const updateValueField = (amount, fieldName, operation, exchangeRates, currency, multi = false) => {
    const currencyRate = exchangeRates[currency?.toUpperCase()];
    const field = document.getElementById(fieldName);

    if (!field) {
        console.error(`Field with ID "${fieldName}" not found.`);
        return;
    }
    if (fieldName == 'bitcoin-donation-shoutout-satoshi') { // Min and Premium shoutout amoutns
        const minAmount = sharedData.minimumShoutoutAmount
        const premiumAmount = sharedData.premiumShoutoutAmount
        const satoshi = amount / currencyRate
        if (satoshi < minAmount) {
            field.style.color = '#e55e65';
            premiumAmount.disabled = true
        } else if (satoshi >= premiumAmount) {
            field.style.color = '#f7931a';
            premiumAmount.disabled = false
        } else {
            field.style.color = '';
            premiumAmount.disabled = false
        }
    } else if (multi && !isNaN(amount) && currencyRate) {
        const value = operation == '*' ? amount * currencyRate : amount / currencyRate;
        const decimals = value > 1000 ? 4 : 8;
        const valueDecimal = value.toFixed(operation == '*' ? decimals : 0);
        const newVal = addNumSeparators(valueDecimal)
        field.textContent = newVal;
        return
    }
    if (!isNaN(amount) && currencyRate) {
        const value = operation == '*' ? amount * currencyRate : amount / currencyRate;
        const valueDecimal = value.toFixed(operation == '*' ? 8 : 0);
        const newVal = addNumSeparators(valueDecimal)
        field.value = newVal
    } else {
        field.value = '';
    }
};

async function fetchCoinsnapExchangeRates() {
    const exchangeRates = {}
    try {
        const response = await fetch(`https://app.coinsnap.io/api/v1/stores/${sharedData.coinsnapStoreId}/rates`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': sharedData.coinsnapApiKey
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        data
            .filter(item => item.currencyPair.includes("SATS")) // Filter only SATS rates
            .forEach(item => {
                const currency = item.currencyPair.replace("SATS_", ""); // Remove "SATS_" prefix
                exchangeRates[currency] = parseFloat(item.rate); // Update exchangeRates
            });

        return exchangeRates;
    } catch (error) {
        console.error('Error fetching exchange rates:', error);
        return null;
    }
}

const createActualInvoice = async (amount, message, lastInputCurrency, name, coinsnap, type, redirect, metadata) => {
    deleteCookie('coinsnap_invoice_');

    const requestData = {
        amount: amount,
        currency: lastInputCurrency,
        redirectAutomatically: true,
        metadata: {
            orderNumber: message,
            referralCode: 'D19833',
            type: type,
            name: name
        }
    };

    if (type == 'Donation Button') {
        requestData.redirectUrl = sharedData?.redirectUrl || window.location.href
    } else if (type == 'Bitcoin Shoutout') {
        requestData.redirectUrl = sharedData?.shoutoutRedirectUrl || window.location.href
    } else if (type == 'Multi Amount Donation') {
        requestData.redirectUrl = sharedData?.multiRedirectUrl || window.location.href
    } else if (type == 'Bitcoin Voting') {
        requestData.metadata.optionId = metadata.optionId
        requestData.metadata.option = metadata.option
        requestData.metadata.pollId = metadata.pollId
        requestData.metadata.orderNumber = `Voted for ${metadata.option}`
        redirectAutomatically = false //TODO test
    }

    if (window.location.href.includes("localhost")) {
        requestData.redirectUrl = "https://coinsnap.io";
    }

    if (coinsnap) {
        requestData.referralCode = 'D19833';
    }

    const url = coinsnap
        ? `https://app.coinsnap.io/api/v1/stores/${sharedData?.coinsnapStoreId}/invoices`
        : `${sharedData?.btcpayUrl}/api/v1/stores/${sharedData?.btcpayStoreId}/invoices`;

    const headers = coinsnap
        ? {
            'x-api-key': sharedData?.coinsnapApiKey,
            'Content-Type': 'application/json'
        }
        : {
            'Authorization': `token ${sharedData?.btcpayApiKey}`,
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

        if (name) {
            createCPT(`${amount} ${lastInputCurrency}`, message, name, responseData.id, coinsnap ? 'coinsnap' : 'btcpay');
        }

        setCookie('coinsnap_invoice_', JSON.stringify(invoiceCookieData), 15);

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
        ? `https://app.coinsnap.io/api/v1/stores/${sharedData.coinsnapStoreId}/invoices/${invoiceId}`
        : `${sharedData.btcpayUrl}/api/v1/stores/${sharedData.btcpayStoreId}/invoices/${invoiceId}`;

    const headers = coinsnap
        ? {
            'x-api-key': sharedData.coinsnapApiKey,
            'Content-Type': 'application/json'

        }
        : {
            'Authorization': `token ${sharedData.btcpayApiKey}`,
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

        if (responseData?.status === 'Settled') {
            return await createActualInvoice(amount, message, lastInputCurrency, name, coinsnap, type, redirect, metadata);
        } else if (responseData?.status === 'New') {
            if (redirect) {
                window.location.href = responseData.checkoutLink;
            }
            return responseData
        }

    } catch (error) {
        console.error('Error creating invoice:', error);
        return null;
    }
};

const createInvoice = async (amount, message, lastInputCurrency, name, type, redirect = true, metadata) => {
    existingInvoice = getCookie('coinsnap_invoice_')
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
                sharedData.provider == 'coinsnap',
                type,
                redirect,
                metadata
            )
            return cs
        }
        else {
            return await createActualInvoice(
                amount,
                message,
                lastInputCurrency,
                name,
                sharedData.provider == 'coinsnap',
                type,
                redirect,
                metadata
            )
        }
    } else {
        return await createActualInvoice(
            amount,
            message,
            lastInputCurrency,
            name,
            sharedData.provider == 'coinsnap',
            type,
            redirect,
            metadata
        )
    }
}

const addNumSeparators = (amount) => {
    var tmp = removeThousandSeparator(amount)
    var val = Number(tmp).toLocaleString();

    if (tmp == '') {
        return '';
    } else {
        return val;
    }

}

const getThousandSeparator = () => {
    return (1000).toLocaleString().replace(/\d/g, '')[0];
}

const removeThousandSeparator = (amount) => {
    const sep = getThousandSeparator()
    return amount?.replace(new RegExp(`\\${sep}`, 'g'), '');

}

const cleanAmount = (amount) => {
    return parseFloat(removeThousandSeparator(amount))

}

const NumericInput = (inputFieldName) => {
    const inp = document.getElementById(inputFieldName)
    if (inp) {
        const sep = getThousandSeparator() == "." ? "," : ".";
        var numericKeys = `0123456789${sep}`;

        inp.addEventListener('keypress', function (e) {
            var event = e || window.event;
            var target = event.target;

            if (event.charCode == 0) {
                return;
            }

            if (-1 == numericKeys.indexOf(event.key)) {
                event.preventDefault();
                return;
            }
        });

        inp.addEventListener('blur', function (e) {
            var event = e || window.event;
            var target = event.target;
            var tmp = removeThousandSeparator(target.value)
            var original = tmp
            tmp = parseFloat(tmp)
            original = original.replace(tmp, "")
            var val = Number(tmp).toLocaleString();

            if (tmp == '') {
                target.value = '';
            } else {
                target.value = `${val}${original}`
            }
        });

        inp.addEventListener('focus', function (e) {
            var event = e || window.event;
            var target = event.target;
            var val = removeThousandSeparator(target.value)

            target.value = val;
        });
    }
}

const limitCursorMovement = (e, primaryCurrency) => {
    const field = e.target;
    const position = field.selectionStart;
    const satsOffset = primaryCurrency === 'sats' ? 5 : 4;
    const satsStart = field.value.length - satsOffset;

    if (field.value.includes(primaryCurrency)) {
        if (e.type === 'click' && position >= satsStart) {
            let value = field.value.replace(` ${primaryCurrency}`, '');
            field.setSelectionRange(value.length, value.length);
        }

        if (e.type === 'keydown' && (e.key === 'ArrowRight' || e.key === 'End') && position >= satsStart) {
            e.preventDefault();
            field.setSelectionRange(satsStart, satsStart);
        }
    }
}
