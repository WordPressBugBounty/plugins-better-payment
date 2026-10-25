import { createHigherOrderComponent } from "@wordpress/compose";

import {
    AttributesContext,
    SetAttributesContext,
    DefaultAttributesContext,
} from "../hooks";

export const withBlockContext = (defaultAttributes) => {
    return createHigherOrderComponent((WrappedComponent) => props => {
        const setAttributesHandler = props.handleBlockDefault ? props.handleBlockDefault : props.setAttributes;
        const attributes = props.blockDefaults ? props.blockDefaults : props.attributes;

        return (
            <DefaultAttributesContext.Provider value={defaultAttributes}>
                <AttributesContext.Provider value={attributes}>
                    <SetAttributesContext.Provider value={setAttributesHandler}>
                        <WrappedComponent {...props} />
                    </SetAttributesContext.Provider>
                </AttributesContext.Provider>
            </DefaultAttributesContext.Provider>
        );
    }, 'withBlockContext')
}
