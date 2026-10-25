/**
 * Fill the receipt's print iframe with DOM APIs, never with a string of HTML.
 *
 * It used to `doc.write()` a template with `${ document.title }` and the page's
 * CSS text in it. `document.title` is the decoded title, so a post title saved as
 * `&lt;/title&gt;&lt;img onerror=…&gt;` (which kses lets through, it holds no tag)
 * became real markup in a same-origin iframe; a CSS rule holding `</style>` (a
 * block's Custom CSS) did the same. Here the title and the CSS are only ever text.
 *
 * @param {Document} doc     The iframe's document.
 * @param {Element}  element The receipt to print.
 * @param {string}   title   The page title.
 * @param {string}   styles  The page's CSS text.
 */
export function buildPrintDocument( doc, element, title, styles ) {
	doc.open();
	doc.write( '<!DOCTYPE html><html><head></head><body></body></html>' );
	doc.close();

	doc.title = title;

	const style = doc.createElement( 'style' );
	style.textContent = styles;
	doc.head.appendChild( style );

	doc.body.appendChild( doc.importNode( element, true ) );
}
