(function ($) {
  // Wait until the DOM is fully loaded
  $(document).ready(function () {
    const $providerSelector = $('#provider');
    const $coinsnapWrapper = $('#coinsnap-settings-wrapper');
    const $btcpayWrapper = $('#btcpay-settings-wrapper');
    const $checkConnectionCoisnanpButton = $('#check_connection_coinsnap_button');
    const $checkConnectionBtcPayButton = $('#check_connection_btcpay_button');

    function checkBtcPayConnection(btcpayUrl, btcpayStoreId, btcpayApiKey) {
      return $.ajax({
        url: `${btcpayUrl}/api/v1/stores/${btcpayStoreId}/invoices`,
        method: 'GET',
        contentType: 'application/json',
        headers: {
          'Authorization': `token ${btcpayApiKey}`,
        },
      })
        .then(() => true)
        .catch(() => false);

    }

    function checkCoinsnapConnection(coinsnapStoreId, coinsnapApiKey) {
      return $.ajax({
        url: `https://app.coinsnap.io/api/v1/stores/${coinsnapStoreId}`,
        method: 'GET',
        contentType: 'application/json',
        headers: {
          'x-api-key': coinsnapApiKey,
        },
      })
        .then(() => true)
        .catch(() => false);
    }

    // Function to toggle visibility based on selected provider
    function toggleProviderSettings() {
      if (!$providerSelector || !$providerSelector.length) {
        return; // Return if no provider element is found
      }
      const selectedProvider = $providerSelector.val();
      $coinsnapWrapper.toggle(selectedProvider === 'coinsnap');
      $btcpayWrapper.toggle(selectedProvider === 'btcpay');
    }

    // Initial toggle on page load
    toggleProviderSettings();

    // Listen for changes to the provider dropdown
    $providerSelector.on('change', toggleProviderSettings);

    function getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return parts.pop().split(';').shift();
    }

    function setCookie(name, value, seconds) {
      const d = new Date();
      d.setTime(d.getTime() + (seconds * 1000));
      const expires = "expires=" + d.toUTCString();
      document.cookie = name + "=" + value + ";" + expires + ";path=/";
    }


    async function handleCheckConnection() {
      // event.preventDefault();
      var connection = false
      if ($providerSelector?.val() == 'coinsnap') {
        const coinsnapStoreId = $('#coinsnap_store_id').val();
        const coinsnapApiKey = $('#coinsnap_api_key').val();
        connection = await checkCoinsnapConnection(coinsnapStoreId, coinsnapApiKey)
      } else {
        const btcpayStoreId = $('#btcpay_store_id').val();
        const btcpayApiKey = $('#btcpay_api_key').val();
        const btcpayUrl = $('#btcpay_url').val();
        connection = await checkBtcPayConnection(btcpayUrl, btcpayStoreId, btcpayApiKey)
      }
      setCookie('coinsnap_connection_', JSON.stringify({ 'connection': connection }), 20)

    }

    // Add click event listener to the check connection button
    $checkConnectionCoisnanpButton.on('click', async () => {
      handleCheckConnection()
    });
    $checkConnectionBtcPayButton.on('click', async () => {
      handleCheckConnection()
    });

    const connectionCookie = getCookie('coinsnap_connection_')
    if (connectionCookie) {
      const connectionState = JSON.parse(connectionCookie)?.connection
      const checkConnection = $(`#check_connection_${$providerSelector?.val()}`)
      connectionState
        ? checkConnection.css({ color: 'green' }).text('Connection successful')
        : checkConnection.css({ color: 'red' }).text('Connection failed');
    }
  });
})(jQuery);