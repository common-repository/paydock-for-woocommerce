import {createElement} from 'react';
import {getSetting} from '@woocommerce/settings';

export default (saveCardLabel = 'Save card') => {
    const settings = getSetting('paydock_apms_data', {});

    if (!settings.isUserLoggedIn || (settings.afterpaySaveCard === false && settings.zippaySaveCard === false)) {
        return '';
    }

    return createElement("div",
        {
            class: 'wc-block-components-checkbox amps-save-card',
        },
        createElement("label",
            {
                for: 'apms_save_card',
                onChange: e => {
                    settings.apmSaveCardChecked = e.target.checked
                }
            },
            createElement("input",
                {
                    class: 'wc-block-components-checkbox__input',
                    id: 'apms_save_card',
                    type: 'checkbox',
                    name: 'apms_save_card'
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
                saveCardLabel
            )
        )
    )
}
