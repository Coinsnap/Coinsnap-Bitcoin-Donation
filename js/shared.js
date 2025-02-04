
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
    // Ensure that the nonce is available for authentication
    const nonce = sharedData.nonce;
    // Construct the request payload
    const data = {
        title: `Shoutout from ${name}`,
        status: "pending",
        meta: {
            _bitcoin_donation_shoutouts_name: name,
            _bitcoin_donation_shoutouts_amount: parseFloat(amount),
            _bitcoin_donation_shoutouts_invoice_id: invoiceId,
            _bitcoin_donation_shoutouts_message: message,
        }
    };


    try {
        const response = await fetch('/wp-json/wp/v2/bitcoin-shoutouts', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": nonce, // Required for authentication
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const result = await response.json();
        alert("Shoutout successfully created!");
    } catch (error) {
        console.error("Error creating shoutout:", error);
        alert("Failed to create shoutout. Please try again.");
    }
}


const updateValueField = (amount, fieldName, operation, exchangeRates) => {
    const currencyRate = exchangeRates[sharedData.currency?.toUpperCase()];
    const field = document.getElementById(fieldName);

    if (!field) {
        console.error(`Field with ID "${fieldName}" not found.`);
        return;
    }
    if (fieldName == 'bitcoin-donation-shoutout-satoshi') { // Min and Premium shoutout amoutns
        const minAmount = sharedData.minimumShoutoutAmount
        const premiumAmount = sharedData.premiumShoutoutAmount
        const shoutButton = document.getElementById('bitcoin-donation-shout')
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
    }

    if (!isNaN(amount) && currencyRate) {
        const value = operation == '*' ? amount * currencyRate : amount / currencyRate;
        field.value = value.toFixed(operation == '*' ? 8 : 0);
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



const createActualInvoice = (amount, message, lastInputCurency, name, coinsnap) => {
    deleteCookie('coinsnap_invoice_')
    if (window.location.href.includes("localhost")) {
        sharedData.redirectUrl = "https://coinsnap.io"
    }
    const requestData = {
        amount: amount,
        currency: lastInputCurency,
        redirectUrl: sharedData.redirectUrl || window.location.href,
        redirectAutomatically: true,
        metadata: {
            orderNumber: message,
            referralCode: 'D19833',
            type: name ? 'Bitcoin Shoutout' : 'Bitcoin Donation',
            name: name
        }
    };
    if (coinsnap) {
        requestData.referralCode = 'D19833';
    }
    const url = coinsnap
        ? `https://app.coinsnap.io/api/v1/stores/${sharedData.coinsnapStoreId}/invoices`
        : `${sharedData.btcpayUrl}/api/v1/stores/${sharedData.btcpayStoreId}/invoices`;

    const headers = coinsnap
        ? {
            'x-api-key': sharedData.coinsnapApiKey,
            'Content-Type': 'application/json'
        }
        : {
            'Authorization': `token ${sharedData.btcpayApiKey}`,
            'Content-Type': 'application/json'
        };


    fetch(
        url,
        {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(requestData)
        }
    )
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(response => {
            const invoiceCookieData = {
                id: response.id,
                amount: amount,
                currency: lastInputCurency,
                checkoutLink: response.checkoutLink,
                message: message,
                name: name
            };
            // createCPT(amount, message, name, 'response.id') // TODO unstring
            setCookie('coinsnap_invoice_', JSON.stringify(invoiceCookieData), 15);
            window.location.href = response.checkoutLink;
        })
        .catch(error => {
            console.error('Error creating invoice:', error);
        });

}


function checkInvoiceStatus(invoiceId, amount, message, lastInputCurency, name, coinsnap) {
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

    fetch(url, {
        method: 'GET',
        headers: headers
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(response => {
            if (response?.status === 'Settled') {
                createActualInvoice(amount, message, lastInputCurency, name, coinsnap);
            } else if (response?.status === 'New') {
                window.location.href = response.checkoutLink;
            }
        })
        .catch(error => {
            console.error('Error checking invoice status:', error);
        });
}


const createInvoice = (amount, message, lastInputCurency, name) => {
    existingInvoice = getCookie('coinsnap_invoice_')
    if (existingInvoice) {
        invoiceJson = JSON.parse(existingInvoice)
        if (
            invoiceJson.id &&
            invoiceJson.checkoutLink &&
            invoiceJson.amount == amount &&
            invoiceJson.currency == lastInputCurency &&
            invoiceJson.message == message &&
            invoiceJson.name == name
        ) {
            checkInvoiceStatus(
                invoiceJson.id,
                amount,
                message,
                lastInputCurency,
                name,
                sharedData.provider == 'coinsnap')
        }
        else {
            createActualInvoice(
                amount,
                message,
                lastInputCurency,
                name,
                sharedData.provider == 'coinsnap')
        }
    } else {
        createActualInvoice(
            amount,
            message,
            lastInputCurency,
            name,
            sharedData.provider == 'coinsnap')
    }
}

