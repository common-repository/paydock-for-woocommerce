import {__} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';
import {useEffect} from 'react';
import {checkboxSavedBankAccountComponent, selectSavedBankAccountsComponent} from '../includes/wc-paydock';

const settings = getSetting('paydock_bank_account_block_data', {});

const textDomain = 'paydock';
const labels = {
    defaultLabel: __('Paydock Payments', textDomain),
    saveBankAcoountLabel: __('Save payment details', textDomain),
    selectTokenLabel: __('Saved payment details', textDomain),
    fillDataError: __('Please fill in the card data.', textDomain)
}

const label = decodeEntities(settings.title) || label.defaultLabel;

let sleepSetTimeout_ctrl;

function sleep(ms) {
    clearInterval(sleepSetTimeout_ctrl);
    return new Promise(resolve => sleepSetTimeout_ctrl = setTimeout(resolve, ms));
}

let formSubmittedAlready = false;
const Content = (props) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentSetup, onCheckoutValidation} = eventRegistration;

    useEffect(() => {
        const validation = onCheckoutValidation(async () => {
            if (window.hasOwnProperty('paydockValidation')) {
                if (!paydockValidation.wcFormValidation()) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        errorMessage: labels.requiredDataError
                    }
                }
            }

            if (settings.selectedToken.trim().length > 0) {
                return true;
            }

            if (formSubmittedAlready) {
                return true;
            }

            window.widgetPaydockBankAccount.trigger(window.paydock.TRIGGER.SUBMIT_FORM);
            let result = false;

            window.widgetPaydockBankAccount.on(window.paydock.EVENT.FINISH, (data) => {
                result = true;
            })

            for (let second = 1; second <= 100; second++) {
                await sleep(100);

                if (result) {
                    break;
                }
            }

            if (result) {
                return true;
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                errorMessage: labels.fillDataError,
            }
        })

        const unsubscribe = onPaymentSetup(async () => {
            const paymentSourceToken = document.querySelector('input[name="payment_source_bank_account_token"]')
            if (paymentSourceToken === null) {
                return;
            }
            settings.paymentSourceToken = paymentSourceToken.value;
            if (settings.paymentSourceToken.length > 0 || settings.selectedToken.length > 0) {
                const data = {...settings}
                data.tokens = '';
                data.styles = '';
                data.supports = '';

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
            validation() && unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
        onCheckoutValidation,
    ]);

    return createElement('div',
        null,
        createElement(
            "div",
            null,
            decodeEntities(settings.description || '')
        ),
        createElement(
            "div",
            {class: 'logo-comm-bank'},
            createElement(
                "img",
                {src: '/wp-content/plugins/paydock/assets/images/logo.png'}
            ),
        ),
        selectSavedBankAccountsComponent(labels.selectTokenLabel),
        createElement(
            "div",
            {id: 'paydockWidgetBankAccount_wrapper'}
        ),
        createElement(
            "input",
            {
                type: 'hidden',
                name: 'payment_source_bank_account_token'
            }
        ),
        checkboxSavedBankAccountComponent(labels.saveBankAcoountLabel)
    );
};


const Label = (props) => {
    const {PaymentMethodLabel} = props.components;
    return <PaymentMethodLabel text={label}/>;
};

const PaydokBankAccountBlock = {
    name: "paydock_bank_account_gateway",
    label: <Label/>,
    content: <Content/>,
    edit: <Content/>,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(PaydokBankAccountBlock);
