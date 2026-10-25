/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Button } from "@wordpress/components";
import { select } from "@wordpress/data";

export default function SupportPanel() {
    const blockName = select('core/block-editor').getSelectedBlock()?.name
    if (!blockName) {
        return null
    }
    // Generate Block key from block full name
    let blockKey = blockName.replace(/essential-blocks\/pro-|essential-blocks\/|-/g, "_");
    if (blockKey.charAt(0) === "_") {
        blockKey = blockKey.substring(1);
    }
    const defaultValue = EssentialBlocksLocalize?.all_blocks_default[blockKey];

    return (
        <>
            {defaultValue && (
                <div className="eb-panel-control eb-support-panel">
                    <div className="eb-block-support">
                        <img src={`${EssentialBlocksLocalize?.eb_plugins_url}assets/images/eb-logo.svg`} alt="Essential Blocks Icon" />
                        <a
                            href="#"
                        // target="_blank"
                        >
                            Need Help?
                        </a>
                    </div>
                    <div className="eb-block-links">
                        <Button href={defaultValue.demo} target="_blank">
                            <img src={`${EssentialBlocksLocalize?.eb_plugins_url}assets/images/demo-icon.svg`} alt="Essential Blocks Icon" />
                            Demo
                        </Button>
                        <Button href={defaultValue.doc} target="_blank">
                            <img src={`${EssentialBlocksLocalize?.eb_plugins_url}assets/images/doc-icon.svg`} alt="Essential Blocks Icon" />
                            Doc
                        </Button>
                    </div>
                </div>
            )}
        </>
    );
}
