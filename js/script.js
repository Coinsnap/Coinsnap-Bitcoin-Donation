// js/script.js
jQuery(document).ready(function ($) {
    const exchangeRates = {};
    var lastInputCurency = bitcoinDonationData.currency // Used to detrmine if invoice should be created in by fiat or crypto

    const setDefaults = () => {
        const amountField = $('#bitcoin-donation-amount');
        amountField.val(bitcoinDonationData.defaultAmount);
        updateSatoshiField(bitcoinDonationData.defaultAmount);
        const messageField = $('#bitcoin-donation-message');
        messageField.val(bitcoinDonationData.defaultMessage);
    }

    function fetchCoinsnapExchangeRates() {
        $.ajax({
            url: `https://app.coinsnap.io/api/v1/stores/${bitcoinDonationData.coinsnapStoreId}/rates`,
            method: 'GET',
            contentType: 'application/json',
            headers: {
                'x-api-key': bitcoinDonationData.coinsnapApiKey
            },
            success: (response) => {
                response
                    .filter(item => item.currencyPair.includes("SATS")) // Filter only SATS rates
                    .forEach(item => {
                        const currency = item.currencyPair.replace("SATS_", ""); // Remove "SATS_" prefix
                        exchangeRates[currency] = parseFloat(item.rate); // Update the exchangeRates
                    });
                setDefaults();

            },
            error: (error) => {
                console.error('Error fetching exchange rates:', error);
            }
        });
    };

    const createCoinsnapInvoice = (amount, message) => {
        deleteCookie('coinsnap_invoice_')
        $.ajax({
            url: `https://app.coinsnap.io/api/v1/stores/${bitcoinDonationData.coinsnapStoreId}/invoices`,
            type: 'POST',
            contentType: 'application/json',
            headers: {
                'x-api-key': bitcoinDonationData.coinsnapApiKey
            },
            data: JSON.stringify({
                amount: amount,
                currency: lastInputCurency,
                redirectUrl: bitcoinDonationData.redirectUrl ?? window.location.href,
                redirectAutomatically: true,
                metadata: {
                    orderNumber: message,
                    referralCode: 'D19833',
                    type: 'Bitcoin Donation'
                },
                referralCode: 'D19833'
            }),
            success: (response) => {
                invoiceCookieData = {
                    'id': response.id,
                    'amount': amount,
                    'currency': lastInputCurency,
                    'checkoutLink': response.checkoutLink,
                    'message': message
                }
                setCookie('coinsnap_invoice_', JSON.stringify(invoiceCookieData), 15)
                window.location.href = response.checkoutLink
            },
            error: (error) => {
                console.error('Error creating invoice:', error);
            }
        });
    }

    function coinsnapInvoiceStatus(invoiceId, amount, message) {
        $.ajax({
            url: `https://app.coinsnap.io/api/v1/stores/${bitcoinDonationData.coinsnapStoreId}/invoices/${invoiceId}`,
            method: 'GET',
            contentType: 'application/json',
            headers: {
                'x-api-key': bitcoinDonationData.coinsnapApiKey
            },
            success: (response) => {
                if (response?.status === 'Settled') {
                    createCoinsnapInvoice(amount, message)
                } else if (response?.status === 'New') {
                    window.location.href = response.checkoutLink;
                }
            },
            error: (error) => {
                console.error('Error checking invoice status:', error);
            }
        });
    }

    const createBTCPayInvoice = (amount, message) => {
        deleteCookie('coinsnap_invoice_')
        $.ajax({
            url: `${bitcoinDonationData.btcpayUrl}/api/v1/stores/${bitcoinDonationData.btcpayStoreId}/invoices`,
            type: 'POST',
            contentType: 'application/json',
            headers: {
                'Authorization': `token ${bitcoinDonationData.btcpayApiKey}`,
            },
            data: JSON.stringify({
                amount: amount,
                currency: lastInputCurency,
                redirectUrl: bitcoinDonationData.redirectUrl ?? window.location.href,
                redirectAutomatically: true,
                metadata: {
                    orderNumber: message,
                    referralCode: 'D19833',
                    type: 'Bitcoin Donation'
                }

            }),
            success: (response) => {
                invoiceCookieData = {
                    'id': response.id,
                    'amount': amount,
                    'currency': lastInputCurency,
                    'checkoutLink': response.checkoutLink,
                    'message': message
                }
                setCookie('coinsnap_invoice_', JSON.stringify(invoiceCookieData), 15)
                window.location.href = response.checkoutLink
            },
            error: (error) => {
                console.error('Error creating invoice:', error);
            }
        });
    }

    function btcpayInvoiceStatus(invoiceId, amount, message) {
        $.ajax({
            url: `${bitcoinDonationData.btcpayUrl}/api/v1/stores/${bitcoinDonationData.btcpayStoreId}/invoices/${invoiceId}`,
            method: 'GET',
            contentType: 'application/json',
            headers: {
                'Authorization': `token ${bitcoinDonationData.btcpayApiKey}`,
            },
            success: (response) => {
                if (response?.status === 'Settled') {
                    createBTCPayInvoice(amount, message)
                } else if (response?.status === 'New') {
                    window.location.href = response.checkoutLink;
                }
            },
            error: (error) => {
                console.error('Error checking invoice status:', error);
            }
        });
    }

    const createInvoice = (amount, message) => {
        existingInvoice = getCookie('coinsnap_invoice_')
        if (existingInvoice) {
            invoiceJson = JSON.parse(existingInvoice)
            if (invoiceJson.id &&
                invoiceJson.checkoutLink &&
                invoiceJson.amount == amount &&
                invoiceJson.currency == lastInputCurency &&
                invoiceJson.message == message) {
                bitcoinDonationData.provider == 'coinsnap' ?
                    coinsnapInvoiceStatus(invoiceJson.id, amount, message) :
                    btcpayInvoiceStatus(invoiceJson.id, amount, message)
            }
            else {
                bitcoinDonationData.provider == 'coinsnap' ?
                    createCoinsnapInvoice(amount, message) :
                    createBTCPayInvoice(amount, message)
            }
        } else {
            bitcoinDonationData.provider == 'coinsnap' ?
                createCoinsnapInvoice(amount, message) :
                createBTCPayInvoice(amount, message)
        }
    }

    const updateSatoshiField = (amount) => {
        const currencyRate = exchangeRates[bitcoinDonationData.currency?.toUpperCase()];
        const satoshiField = $('#bitcoin-donation-satoshi');
        if (!isNaN(amount) && currencyRate) {
            const satoshiValue = amount / currencyRate;
            satoshiField.val(satoshiValue.toFixed(0));
        } else {
            satoshiField.val('');
        }
    };

    const updateAmountField = (satoshi) => {
        const currencyRate = exchangeRates[bitcoinDonationData.currency?.toUpperCase()];
        const amountField = $('#bitcoin-donation-amount');

        if (!isNaN(satoshi) && currencyRate) {
            const amountValue = satoshi * currencyRate;
            amountField.val(amountValue.toFixed(8));
        } else {
            amountField.val('');
        }
    };

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

    // Event listeners
    $('#bitcoin-donation-pay').on('click', function () {
        const amountField = $('#bitcoin-donation-amount');
        const satoshiField = $('#bitcoin-donation-satoshi');
        const messageField = $('#bitcoin-donation-message');
        const satsAmount = parseFloat(satoshiField.val())
        const message = messageField.val()
        const amount = lastInputCurency == 'SATS' ? satsAmount : parseFloat(amountField.val());
        if (amount) {
            createInvoice(amount, message);
        }
    });

    $('#bitcoin-donation-amount').on('input', function () {
        const amount = parseFloat($(this).val());
        lastInputCurency = bitcoinDonationData.currency
        updateSatoshiField(amount);
    });

    $('#bitcoin-donation-satoshi').on('input', function () {
        const satoshi = parseFloat($(this).val());
        lastInputCurency = 'SATS'
        updateAmountField(satoshi);
    });

    fetchCoinsnapExchangeRates();
});

