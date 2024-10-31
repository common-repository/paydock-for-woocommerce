import Select from 'react-select'
import {getSetting} from '@woocommerce/settings';

export default (selectTokenLabel = 'Saved cards', newCardLabel = 'New card') => {
    const settings = getSetting('paydock_data', {});

    if (!settings.hasOwnProperty('tokens') || typeof settings.tokens !== "object") {
        return '';
    }

    const tokens = settings.tokens.filter(token => token.type === 'card')
    if (!settings.cardSaveCard || !settings.isUserLoggedIn || tokens.length === 0) {
        return '';
    }

    const options = [{
        label: newCardLabel,
        value: ''
    }];

    tokens.forEach(token => {
        const cardScheme = token.card_scheme.charAt(0).toUpperCase() + token.card_scheme.slice(1)
        const expireMonth = token.expire_month < 10 ? `0${token.expire_month}` : token.expire_month;
        const label = `${cardScheme} ${token.card_number_last4} ${expireMonth}/${token.expire_year}`;
        options.push({
            label: label,
            value: token.vault_token
        })
    })

    return (
        <div className="paydock-select-saved-cards">
            <label style={{
                fontSize: '1rem',
                fontWeight: 'bold',
                lineHeight: 2,
            }} htmlFor="select-saved-cards">
                {selectTokenLabel}
            </label>
            <Select
                inputId="select-saved-cards"
                label={selectTokenLabel}
                styles={
                    {
                        control: styles => ({...styles, marginBottom: '20px'})
                    }
                }
                options={options}
                onChange={(option) => {
                    const value = option.value.trim()
                    const $saveCard = jQuery('.card-save-card')
                    settings.selectedToken = value

                    $saveCard.show()
                    jQuery('#paydockWidgetCard_wrapper').show()

                    if (value !== '') {
                        const token = settings.tokens.find(token => token.vault_token === value)
                        if (token !== undefined) {
                            jQuery('#paydockWidgetCard_wrapper').hide()

                            $saveCard.hide()
                        }
                    }

                }}
            />
        </div>
    )
}
