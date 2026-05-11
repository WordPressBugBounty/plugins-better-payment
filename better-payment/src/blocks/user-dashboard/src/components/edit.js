/**
 * Edit component for User Dashboard block.
 *
 * @package Better_Payment
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { memo, useEffect } from '@wordpress/element';
import { withBlockContext, BlockProps } from '@better-payment/controls';
import classnames from 'classnames';

// Import components
import Inspector from './inspector';
import DashboardPreview from './preview';
import Style from './style';
import defaultAttributes from './attributes';

/**
 * Edit component - displayed in the block editor.
 *
 * @param {Object} props Block properties
 * @param {Object} props.attributes Block attributes
 * @param {Function} props.setAttributes Function to update attributes
 * @param {string} props.clientId Block client ID
 * @param {boolean} props.isSelected Whether block is selected
 * @returns {JSX.Element} Edit component
 */
function Edit(props) {
    const { attributes, setAttributes, clientId, isSelected } = props;
    const { blockId, dashboardLayout } = attributes;

    // Set blockId from clientId on first insert
    useEffect(() => {
        if (!blockId) {
            setAttributes({ blockId: clientId });
        }
    }, []);

    // Build block props with custom classes
    const blockProps = useBlockProps({
        className: classnames(
            'better-payment-user-dashboard',
            blockId && `bp-${blockId}`,
            `bp-dashboard-${dashboardLayout}`
        ),
    });

    // Wire Style component into BlockProps.Edit via enhancedProps
    const enhancedProps = {
        ...props,
        style: <Style {...props} />,
    };

    return (
        <>
            {isSelected && (
                <InspectorControls>
                    <Inspector attributes={attributes} setAttributes={setAttributes} />
                </InspectorControls>
            )}
            <BlockProps.Edit {...enhancedProps}>
                <div {...blockProps}>
                    <DashboardPreview attributes={attributes} />
                </div>
            </BlockProps.Edit>
        </>
    );
}

export default memo(withBlockContext(defaultAttributes)(Edit));
