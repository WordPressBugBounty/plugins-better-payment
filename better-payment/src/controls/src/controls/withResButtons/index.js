import { dispatch } from "@wordpress/data";

import {
  handleDesktopBtnClick,
  handleMobileBtnClick,
  handleTabBtnClick,
} from "../../helpers";
import { useBlockSetAttributes, useDeviceType } from "../../hooks";

export default function WithResButtons({
  className,
  children,
  label, // this prop is passed only from background control
}) {
  const setAttributes = useBlockSetAttributes();
  const resOption = useDeviceType();

  const getMethodForSettingPreviewDeviceType = () => {
      return dispatch('core/editor').setDeviceType;
  };

  return (
    <div className={`wrap_res ${className || " "}`}>
      <div className={`${label ? `resBtns` : `resIcons`}`}>
        {/* 'label' prop is used in background-control */}
        {label && (
          <span style={{ paddingRight: "5px" }} className="resLabel">
            {label}
          </span>
        )}
        <span
          onClick={() =>
            handleDesktopBtnClick({
              setAttributes,
              setPreviewDeviceType: getMethodForSettingPreviewDeviceType(),
            })
          }
          className={`typoResButton dashicons dashicons-desktop ${
            resOption === "Desktop" ? "active" : " "
          }`}
        ></span>
        <span
          onClick={() =>
            handleTabBtnClick({
              setAttributes,
              setPreviewDeviceType: getMethodForSettingPreviewDeviceType(),
            })
          }
          className={`typoResButton dashicons dashicons-tablet ${
            resOption === "Tablet" ? "active" : " "
          }`}
        ></span>
        <span
          onClick={() =>
            handleMobileBtnClick({
              setAttributes,
              setPreviewDeviceType: getMethodForSettingPreviewDeviceType(),
            })
          }
          className={`typoResButton dashicons dashicons-smartphone ${
            resOption === "Mobile" ? "active" : " "
          }`}
        ></span>
      </div>
      {children}
    </div>
  );
}
