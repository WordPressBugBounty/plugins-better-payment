import { __ } from '@wordpress/i18n'
import { BaseControl, Button, ButtonGroup } from '@wordpress/components'
import {memo} from '@wordpress/element'
import { useBlockSetAttributes } from '../../hooks'

export const ButtonGroupControl = memo(({
    label = '',
    options,
    currentValue,
    attrName,
    onChange
}) => {
    const setAttributes = useBlockSetAttributes();
    return <BaseControl label={label}>
        <ButtonGroup>
            {options.map((option, index) => (
                <Button
                    key={index}
                    isSecondary={currentValue !== option.value}
                    isPrimary={currentValue === option.value}
                    onClick={() =>{
                        if(onChange) {
                            onChange(option.value);
                        }else {
                            setAttributes({
                                [attrName]: option.value,
                            })
                        }
                    }}
                >
                    {option.label}
                </Button>
            ))}
        </ButtonGroup>
    </BaseControl>
})
