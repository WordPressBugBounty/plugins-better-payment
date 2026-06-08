import { __ } from '@wordpress/i18n';

export function openWpMedia( onSelect ) {
    if ( ! window.wp?.media ) return;
    const frame = window.wp.media( {
        title: __( 'Select Image', 'better-payment' ),
        multiple: false,
        library: { type: 'image' },
        button: { text: __( 'Select', 'better-payment' ) },
    } );
    frame.on( 'select', () => {
        const att = frame.state().get( 'selection' ).first().toJSON();
        onSelect( { url: att.url, id: att.id, sizes: att.sizes || {} } );
    } );
    frame.open();
}
