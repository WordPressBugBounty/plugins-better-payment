import { PanelRow, ColorPalette, ColorPicker } from "@wordpress/components";
import { select } from "@wordpress/data";
import { useEffect, useState } from "@wordpress/element";

export const AdvancedColorPicker = ({ color, setColor }) => {
    const [isEssentialBlocksStore, setIsEssentialBlocksStore] = useState(false);
    useEffect(() => {
        if (select("essential-blocks")) {
            setIsEssentialBlocksStore(true);
        }
    }, []);

    const themeColors = select('core/block-editor').getSettings().colors || undefined;
    const generateColorArray = (colors) => {
        const updatedColorArr = []
        if (!colors || colors.length === 0) {
            return updatedColorArr
        }
        colors.map((item) => {
            updatedColorArr.push({
                color: `var(${item?.var})`,
                name: item?.name,
                slug: item?.slug
            })
        })

        return updatedColorArr;
    };
    const globalColors = isEssentialBlocksStore ? select("essential-blocks").getGlobalColors() : undefined;
    const modifiedGlobalColors = globalColors ? generateColorArray(globalColors) : undefined;
    const customColors = isEssentialBlocksStore ? select("essential-blocks").getCustomColors() : undefined;
    const modifiedCustomColors = customColors ? generateColorArray(customColors) : undefined;

    const combinedArray = [
        ...(themeColors !== undefined ? themeColors : []),
        ...(globalColors !== undefined ? globalColors : []),
        ...(customColors !== undefined ? customColors : []),
    ];

    const getColorByVariable = (variable) => {
        const colorObject = combinedArray.find(color => color.var === variable);
        return colorObject ? colorObject.color : null;
    }

    const getColorValue = (value) => {
        const startsWithVar = /^var/i.test(value);
        if (startsWithVar) {
            const regex = /\(([^)]+)\)/;
            const matches = value.match(regex);
            const valueInsideBrackets = matches ? matches[1] : null;
            const colorValue = getColorByVariable(valueInsideBrackets);
            if (colorValue) {
                return colorValue;
            }
        }
        return value;
    }

    return (
        <>
            <ColorPicker
                color={getColorValue(color)}
                onChangeComplete={({ rgb }) => {
                    setColor(
                        `rgba(${rgb.r},${rgb.g},${rgb.b},${rgb.a})`
                    );
                }}
            />
            {modifiedGlobalColors && modifiedGlobalColors.length > 0 && (
                <>
                    <PanelRow>GLOBAL</PanelRow>
                    <ColorPalette
                        colors={modifiedGlobalColors}
                        value={color}
                        disableCustomColors={true}
                        clearable={false}
                        // enableAlpha= {true}
                        isGradientColor={true}
                        onChange={(color) => setColor(color)}
                    />
                </>
            )}
            {themeColors && themeColors.length > 0 && (
                <>
                    <PanelRow>THEME</PanelRow>
                    <ColorPalette
                        colors={themeColors}
                        value={color}
                        disableCustomColors={true}
                        clearable={false}
                        // enableAlpha= {true}
                        // isGradientColor={true}
                        onChange={(color) => setColor(color)}
                    />
                </>
            )}
            {modifiedCustomColors && modifiedCustomColors.length > 0 && (
                <>
                    <PanelRow>Custom Colors</PanelRow>
                    <ColorPalette
                        colors={modifiedCustomColors}
                        value={color}
                        disableCustomColors={true}
                        clearable={false}
                        // enableAlpha= {true}
                        isGradientColor={true}
                        onChange={(color) => setColor(color)}
                    />
                </>
            )}
        </>
    );
}
