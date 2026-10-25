import { __ } from "@wordpress/i18n";
import {
    BaseControl,
    Button,
    Dropdown,
    RangeControl,
    SelectControl,
    Dashicon
} from "@wordpress/components";
import { useEffect, useState, useMemo } from "@wordpress/element";
import { useSelect } from "@wordpress/data";
import { uniq, isNumber } from "lodash";

import UnitControl from "../unit-control";
import FontPicker from "./fontPicker";
import { TypographyIcon } from "../../extras/icons";
import WithResButtons from "../withResButtons";
import ResetControl from "../reset-control";
import {
    sizeUnitTypes,
    optionsFontWeights,
    optionsFontStyles,
    optionsTextTransforms,
    optionsTextDecorations,
    optionsLhLsp,
} from "./constants";
import { googleFonts } from "./fontPicker/googleFonts";
import { useBlockSetAttributes, useBlockAttributes, useBlockDefaultAttributes, useDeviceType } from "../../hooks";

function TypographyDropdown({
    baseLabel,
    typographyPrefixConstant,
    defaultFontSize,
}) {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const objAttributes = useBlockDefaultAttributes();
    const resOption = useDeviceType();

    const {
        [`${typographyPrefixConstant}FontFamily`]: fontFamily,
        [`${typographyPrefixConstant}FontSource`]: fontSource,
        [`${typographyPrefixConstant}FontWeight`]: fontWeight,
        [`${typographyPrefixConstant}FontStyle`]: fontStyle,
        [`${typographyPrefixConstant}TextTransform`]: textTransform,
        [`${typographyPrefixConstant}TextDecoration`]: textDecoration,
        [`${typographyPrefixConstant}FontSize`]: fontSize = defaultFontSize || undefined,
        [`${typographyPrefixConstant}SizeUnit`]: sizeUnit,
        [`${typographyPrefixConstant}LetterSpacing`]: letterSpacing,
        [`${typographyPrefixConstant}LetterSpacingUnit`]: letterSpacingUnit,
        [`${typographyPrefixConstant}LineHeight`]: lineHeight,
        [`${typographyPrefixConstant}LineHeightUnit`]: lineHeightUnit,

        [`TAB${typographyPrefixConstant}SizeUnit`]: TABsizeUnit,
        [`TAB${typographyPrefixConstant}LetterSpacingUnit`]:
        TABletterSpacingUnit,
        [`TAB${typographyPrefixConstant}LineHeightUnit`]: TABlineHeightUnit,
        [`TAB${typographyPrefixConstant}FontSize`]: TABfontSize,
        [`TAB${typographyPrefixConstant}LetterSpacing`]: TABletterSpacing,
        [`TAB${typographyPrefixConstant}LineHeight`]: TABlineHeight,

        [`MOB${typographyPrefixConstant}SizeUnit`]: MOBsizeUnit,
        [`MOB${typographyPrefixConstant}LetterSpacingUnit`]: MOBletterSpacingUnit,
        [`MOB${typographyPrefixConstant}LineHeightUnit`]: MOBlineHeightUnit,
        [`MOB${typographyPrefixConstant}FontSize`]: MOBfontSize,
        [`MOB${typographyPrefixConstant}LetterSpacing`]: MOBletterSpacing,
        [`MOB${typographyPrefixConstant}LineHeight`]: MOBlineHeight,
    } = attributes;

    //Update Font Weight and Font Varient
    const [ebFontWeight, setEbFontWeight] = useState(optionsFontWeights);
    useEffect(() => {
        const fontFamilyKey = (fontFamily || "").replace(/\s+/g, "-");
        let googleFontWeight = googleFonts[fontFamilyKey]
            ? googleFonts[fontFamilyKey]?.variants.map((i) => (i === "regular" ? "400" : i))
            : [];
        googleFontWeight.push('400', '700')
        googleFontWeight = uniq(googleFontWeight)
        let fontWeightVal = googleFontWeight.map((item) => ({
            label: item,
            value: item,
        }));
        const fontWeightwithDefault = [
            { label: "Default", value: "" },
            ...fontWeightVal,
        ];
        setEbFontWeight(fontWeightwithDefault);
    }, [fontFamily]);

    const typoStyleAttrs = [
        fontFamily,
        fontWeight,
        fontStyle,
        textTransform,
        textDecoration,
        fontSize,
        letterSpacing,
        lineHeight,
        TABfontSize,
        TABletterSpacing,
        TABlineHeight,
        MOBfontSize,
        MOBletterSpacing,
        MOBlineHeight
    ]

    const isCustomTypo = (typoStyleAttrs) => {
        if (typoStyleAttrs.length > 0) {
            const filteredAttrs = typoStyleAttrs.filter((item) => item !== undefined)
            if (filteredAttrs.length > 0) {
                return true
            }
        }
        return false
    }

    const getCustomTypoList = useSelect((select) => {
        let customTypos = { global: 'Global Typography' }
        const globalTypos = select('essential-blocks')?.getGlobalTypography()
        if (globalTypos && globalTypos.custom && typeof globalTypos.custom === 'object' && Object.keys(globalTypos.custom).length > 0) {
            Object.keys(globalTypos.custom).map((item) => {
                if (globalTypos.custom[item].name) {
                    customTypos[item] = globalTypos.custom[item].name
                }
            })
        }
        return customTypos
    }, [])

    const customTypoList = useMemo(() => getCustomTypoList, [getCustomTypoList])
    // const customTypoList = getCustomTypoList

    //Retrieve typography global key for global fonts || return false if custom typo
    const getGlobalSource = (fontSource) => {
        if (!fontSource) {
            return false;
        }
        const parts = fontSource.split(':');
        if (parts.length === 2 && parts[0].trim() === 'global') {
            return parts[1].trim();
        } else if (parts.length === 1 && parts[0].trim() === 'global') {
            return 'global';
        } else {
            return false;
        }
    }

    // 'useEffect' for set fontSource on attribute test
    useEffect(() => {
        if (!fontSource) {
            setAttributes({ [`${typographyPrefixConstant}FontSource`]: isCustomTypo(typoStyleAttrs) ? 'custom' : 'global' });
        }
    }, [])

    return (
        <BaseControl label={__(baseLabel)} className="eb-typography-base">
            <div className="eb-typography-options">
                <Dropdown
                    key={'eb-typography-global'}
                    className={`eb-typography-dropdown eb-typography-global ${getGlobalSource(fontSource) !== false ? 'active' : ''}`}
                    contentClassName={`eb-typography-global-conent`}
                    popoverProps={{ placement: 'bottom-start' }}
                    renderToggle={({ isOpen, onToggle }) => (
                        <Button onClick={onToggle} aria-expanded={isOpen}>
                            <Dashicon icon={"admin-site-alt3"} />
                        </Button>
                    )}
                    // onToggle={() => setAttributes({ [`${typographyPrefixConstant}FontSource`]: 'global' })}
                    renderContent={() => (
                        <div
                            autoFocus
                            className="eb-panel-control eb-typography-global-panel"
                        >
                            {typeof customTypoList === 'object' && Object.keys(customTypoList).map((item, index) => (
                                <div key={index} className="eb-typography-global-options">
                                    <input
                                        type="checkbox"
                                        id={item}
                                        name={item}
                                        checked={getGlobalSource(fontSource) === item ? true : false}
                                        onClick={() => setAttributes({ [`${typographyPrefixConstant}FontSource`]: `global:${item}` })}
                                    />
                                    <label
                                        for={item}
                                        className={getGlobalSource(fontSource) === item ? 'checked' : ''}
                                    >
                                        {customTypoList[item]}
                                    </label>
                                </div>
                            ))}
                        </div>
                    )}
                />
                <Dropdown
                    key={'eb-typography-custom'}
                    className={`eb-typography-dropdown eb-typography-custom ${getGlobalSource(fontSource) === false ? 'active' : ''}`}
                    contentClassName={`eb-typography-custom-content`}
                    popoverProps={{ placement: 'bottom-start' }}
                    renderToggle={({ isOpen, onToggle }) => (
                        <Button
                            onClick={
                                () => {
                                    onToggle()
                                    setAttributes({ [`${typographyPrefixConstant}FontSource`]: 'custom' })
                                }
                            }
                            aria-expanded={isOpen}
                        >
                            <TypographyIcon />
                        </Button>
                    )}
                    renderContent={() => (
                        <div
                            className="eb-panel-control eb-typography-component-panel"
                            style={{ padding: "0.2rem" }}>
                            <FontPicker
                                className="eb-fontpicker-fontfamily"
                                label={__("Font Family", "essential-blocks")}
                                value={fontFamily}
                                onChange={(FontFamily) => {
                                    setAttributes({
                                        [`${typographyPrefixConstant}FontFamily`]:
                                            FontFamily,
                                    });
                                }}
                            />

                            <WithResButtons
                                className="forFontSize"
                            >
                                {resOption === "Desktop" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={sizeUnit}
                                            unitTypes={sizeUnitTypes}
                                            onClick={(sizeUnit) =>
                                                setAttributes({
                                                    [`${typographyPrefixConstant}SizeUnit`]:
                                                        sizeUnit,
                                                })
                                            }
                                        />
                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`${typographyPrefixConstant}FontSize`]:
                                                        defaultFontSize ||
                                                        (
                                                            objAttributes[
                                                            `${typographyPrefixConstant}FontSize`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Font Size",
                                                    "essential-blocks"
                                                )}
                                                value={fontSize}
                                                onChange={(FontSize) =>
                                                    setAttributes({
                                                        [`${typographyPrefixConstant}FontSize`]:
                                                            FontSize,
                                                    })
                                                }
                                                step={sizeUnit === "em" ? 0.1 : 1}
                                                min={0}
                                                max={sizeUnit === "em" ? 10 : 300}
                                            />
                                        </ResetControl>
                                    </>
                                )}

                                {resOption === "Tablet" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={TABsizeUnit}
                                            unitTypes={sizeUnitTypes}
                                            onClick={(TABsizeUnit) =>
                                                setAttributes({
                                                    [`TAB${typographyPrefixConstant}SizeUnit`]:
                                                        TABsizeUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`TAB${typographyPrefixConstant}FontSize`]:
                                                        (
                                                            objAttributes[
                                                            `TAB${typographyPrefixConstant}FontSize`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Font Size",
                                                    "essential-blocks"
                                                )}
                                                value={TABfontSize}
                                                onChange={(FontSize) =>
                                                    setAttributes({
                                                        [`TAB${typographyPrefixConstant}FontSize`]:
                                                            FontSize,
                                                    })
                                                }
                                                step={
                                                    TABsizeUnit === "em" ? 0.1 : 1
                                                }
                                                min={0}
                                                max={
                                                    TABsizeUnit === "em" ? 10 : 300
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}

                                {resOption === "Mobile" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={MOBsizeUnit}
                                            unitTypes={sizeUnitTypes}
                                            onClick={(MOBsizeUnit) =>
                                                setAttributes({
                                                    [`MOB${typographyPrefixConstant}SizeUnit`]:
                                                        MOBsizeUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`MOB${typographyPrefixConstant}FontSize`]:
                                                        (
                                                            objAttributes[
                                                            `MOB${typographyPrefixConstant}FontSize`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Font Size",
                                                    "essential-blocks"
                                                )}
                                                value={MOBfontSize}
                                                onChange={(FontSize) =>
                                                    setAttributes({
                                                        [`MOB${typographyPrefixConstant}FontSize`]:
                                                            FontSize,
                                                    })
                                                }
                                                step={
                                                    MOBsizeUnit === "em" ? 0.1 : 1
                                                }
                                                min={0}
                                                max={
                                                    MOBsizeUnit === "em" ? 10 : 300
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}
                            </WithResButtons>

                            <SelectControl
                                label={__("Font Weight", "essential-blocks")}
                                value={fontWeight}
                                options={ebFontWeight}
                                onChange={(FontWeight) =>
                                    setAttributes({
                                        [`${typographyPrefixConstant}FontWeight`]:
                                            FontWeight,
                                    })
                                }
                            />

                            <SelectControl
                                label={__("Font Style", "essential-blocks")}
                                value={fontStyle}
                                options={optionsFontStyles}
                                onChange={(fontStyle) =>
                                    setAttributes({
                                        [`${typographyPrefixConstant}FontStyle`]:
                                            fontStyle,
                                    })
                                }
                            />

                            <SelectControl
                                label={__("Text Transform", "essential-blocks")}
                                value={textTransform}
                                options={optionsTextTransforms}
                                onChange={(TextTransform) =>
                                    setAttributes({
                                        [`${typographyPrefixConstant}TextTransform`]:
                                            TextTransform,
                                    })
                                }
                            />

                            <SelectControl
                                label={__("Text Decoration", "essential-blocks")}
                                value={textDecoration}
                                options={optionsTextDecorations}
                                onChange={(TextDecoration) =>
                                    setAttributes({
                                        [`${typographyPrefixConstant}TextDecoration`]:
                                            TextDecoration,
                                    })
                                }
                            />

                            <WithResButtons
                                className="forLetterSpacing"
                            >
                                {resOption === "Desktop" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={letterSpacingUnit}
                                            unitTypes={optionsLhLsp}
                                            onClick={(LetterSpacingUnit) =>
                                                setAttributes({
                                                    [`${typographyPrefixConstant}LetterSpacingUnit`]:
                                                        LetterSpacingUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`${typographyPrefixConstant}LetterSpacing`]:
                                                        (
                                                            objAttributes[
                                                            `${typographyPrefixConstant}LetterSpacing`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Letter Spacing",
                                                    "essential-blocks"
                                                )}
                                                value={letterSpacing}
                                                onChange={(LetterSpacing) =>
                                                    setAttributes({
                                                        [`${typographyPrefixConstant}LetterSpacing`]:
                                                            LetterSpacing,
                                                    })
                                                }
                                                min={0}
                                                max={
                                                    letterSpacingUnit === "em"
                                                        ? 10
                                                        : 100
                                                }
                                                step={
                                                    letterSpacingUnit === "em"
                                                        ? 0.1
                                                        : 1
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}

                                {resOption === "Tablet" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={TABletterSpacingUnit}
                                            unitTypes={optionsLhLsp}
                                            onClick={(TABletterSpacingUnit) =>
                                                setAttributes({
                                                    [`TAB${typographyPrefixConstant}LetterSpacingUnit`]:
                                                        TABletterSpacingUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`TAB${typographyPrefixConstant}LetterSpacing`]:
                                                        (
                                                            objAttributes[
                                                            `TAB${typographyPrefixConstant}LetterSpacing`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Letter Spacing",
                                                    "essential-blocks"
                                                )}
                                                value={TABletterSpacing}
                                                onChange={(LetterSpacing) =>
                                                    setAttributes({
                                                        [`TAB${typographyPrefixConstant}LetterSpacing`]:
                                                            LetterSpacing,
                                                    })
                                                }
                                                min={0}
                                                max={
                                                    TABletterSpacingUnit === "em"
                                                        ? 10
                                                        : 100
                                                }
                                                step={
                                                    TABletterSpacingUnit === "em"
                                                        ? 0.1
                                                        : 1
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}

                                {resOption === "Mobile" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={MOBletterSpacingUnit}
                                            unitTypes={optionsLhLsp}
                                            onClick={(MOBletterSpacingUnit) =>
                                                setAttributes({
                                                    [`MOB${typographyPrefixConstant}LetterSpacingUnit`]:
                                                        MOBletterSpacingUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`MOB${typographyPrefixConstant}LetterSpacing`]:
                                                        (
                                                            objAttributes[
                                                            `MOB${typographyPrefixConstant}LetterSpacing`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Letter Spacing",
                                                    "essential-blocks"
                                                )}
                                                value={MOBletterSpacing}
                                                onChange={(LetterSpacing) =>
                                                    setAttributes({
                                                        [`MOB${typographyPrefixConstant}LetterSpacing`]:
                                                            LetterSpacing,
                                                    })
                                                }
                                                min={0}
                                                max={
                                                    MOBletterSpacingUnit === "em"
                                                        ? 10
                                                        : 100
                                                }
                                                step={
                                                    MOBletterSpacingUnit === "em"
                                                        ? 0.1
                                                        : 1
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}
                            </WithResButtons>

                            <WithResButtons
                                className="forLineHeight"
                            >
                                {resOption === "Desktop" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={lineHeightUnit}
                                            unitTypes={optionsLhLsp}
                                            onClick={(LineHeightUnit) =>
                                                setAttributes({
                                                    [`${typographyPrefixConstant}LineHeightUnit`]:
                                                        LineHeightUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`${typographyPrefixConstant}LineHeight`]:
                                                        (
                                                            objAttributes[
                                                            `${typographyPrefixConstant}LineHeight`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Line Height",
                                                    "essential-blocks"
                                                )}
                                                value={lineHeight}
                                                onChange={(LineHeight) =>
                                                    setAttributes({
                                                        [`${typographyPrefixConstant}LineHeight`]:
                                                            LineHeight,
                                                    })
                                                }
                                                min={0}
                                                max={
                                                    lineHeightUnit === "em"
                                                        ? 10
                                                        : 600
                                                }
                                                step={
                                                    lineHeightUnit === "em"
                                                        ? 0.1
                                                        : 1
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}

                                {resOption === "Tablet" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={TABlineHeightUnit}
                                            unitTypes={optionsLhLsp}
                                            onClick={(TABlineHeightUnit) =>
                                                setAttributes({
                                                    [`TAB${typographyPrefixConstant}LineHeightUnit`]:
                                                        TABlineHeightUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`TAB${typographyPrefixConstant}LineHeight`]:
                                                        (
                                                            objAttributes[
                                                            `TAB${typographyPrefixConstant}LineHeight`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Line Height",
                                                    "essential-blocks"
                                                )}
                                                value={TABlineHeight}
                                                onChange={(LineHeight) =>
                                                    setAttributes({
                                                        [`TAB${typographyPrefixConstant}LineHeight`]:
                                                            LineHeight,
                                                    })
                                                }
                                                min={0}
                                                max={
                                                    TABlineHeightUnit === "em"
                                                        ? 10
                                                        : 600
                                                }
                                                step={
                                                    TABlineHeightUnit === "em"
                                                        ? 0.1
                                                        : 1
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}

                                {resOption === "Mobile" && (
                                    <>
                                        <UnitControl
                                            selectedUnit={MOBlineHeightUnit}
                                            unitTypes={optionsLhLsp}
                                            onClick={(MOBlineHeightUnit) =>
                                                setAttributes({
                                                    [`MOB${typographyPrefixConstant}LineHeightUnit`]:
                                                        MOBlineHeightUnit,
                                                })
                                            }
                                        />

                                        <ResetControl
                                            onReset={() =>
                                                setAttributes({
                                                    [`MOB${typographyPrefixConstant}LineHeight`]:
                                                        (
                                                            objAttributes[
                                                            `MOB${typographyPrefixConstant}LineHeight`
                                                            ] || {}
                                                        ).default,
                                                })
                                            }>
                                            <RangeControl
                                                label={__(
                                                    "Line Height",
                                                    "essential-blocks"
                                                )}
                                                value={MOBlineHeight}
                                                onChange={(LineHeight) =>
                                                    setAttributes({
                                                        [`MOB${typographyPrefixConstant}LineHeight`]:
                                                            LineHeight,
                                                    })
                                                }
                                                min={0}
                                                max={
                                                    MOBlineHeightUnit === "em"
                                                        ? 10
                                                        : 600
                                                }
                                                step={
                                                    MOBlineHeightUnit === "em"
                                                        ? 0.1
                                                        : 1
                                                }
                                            />
                                        </ResetControl>
                                    </>
                                )}
                            </WithResButtons>
                        </div>
                    )}
                />
            </div>
        </BaseControl >
    );
}

export default TypographyDropdown;
