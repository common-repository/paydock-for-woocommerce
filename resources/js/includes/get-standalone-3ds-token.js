import {getSetting} from '@woocommerce/settings';

export default async () => {
    const data = {...getSetting('paydock_data', {})};
    data.action = 'paydock_get_vault_token';
    data.type = 'standalone-3ds-token';
    data._wpnonce = PaydockAjax.wpnonce;

    if (document.querySelector('#shipping-first_name') !== null) {
        data.first_name = document.querySelector('#shipping-first_name').value
    }
    if (document.querySelector('#billing-first_name') !== null) {
        data.first_name = document.querySelector('#billing-first_name').value
    }

    if (document.querySelector('#shipping-last_name') !== null) {
        data.last_name = document.querySelector('#shipping-last_name').value
    }
    if (document.querySelector('#billing-last_name') !== null) {
        data.last_name = document.querySelector('#billing-last_name').value
    }

    if (document.querySelector('#shipping-phone') !== null) {
        data.phone = document.querySelector('#shipping-phone').value
    }
    if (document.querySelector('#billing-phone') !== null) {
        data.phone = document.querySelector('#billing-phone').value
    }

    if (document.querySelector('#email') !== null) {
        data.email = document.querySelector('#email').value;
    }

    data.tokens = '';
    data.styles = '';
    data.supports = '';

    return jQuery.post(PaydockAjax.url, data).then();
}
