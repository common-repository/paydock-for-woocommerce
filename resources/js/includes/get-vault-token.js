import {getSetting} from '@woocommerce/settings';

export default async () => {
    const data = {...getSetting('paydock_data', {})}
    data.action = 'paydock_get_vault_token';
    data._wpnonce = PaydockAjax.wpnonce;
    data.tokens = '';
    data.styles = '';
    data.supports = '';

    return jQuery.post(PaydockAjax.url, data).then();
}
