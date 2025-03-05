jQuery(document).ready(function ($) {

    if (document.getElementById('bitcoin-voting-form')) {
        var exchangeRates = {};
        var retryId = '';

        const hideElementById = (id) => {
            document.getElementById(id).style.display = 'none'
        }
        const hideElementsById = (ids) => {
            ids.forEach(id => {
                hideElementById(id)
            })
        }
        const showElementById = (id, display) => {
            document.getElementById(id).style.display = display
        }
        const showElementsById = (ids, display) => {
            ids.forEach(id => {
                showElementById(id, display)
            })
        }

        if (document.getElementById('bitcoin-voting-form')) {
            fetchCoinsnapExchangeRates().then(rates => {
                exchangeRates = rates
            })
        }

        const pollButtons = document.querySelectorAll(".poll-option");
        pollButtons.forEach(button => {
            button.addEventListener("click", function () {
                document.getElementById('qr-payment-container').style.display = "flex";
                document.querySelector(".blur-overlay").style.display = "block";
                retryId = '';
            });
        });

        const returnButton = document.getElementById('return-button')
        if (returnButton) {
            returnButton.addEventListener("click", function () {
                document.querySelector(".poll-options").style.display = "flex";
                document.querySelector(".poll-results").style.display = "none";
                returnButton.classList.remove('return-buton-visible')
            });
        }

        const fetchResultsFromDb = (pollId) => {
            fetch(`/wp-json/my-plugin/v1/voting_results/${pollId}`)
                .then(response => response.json())
                .then(data => {
                    const votesDb = data.results
                    const votesLen = votesDb.length
                    let votes = {};
                    votesDb.forEach(result => {
                        const vote = parseInt(result.option_id)
                        votes[vote] = (votes[vote] || 0) + 1;
                    });
                    const maxVote = Math.max(...Object.values(votes))
                    const maxVoteOption = Object.keys(votes).find(key => votes[key] === maxVote);

                    document.querySelector(`.voting-progress-bar[data-option='${maxVoteOption}']`).style['background-color'] = '#f7a70a';
                    document.getElementById("total-votes").textContent = `${votesDb.length}`;

                    Object.keys(votes).forEach(opt => {
                        let percentage = votesLen > 0 ? (votes[opt] / votesLen) * 100 : 0;
                        if (percentage > 0) {
                            const percentageSpan = document.querySelector(`.voting-progress-percentage[data-option='${opt}']`);

                            percentageSpan.textContent = percentage.toFixed(1) + "%";
                        }
                        document.querySelector(`.voting-progress-bar[data-option='${opt}']`).style.width = percentage + "%";
                        document.querySelector(`.vote-count[data-option='${opt}']`).textContent = votes[opt];
                    });
                })
        }

        const showResultsFn = (pollId) => {
            document.querySelector(".poll-options").style.display = "none";
            document.querySelector(".poll-results").style.display = "block";
            const oneVote = document.querySelector('#bitcoin-voting-form')?.dataset.oneVote
            const voted = getCookie('coinsnap_poll_' + pollId)
            if (oneVote && voted) {
                returnButton.classList.remove('return-buton-visible')
            } else {
                returnButton.classList.add('return-buton-visible')
            }
            fetchResultsFromDb(pollId)
        }

        const pollId = document.querySelector('#bitcoin-voting-form')?.dataset.pollId

        if (pollId) {// Check cookie and show results if user voted already
            const voted = getCookie('coinsnap_poll_' + pollId)
            if (voted) {
                showResultsFn(pollId)
            }
        }

        const checkResults = document.getElementById("check-results");
        if (checkResults) {
            checkResults.addEventListener("click", function () {
                showResultsFn(pollId)
            });
        }

        window.addEventListener("click", function (event) { // Hde qr popup on click outside
            const qrPopup = document.getElementById('qr-payment-container')
            const thankYou = document.getElementById('thank-you-popup')
            if ((qrPopup?.style.display == 'flex' || thankYou?.style.display == 'flex') &&
                !event.target.classList.contains('poll-option') &&
                ![...event.target.classList].some((className) => className.startsWith('qr-'))) {
                document.getElementById("qr-lightning").textContent = ``;
                document.getElementById("qr-amount").textContent = ``;
                document.getElementById("qr-spinner").style.display = "block";
                hideElementsById(['qrCode', 'qr-lightning-container', 'qr-fiat', 'lightning-wrapper', 'pay-in-wallet', 'btc-wrapper', 'qrCodeBtc', 'qr-summary', 'thank-you-popup'])
                qrPopup.style.display = "none";
                document.querySelector(".blur-overlay").style.display = "none";
                retryId = '';
            }
        });

        // In case poll is closed
        const pollResults = document.getElementById("poll-results");
        if (pollResults) {
            const endDate = new Date(pollResults.dataset.endDate)
            const nowDate = new Date()
            const pollId = pollResults.dataset.pollId

            if (endDate < nowDate) {
                fetchResultsFromDb(pollId)
            }
        }

        // On vote click
        document.querySelectorAll(".poll-option").forEach(button => {
            button.addEventListener("click", async function () {

                let option = this.getAttribute("data-option"); // Selected voting option number

                const optionName = document.querySelector(`.poll-option[data-option='${option}']`)?.textContent
                const pollId = document.querySelector('#bitcoin-voting-form').dataset.pollId
                const amount = document.querySelector('#bitcoin-voting-form').dataset.pollAmount

                const metadata = {
                    optionId: option,
                    option: optionName,
                    pollId: pollId
                }

                const res = await createInvoice(amount, option, "SATS", undefined, 'Bitcoin Voting', false, metadata)

                if (res) {
                    // Update addresses 
                    const qrLightning = res.lightningInvoice
                    const qrBitcoin = res.onchainAddress

                    if (qrBitcoin) {
                        showElementsById(['btc-wrapper', 'qr-btc-container'], 'flex')
                    }

                    // Hide spinner and show qr code stuff
                    showElementsById(['qrCode', 'lightning-wrapper', 'qr-fiat', 'qrCodeBtc'], 'block')
                    showElementsById(['qr-summary', 'qr-lightning-container', 'pay-in-wallet'], 'flex')
                    hideElementById('qr-spinner')

                    // Update actuall data
                    document.getElementById("qrCode").src = res.qrCodes.lightningQR;
                    document.getElementById("qr-lightning").textContent = `${qrLightning.substring(0, 20)}...${qrLightning.slice(-15)}`;
                    document.getElementById("qr-btc").textContent = `${qrBitcoin.substring(0, 20)}...${qrBitcoin.slice(-15)}`;
                    document.getElementById("qr-amount").textContent = `Amount: ${res.amount} sats`;

                    // Copy address functionallity 
                    const copyLightning = document.querySelector('#qr-lightning-container .qr-copy-icon');
                    const copyBtc = document.querySelector('#qr-btc-container .qr-copy-icon');
                    copyLightning.addEventListener('click', () => { navigator.clipboard.writeText(qrLightning); });
                    copyBtc.addEventListener('click', () => { navigator.clipboard.writeText(qrBitcoin); });

                    // Add fiat amount
                    if (exchangeRates['EUR']) {
                        document.getElementById("qr-fiat").textContent = `≈ ${res.amount * exchangeRates['EUR']} EUR`;
                        document.getElementById("pay-in-wallet").addEventListener('click', function () {
                            window.location.href = `lightning:${qrLightning}`;
                        });

                    }

                    // Reset retry counter
                    var retryNum = 0;
                    retryId = res.id

                    const checkPaymentStatus = () => {
                        fetch(`/wp-json/my-plugin/v1/payment-status-long-poll/${res.id}/${pollId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'completed') {
                                    setCookie(`coinsnap_poll_${pollId}`, option, 30 * 24 * 60);

                                    const votesDb = data.results;
                                    const votesLen = votesDb.length;
                                    let votes = {};
                                    votesDb.forEach(result => {
                                        const vote = parseInt(result.option_id);
                                        votes[vote] = (votes[vote] || 0) + 1;
                                    });

                                    document.querySelector(".poll-options").style.display = "none";
                                    document.querySelector(".poll-results").style.display = "block";
                                    document.getElementById("total-votes").textContent = `${votesDb.length}`;
                                    document.getElementById('thank-you-popup').style.display = 'flex';
                                    document.getElementById('qr-payment-container').style.display = "none";

                                    Object.keys(votes).forEach(opt => {
                                        let percentage = votesLen > 0 ? (votes[opt] / votesLen) * 100 : 0;
                                        const progressBar = document.querySelector(`.voting-progress-bar[data-option='${opt}']`);
                                        opt == option ? progressBar.style['background-color'] = '#f7a70a' : progressBar.style['background-color'] = '#9d9d9d';
                                        if (percentage > 0) {
                                            const percentageSpan = document.querySelector(`.voting-progress-percentage[data-option='${opt}']`);
                                            percentageSpan.textContent = percentage.toFixed(1) + "%";
                                        }
                                        progressBar.style.width = percentage + "%";
                                        document.querySelector(`.vote-count[data-option='${opt}']`).textContent = votes[opt];

                                    });
                                } else if (retryNum < 180 && retryId == res.id) {
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
            });
        });
    }
});