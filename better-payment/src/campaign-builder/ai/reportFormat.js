/**
 * Assistant-message formatting — a deliberately tiny Markdown subset.
 *
 * The panel used to print every reply as raw text inside a chat bubble. That is
 * fine for "Done, I updated the headline", but the Analyze turn returns a
 * *report* — a verdict line plus findings of the shape
 * `- **Progress Bar** — the goal label repeats the amount.` — and rendered raw
 * that is a wall of asterisks and hyphens. It read like debug output for the one
 * feature whose entire job is to be read.
 *
 * The subset is intentionally minimal: bold, headings, bullets and numbered
 * lists. There is no link or image support and no HTML passthrough — this parses
 * model output, and every node it emits is rendered as a React child (i.e. as
 * text), never as markup. Anything unrecognised falls through as a paragraph, so
 * an unexpected reply degrades to exactly what it does today.
 *
 * Pure and exported for Jest — see tests/JS/aiReportFormat.test.js.
 */

const BULLET = /^\s*(?:[-*•])\s+(.*)$/;
const ORDERED = /^\s*\d+[.)]\s+(.*)$/;
const HEADING = /^\s*#{1,6}\s+(.*)$/;

/**
 * Split a line into bold / plain runs.
 *
 * @param {string} text Line of text, possibly containing `**bold**`.
 * @return {Array<{text: string, bold: boolean}>} Runs, in order.
 */
export function parseInline( text ) {
	const runs = [];
	// Non-greedy so `**a** and **b**` is two bold runs, not one spanning both.
	const pattern = /\*\*(.+?)\*\*|__(.+?)__/g;
	let last = 0;
	let match = pattern.exec( text );

	while ( match !== null ) {
		if ( match.index > last ) {
			runs.push( { text: text.slice( last, match.index ), bold: false } );
		}
		runs.push( { text: match[ 1 ] ?? match[ 2 ] ?? '', bold: true } );
		last = match.index + match[ 0 ].length;
		match = pattern.exec( text );
	}

	if ( last < text.length ) {
		runs.push( { text: text.slice( last ), bold: false } );
	}
	// An empty string still needs one run so callers can render unconditionally.
	return runs.length ? runs : [ { text: '', bold: false } ];
}

/**
 * Parse a reply into renderable blocks.
 *
 * @param {string} text Raw assistant message.
 * @return {Array<Object>} Blocks: `heading`, `list` (with `ordered`), `para`.
 */
export function parseReport( text ) {
	const source = String( text ?? '' );
	if ( ! source.trim() ) return [];

	const blocks = [];
	let list = null; // Open list being accumulated.
	let para = []; // Open paragraph's lines.

	const closeList = () => {
		if ( list ) blocks.push( list );
		list = null;
	};
	const closePara = () => {
		if ( para.length ) {
			blocks.push( {
				type: 'para',
				runs: parseInline( para.join( ' ' ) ),
			} );
		}
		para = [];
	};

	for ( const line of source.split( '\n' ) ) {
		const heading = line.match( HEADING );
		const bullet = line.match( BULLET );
		const ordered = line.match( ORDERED );

		if ( heading ) {
			closeList();
			closePara();
			blocks.push( {
				type: 'heading',
				runs: parseInline( heading[ 1 ] ),
			} );
			continue;
		}

		if ( bullet || ordered ) {
			closePara();
			const isOrdered = ! bullet;
			// A switch between bullet and numbered starts a new list rather than
			// silently renumbering the items into the open one.
			if ( ! list || list.ordered !== isOrdered ) {
				closeList();
				list = { type: 'list', ordered: isOrdered, items: [] };
			}
			list.items.push( parseInline( ( bullet ?? ordered )[ 1 ] ) );
			continue;
		}

		if ( ! line.trim() ) {
			closeList();
			closePara();
			continue;
		}

		closeList();
		para.push( line.trim() );
	}

	closeList();
	closePara();

	return blocks;
}
