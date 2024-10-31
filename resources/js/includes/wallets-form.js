import {__} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';
import {select} from '@wordpress/data';
import {CART_STORE_KEY, CHECKOUT_STORE_KEY} from '@woocommerce/block-data';
import {createElement, useEffect} from 'react';
import validateData from './wallets/validate-form';
import initButton from './wallets/init';
import axios from 'axios';
import canMakePayment from "./canMakePayment";

const textDomain = 'paydock';
const labels = {
    validationError: __('Please fill in the required fields of the form to display payment methods', textDomain),
    fillDataError: __('The payment service does not accept payment. Please try again later or choose another payment method.', textDomain),
    notAvailable: __('Payment method is not avalaible for your country!!!', textDomain)
}

const afterpayCountries = ['au', 'nz', 'us', 'ca', 'uk', 'gb', 'fr', 'it', 'es', 'de'];

let localState = {
    initData: null,
    total: 0,
}

export default (id, defaultLabel, buttonId, dataFieldsRequired) => {
    const settingKey = `paydock_${id}_wallet_block_data`;
    const paymentName = `paydock_${id}_wallets_gateway`;

    const settings = getSetting(settingKey, {});
    const label = decodeEntities(settings.title) || __(defaultLabel, textDomain);

    const store = select(CHECKOUT_STORE_KEY);
    const cart = select(CART_STORE_KEY);

    let button;

    const initWallet = () => {
        if (!button.length || localState.total === cart.getCartTotals()?.total_price) {
            return;
        }
        jQuery('#' + buttonId).each((index, element) => {
            element.innerHTML = '';
        })
        localState.total = cart.getCartTotals()?.total_price;

        button.each((index, element) => element.innerHTML = '')

        let billingData = {
            type: id,
            order_id: store.getOrderId(),
            total: cart.getCartTotals(),
            address: cart.getCustomerData().billingAddress,
            shipping_address: cart.getCustomerData().shippingAddress,
            shipping_rates: cart.getShippingRates(),
            items: cart.getCartData().items
        }

        axios.post('/wp-json/paydock/v1/wallets/charge', billingData).then((response) => {
            localState.initData = response.data
            setTimeout(() => {
                initButton(id, '#' + buttonId, localState.initData, settings.isSandbox, localState.reload)
            }, 100)
        }).catch((e) => {
            localState.wasInit = false;
        })
    }
    const Content = (props) => {
        button = jQuery('#' + buttonId)

        const {eventRegistration, emitResponse} = props;
        const {onPaymentSetup, onCheckoutValidation, onShippingRateSelectSuccess} = eventRegistration;
        const billingAddress = cart.getCustomerData().billingAddress;
        const afterpayCountriesError = jQuery('.paydock-country-available');

        let validationSuccess = validateData(billingAddress, dataFieldsRequired);

        jQuery('.wc-block-components-checkout-place-order-button').hide();

        if (('paydockWalletAfterpayButton' === buttonId)
            && validationSuccess
            && !afterpayCountries.find((element) => element === billingAddress.country.toLowerCase())) {
            afterpayCountriesError.show()
        } else if (validationSuccess && !localState.initData) {
            afterpayCountriesError.hide()
            initWallet();
        } else if (validationSuccess && localState.initData && !button.length) {
            afterpayCountriesError.hide()
            setTimeout(() => {
                initButton(id, '#' + buttonId, localState.initData, settings.isSandbox)
            }, 100)
        }

        useEffect(() => {
            const onShipping = onShippingRateSelectSuccess(async () => {
                if ((localState.total !== cart.getCartTotals()?.total_price)
                    && canMakePayment(settings.total_limitation, cart.getCartTotals()?.total_price)) {
                    initWallet();
                }
            })
            const oncheckout = onCheckoutValidation(async (data) => {
                if (!validationSuccess) {
                    return {
                        type: emitResponse.responseTypes.ERROR, errorMessage: labels.fillDataError
                    }
                }

                if (document.getElementById('paymentSourceWalletsToken').value) {
                    return true;
                }

                return {
                    type: emitResponse.responseTypes.ERROR, errorMessage: labels.fillDataError,
                }
            });

            const unsubscribe = onPaymentSetup(async (data) => {
                if (document.getElementById('paymentSourceWalletsToken').value) {
                    return {
                        type: emitResponse.responseTypes.SUCCESS, meta: {
                            paymentMethodData: {
                                payment_response: document.getElementById('paymentSourceWalletsToken').value,
                                wallets: JSON.stringify(settings.wallets),
                                _wpnonce: settings._wpnonce
                            }
                        },
                    };
                }

                return {
                    type: emitResponse.responseTypes.ERROR, errorMessage: labels.fillDataError,
                }
            });
            return () => {
                unsubscribe() && oncheckout() && onShipping();
            };
        }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup,]);

        const description = createElement("div", null, decodeEntities(settings.description || ''));

        const input = createElement("input", {
            type: 'hidden', id: 'paymentSourceWalletsToken'
        });

        const paymentWasSuccessElement = createElement('div', {
            id: 'paymentCompleted', style: {
                display: 'none',
                'background-color': settings.styles.background_color,
                'color': settings.styles.success_color,
                'font-size': settings.styles.font_size,
                'font-family': settings.styles.font_family,
            }
        }, 'Payment Details Collected')

        const wallets = [createElement('div', {
            id: buttonId, class: "paudock-wallets-buttons",
        }),];

        return createElement(
            'div',
            null,
            description,
            paymentWasSuccessElement,
            createElement(
                'div',
                {id: 'paydockWidgetWallets', class: 'paydock-widget-content',},
                ...wallets
            ), createElement(
                'div',
                {class: 'paydock-validation-error', style: {display: validationSuccess ? 'none' : ''}},
                labels.validationError
            ), createElement(
                "div",
                {class: 'paydock-country-available', style: {display: 'none'}},
                labels.notAvailable
            ),
            input
        );
    };

    const PaydokWalletBlock = {
        name: paymentName,
        label: createElement(() =>
            createElement(
                "div",
                {
                    className: 'paydock-payment-method-label'
                },
                createElement("img", {
                    src: `/wp-content/plugins/paydock/assets/images/icons/${id}.png`,
                    alt: label,
                    className: `paydock-payment-method-label-icon ${id}`
                }),
                "  " + label,
            )
        ),
        content: <Content/>,
        edit: <Content/>,
        canMakePayment: () => canMakePayment(settings.total_limitation, cart.getCartTotals()?.total_price),
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };

    registerPaymentMethod(PaydokWalletBlock);
}
