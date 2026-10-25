import { getEditorRoot } from './index'

// this function is for creating a unique blockId for each block's unique className
export const duplicateBlockIdFix = ({
    blockPrefix,
    blockId,
    setAttributes,
    clientId,
}) => {
    const getUniqueId = () => blockPrefix + "-" + Math.random().toString(36).substring(2, 7);
    /**
     * Define and Generate Unique Block ID
     */

    if (!blockId) {
        setAttributes({ blockId: getUniqueId() });
    } else {
        const editor = getEditorRoot();
        if (editor) {
            const duplicateBlocks = editor.querySelectorAll(`.root-${blockId}`);

            if (duplicateBlocks.length > 1) {
                const els = Array.prototype.filter.call(duplicateBlocks, el => {
                    // Exclude reusable blocks because they can have the same unique ID.
                    return !el.closest('[data-type="core/block"]')
                })
                for (const block of els) {
                    if (block.dataset.block === clientId) {
                        //dispatch( 'core/block-editor' ).updateBlockAttributes( clientId, { blockId: getUniqueId() })
                        setAttributes({ blockId: getUniqueId() });
                    }
                }
            }
        }
    }
};
