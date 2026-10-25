/**
 * WordPress dependencies
 */
import { Dashicon } from "@wordpress/components";
import { ButtonGroup, Button } from "@wordpress/components";

const UnitControl = ({ selectedUnit, unitTypes, onClick, useCustomValue = false, setIsTextField }) => {
    return (
        <>
            <ButtonGroup className="eb-unit-control-btn-group" >
                {
                    unitTypes.map((unit, index) => (
                        <Button
                            key={index}
                            className={`eb-unit-control-btn ${unit.value === selectedUnit && "eb-unit-active"
                                }`}
                            isSmall
                            isPrimary={unit.value === selectedUnit}
                            onClick={() => {
                                onClick(unit.value);
                                typeof setIsTextField === 'function' && setIsTextField(false);
                            }}>
                            {unit.label}
                        </Button>
                    ))
                }
                {useCustomValue === true && typeof setIsTextField === 'function' && (
                    <Button
                        className={`eb-unit-control-btn eb-unit-custom-btn ${'' === selectedUnit && "eb-unit-active"
                            }`}
                        isSmall
                        isPrimary={'' === selectedUnit}
                        onClick={() => {
                            onClick('');
                            setIsTextField(true)
                        }}>
                        <Dashicon icon={"edit"} />
                    </Button>
                )}
            </ButtonGroup>
        </>
    )
}

export default UnitControl;
