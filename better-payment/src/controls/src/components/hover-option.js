import { BaseControl, Button, ButtonGroup } from '@wordpress/components'
import { useBlockSetAttributes } from '../hooks'
import { memo } from '@wordpress/element'
import { ButtonGroupControl } from '../controls/button-group-control'

/**
 * Represents a component for handling hover option.
 *
 * @param {Object} options - The options for the HoverOption component.
 * @param {string} options.attrName - The name of the attribute to set when the option is selected.
 * @param {string} options.currentValue - The current value of the hover option.
 * @param {function} options.onChange - The callback function to be called when the option is changed.
 * @returns {JSX.Element} - The JSX element representing the HoverOption component.
 */
export const HoverOption = memo(({ attrName, currentValue, onChange}) => {
    return (
        <ButtonGroupControl
            options={[
                {
                    label: 'Normal',
                    value: 'normal',
                },
                {
                    label: 'Hover',
                    value: 'hover',
                },
            ]}
            attrName={attrName}
            currentValue={currentValue}
            onChange={onChange}
        />
    )
})
