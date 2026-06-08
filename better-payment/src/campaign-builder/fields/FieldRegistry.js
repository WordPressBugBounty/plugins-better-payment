/**
 * Campaign element registry — reads from PHP-localized data.
 *
 * PHP (CampaignElements.php) defines element schemas and localizes them to
 * window.betterPaymentCampaignData.elements. This module provides a typed
 * interface over that data so the rest of the builder doesn't access the
 * global directly.
 *
 * Falls back to a minimal built-in list when the global is unavailable
 * (e.g., during unit tests or when wp_localize_script hasn't run).
 */

const FALLBACK = [
    { type: 'campaign_title',       label: 'Campaign Title',   icon: 'heading',          defaultSettings: { font_size: '2rem', color: '#1a1a2e' },                      settingsSchema: [] },
    { type: 'campaign_description', label: 'Description',      icon: 'editor-paragraph', defaultSettings: {},                                                            settingsSchema: [] },
    { type: 'photo',                label: 'Photo',            icon: 'format-image',     defaultSettings: { src: '', src_id: 0, src_sizes: {}, alt: '', size: 'full', width: 100, align: 'center' }, settingsSchema: [] },
    { type: 'progress_bar',         label: 'Progress Bar',     icon: 'chart-bar',        defaultSettings: { show_percent: true, show_amounts: true },                    settingsSchema: [] },
    { type: 'campaign_summary',     label: 'Campaign Summary', icon: 'info',             defaultSettings: { show_raised: true, show_donors: true, show_days: true },     settingsSchema: [] },
    { type: 'donation_form',        label: 'Donate Button',    icon: 'heart',            defaultSettings: { button_text: 'Donate Now' },                                 settingsSchema: [] },
    { type: 'organizer',            label: 'Organizer',        icon: 'admin-users',      defaultSettings: { show_avatar: true, show_name: true, label: 'Campaign by' }, settingsSchema: [] },
    { type: 'donate_amount',        label: 'Donate Amount',    icon: 'money-alt',        defaultSettings: { preset_amounts: '10,25,50,100', allow_custom: true },        settingsSchema: [] },
    { type: 'social_sharing',       label: 'Social Sharing',   icon: 'share',            defaultSettings: { show_facebook: true, show_twitter: true, show_whatsapp: true }, settingsSchema: [] },
    { type: 'social_links',         label: 'Social Links',     icon: 'admin-links',      defaultSettings: { facebook: '', twitter: '', instagram: '', youtube: '', website: '' }, settingsSchema: [] },
];

function getRegistry() {
    return window?.betterPaymentCampaignData?.elements || FALLBACK;
}

export function getAllElements() {
    return getRegistry();
}

export function getElement( type ) {
    return getRegistry().find( ( el ) => el.type === type ) || null;
}

export function getDefaultSettings( type ) {
    return getElement( type )?.defaultSettings || {};
}

export default getAllElements;
