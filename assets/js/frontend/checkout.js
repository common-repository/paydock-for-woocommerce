const paydockSettings = window.wc.wcSettings.getSetting('paydock_data', {});
const paydockLabel = window.wp.htmlEntities.decodeEntities(paydockSettings.title)
    || window.wp.i18n.__('Paydock', 'paydock_gateway');

const paydockContent = () => {
    return wp.element.createElement(
        'div',
        null,
        wp.element.createElement(
            "div",
            null,
            window.wp.htmlEntities.decodeEntities(paydockSettings.description || '')),
        wp.element.createElement(
            "div", {
                id: 'paydockWidgetCard',
            }
        ), wp.element.createElement(
            "input", {
                type: 'hidden',
                name: 'paydock_payment_source_token'
            }
        )
    );
};

const Block_Paydock_Gateway = {
    name: 'paydock_gateway',
    label: paydockLabel,
    content: Object(window.wp.element.createElement)(paydockContent, null),
    edit: Object(window.wp.element.createElement)(paydockContent, null),
    placeOrderButtonLabel: 'Place Order by Paydock',
    canMakePayment: () => true,
    ariaLabel: paydockLabel,
    supports: {
        features: paydockSettings.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Paydock_Gateway);
