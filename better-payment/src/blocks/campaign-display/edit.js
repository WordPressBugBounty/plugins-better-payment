import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Placeholder, Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
    const { campaignId } = attributes;
    const blockProps = useBlockProps();

    const campaigns = useSelect( ( select ) => {
        return select( coreStore ).getEntityRecords( 'postType', 'bp_campaign', {
            per_page: 100,
            status: 'publish,draft',
            _fields: 'id,title',
        } );
    }, [] );

    const campaignOptions = campaigns
        ? [
              { value: 0, label: __( '— Select a campaign —', 'better-payment' ) },
              ...campaigns.map( ( c ) => ( { value: c.id, label: c.title?.rendered || c.title?.raw || String( c.id ) } ) ),
          ]
        : [ { value: 0, label: __( 'Loading campaigns…', 'better-payment' ) } ];

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Campaign', 'better-payment' ) }>
                    <SelectControl
                        label={ __( 'Select Campaign', 'better-payment' ) }
                        value={ campaignId }
                        options={ campaignOptions }
                        onChange={ ( val ) => setAttributes( { campaignId: parseInt( val, 10 ) } ) }
                    />
                    { campaignId > 0 && (
                        <p>
                            <a
                                href={ `${ window.betterPaymentAdmin?.adminUrl || '' }admin.php?page=bp-campaign-builder&campaign_id=${ campaignId }` }
                                target="_blank"
                                rel="noreferrer"
                            >
                                { __( 'Edit in Campaign Builder →', 'better-payment' ) }
                            </a>
                        </p>
                    ) }
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                { ! campaigns && <Spinner /> }
                { campaigns && ! campaignId && (
                    <Placeholder
                        icon="heart"
                        label={ __( 'Campaign Display', 'better-payment' ) }
                        instructions={ __( 'Select a campaign from the block settings panel.', 'better-payment' ) }
                    />
                ) }
                { campaignId > 0 && (
                    <ServerSideRender
                        block="better-payment/campaign-display"
                        attributes={ attributes }
                    />
                ) }
            </div>
        </>
    );
}
