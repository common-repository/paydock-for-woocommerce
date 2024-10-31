import {createElement} from 'react';
import {getSetting} from '@woocommerce/settings';

export default (saveBankAccountLabel = 'Save bank account') => {
    const settings = getSetting('paydock_bank_account_block_data', {});

    if (!settings.bankAccountSaveAccount || !settings.isUserLoggedIn) {
        return '';
    }

    return createElement("div",
        {class: 'wc-block-components-checkbox bank-account-save-card'},
        createElement("label",
            {
                for: 'bank_account_save',
                onChange: e => {
                    settings.bankAccountSaveChecked = e.target.checked
                }
            },
            createElement("input",
                {
                    class: 'wc-block-components-checkbox__input',
                    id: 'bank_account_save',
                    type: 'checkbox',
                    name: 'bank_account_save'
                }
            ),
            createElement("svg",
                {
                    class: 'wc-block-components-checkbox__mark',
                    "aria-hidden": true,
                    xmlns: 'http://www.w3.org/2000/svg',
                    "viewBox": '0 0 24 20'
                },
                createElement("path", {d: 'M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z'})
            ),
            createElement("span",
                {class: 'wc-block-components-checkbox__label'},
                saveBankAccountLabel
            )
        )
    )
}
