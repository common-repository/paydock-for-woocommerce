import {useState} from 'react';
import {__} from '@wordpress/i18n';
import {getSetting} from '@woocommerce/settings';

export default (label = 'Security number') => {
    const settings = getSetting('paydock_data', {});

    const wrapperClassName = 'wc-block-components-text-input'
    const [hasError, setHasError] = useState(false)
    const [isActive, setIsActive] = useState(false)

    return (
        <div
            className={wrapperClassName + ' paydock-cvv-code' + (hasError ? ' has-error' : '') + (isActive ? ' is-active' : '')}
            style={{display: 'none'}}>
            <input
                id="cvv"
                name="cvv"
                type="number"
                onChange={event => {
                    const value = event.target.value.trim()

                    setHasError(false)
                    if (value.length > 0 && !/^\d+$/.test(value)) {
                        setHasError(true)
                    }

                    if (value.length > 3) {
                        setHasError(true)
                    }

                    if (!hasError) {
                        settings.cvv = value
                    }
                }}
                onFocus={() => {
                    setIsActive(true)
                }}
                onBlur={event => {
                    if (event.target.value.trim().length === 0) {
                        setIsActive(false)
                    }
                }}
            />
            <label
                htmlFor="cvv">
                {label}
            </label>
            <div
                className="wc-block-components-validation-error"
                style={{
                    display: hasError ? 'block' : 'none'
                }}>
                <p>{__('Please enter a valid CVV code.')}</p>
            </div>
        </div>
    )
}
