import { ShapeDividerList } from "./shapeDividerList";

export const ShapeDividerContent = ({ position, style, negative }) => {
    const shapeLists = ShapeDividerList();
    const Content = negative
        ? shapeLists[style + "_negative"]?.component
        : shapeLists[style]?.component;

    const TopContent = () => {
        return (
            <>
                <div
                    className="eb-shape-divider eb-shape-divider-top"
                    data-shape={style}
                    data-negative={negative ? "true" : "false"}
                >
                    {Content != undefined && <Content />}
                </div>
            </>
        );
    };

    const BottomContent = () => {
        return (
            <div
                className="eb-shape-divider eb-shape-divider-bottom"
                data-shape={style}
                data-negative={negative ? "true" : "false"}
            >
                {Content != undefined && <Content />}
            </div>
        );
    };

    return (
        <>
            {position == "top" && <TopContent />}
            {position == "bottom" && <BottomContent />}
        </>
    );
};
