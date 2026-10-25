import { select, useSelect } from "@wordpress/data";
import { store as editorStore } from '@wordpress/editor';

export const useDeviceType = () => {
    const deviceType = useSelect((select) => {
        const { getDeviceType } = select(editorStore)
        const editorType = `core/${eb_conditional_localize?.editor_type || "edit-post"}`;
        let __experimentalGetPreviewDeviceType = select(editorType)?.__experimentalGetPreviewDeviceType;
        return getDeviceType?.() || __experimentalGetPreviewDeviceType?.() || 'Desktop';
    });
    return deviceType
}
