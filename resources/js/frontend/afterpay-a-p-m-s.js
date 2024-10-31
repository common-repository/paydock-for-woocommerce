import walletsForm from "../includes/apms";

walletsForm(
    'afterpay',
    'Paydock Afterpay',
    'paydockAPMsAfterpayButton',
    [
        'first_name',
        'last_name',
        'email',
        'address_1',
        'city',
        'state',
        'country',
        'postcode',
    ],
    ['au', 'nz', 'us', 'ca', 'uk', 'gb', 'fr', 'it', 'es', 'de']
);
