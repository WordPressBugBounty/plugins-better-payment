/**
 * Better Payment - User Dashboard Block
 *
 * Get a complete overview of payments with a centralized dashboard. Transaction history, subscription status & payment analytics in one place.
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
 * Register the User Dashboard block.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType('better-payment/user-dashboard', {
    /**
     * Block title displayed in the inserter.
     */
    title: __('User Dashboard', 'better-payment'),

    /**
     * Block description displayed in the block inspector.
     */
    description: __(
        'Get a complete overview of payments with a centralized dashboard. Transaction history, subscription status & payment analytics in one place.',
        'better-payment'
    ),

    /**
     * Block category - groups blocks in the inserter.
     */
    category: 'better-payment',

    /**
     * Block icon - shown in the inserter.
     */
    icon: BlockIcon,

    /**
     * Block attributes.
     */
    attributes,

    /**
     * Edit component - shown in the block editor.
     */
    edit: Edit,

    /**
     * Save component - determines what gets saved to post content.
     * Returning null makes this a dynamic block (PHP-rendered).
     */
    save,
});
