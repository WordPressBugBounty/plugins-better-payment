/**
 * Better Payment Quick Setup - React Entry Point
 * 
 * This file serves as the entry point for the React-based Quick Setup wizard.
 * It mounts the QuickSetupWizard component to the DOM and initializes the app.
 */

import { createRoot } from '@wordpress/element';
import QuickSetupWizard from './components/QuickSetupWizard';
import './styles/quick-setup.scss';

/**
 * Initialize the Quick Setup wizard when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('better-payment-quick-setup-wizard-container');

    if (container) {
        const root = createRoot(container);
        root.render(<QuickSetupWizard />);
    }
});

