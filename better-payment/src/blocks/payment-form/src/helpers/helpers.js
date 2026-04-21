const ATTRIBUTE_CONFIG = {
    // Global Settings Attributes
    paypalEnabled: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_general_general_paypal'
    },
    paypalBusinessEmail: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_paypal_email'
    },
    paypalLiveMode: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_payment_paypal_live_mode'
    },
    stripeEnabled: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_general_general_stripe'
    },
    stripeLiveMode: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_payment_stripe_live_mode'
    },
    stripeTestPublicKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_stripe_test_public'
    },
    stripeTestSecretKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_stripe_test_secret'
    },
    stripeLivePublicKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_stripe_live_public'
    },
    stripeLiveSecretKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_stripe_live_secret'
    },
    paystackEnabled: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_general_general_paystack'
    },
    paystackLiveMode: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_payment_paystack_live_mode'
    },
    paystackTestPublicKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_paystack_test_public'
    },
    paystackTestSecretKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_paystack_test_secret'
    },
    paystackLivePublicKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_paystack_live_public'
    },
    paystackLiveSecretKey: {
        type: 'string',
        settingsKey: 'better_payment_settings_payment_paystack_live_secret'
    },
    currency: {
        type: 'string',
        settingsKey: 'better_payment_settings_general_general_currency'
    },
    emailNotificationEnabled: {
        type: 'boolean',
        settingsKey: 'better_payment_settings_general_general_email'
    }
};

/**
 * Currency data matching PHP get_currency_data().
 */
const CURRENCY_DATA = {
    'USD': { name: 'US Dollar', symbol: '$' },
    'EUR': { name: 'Euro', symbol: '€' },
    'GBP': { name: 'British Pound', symbol: '£' },
    'AED': { name: 'UAE Dirham', symbol: 'د.إ' },
    'AUD': { name: 'Australian Dollar', symbol: '$' },
    'BGN': { name: 'Bulgarian Lev', symbol: 'лв' },
    'BAM': { name: 'Bosnia and Herzegovina Convertible Mark', symbol: 'KM' },
    'CAD': { name: 'Canadian Dollar', symbol: '$' },
    'CHF': { name: 'Swiss Franc', symbol: 'CHF' },
    'CZK': { name: 'Czech Koruna', symbol: 'Kč' },
    'DKK': { name: 'Danish Krone', symbol: 'kr' },
    'GHS': { name: 'Ghanaian Cedi', symbol: '₵' },
    'HKD': { name: 'Hong Kong Dollar', symbol: '$' },
    'HUF': { name: 'Hungarian Forint', symbol: 'ft' },
    'ILS': { name: 'Israeli Shekel', symbol: '₪' },
    'JPY': { name: 'Japanese Yen', symbol: '¥' },
    'KES': { name: 'Kenyan Shilling', symbol: 'Ksh.' },
    'MXN': { name: 'Mexican Peso', symbol: '$' },
    'MYR': { name: 'Malaysian Ringgit', symbol: 'MYR' },
    'NGN': { name: 'Nigerian Naira', symbol: '₦' },
    'NOK': { name: 'Norwegian Krone', symbol: 'kr' },
    'NZD': { name: 'New Zealand Dollar', symbol: '$' },
    'PHP': { name: 'Philippine Peso', symbol: '₱' },
    'PLN': { name: 'Polish Zloty', symbol: 'zł' },
    'RON': { name: 'Romanian Leu', symbol: 'lei' },
    'RUB': { name: 'Russian Ruble', symbol: '₽' },
    'SEK': { name: 'Swedish Krona', symbol: 'kr' },
    'SGD': { name: 'Singapore Dollar', symbol: '$' },
    'THB': { name: 'Thai Baht', symbol: '฿' },
    'TRY': { name: 'Turkish Lira', symbol: '₺' },
    'TWD': { name: 'Taiwan Dollar', symbol: '$' },
    'ZAR': { name: 'South African Rand', symbol: 'R' },
};

/**
 * Get currency symbol from currency code.
 * Matches PHP Helper::get_currency_symbol().
 *
 * @param {string} currencyCode The currency code (e.g., 'USD', 'EUR').
 * @return {string} The currency symbol or the code if not found.
 */
export const getCurrencySymbol = (currencyCode) => {
    if (CURRENCY_DATA[currencyCode]) {
        return CURRENCY_DATA[currencyCode].symbol;
    }
    return currencyCode; // Fallback to currency code
};

/**
 * Convert text to snake_case.
 * Matches PHP Helper::titleToSnake().
 *
 * @param {string} text    The text to convert.
 * @param {string} divider The divider character (default: '_').
 * @return {string} The snake_case text.
 */
export const titleToSnake = (text, divider = '_') => {
    if (text === null || text === undefined || text === '') {
        return 'n_a';
    }

    text = String(text);
    // Replace non-alphanumeric characters with the divider
    text = text.replace(/[^\w]+/g, divider);
    // Trim divider from edges
    text = text.replace(new RegExp(`^${divider}+|${divider}+$`, 'g'), '');
    // Replace multiple dividers with single
    text = text.replace(new RegExp(`${divider}+`, 'g'), divider);
    text = text.toLowerCase();

    if (!text) {
        return 'n_a';
    }

    return text;
};

/**
 * Normalize icon class string for rendering.
 *
 * Dashicons need both `dashicons` and the icon class (e.g. `dashicons dashicons-admin-site`).
 *
 * @param {string} iconClass Raw icon class value.
 * @return {string} Normalized class string.
 */
export const normalizeIconClass = (iconClass) => {
    const value = (iconClass || '').trim();
    if (!value) {
        return value;
    }

    if (value.includes('dashicons-') && !value.includes('dashicons ')) {
        return `dashicons ${value}`;
    }

    return value;
};

export const mapSettingsToAttributes = (settings) => {
    const state = {};

    // Handle missing or empty settings gracefully
    if (!settings || typeof settings !== 'object') {
        return state;
    }

    Object.entries(ATTRIBUTE_CONFIG).forEach(([attr, config]) => {
        let value = settings[config.settingsKey];

        if (config.type === 'boolean') {
            value = value === 'yes';
        }

        state[attr] = value ?? '';
    });

    return state;
};
