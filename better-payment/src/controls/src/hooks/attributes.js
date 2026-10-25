import { useContextSelector, createContext as createContextSelector } from "./use-context-selector";
import { createContext, useContext } from "@wordpress/element";

export const DEFAULT_BLOCK_ATTRIBUTES_CONTEXT = {}
export const DEFAULT_SET_ATTRIBUTES_CONTEXT = () => { }
export const AttributesContext = createContextSelector(DEFAULT_BLOCK_ATTRIBUTES_CONTEXT)
export const SetAttributesContext = createContext(DEFAULT_SET_ATTRIBUTES_CONTEXT)
const SELECTOR_DEFAULT = state => state

export const useBlockAttributes = (selectorFn = SELECTOR_DEFAULT) => {
    return useContextSelector(AttributesContext, state => {
        return selectorFn(state)
    })
}

export const useBlockSetAttributes = () => {
    return useContext(SetAttributesContext)
}

export const DefaultAttributesContext = createContext({});
export const useBlockDefaultAttributes = () => {
    return useContext(DefaultAttributesContext);
}
