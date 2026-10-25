import { useState, useEffect } from "@wordpress/element";
import { getDefaultAttributes } from '../helpers'

export const useBlockDefaults = (name, blockDefaults, setBlockDefaults, objAttributes, contentAttributes = []) => {
    const [isDefaultSet, setDefaultSet] = useState(false);

    /**
     * Function to remove content attributes from All Attributes
     * @param {object} obj
     * @param {Array} keysToRemove
     * @returns {object}
     */
    function removeProps(obj, keysToRemove) {
        const newObj = { ...obj }; // Create a shallow copy of the object
        keysToRemove.forEach(key => {
            delete newObj[key]; // Delete the key from the new object
        });
        return newObj;
    }

    /**
     * Update block defaults
     */
    useEffect(() => {
        if (blockDefaults && Object.keys(blockDefaults).length === 0) {
            const filteredAttr = removeProps(getDefaultAttributes(objAttributes), contentAttributes)
            setBlockDefaults({
                [name]: filteredAttr
            })
        }
    }, []);

    useEffect(() => {
        if (!isDefaultSet && (blockDefaults && Object.keys(blockDefaults).length > 0)) {
            setDefaultSet(true);
        }
    }, [blockDefaults])

    return isDefaultSet
}
