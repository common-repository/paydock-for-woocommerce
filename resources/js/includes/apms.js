import {__} from '@wordpress/i18n';
import {createElement, useEffect} from 'react';
import {decodeEntities} from "@wordpress/html-entities";
import {getSetting} from "@woocommerce/settings";
import validateData from "./wallets/validate-form";
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {select} from '@wordpress/data';
import {CART_STORE_KEY} from '@woocommerce/block-data';
import canMakePayment from "./canMakePayment";

const textDomain = 'paydock';
const labels = {
    defaultLabel: __('Paydock Payments', textDomain),
    placeOrderButtonLabel: __('Place Order by Paydock', textDomain),
    validationError: __('Please fill in the required fields of the form to display payment methods', textDomain),
    notAvailable: __('The payment method is not available in your country.', textDomain),
}
let wasInit = false;
export default (id, defaultLabel, buttonId, dataFieldsRequired, countries) => {
    const settingKey = `paydock_${id}_a_p_m_s_block_data`;
    const paymentName = `paydock_${id}_a_p_m_s_gateway`;

    const settings = getSetting(settingKey, {});
    const label = decodeEntities(settings.title) || __(defaultLabel, textDomain);
    const cart = select(CART_STORE_KEY);
    const Content = (props) => {
        const {eventRegistration, emitResponse} = props;
        const {onPaymentSetup, onCheckoutValidation} = eventRegistration;

        const billingAddress = cart.getCustomerData().billingAddress;
        const shippingAddress = cart.getCustomerData().shippingAddress;
        const shippingRates = cart.getShippingRates();
        const countriesError = jQuery('.paydock-country-available');
        const validationError = jQuery('.paydock-validation-error');
        const buttonElement = jQuery('#' + buttonId);
        const orderButton = jQuery('.wc-block-components-checkout-place-order-button');
        const paymentCompleteElement = jQuery('#paymentCompleted');

        let validationSuccess = validateData(billingAddress, dataFieldsRequired);
        let isAvailableCountry = !!countries.find(
            (element) => element === billingAddress.country.toLowerCase()
        );
        let button = null;
        let meta = {};
        let data = {...settings};
        data.customers = '';
        data.styles = '';
        data.supports = '';
        data.pickupLocations = '';

        if(data.total_limitation){
            delete data.total_limitation;
        }

        validationError.hide();
        countriesError.hide();
        buttonElement.hide();

        if (!validationSuccess) {
            wasInit = false;
            validationError.show();
        } else if (validationSuccess && !isAvailableCountry) {
            wasInit = false;
            countriesError.show();
        } else if (validationSuccess && isAvailableCountry) {
            buttonElement.show();
        }
        setTimeout(() => {
            if ((validationSuccess && 'zip' === id) && !wasInit) {
                wasInit = true;
                button = new window.paydock.ZipmoneyCheckoutButton('#' + buttonId, settings.publicKey, settings.gatewayId);

                data.gatewayType = 'zippay'
            } else if ((validationSuccess && 'afterpay' === id) && !wasInit) {
                wasInit = true;
                button = new window.paydock.AfterpayCheckoutButton('#' + buttonId, settings.publicKey, settings.gatewayId);
                meta = {
                    amount: settings.amount,
                    currency: settings.currency,
                    email: billingAddress.email,
                    first_name: billingAddress.first_name,
                    last_name: billingAddress.last_name,
                    address_line: billingAddress.address_1,
                    address_line2: billingAddress.address_2,
                    address_city: billingAddress.city,
                    address_state: billingAddress.state,
                    address_postcode: billingAddress.postcode,
                    address_country: billingAddress.country,
                    phone: billingAddress.phone
                }

                data.gatewayType = 'afterpay'
            }


            if (button) {
                button.onFinishInsert('input[name="payment_source_apm_token"]', 'payment_source_token');

                const shipping_address = {
                    first_name: shippingAddress.first_name,
                    last_name: shippingAddress.last_name,
                    line1: shippingAddress.address_1,
                    line2: shippingAddress.address_2,
                    country: shippingAddress.country,
                    postcode: shippingAddress.postcode,
                    city: shippingAddress.city,
                    state: shippingAddress.state
                };

                if (shippingRates.length && shippingRates[0].shipping_rates.length) {
                    shippingRates[0].shipping_rates.forEach((rate, key) => {
                        if (!rate.selected) {
                            return;
                        }

                        shipping_address.amount = Number((rate.price / 100).toFixed(3)).toFixed(2)
                        shipping_address.currency = rate.currency_code

                        if (rate.method_id !== 'pickup_location') {
                            return
                        }

                        const rateId = rate.rate_id.split(':')
                        const pickupLocation = settings.pickupLocations[rateId[1]]

                        shipping_address.line1 = pickupLocation.address.address_1
                        shipping_address.line2 = ''
                        shipping_address.country = pickupLocation.address.country
                        shipping_address.postcode = pickupLocation.address.postcode
                        shipping_address.city = pickupLocation.address.city
                        shipping_address.state = pickupLocation.address.state
                    });
                }

                meta.charge = {
                    amount: settings.amount,
                    currency: settings.currency,
                    email: billingAddress.email,
                    first_name: billingAddress.first_name,
                    last_name: billingAddress.last_name,
                    shipping_address: shipping_address,
                    billing_address: {
                        first_name: billingAddress.first_name,
                        last_name: billingAddress.last_name,
                        line1: billingAddress.address_1,
                        line2: billingAddress.address_2,
                        country: billingAddress.country,
                        postcode: billingAddress.postcode,
                        city: billingAddress.city,
                        state: billingAddress.state
                    },
                    items: cart.getCartData().items.map(item => {
                        const result = {
                            name: item.name,
                            amount: item.prices.price / 100,
                            quantity: item.quantity,
                            reference: item.short_description,
                        };

                        if (item.images.length > 0) {
                            result.image_uri = item.images[0].src
                        }

                        return result
                    })
                }
                button.setEnv(settings.isSandbox ? 'sandbox' : 'production')
                button.setMeta(meta);
                button.on('finish', () => {
                    if (settings.directCharge) {
                        data.directCharge = true
                    }

                    if (settings.fraud) {
                        data.fraud = true
                        data.fraudServiceId = settings.fraudServiceId
                    }

                    if (orderButton !== null) {
                        orderButton.click()
                    }
                    paymentCompleteElement.show();
                })
            }
        }, 100)

        useEffect(() => {
            const unsubscribe = onPaymentSetup(async () => {
                const paymentSourceToken = document.querySelector('input[name="payment_source_apm_token"]')
                if (paymentSourceToken === null) {
                    return;
                }

                data.paymentSourceToken = paymentSourceToken.value;
                if (data.paymentSourceToken.length > 0 || settings.selectedToken.length > 0) {
                    return {
                        type: emitResponse.responseTypes.SUCCESS,
                        meta: {
                            paymentMethodData: data
                        },
                    };
                }

                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: labels.fillDataError,
                };
            });
            return () => {
                unsubscribe();
            };
        }, [
            emitResponse.responseTypes.ERROR,
            emitResponse.responseTypes.SUCCESS,
            onPaymentSetup,
            onCheckoutValidation,
        ]);

        return createElement(
            'div',
            {id: 'paydockWidgetApm'},
            createElement('div', {
                id: 'paymentCompleted', style: {
                    display: 'none',
                    'background-color': settings.styles.background_color,
                    'color': settings.styles.success_color,
                    'font-size': settings.styles.font_size,
                    'font-family': settings.styles.font_family,
                }
            }, 'Payment Details Collected'),
            createElement(
                'div',
                null,
                decodeEntities(settings.description || '')
            ),
            createElement('div', {
                class: 'apms-button-wrapper',
            }, createElement('button',
                {
                    type: 'button',
                    id: buttonId,
                    class: `btn-apm btn-apm-${id}`,
                    style: {
                        display: 'none',
                    }
                },
                createElement('img',
                    {
                        src: `/wp-content/plugins/paydock/assets/images/${id}.png`,
                    },
                ),
            ),),
            createElement(
                'div',
                {
                    class: 'paydock-validation-error',
                },
                labels.validationError
            ),
            createElement(
                'input',
                {
                    type: 'hidden',
                    name: 'payment_source_apm_token'
                }
            ),
            createElement(
                "div",
                {
                    class: 'paydock-country-available',
                    style: {
                        display: 'none'
                    }
                },
                labels.notAvailable
            ),
        )
    }
    const Label = (props) => {
        const {PaymentMethodLabel} = props.components;
        return <PaymentMethodLabel text={label}/>;
    };

    const PaydokApms = {
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
        placeOrderButtonLabel: labels.placeOrderButtonLabel,
        canMakePayment: () => canMakePayment(settings.total_limitation, cart.getCartTotals()?.total_price),
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };

    registerPaymentMethod(PaydokApms);
}
