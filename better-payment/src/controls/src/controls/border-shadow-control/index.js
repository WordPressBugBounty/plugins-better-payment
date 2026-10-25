/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n'
import {
    ToggleControl,
    Button,
    RangeControl,
    BaseControl,
    ButtonGroup,
    SelectControl,
    Dropdown,
    PanelRow
} from '@wordpress/components'

/**
 * Internal dependencies
 */

import ColorControl from '../color-control'
import ResetControl from '../reset-control'
import ResponsiveDimensionsControl from '../dimensions-control-v2'
import { TypographyIcon } from '../../extras/icons'
import {
    useBlockAttributes,
    useBlockDefaultAttributes,
    useBlockSetAttributes, useDeviceType,
} from '../../hooks'
import { HoverOption } from '../../components'
import { isGradientColor } from '../../helpers'

export default function BorderShadowControl({
    controlName,
    noBorder,
    noShadow,
    noBdrHover,
    noShdowHover,
    noBorderRadius,
    isGradient = true
}) {
    const setAttributes = useBlockSetAttributes()
    const defaultAttributes = useBlockDefaultAttributes()
    const attributes = useBlockAttributes()
    const {
        [`${controlName}borderStyle`]: borderStyle,
        [`${controlName}borderColor`]: borderColor,
        [`${controlName}shadowType`]: shadowType,
        [`${controlName}shadowColor`]: shadowColor,
        [`${controlName}hOffset`]: hOffset,
        [`${controlName}vOffset`]: vOffset,
        [`${controlName}blur`]: blur,
        [`${controlName}spread`]: spread,
        [`${controlName}hoverShadowColor`]: hoverShadowColor,
        [`${controlName}hoverHOffset`]: hoverHOffset,
        [`${controlName}hoverVOffset`]: hoverVOffset,
        [`${controlName}hoverBlur`]: hoverBlur,
        [`${controlName}hoverSpread`]: hoverSpread,
        [`${controlName}inset`]: inset,

        [`${controlName}BorderType`]: borderType = 'normal',
        [`${controlName}HborderStyle`]: HborderStyle,
        [`${controlName}HborderColor`]: HborderColor,

        [`${controlName}borderTransition`]: borderTransition,
        [`${controlName}radiusTransition`]: radiusTransition,
        [`${controlName}shadowTransition`]: shadowTransition,
    } = attributes

    const handleAttributeChange = (name, value) => {
        if (borderType) {
            if (borderType === 'normal') {
                setAttributes({
                    [`${controlName}${name}`]: value,
                })
            } else {
                setAttributes({
                    [`${controlName}H${name}`]: value,
                })
            }
        }
    }

    const getValue = (name, hoverPrefix = 'hover') => {
        if (borderType && borderType === 'hover') {
            return attributes[`${controlName}${hoverPrefix}${name}`]
        }
        return attributes[`${controlName}${name}`]
    }

    const getAttrName = (name, hoverPrefix = 'hover') => {
        if (borderType && borderType === 'hover') {
            return `${controlName}${hoverPrefix}${name}`
        }
        return `${controlName}${name}`
    }

    return (
        <>
            {noBorder !== true && (
                <>
                    {!noBdrHover && (
                        <HoverOption
                            attrName={`${controlName}BorderType`}
                            currentValue={borderType}
                        />
                    )}
                    <>
                        <SelectControl
                            label={__('Border Style', 'essential-blocks')}
                            value={getValue('borderStyle', 'H')}
                            options={[
                                {
                                    label: __('None', 'essential-blocks'),
                                    value: 'none',
                                },
                                {
                                    label: __('Dashed', 'essential-blocks'),
                                    value: 'dashed',
                                },
                                {
                                    label: __('Solid', 'essential-blocks'),
                                    value: 'solid',
                                },
                                {
                                    label: __('Dotted', 'essential-blocks'),
                                    value: 'dotted',
                                },
                                {
                                    label: __('Double', 'essential-blocks'),
                                    value: 'double',
                                },
                                {
                                    label: __('Groove', 'essential-blocks'),
                                    value: 'groove',
                                },
                                {
                                    label: __('Inset', 'essential-blocks'),
                                    value: 'inset',
                                },
                                {
                                    label: __('Outset', 'essential-blocks'),
                                    value: 'outset',
                                },
                                {
                                    label: __('Ridge', 'essential-blocks'),
                                    value: 'ridge',
                                },
                            ]}
                            onChange={(borderStyle) => handleAttributeChange(
                                'borderStyle', borderStyle)}
                        />
                        {((borderType === 'normal' && borderStyle !== 'none') || (borderType === 'hover' && HborderStyle !== 'none')) && (
                            <>
                                <ColorControl
                                    defaultColor={(defaultAttributes[`${controlName}borderColor`] || {}).default}
                                    label={__('Border Color', 'essential-blocks')}
                                    color={getValue('borderColor', 'H')}
                                    attributeName={getAttrName('borderColor', 'H')}
                                    key={getAttrName('borderColor', 'H')}
                                    isGradient={isGradient}
                                />
                                <ResponsiveDimensionsControl
                                    controlName={getAttrName('Bdr_', 'H')}
                                    baseLabel="Border Width"
                                    key={getAttrName('Bdr_', 'H')}
                                />
                            </>
                        )}
                        {!noBorderRadius && !isGradientColor(getValue('borderColor', 'H')) && (
                            <>
                                <ResponsiveDimensionsControl
                                    forBorderRadius
                                    controlName={getAttrName('Rds_', 'H')}
                                    baseLabel="Border Radius"
                                    key={getAttrName('Rds_', 'H')}
                                />
                            </>
                        )}
                        {borderType === 'hover' && HborderStyle !== 'none' && (
                            <RangeControl
                                label={__('Border Transition',
                                    'essential-blocks')}
                                value={borderTransition}
                                onChange={(borderTransition) =>
                                    setAttributes({
                                        [`${controlName}borderTransition`]: borderTransition,
                                    })
                                }
                                step={0.01}
                                min={0}
                                max={5}
                            />
                        )}
                    </>
                </>
            )}
            {noShadow !== true && (
                <>
                    <BaseControl label={__('Box Shadow', 'essential-blocks')} className="eb-typography-base eb-control-boxshadow-wrap">
                        <Dropdown
                            className="eb-typography-dropdown"
                            contentClassName="my-popover-content-classname"
                            placement="bottom right"
                            renderToggle={({ isOpen, onToggle }) => (
                                <Button isSmall onClick={onToggle}
                                    aria-expanded={isOpen}>
                                    <TypographyIcon />
                                </Button>
                            )}
                            renderContent={() => (
                                <>
                                    <div
                                        className="eb-panel-control"
                                        style={{
                                            minWidth: '230px',
                                            padding: '10px',
                                        }}
                                    >
                                        {!noShdowHover && (
                                            <HoverOption
                                                attrName={`${controlName}shadowType`}
                                                currentValue={shadowType}
                                            />
                                        )}

                                        <ToggleControl
                                            label={__('Inset',
                                                'essential-blocks')}
                                            checked={inset}
                                            onChange={() =>
                                                setAttributes({
                                                    [`${controlName}inset`]: !inset,
                                                })
                                            }
                                        />

                                        {(shadowType === 'normal' ||
                                            noShdowHover) && (
                                                <>
                                                    <ColorControl
                                                        defaultColor={(defaultAttributes[`${controlName}shadowColor`] ||
                                                            {}).default}
                                                        label={__('Shadow Color',
                                                            'essential-blocks')}
                                                        color={shadowColor}
                                                        onChange={(shadowColor) =>
                                                            setAttributes({
                                                                [`${controlName}shadowColor`]: shadowColor,
                                                            })
                                                        }
                                                    />

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}hOffset`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Horizontal Offset',
                                                                'essential-blocks')}
                                                            value={hOffset}
                                                            onChange={(hOffset) =>
                                                                setAttributes({
                                                                    [`${controlName}hOffset`]: hOffset,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}vOffset`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Vertical Offset',
                                                                'essential-blocks')}
                                                            value={vOffset}
                                                            onChange={(vOffset) =>
                                                                setAttributes({
                                                                    [`${controlName}vOffset`]: vOffset,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}blur`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__('Shadow Blur',
                                                                'essential-blocks')}
                                                            value={blur}
                                                            onChange={(blur) =>
                                                                setAttributes({
                                                                    [`${controlName}blur`]: blur,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}spread`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Shadow Spread',
                                                                'essential-blocks')}
                                                            value={spread}
                                                            onChange={(spread) =>
                                                                setAttributes({
                                                                    [`${controlName}spread`]: spread,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>
                                                </>
                                            )}

                                        {shadowType === 'hover' &&
                                            !noShdowHover && (
                                                <>
                                                    <ColorControl
                                                        defaultColor={(defaultAttributes[`${controlName}hoverShadowColor`] ||
                                                            {}).default}
                                                        label={__(
                                                            'Hover Shadow Color',
                                                            'essential-blocks')}
                                                        color={hoverShadowColor}
                                                        onChange={(hoverShadowColor) =>
                                                            setAttributes({
                                                                [`${controlName}hoverShadowColor`]: hoverShadowColor,
                                                            })
                                                        }
                                                    />

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}hoverHOffset`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Horizontal Offset',
                                                                'essential-blocks')}
                                                            value={hoverHOffset}
                                                            onChange={(hoverHOffset) =>
                                                                setAttributes({
                                                                    [`${controlName}hoverHOffset`]: hoverHOffset,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}hoverVOffset`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Vertical Offset',
                                                                'essential-blocks')}
                                                            value={hoverVOffset}
                                                            onChange={(hoverVOffset) =>
                                                                setAttributes({
                                                                    [`${controlName}hoverVOffset`]: hoverVOffset,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}hoverBlur`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Shadow Blur',
                                                                'essential-blocks')}
                                                            value={hoverBlur}
                                                            onChange={(hoverBlur) =>
                                                                setAttributes({
                                                                    [`${controlName}hoverBlur`]: hoverBlur,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <ResetControl
                                                        onReset={() =>
                                                            setAttributes({
                                                                [`${controlName}hoverSpread`]: undefined,
                                                            })
                                                        }
                                                    >
                                                        <RangeControl
                                                            label={__(
                                                                'Shadow Spread',
                                                                'essential-blocks')}
                                                            value={hoverSpread}
                                                            onChange={(hoverSpread) =>
                                                                setAttributes({
                                                                    [`${controlName}hoverSpread`]: hoverSpread,
                                                                })
                                                            }
                                                            min={0}
                                                            max={200}
                                                        />
                                                    </ResetControl>

                                                    <RangeControl
                                                        label={__(
                                                            'Shadow Transition',
                                                            'essential-blocks')}
                                                        value={shadowTransition}
                                                        onChange={(shadowTransition) =>
                                                            setAttributes({
                                                                [`${controlName}shadowTransition`]: shadowTransition,
                                                            })
                                                        }
                                                        step={0.01}
                                                        min={0}
                                                        max={5}
                                                    />
                                                </>
                                            )}
                                    </div>
                                </>
                            )}
                        />
                        <ResetControl
                            onReset={() =>
                                setAttributes({
                                    [`${controlName}shadowColor`]: undefined,
                                    [`${controlName}hOffset`]: undefined,
                                    [`${controlName}vOffset`]: undefined,
                                    [`${controlName}blur`]: undefined,
                                    [`${controlName}spread`]: undefined,
                                    [`${controlName}inset`]: false, // Default to false or as per requirement
                                    [`${controlName}hoverShadowColor`]: undefined,
                                    [`${controlName}hoverHOffset`]: undefined,
                                    [`${controlName}hoverVOffset`]: undefined,
                                    [`${controlName}hoverBlur`]: undefined,
                                    [`${controlName}hoverSpread`]: undefined,
                                    [`${controlName}shadowTransition`]: undefined,
                                })
                            }
                        ></ResetControl>
                    </BaseControl>
                </>
            )}
        </>
    )
}
