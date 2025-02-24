
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


async function createCPT(amount, message, name, invoiceId) {
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



const handleButtonClick = (buttonId, honeypotId, amountId, satoshiId, messageId, lastInputCurency, name) => {
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

    const currency = lastInputCurency.toUpperCase();
    const amount = currency === 'SATS' ? satsAmount : fiatAmount;

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

const updateValueField = (amount, fieldName, operation, exchangeRates, currency) => {
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
    } else if (fieldName?.includes('bitcoin-donation-satoshi-multi') && !isNaN(amount) && currencyRate) {
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

    if (window.location.href.includes("localhost")) {
        sharedData.redirectUrl = "https://coinsnap.io";
    }

    const requestData = {
        amount: amount,
        currency: lastInputCurrency,
        redirectUrl: sharedData?.redirectUrl || window.location.href,
        redirectAutomatically: true,
        metadata: {
            orderNumber: message,
            referralCode: 'D19833',
            type: type,
            name: name
        }
    };
    if (coinsnap) {
        requestData.referralCode = 'D19833';
    }
    if (type == 'Bitcoin Voting') {
        requestData.metadata.optionId = metadata.optionId
        requestData.metadata.option = metadata.option
        requestData.metadata.pollId = metadata.pollId
        requestData.metadata.orderNumber = `Voted for ${metadata.option}`
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
            createCPT(`${amount} ${lastInputCurrency}`, message, name, responseData.id);
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

        // restricts input to numeric keys 0-9
        inp.addEventListener('keypress', function (e) {
            var event = e || window.event;
            var target = event.target;

            if (event.charCode == 0) {
                return;
            }

            if (-1 == numericKeys.indexOf(event.key)) {
                // Could notify the user that 0-9 is only acceptable input.
                event.preventDefault();
                return;
            }
        });

        inp.addEventListener('blur', function (e) {
            var event = e || window.event;
            var target = event.target;
            var tmp = removeThousandSeparator(target.value)
            var original = tmp
            if (inputFieldName.includes("multi")) {
                tmp = parseFloat(tmp)
                original = original.replace(tmp, "")
            }
            var val = Number(tmp).toLocaleString();

            if (tmp == '') {
                target.value = '';
            } else {
                target.value = inputFieldName.includes("multi") ? `${val}${original}` : val;
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
