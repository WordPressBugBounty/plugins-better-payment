/**
 * Better Payment - Payment Form Block
 *
 * This is the main entry point for the Payment Form Gutenberg block.
 * It registers the block with WordPress and imports all necessary components.
 *
 * @package Better_Payment
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

// Import block components
import Edit from './components/edit';
import save from './components/save';
import attributes from './components/attributes';

// Import styles
import './styles/editor.scss';
import './styles/style.scss';

// Import block icon
import BlockIcon from './components/icon';

/**
 * Register the Payment Form block.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType('better-payment/payment-form', {
    /**
     * Block title displayed in the inserter.
     */
    title: __('Payment Form', 'better-payment'),

    /**
     * Block description displayed in the block inspector.
     */
    description: __(
        'A payment form block for accepting payments through Stripe, PayPal, and Paystack.',
        'better-payment'
    ),

    /**
     * Block category - groups blocks in the inserter.
     */
    category: 'better-payment',

    /**
     * Block icon.
     */
    icon: BlockIcon,

    /**
     * Keywords for block search.
     */
    keywords: [
        __('payment', 'better-payment'),
        __('stripe', 'better-payment'),
        __('paypal', 'better-payment'),
        __('paystack', 'better-payment'),
        __('donate', 'better-payment'),
    ],

    /**
     * Block attributes.
     */
    attributes,

    /**
     * Block example for preview.
     */
    example: {
        attributes: {
            formTitle: __('Payment Form', 'better-payment'),
            formLayout: 'layout-1',
            showTitle: true,
        },
    },

    /**
     * Edit component - renders in the editor.
     */
    edit: Edit,

    /**
     * Save component - returns null for dynamic blocks.
     */
    save,
});
