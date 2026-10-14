/**
 * Local stand-in for `@monaco-editor/react`.
 *
 * The shared block controls (`src/controls`, an Essential Blocks submodule)
 * use Monaco for the Advanced → Custom CSS panel. `@monaco-editor/react` does
 * not bundle Monaco: its loader fetches the editor from cdn.jsdelivr.net when
 * the panel opens. That is executable code from a third-party CDN, which
 * WordPress.org Guideline 8 forbids (issue #147). Bundling Monaco itself would
 * add several MB to the zip.
 *
 * webpack.blocks.config.js aliases `@monaco-editor/react` to this file, so the
 * submodule needs no edit (a local edit there is lost on the next sync). It
 * renders a plain monospace textarea with the same props the panel uses.
 *
 * Differences from Monaco, all deliberate:
 * - No syntax highlighting or autocomplete.
 * - `onValidate` is never called. The panel reads `window.monaco` inside that
 *   callback, and Monaco is never loaded, so calling it would throw. Without it
 *   the panel's error list stays empty, as it would for valid CSS.
 */
import { useState } from '@wordpress/element';

/**
 * The text the editor opens with. Monaco treats `value` as controlled and
 * `defaultValue` as the initial text.
 *
 * @param {Object} props Editor props.
 * @return {string} Initial text.
 */
export const initialValue = ( props ) =>
	props?.value ?? props?.defaultValue ?? '';

/**
 * Inline style for the textarea. A number height is pixels, as in Monaco.
 *
 * @param {string|number} height Editor height.
 * @return {Object} Style object.
 */
export const textareaStyle = ( height ) => ( {
	width: '100%',
	height: typeof height === 'number' ? `${ height }px` : height ?? '250px',
	fontFamily: 'Menlo, Consolas, Monaco, "Liberation Mono", monospace',
	fontSize: '12px',
	lineHeight: '1.5',
	tabSize: 4,
	whiteSpace: 'pre',
	boxSizing: 'border-box',
	resize: 'vertical',
} );

/**
 * Drop-in for Monaco's `<Editor>`.
 *
 * @param {Object} props Same props as `@monaco-editor/react`'s Editor.
 * @return {JSX.Element} Textarea editor.
 */
const Editor = ( props ) => {
	const { height, onChange, className, defaultLanguage, language } = props;
	const [ text, setText ] = useState( () => initialValue( props ) );
	const controlled = props?.value !== undefined;

	return (
		<textarea
			className={ [ 'bp-code-editor', className ]
				.filter( Boolean )
				.join( ' ' ) }
			data-language={ language ?? defaultLanguage ?? 'plaintext' }
			aria-label={ props?.[ 'aria-label' ] ?? 'Code editor' }
			spellCheck={ false }
			autoCapitalize="off"
			autoComplete="off"
			style={ textareaStyle( height ) }
			value={ controlled ? props.value : text }
			onChange={ ( event ) => {
				setText( event.target.value );
				onChange?.( event.target.value, event );
			} }
		/>
	);
};

/**
 * Monaco's diff editor. Nothing in this plugin uses it.
 *
 * @return {null} Nothing.
 */
export const DiffEditor = () => null;

/**
 * Monaco's instance hook. There is no Monaco instance.
 *
 * @return {null} Nothing.
 */
export const useMonaco = () => null;

/**
 * Monaco's loader. Never fetches anything.
 */
export const loader = {
	config: () => {},
	init: () =>
		Promise.reject(
			new Error( 'Monaco is not bundled with Better Payment.' )
		),
};

export default Editor;
