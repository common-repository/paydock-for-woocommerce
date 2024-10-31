jQuery(document).ready(function ($) {
    const DISABLE_TEXT = 'DISABLE';
    const types = {
        select: 'select', checkbox: 'checkbox'
    };
    const prefixes = [
        'woocommerce_paydock_paydock_',
        'woocommerce_paydock_sandbox_paydock_sandbox_',
        'woocommerce_paydock_widget_paydock_widget_'
    ];

    const conditions = [{
        element: 'VERSION',
        condition: 'custom',
        type: types.select,
        hide: ['CREDENTIALS_ACCESS_KEY'],
        show: ['CUSTOM_VERSION',],
    }, {
        element: 'CREDENTIALS_TYPE',
        condition: 'CREDENTIALS',
        type: types.select,
        hide: ['CREDENTIALS_ACCESS_KEY', 'CREDENTIALS_WIDGET_KEY'],
        show: ['CREDENTIALS_PUBLIC_KEY', 'CREDENTIALS_SECRET_KEY'],
    }, {
        element: 'CARD_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['CARD_SAVE_CARD_OPTION'],
    }, {
        element: 'CARD_DS',
        condition: 'DISABLE',
        type: types.select,
        hide: ['CARD_DS_SERVICE_ID', 'CARD_TYPE_EXCHANGE_OTT'],
        show: [],
    }, {
        element: 'CARD_TYPE_EXCHANGE_OTT', condition: 'custom', type: types.select, hide: [], show: [],
    }, {
        element: 'CARD_FRAUD', condition: 'DISABLE', type: types.select, hide: ['CARD_FRAUD_SERVICE_ID'], show: [],
    }, {
        element: 'BANK_ACCOUNT_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['BANK_ACCOUNT_SAVE_CARD_OPTION'],
    }, {
        element: 'WALLETS_AFTERPAY_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_AFTERPAY_SAVE_CARD_OPTION'],
    }, {
        element: 'WALLETS_ZIPPAY_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_ZIPPAY_SAVE_CARD_OPTION'],
    }, {
        element: 'WALLETS_PAY_PAL_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_PAY_PAL_SAVE_CARD_OPTION'],
    }, {
        element: 'WALLETS_APPLE_PAY_FRAUD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_APPLE_PAY_FRAUD_SERVICE_ID'],
    }, {
        element: 'WALLETS_GOOGLE_PAY_FRAUD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_GOOGLE_PAY_FRAUD_SERVICE_ID'],
    }, {
        element: 'WALLETS_PAY_PAL_SMART_BUTTON_FRAUD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_PAY_PAL_SMART_BUTTON_FRAUD_SERVICE_ID'],
    }, {
        element: 'WALLETS_AFTERPAY_FRAUD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['WALLETS_AFTERPAY_FRAUD_SERVICE_ID'],
    }, {
        element: 'A_P_M_S_ZIPPAY_FRAUD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['A_P_M_S_ZIPPAY_FRAUD_SERVICE_ID'],
    }, {
        element: 'A_P_M_S_ZIPPAY_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['A_P_M_S_ZIPPAY_SAVE_CARD_OPTION'],
    }, {
        element: 'A_P_M_S_AFTERPAY_SAVE_CARD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['A_P_M_S_AFTERPAY_SAVE_CARD_OPTION'],
    }, {
        element: 'A_P_M_S_AFTERPAY_FRAUD',
        condition: 'DISABLE',
        type: types.checkbox,
        hide: [],
        show: ['A_P_M_S_AFTERPAY_FRAUD_SERVICE_ID'],
    }];

    function removeOptionsExcept(value, element) {
        element.find('option').each((index, el) => {
            const option = jQuery(el);
            if (typeof value === "object") {
                if (option.val(), value.includes(option.val())) {
                    return
                }
            } else {
                if (option.val() === value) {
                    return
                }
            }

            option.remove()
        })
    }

    function processedElement(conditionValue, element, prefix) {
        const selectCondition = types.select === conditionValue.type && element.value === conditionValue.condition;
        const checkboxCondition = types.checkbox === conditionValue.type && element.checked;
        if (selectCondition || checkboxCondition) {
            conditionValue.show.map((id) => {
                $('#' + prefix + id).closest('tr').show()
            })
            conditionValue.hide.map((id) => {
                $('#' + prefix + id).closest('tr').hide()
            })
        } else {
            conditionValue.show.map((id) => {
                $('#' + prefix + id).closest('tr').hide()
            })
            conditionValue.hide.map((id) => {
                $('#' + prefix + id).closest('tr').show()
            })
        }
    }

    function saveCardProcess(saveCard, saveCardOption, _3DSFlow, _3DS) {
        saveCard.removeAttribute('disabled')
        saveCardOption.removeAttribute('disabled')
        jQuery(saveCard).closest('tr').show()
        jQuery(saveCardOption).closest('tr').show()

        if (!saveCard.checked) {
            jQuery(saveCardOption).closest('tr').hide()
        }

        if ('SESSION_VAULT' === _3DSFlow.val() && _3DS.val() === 'IN_BUILD') {
            saveCard.setAttribute('checked', false)
            saveCard.setAttribute('disabled', true)
            saveCardOption.setAttribute('disabled', true)
            jQuery(saveCard).closest('tr').hide()
            jQuery(saveCardOption).closest('tr').hide()
        }
    }

    function connect3DSWithFraud(_3DS, _3DSFlow, fraud, prefix) {
        const _3DSVal = _3DS.val()
        const old3DSFlowVal = _3DSFlow.val()

        if ('IN_BUILD' === _3DSVal) {
            $('#' + prefix + 'CARD_DS_SERVICE_ID').closest('tr').hide()
            if ('SESSION_VAULT' === old3DSFlowVal) {
                removeOptionsExcept(['DISABLE', 'IN_BUILD'], fraud)
            }
        } else if ('STANDALONE' === _3DSVal) {
            removeOptionsExcept(['DISABLE', 'STANDALONE'], fraud)
        }
    }

    function _3DSFlowProcess(_3DS, _3DSFlow, fraud, optionsHtml, prefix) {
        const old3DSFlowVal = _3DSFlow.val()
        const _3DSVal = _3DS.val()
        const fraudVal = fraud.val()

        fraud.html(optionsHtml[prefix + 'CARD_FRAUD'])

        if ('IN_BUILD' === _3DSVal) {
            if ('SESSION_VAULT' === old3DSFlowVal) {
                removeOptionsExcept(['DISABLE', 'IN_BUILD'], fraud)
            }
        }

        connect3DSWithFraud(_3DS, _3DSFlow, fraud, prefix)

        fraud.val(fraudVal)
    }

    function _3DSProcess(_3DS, _3DSFlow, fraud, optionsHtml, prefix) {
        const old3DSFlowVal = _3DSFlow.val()
        const _3DSVal = _3DS.val()
        const fraudVal = fraud.val()
        _3DSFlow.html(optionsHtml[prefix + 'CARD_TYPE_EXCHANGE_OTT'])
        fraud.html(optionsHtml[prefix + 'CARD_FRAUD'])

        connect3DSWithFraud(_3DS, _3DSFlow, fraud, prefix)

        fraud.val(fraudVal)

        if ('STANDALONE' === _3DSVal || 'STANDALONE' === fraud.val()) {
            removeOptionsExcept('PERMANENT_VAULT', _3DSFlow)
        } else {
            _3DSFlow.val(old3DSFlowVal).change()
        }
    }

    function fraudFlowProcess(_3DS, _3DSFlow, fraud, optionsHtml, prefix) {
        const old3DSFlowVal = _3DSFlow.val()
        const fraudVal = fraud.val()
        const _3DSVal = _3DS.val()
        _3DSFlow.html(optionsHtml[prefix + 'CARD_TYPE_EXCHANGE_OTT'])
        _3DS.html(optionsHtml[prefix + 'CARD_DS'])

        if ('IN_BUILD' === fraudVal) {
            removeOptionsExcept(['DISABLE', 'IN_BUILD'], _3DS)
        }

        connect3DSWithFraud(_3DS, _3DSFlow, fraud, prefix)

        _3DS.val(_3DSVal)

        if ('STANDALONE' === fraud.val()) {
            removeOptionsExcept('PERMANENT_VAULT', _3DSFlow)
        } else {
            _3DSFlow.val(old3DSFlowVal).change()
        }
    }

    const optionsCache = {};
    prefixes.map((prefix) => {
        if (document.getElementById(prefix + 'CREDENTIALS_TYPE') === null) {
            return;
        }

        // todo: remove when api for save card will ready
        $('#' + prefix + 'A_P_M_S_ZIPPAY_SAVE_CARD').closest('tr').remove()
        $('#' + prefix + 'A_P_M_S_ZIPPAY_SAVE_CARD_OPTION').closest('tr').remove()
        $('#' + prefix + 'A_P_M_S_AFTERPAY_SAVE_CARD').closest('tr').remove()
        $('#' + prefix + 'A_P_M_S_AFTERPAY_SAVE_CARD_OPTION').closest('tr').remove()
        // todo: remove when api for save card will ready

        //checkoboxes
        const saveCard = document.getElementById(prefix + 'CARD_SAVE_CARD');
        const saveCardOption = document.getElementById(prefix + 'CARD_SAVE_CARD_OPTION');

        //selects
        const _3DS = $('#' + prefix + 'CARD_DS');
        const _3DSFlow = $('#' + prefix + 'CARD_TYPE_EXCHANGE_OTT');
        const fraud = $('#' + prefix + 'CARD_FRAUD');

        optionsCache[prefix + 'CARD_TYPE_EXCHANGE_OTT'] = _3DSFlow.html()
        optionsCache[prefix + 'CARD_DS'] = _3DS.html()
        optionsCache[prefix + 'CARD_FRAUD'] = fraud.html()

        _3DSProcess(_3DS, _3DSFlow, fraud, optionsCache, prefix);
        _3DSFlowProcess(_3DS, _3DSFlow, fraud, optionsCache, prefix);
        fraudFlowProcess(_3DS, _3DSFlow, fraud, optionsCache, prefix);
        saveCardProcess(saveCard, saveCardOption, _3DSFlow, _3DS)

        conditions.map((conditionValue) => {
            const trackedElement = $('#' + prefix + conditionValue.element)
            if (trackedElement.length === 0) {
                return;
            }

            processedElement(conditionValue, trackedElement[0], prefix)

            trackedElement.on('change', (event) => {
                const target = event.target

                saveCardProcess(saveCard, saveCardOption, _3DSFlow, _3DS)
                processedElement(conditionValue, target, prefix)

                switch (target.getAttribute('id')) {
                    case prefix + 'CARD_DS':
                        _3DSProcess(_3DS, _3DSFlow, fraud, optionsCache, prefix)
                        break;
                    case prefix + 'CARD_FRAUD':
                        fraudFlowProcess(_3DS, _3DSFlow, fraud, optionsCache, prefix)
                        break;
                    case prefix + 'CARD_TYPE_EXCHANGE_OTT':
                        _3DSFlowProcess(_3DS, _3DSFlow, fraud, optionsCache, prefix)
                        break;
                }
            });
        })

        _3DS.change();
    })
});
