/**
 * Builds the compact { layout, meta } context sent to the AI server.
 *
 * The server further trims this (PromptBuilder::compact_context), but we already
 * send only the two fields the model needs — never rendered HTML or transient UI
 * state (selection, saving flags, ...).
 */
export function buildContext( state ) {
    return {
        layout: state?.layout || { layout: '1-column', columns: [] },
        meta: state?.meta || {},
    };
}
