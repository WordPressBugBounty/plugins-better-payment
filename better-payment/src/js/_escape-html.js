/**
 * Escape a value for an HTML text node or a quoted attribute.
 *
 * For markup built as a string (template literals handed to jQuery or
 * `doc.write`). Values from widget settings are set by Contributors, who have
 * no `unfiltered_html`, so they must never reach a string of HTML raw.
 *
 * @param {*} value Anything; `null` and `undefined` become ''.
 * @return {string} The value with & < > " ' escaped.
 */
const HTML_ENTITIES = {
	'&': '&amp;',
	'<': '&lt;',
	'>': '&gt;',
	'"': '&quot;',
	"'": '&#039;',
};

export function escapeHtml( value ) {
	return String( value ?? '' ).replace(
		/[&<>"']/g,
		( char ) => HTML_ENTITIES[ char ]
	);
}
