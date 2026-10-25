import DimensionsControl from "./dimensions";
import UnitControl from "../unit-control";
import { useState } from "@wordpress/element";
import { useBlockAttributes, useBlockDefaultAttributes, useBlockSetAttributes, useDeviceType } from "../../hooks";

export default function ResponsiveDimensionsControl({
    controlName,
    baseLabel,
    forBorderRadius,
    disableLeftRight = false,
    disableLinkedButton = false,
    useCustomValue = false
}) {
    const attributes = useBlockAttributes();
    const setAttributes = useBlockSetAttributes();
    const resOption = useDeviceType();

    const {
        [`${controlName}isLinked`]: dimensionIsLinked,

        [`${controlName}Unit`]: dimensionUnit,
        [`${controlName}Top`]: dimensionTop,
        [`${controlName}Right`]: dimensionRight,
        [`${controlName}Bottom`]: dimensionBottom,
        [`${controlName}Left`]: dimensionLeft,

        [`TAB${controlName}Unit`]: TABdimensionUnit,
        [`TAB${controlName}Top`]: TABdimensionTop,
        [`TAB${controlName}Right`]: TABdimensionRight,
        [`TAB${controlName}Bottom`]: TABdimensionBottom,
        [`TAB${controlName}Left`]: TABdimensionLeft,

        [`MOB${controlName}Unit`]: MOBdimensionUnit,
        [`MOB${controlName}Top`]: MOBdimensionTop,
        [`MOB${controlName}Right`]: MOBdimensionRight,
        [`MOB${controlName}Bottom`]: MOBdimensionBottom,
        [`MOB${controlName}Left`]: MOBdimensionLeft,
    } = attributes;

    const UNITS = [
        { label: "px", value: "px" },
        { label: "em", value: "em" },
        { label: "%", value: "%" },
    ];

    const neededProps = {
        resOption,
        baseLabel,
        forBorderRadius,
        setAttributes,
        dimensionIsLinked,
        controlName,
    };

    const [isTextField, setIsTextField] = useState('')

    return (
        <>
            {resOption == "Desktop" && (
                <>
                    <UnitControl
                        useCustomValue={useCustomValue}
                        setIsTextField={setIsTextField}
                        selectedUnit={dimensionUnit}
                        unitTypes={UNITS}
                        onClick={(newDimensionUnit) => {
                            setAttributes({ [`${controlName}Unit`]: newDimensionUnit })
                        }}
                    />
                    <DimensionsControl
                        // key={`${controlName}-${dimensionTop}-${dimensionRight}-${dimensionBottom}-${dimensionLeft}`}
                        top={dimensionTop}
                        right={dimensionRight}
                        bottom={dimensionBottom}
                        left={dimensionLeft}
                        neededProps={neededProps}
                        disableLeftRight={disableLeftRight}
                        disableLinkedButton={disableLinkedButton}
                        isTextField={isTextField}
                        setIsTextField={setIsTextField}
                        selectedUnit={dimensionUnit}
                        useCustomValue={useCustomValue}
                        onChange={({ top, right, bottom, left }) => {
                            setAttributes({
                                [`${controlName}Top`]: top,
                                [`${controlName}Right`]: right,
                                [`${controlName}Bottom`]: bottom,
                                [`${controlName}Left`]: left,
                            })
                        }}
                    />
                </>
            )}
            {resOption == "Tablet" && (
                <>
                    <UnitControl
                        useCustomValue={useCustomValue}
                        setIsTextField={setIsTextField}
                        selectedUnit={TABdimensionUnit}
                        unitTypes={UNITS}
                        onClick={(newTABdimensionUnit) => {
                            setAttributes({ [`TAB${controlName}Unit`]: newTABdimensionUnit })
                        }}
                    />

                    <DimensionsControl
                        top={TABdimensionTop}
                        right={TABdimensionRight}
                        bottom={TABdimensionBottom}
                        left={TABdimensionLeft}
                        neededProps={neededProps}
                        disableLeftRight={disableLeftRight}
                        disableLinkedButton={disableLinkedButton}
                        isTextField={isTextField}
                        setIsTextField={setIsTextField}
                        selectedUnit={TABdimensionUnit}
                        onChange={({ top, right, bottom, left }) =>
                            setAttributes({
                                [`TAB${controlName}Top`]: top,
                                [`TAB${controlName}Right`]: right,
                                [`TAB${controlName}Bottom`]: bottom,
                                [`TAB${controlName}Left`]: left,
                            })
                        }
                    />
                </>
            )}
            {resOption == "Mobile" && (
                <>
                    <UnitControl
                        useCustomValue={useCustomValue}
                        setIsTextField={setIsTextField}
                        selectedUnit={MOBdimensionUnit}
                        unitTypes={UNITS}
                        onClick={(newMOBdimensionUnit) => {
                            setAttributes({ [`MOB${controlName}Unit`]: newMOBdimensionUnit })
                        }}
                    />

                    <DimensionsControl
                        top={MOBdimensionTop}
                        right={MOBdimensionRight}
                        bottom={MOBdimensionBottom}
                        left={MOBdimensionLeft}
                        neededProps={neededProps}
                        disableLeftRight={disableLeftRight}
                        disableLinkedButton={disableLinkedButton}
                        isTextField={isTextField}
                        setIsTextField={setIsTextField}
                        selectedUnit={MOBdimensionUnit}
                        onChange={({ top, right, bottom, left }) =>
                            setAttributes({
                                [`MOB${controlName}Top`]: top,
                                [`MOB${controlName}Right`]: right,
                                [`MOB${controlName}Bottom`]: bottom,
                                [`MOB${controlName}Left`]: left,
                            })
                        }
                    />
                </>
            )}
        </>
    );
}
