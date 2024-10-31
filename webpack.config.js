const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
    '@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
    '@woocommerce/settings': ['wc', 'wcSettings']
};

const wcHandleMap = {
    '@woocommerce/blocks-registry': 'wc-blocks-registry',
    '@woocommerce/settings': 'wc-settings'
};

const requestToExternal = (request) => {
    if (wcDepMap[request]) {
        return wcDepMap[request];
    }
};

const requestToHandle = (request) => {
    if (wcHandleMap[request]) {
        return wcHandleMap[request];
    }
};

// Export configuration.
module.exports = {
    ...defaultConfig,
    entry: {
        'frontend/blocks': '/resources/js/frontend/index.js',
        'frontend/bank-account-form': '/resources/js/frontend/bank-account-form.js',
        'frontend/pay-pal-wallet': '/resources/js/frontend/pay-pal-wallet.js',
        'frontend/google-pay-wallet': '/resources/js/frontend/google-pay-wallet.js',
        'frontend/apple-pay-wallet': '/resources/js/frontend/apple-pay-wallet.js',
        'frontend/afterpay-wallet': '/resources/js/frontend/afterpay-wallet.js',
        'frontend/afterpay-a-p-m-s': '/resources/js/frontend/afterpay-a-p-m-s.js',
        'frontend/zip-a-p-m-s': '/resources/js/frontend/zip-a-p-m-s.js',
    },
    output: {
        path: path.resolve(__dirname, 'assets/build/js'),
        filename: '[name].js',
    },
    plugins: [
        ...defaultConfig.plugins.filter(
            (plugin) =>
                plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
        ),
        new WooCommerceDependencyExtractionWebpackPlugin({
            requestToExternal,
            requestToHandle
        })
    ]
};
