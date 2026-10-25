import "./style.scss";
/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { MediaPlaceholder } from "@wordpress/block-editor";
import { Button } from "@wordpress/components";
import { useState, useRef } from "@wordpress/element";
import AIImageIcon from "../../icons/ai-image-icon";
import AIImagePopover from "../AI/AIImagePopover";

/**
 * Enhanced MediaPlaceholder wrapper component for Essential Blocks
 *
 * @param {Object} props Component props
 * @returns {JSX.Element} Enhanced MediaPlaceholder component
 */
export const EBMediaPlaceholder = (props) => {
    const {
        onSelect,
        onSelectURL,
        onError,
        accept = "image/*",
        allowedTypes = ["image"],
        multiple = false,
        value,
        labels = {
            title: __("Media", "essential-blocks"),
            instructions: __("Drag media file, upload or select files from your library.", "essential-blocks"),
        },
        mediaLibraryButton,
        addToGallery,
        isAppender,
        dropZoneUIOnly,
        disableMediaButtons,
        className,
        icon,
        children,
        enableAI = true,
        ...otherProps
    } = props;

    // State for AI popover visibility
    const [isAIPopoverVisible, setIsAIPopoverVisible] = useState(false);
    const aiButtonRef = useRef(null);

    /**
     * Handle AI button click to toggle popover
     */
    const handleAIButtonClick = () => {
        setIsAIPopoverVisible(!isAIPopoverVisible);
    };

    /**
     * Handle AI image generation success
     */
    const handleAIImageGenerated = (imageData) => {
        if (onSelect) {
            onSelect(imageData);
        }
        setIsAIPopoverVisible(false);
    };

    return (
        <div className={`eb-media-placeholder-wrapper ${className || ""}`}>
            <MediaPlaceholder
                onSelect={onSelect}
                onSelectURL={onSelectURL}
                onError={onError}
                accept={accept}
                allowedTypes={allowedTypes}
                multiple={multiple}
                value={value}
                labels={labels}
                mediaLibraryButton={mediaLibraryButton}
                addToGallery={addToGallery}
                isAppender={isAppender}
                dropZoneUIOnly={dropZoneUIOnly}
                disableMediaButtons={disableMediaButtons}
                icon={icon}
                {...otherProps}
            >
                {children}
                {enableAI && window.EssentialBlocksLocalize?.enableGenerateImage === "1" && (
                    <>
                        <Button
                            ref={aiButtonRef}
                            icon={<AIImageIcon />}
                            variant="secondary"
                            label={__("Generate with AI", "essential-blocks")}
                            showTooltip
                            tooltipPosition="top center"
                            onClick={handleAIButtonClick}
                            className="eb-placeholder-ai-img-button"
                        >
                            {__("Generate with AI", "essential-blocks")}
                        </Button>
                        <AIImagePopover
                            isVisible={isAIPopoverVisible}
                            onClose={() => setIsAIPopoverVisible(false)}
                            anchor={aiButtonRef.current}
                            onImageGenerated={handleAIImageGenerated}
                            value={value}
                        />
                    </>
                )}
            </MediaPlaceholder>
        </div>
    );
};
