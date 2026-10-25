/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { useEffect, useState } from "@wordpress/element";
import { applyFilters } from "@wordpress/hooks";

export default function DynamicImageHandler(props) {
    const {
        src,
        alt,
        className
    } = props

    const [content, setContent] = useState("")

    useEffect(() => {
        applyFilters(
            'essential_blocks_dynamic_image_handle',
            content,
            setContent,
            src,
            alt || '',
            className || ''
        )
        const imageHtml = `<img src="${src}" alt="${alt}" />`;
        setContent(<div dangerouslySetInnerHTML={{ __html: imageHtml }} />);
    }, [src, alt, className])

    return content
}

