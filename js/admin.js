(function ($) {
  // Wait until the DOM is fully loaded
  $(document).ready(function () {
    const $providerSelector = $('#provider');
    const $coinsnapWrapper = $('#coinsnap-settings-wrapper');
    const $btcpayWrapper = $('#btcpay-settings-wrapper');
    const $checkConnectionCoisnanpButton = $('#check_connection_coinsnap_button');
    const $checkConnectionBtcPayButton = $('#check_connection_btcpay_button');

    const tabs = document.querySelectorAll(".nav-tab");
    const contents = document.querySelectorAll(".tab-content");

    tabs.forEach(tab => {
      tab.addEventListener("click", function (e) {
        e.preventDefault();
        tabs.forEach(t => t.classList.remove("nav-tab-active"));
        contents.forEach(c => c.classList.remove("active"));
        tab.classList.add("nav-tab-active");
        const target = tab.getAttribute("data-tab");
        document.getElementById(target).classList.add("active");
        localStorage.setItem('activeTab', target);
      });
    });
    const restoreTabs = () => {
      const savedTab = localStorage.getItem('activeTab');
      const activeTab = document.querySelector(`.nav-tab[data-tab="${savedTab}"]`);
      if (activeTab) {
        activeTab.click()
      }
    }
    restoreTabs()

    const coinsnapStoreIdField = document.getElementById('coinsnap_store_id');
    const coinsnapApiKeyField = document.getElementById('coinsnap_api_key');
    const btcpayStoreIdField = document.getElementById('btcpay_store_id');
    const btcpayApiKeyField = document.getElementById('btcpay_api_key');
    const btcpayUrlField = document.getElementById('btcpay_url');

    if ($providerSelector.val() === 'coinsnap' && coinsnapStoreIdField && coinsnapApiKeyField) {
      $checkConnectionCoisnanpButton.prop("disabled", !(coinsnapApiKeyField.value.length > 12 && coinsnapStoreIdField.value.length > 12));
      coinsnapApiKeyField.addEventListener('input', function () {
        $checkConnectionCoisnanpButton.prop("disabled", !(coinsnapApiKeyField.value.length > 12 && coinsnapStoreIdField.value.length > 12));
      });
      coinsnapStoreIdField.addEventListener('input', function () {
        $checkConnectionCoisnanpButton.prop("disabled", !(coinsnapApiKeyField.value.length > 12 && coinsnapStoreIdField.value.length > 12));
      });
    } else if ($providerSelector.val() === 'btcpay' && btcpayStoreIdField && btcpayApiKeyField && btcpayUrlField) {
      $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      btcpayApiKeyField.addEventListener('input', function () {
        $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      });
      btcpayStoreIdField.addEventListener('input', function () {
        $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      });
      btcpayUrlField.addEventListener('input', function () {
        $checkConnectionBtcPayButton.prop("disabled", !(btcpayApiKeyField.value.length > 4 && btcpayStoreIdField.value.length > 12 && btcpayUrlField.value.length > 12));
      });
    }

    function checkConnection(storeId, apiKey, btcpayUrl) {
      const headers = btcpayUrl ? { 'Authorization': `token ${apiKey}` } : { 'x-api-key': apiKey, };
      const url = btcpayUrl
        ? `${btcpayUrl}/api/v1/stores/${storeId}/invoices`
        : `https://app.coinsnap.io/api/v1/stores/${storeId}`

      return $.ajax({
        url: url,
        method: 'GET',
        contentType: 'application/json',
        headers: headers
      })
        .then(() => true)
        .catch(() => false);
    }

    const getWebhookSecret = async () => {
      fetch(`/wp-json/my-plugin/v1/get-wh-secret`)
        .then(response => response.json())
        .then(data => {
          return data;
        });
    }
    
    function checkWebhooks(storeId, apiKey, btcpayUrl) {
      const headers = btcpayUrl ? { 'Authorization': `token ${apiKey}` } : { 'x-api-key': apiKey, };
      const url = btcpayUrl
        ? `${btcpayUrl}/api/v1/stores/${storeId}/webhooks`
        : `https://app.coinsnap.io/api/v1/stores/${storeId}/webhooks`

      return $.ajax({
        url: url,
        method: 'GET',
        contentType: 'application/json',
        headers: headers
      })
        .then((response) => response)
        .catch(() => []);
    }

    async function updateWebhook(storeId, apiKey, webhookUrl, webhookId, btcpayUrl) {
      const webhookSecret = await getWebhookSecret();
      console.log(webhookSecret)
      console.log(webhookUrl)
      const data = {
        url: webhookUrl,
        events: ['Settled'],
        secret: webhookSecret
      }
      const headers = btcpayUrl ? { 'Authorization': `token ${apiKey}` } : { 'x-api-key': apiKey, };
      const url = btcpayUrl
        ? `${btcpayUrl}/api/v1/stores/${storeId}/webhooks/${webhookId}`
        : `https://app.coinsnap.io/api/v1/stores/${storeId}/webhooks/${webhookId}`

      return $.ajax({
        url: url,
        method: 'PUT',
        contentType: 'application/json',
        headers: headers,
        data: JSON.stringify(data)
      })
        .then((response) => response)
        .catch(() => []);
    }

    async function createWebhook(storeId, apiKey, webhookUrl, btcpayUrl) {
      const webhookSecret = await getWebhookSecret();

      const data = {
        url: webhookUrl,
        events: ['Settled'],
        secret: webhookSecret
      }

      const headers = btcpayUrl
        ? { 'Authorization': `token ${apiKey}` }
        : { 'x-api-key': apiKey };

      const url = btcpayUrl
        ? `${btcpayUrl}/api/v1/stores/${storeId}/webhooks`
        : `https://app.coinsnap.io/api/v1/stores/${storeId}/webhooks`

      return $.ajax({
        url: url,
        method: 'POST',
        contentType: 'application/json',
        headers: headers,
        data: JSON.stringify(data)
      })
    }

    function toggleProviderSettings() {
      if (!$providerSelector || !$providerSelector.length) {
        return;
      }
      const selectedProvider = $providerSelector?.val();
      $coinsnapWrapper.toggle(selectedProvider === 'coinsnap');
      $btcpayWrapper.toggle(selectedProvider === 'btcpay');
    }

    toggleProviderSettings();

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

    async function handleCheckConnection(isSubmit = false) {
      event.preventDefault();
      var connection = false
      const ngrokLiveUrl = document.getElementById('ngrok_url')?.value;
      const origin = ngrokLiveUrl ? ngrokLiveUrl : new URL(window.location.href).origin;
      const webhookUrl = `${origin}/wp-json/coinsnap-bitcoin-donation/v1/webhook`
      if ($providerSelector?.val() == 'coinsnap') {
        const coinsnapStoreId = $('#coinsnap_store_id').val();
        const coinsnapApiKey = $('#coinsnap_api_key').val();
        connection = await checkConnection(coinsnapStoreId, coinsnapApiKey)
        if (connection) {
          const webhooks = await checkWebhooks(coinsnapStoreId, coinsnapApiKey)
          const webhookFound = webhooks?.find(webhook => webhook.url === webhookUrl);
          if (!webhookFound) {
            await createWebhook(coinsnapStoreId, coinsnapApiKey, webhookUrl)
          } else {
            await updateWebhook(coinsnapStoreId, coinsnapApiKey, webhookUrl, webhookFound.id)
          }
        }
      } else {
        const btcpayStoreId = $('#btcpay_store_id').val();
        const btcpayApiKey = $('#btcpay_api_key').val();
        const btcpayUrl = $('#btcpay_url').val();
        connection = await checkConnection(btcpayStoreId, btcpayApiKey, btcpayUrl)
        if (connection) {
          const webhooks = await checkWebhooks(btcpayStoreId, btcpayApiKey, btcpayUrl)
          const webhookFound = webhooks?.find(webhook => webhook.url === webhookUrl);
          if (!webhookFound) {
            await createWebhook(btcpayStoreId, btcpayApiKey, webhookUrl, btcpayUrl)
          } else {
            await updateWebhook(btcpayStoreId, btcpayApiKey, webhookUrl, webhookFound.id, btcpayUrl)
          }
        }
      }
      setCookie('coinsnap_connection_', JSON.stringify({ 'connection': connection }), 20)
      if (!isSubmit) {
        $('#submit').click();

      }
    }

    $('#submit').click(async function (event) {
      await handleCheckConnection(true);
      $('#submit').click();
    });

    $checkConnectionCoisnanpButton.on('click', async (event) => { await handleCheckConnection(); })
    $checkConnectionBtcPayButton.on('click', async (event) => { await handleCheckConnection(); });

    const connectionCookie = getCookie('coinsnap_connection_')
    if (connectionCookie) {
      const connectionState = JSON.parse(connectionCookie)?.connection
      const checkConnection = $(`#check_connection_${$providerSelector?.val()}`)
      connectionState
        ? checkConnection.css({ color: 'green' }).text('Connection successful')
        : checkConnection.css({ color: 'red' }).text('Connection failed');
    }
  });

  function togglePublicDonorFields(section, force) {
    section = section.replace(/-/g, '_')
    const element = document.getElementById(section + '_public_donors');
    var isChecked = element?.checked;
    if (force !== undefined) {
      isChecked = force;
    }
    $('.public-donor-field.' + section.replace(/_/g, '-')).closest('tr').toggle(isChecked);
  }

  function toggleShoutoutFields(section) {
    var isChecked = $('#' + section + '_donation_active').is(':checked');
    section = section.replace(/_/g, '-')
    if (section == 'shoutout') {
      $('#' + section + '-donation table tr')
        .not(':first')
        .toggle(isChecked);
    } else {
      $('#' + section + '-donation table tbody tr')
        .not(':first')
        .toggle(isChecked);
    }
    if (!isChecked) {
      togglePublicDonorFields(section, false);
    } else {
      togglePublicDonorFields(section);
    }
  }

  function toggleShortcode(value, section) {
    const regular = document.getElementById(`shortcode_${section}`);
    const wide = document.getElementById(`shortcode_${section}_wide`);
    if (!regular || !wide) {
      return;
    }
    if (value == 'WIDE') {
      regular.style.display = 'none';
      wide.classList.remove('hiddenRow');
      wide.style.display = 'table-row!important';
    } else {
      regular.style.display = 'table-row';
      wide.classList.add('hiddenRow');
    }
  }

  if ($('#form_type')?.val) {
    const value = $('#form_type').val();
    toggleShortcode(value, 'coinsnap_bitcoin_donation');
  }
  if ($('#multi_amount_form_type')?.val) {
    const value = $('#multi_amount_form_type').val();
    toggleShortcode(value, 'multi_amount_donation');

  }

  // Initial state
  togglePublicDonorFields('simple_donation');
  togglePublicDonorFields('shoutout');
  togglePublicDonorFields('multi_amount');
  toggleShoutoutFields('shoutout');
  toggleShoutoutFields('multi_amount');

  // Change handlers
  $('#simple_donation_public_donors').change(function () {
    togglePublicDonorFields('simple_donation');
  });
  $('#shoutout_public_donors').change(function () {
    togglePublicDonorFields('shoutout');
  });
  $('#multi_amount_public_donors').change(function () {
    togglePublicDonorFields('multi_amount');
  });
  $('#shoutout_donation_active').change(function () {
    toggleShoutoutFields('shoutout');
  });

  $('#multi_amount_donation_active').change(function () {
    toggleShoutoutFields('multi_amount');
  });

  $('#form_type').change(function () {
    const value = $(this).val();
    toggleShortcode(value, 'coinsnap_bitcoin_donation');
  });

  $('#multi_amount_form_type').change(function () {
    const value = $(this).val();
    toggleShortcode(value, 'multi_amount_donation');
  });

})(jQuery);
