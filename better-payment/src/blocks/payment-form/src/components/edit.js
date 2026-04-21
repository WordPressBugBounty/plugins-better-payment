/**
 * Edit component for the Payment Form block.
 *
 * This component renders the block in the editor and provides
 * the editing interface for block attributes.
 *
 * @package Better_Payment
 * @since 1.0.0
 */

import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import classnames from 'classnames';
import { useEffect, memo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
// Import the inspector controls
import Inspector from './inspector';
import Layout1 from './layouts/layout-1';
import Layout2 from './layouts/layout-2';
import Layout3 from './layouts/layout-3';
import { mapSettingsToAttributes } from '../helpers/helpers';
import Style from './style';
import defaultAttributes from "./attributes";
import { BlockProps, withBlockContext } from '@better-payment/controls';
/**
 * Edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set block attributes.
 * @param {boolean}  props.isSelected    Whether the block is selected.
 * @returns {JSX.Element} The edit component.
 */
function Edit(props) {
    const { attributes, setAttributes, isSelected, clientId } = props;
    const { initialSettingsApplied, formLayout, blockId } = attributes;

    useEffect(() => {
        if ( ! blockId ) {
            setAttributes( { blockId: clientId } );
        }
        if (initialSettingsApplied) {
            return;
        }
        const settings = window?.betterPaymentBlockData?.betterPaymentSettings;
        // Only apply settings if they exist; otherwise rely on default attributes
        const attributesFromSettings = settings ? mapSettingsToAttributes(settings) : {};
        setAttributes({ ...attributes, ...attributesFromSettings, initialSettingsApplied: true });
    }, []);

    // Fetch product data when payment source or product ID changes
    const { paymentSource, woocommerceProductId, fluentcartProductId, stripeDefaultPriceId } = attributes;

    useEffect(() => {
        if (paymentSource === 'woocommerce' && woocommerceProductId) {
            const productId = parseInt(woocommerceProductId, 10);
            if (productId > 0) {
                apiFetch({ path: `/wc/v3/products/${productId}` })
                    .then((product) => {
                        if (product) {
                            setAttributes({
                                productName: product.name || '',
                                productPrice: product.price || '',
                                productPermalink: product.permalink || '',
                            });
                        }
                    })
                    .catch(() => {
                        // Product not found or WooCommerce REST API not available
                    });
            }
        } else if (paymentSource === 'fluentcart' && fluentcartProductId) {
            const productId = parseInt(fluentcartProductId, 10);
            if (productId > 0) {
                apiFetch({ path: `/better-payment/v1/fluentcart-product/${productId}` })
                    .then((product) => {
                        if (product) {
                            setAttributes({
                                productName: product.name || '',
                                productPrice: product.price || '',
                                productPermalink: product.permalink || '',
                            });
                        }
                    })
                    .catch(() => {
                        // Product not found or FluentCart REST API not available
                    });
            }
        } else if (paymentSource === 'stripe' && stripeDefaultPriceId) {
            // Validate Stripe Price ID format (should start with 'price_')
            if (stripeDefaultPriceId.startsWith('price_')) {
                apiFetch({ path: `/better-payment/v1/stripe-price?price_id=${encodeURIComponent(stripeDefaultPriceId)}` })
                    .then((result) => {
                        if (result && result.success) {
                            setAttributes({
                                productName: result.product_name || '',
                                productPrice: String(result.amount) || '',
                                productPermalink: '',
                            });
                        }
                    })
                    .catch(() => {
                        // Stripe API error or price not found
                    });
            }
        } else if (paymentSource !== 'woocommerce' && paymentSource !== 'fluentcart' && paymentSource !== 'stripe') {
            // Clear product data when switching away from product-based payment sources
            if (attributes.productName || attributes.productPrice || attributes.productPermalink) {
                setAttributes({
                    productName: '',
                    productPrice: '',
                    productPermalink: '',
                });
            }
        }
    }, [paymentSource, woocommerceProductId, fluentcartProductId, stripeDefaultPriceId]);

    const postId = useSelect( ( select ) => {
        return select( 'core/editor' ).getCurrentPostId();
    }, [] );

    const serialized = JSON.stringify({
        page_id: postId,
        widget_id: attributes.blockId || clientId
    });

    // Build block props with custom classes
    const blockProps = useBlockProps({
        className: classnames('better-payment-block', `bp-form-${formLayout}`),
    });

    // you must declare this variable
    const enhancedProps = {
        ...props,
        style: <Style {...props} />
    };

    return (
        <>
            {isSelected &&
                <InspectorControls>
                    <Inspector
                        attributes={attributes}
                        setAttributes={setAttributes}
                    />
                </InspectorControls>
            }
            <BlockProps.Edit {...enhancedProps}>
                <div {...blockProps}>
                    {(formLayout === 'layout-1') && <Layout1 attributes={attributes} setAttributes={setAttributes} serialized={serialized} pageId={postId} />}
                    {(formLayout === 'layout-2') && <Layout2 attributes={attributes} setAttributes={setAttributes} serialized={serialized} pageId={postId} />}
                    {(formLayout === 'layout-3') && <Layout3 attributes={attributes} setAttributes={setAttributes} serialized={serialized} pageId={postId} />}
                </div>
            </BlockProps.Edit>
        </>
    );
}
export default memo(withBlockContext(defaultAttributes)(Edit));