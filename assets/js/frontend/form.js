setTimeout(() => jQuery(function ($) {
    let lastInit = '';

    const paydockValidation = {
        shouldValidateWcForm: true,
        wcFormSetBlurEventListener() {
            const checkoutFormElements = document.querySelectorAll('.wc-block-checkout__form input, .wc-block-checkout__form select');
            if (checkoutFormElements.length === 0) {
                return;
            }

            const self = this
            checkoutFormElements.forEach(element => {
                switch (element.type) {
                    case 'email':
                    case 'text':
                    case 'tel':
                        element.addEventListener('blur', () => {
                            if (!self.shouldValidateWcForm) {
                                return;
                            }

                            if (self.wcFormValidation()) {
                                self.shouldValidateWcForm = false
                                lastInit = ''
                            }
                        })
                        break;
                }
            })
        },
        lastWcFormValidation: false,
        wcFormValidation() {
            const checkoutFormElements = document.querySelectorAll('.wc-block-checkout__form input, .wc-block-checkout__form select');
            if (checkoutFormElements.length === 0) {
                return null;
            }

            const BreakException = {};

            let result = true

            try {
                checkoutFormElements.forEach(element => {
                    if (!element.required) {
                        return
                    }

                    result = element.validity.valid;
                    if (result === false) {
                        throw BreakException
                    }
                })
            } catch (e) {
                if (e !== BreakException) throw e
            }

            this.lastWcFormValidation = result
            this.shouldValidateWcForm = !result

            return result;
        },
        createWidgetDiv(id) {
            if (document.getElementById(id) !== null) {
                return null;
            }

            const div = document.createElement('div')
            div.setAttribute('id', id)
            div.setAttribute('style', 'display:none')

            document.body.append(div)

            return div;
        },
        passWidgetToWrapper(id) {
            const wrapper = document.getElementById(id + '_wrapper')
            const htmlWidget = document.getElementById(id)
            if (wrapper.querySelector('#' + id) === null && htmlWidget !== null) {
                const clonnedHtmlWidget = htmlWidget.cloneNode(true)
                clonnedHtmlWidget.setAttribute('style', '');
                document.getElementById(id + '_wrapper').append(clonnedHtmlWidget);
            }
            window.widgetPaydock.hideElements(['submit_button', 'email', 'phone']);
        },
    }

    window.paydockValidation = paydockValidation;

    const idPaydockWidgetCard = 'radio-control-wc-payment-method-options-paydock_gateway';
    const idPaydockWidgetBankAccount = 'radio-control-wc-payment-method-options-paydock_bank_account_gateway';

    const searchParams = new URLSearchParams(window.location.search);
    const paydockAfterpayWalletsSettings = window.wc?.wcSettings?.getSetting('paydock_afterpay_wallet_block_data', {});

    function initPaydockWidgetBankAccount() {
        lastInit = idPaydockWidgetBankAccount;
        const paydockBankAccountSettings = window.wc.wcSettings.getSetting('paydock_bank_account_block_data', {});

        if (!paydockBankAccountSettings.isActive) {
            return;
        }

        const htmlWidget = document.getElementById('paydockWidgetBankAccount')

        if (htmlWidget !== null && htmlWidget.innerHTML.trim().length > 0) {
            paydockValidation.passWidgetToWrapper('paydockWidgetBankAccount')
            return;
        }

        const gateway = 'not_configured';

        const bankAccount = new paydock.Configuration(gateway, 'bank_account');
        bankAccount.setFormFields(['account_routing']);

        paydockValidation.createWidgetDiv('paydockWidgetBankAccount');
        const widget = new paydock.HtmlWidget('#paydockWidgetBankAccount', paydockBankAccountSettings.publicKey, 'not_configured', 'bank_account', 'payment_source');
        widget.setFormFields(['account_routing']);

        window.widgetPaydockBankAccount = widget;
        if (paydockBankAccountSettings.hasOwnProperty('styles'))
            widget.setStyles(paydockBankAccountSettings.styles);

        if (
            paydockBankAccountSettings.hasOwnProperty('styles')
            && typeof paydockBankAccountSettings.styles.custom_elements !== "undefined"
        ) {
            $.each(paydockBankAccountSettings.styles.custom_elements, function (element, styles) {
                widget.setElementStyle(element, styles);
            });
        }

        widget.onFinishInsert('input[name="payment_source_bank_account_token"]', 'payment_source');
        widget.hideElements(['submit_button']);
        widget.interceptSubmitForm('#widget');
        widget.load();

        widget.on(window.paydock.EVENT.AFTER_LOAD, () => {
            if ($('#paydockWidgetBankAccount_wrapper').length > 0) {
                paydockValidation.passWidgetToWrapper('paydockWidgetBankAccount')
            }
        })
    }

    function initPaydockWidgetCard() {
        lastInit = idPaydockWidgetCard;

        const htmlWidget = document.getElementById('paydockWidgetCard')
        if (htmlWidget !== null && htmlWidget.innerHTML.trim().length > 0) {
            paydockValidation.passWidgetToWrapper('paydockWidgetCard')
            return;
        }

        const paydockCardSettings = window.wc.wcSettings.getSetting('paydock_data', {});
        paydockValidation.createWidgetDiv('paydockWidgetCard');

        let isPermanent = paydockCardSettings.hasOwnProperty('card3DSFlow')
            && ("SESSION_VAULT" === paydockCardSettings.card3DSFlow) && (
                paydockCardSettings.hasOwnProperty('card3DS')
                && 'DISABLE' !== paydockCardSettings.card3DS
            )

        let gatewayId = isPermanent ? paydockCardSettings.gatewayId : 'not_configured';

        widget = new paydock.HtmlWidget('#paydockWidgetCard', paydockCardSettings.publicKey, gatewayId);

        window.widgetPaydock = widget;
        if (paydockCardSettings.hasOwnProperty('styles')) {
            widget.setStyles(paydockCardSettings.styles);
        }

        if (paydockCardSettings.hasOwnProperty('styles') && typeof paydockCardSettings.styles.custom_elements !== "undefined") {
            $.each(paydockCardSettings.styles.custom_elements, function (element, styles) {
                widget.setElementStyle(element, styles);
            });
        }

        if (paydockCardSettings.hasOwnProperty('styles') && paydockCardSettings.cardSupportedCardTypes !== '') {
            supportedCard = paydockCardSettings.cardSupportedCardTypes.replaceAll(' ', '').split(',')
            widget.setSupportedCardIcons(supportedCard);
        }
        widget.setEnv(paydockCardSettings.isSandbox ? 'sandbox' : 'production');
        widget.setFormFields(['email', 'phone']);
        widget.onFinishInsert('input[name="paydock_payment_source_token"]', 'payment_source');
        widget.interceptSubmitForm('#widget');
        widget.load();

        widget.on(window.paydock.EVENT.AFTER_LOAD, () => {
            widget.hideElements(['submit_button', 'email', 'phone']);
            if ($('#paydockWidgetCard_wrapper').length > 0) {
                paydockValidation.passWidgetToWrapper('paydockWidgetCard')
            }
        })
    }

    function setPaymentMethodWatcher() {
        $('.wc-block-components-radio-control__input').on('change', (event) => {
            const $orderButton = $('.wc-block-components-checkout-place-order-button');
            switch (event.target.value) {
                case 'paydock_gateway':
                    initPaydockWidgetCard();
                    $orderButton.show();
                    break;
                case 'paydock_bank_account_gateway':
                    initPaydockWidgetBankAccount();
                    $orderButton.show();
                    break;
                case 'paydock_google-pay_wallets_gateway':
                case 'paydock_apple-pay_wallets_gateway':
                case 'paydock_afterpay-pay_wallets_gateway':
                case 'paydock_pay-pal_wallets_gateway':
                case 'paydock_afterpay_a_p_m_s_gateway':
                case 'paydock_zip_a_p_m_s_gateway':
                    $orderButton.hide();
                    break;
                default:
                    $orderButton.show();
            }
        })
    }

    let wasClick = false;
    let wasInit = false;

    setInterval(() => {
        try {
            const $radioWidgetCard = $('#' + idPaydockWidgetCard);
            const $radioWidgetBankAccount = $('#' + idPaydockWidgetBankAccount);
            const $orderButton = $('.wc-block-components-checkout-place-order-button');
            const $afterpayRadiobatton = $('#radio-control-wc-payment-method-options-paydock_afterpay_wallets_gateway');
            if (
                paydockAfterpayWalletsSettings.hasOwnProperty('afterpayChargeId')
                && (paydockAfterpayWalletsSettings.afterpayChargeId.length > 0)
                && searchParams.has('afterpay_success')
                && !wasClick
                && $orderButton.length
                && $afterpayRadiobatton.length
            ) {
                wasClick = true
                $afterpayRadiobatton.parent().click();
                $('#paymentCompleted').show();
                $('#paydockWalletAfterpayButton').hide();
                $orderButton.hide();
                $('#paymentSourceWalletsToken').val(JSON.stringify({
                    data: {
                        id: paydockAfterpayWalletsSettings.afterpayChargeId,
                        status: (searchParams.get('direct_charge') === 'true') ? 'paid' : 'pending'
                    }
                }));
                $orderButton.click();
            }

            if ($radioWidgetCard[0] && $radioWidgetCard[0].checked && !wasInit) {
                wasInit = true;
                initPaydockWidgetCard();
                setPaymentMethodWatcher();
            } else if ($radioWidgetBankAccount[0] && $radioWidgetBankAccount[0].checked && !wasInit) {
                wasInit = true;
                initPaydockWidgetBankAccount()
                setPaymentMethodWatcher();
            }
        } catch (e) {
            console.log(e);
        }

    }, 100)

}), 1000)
