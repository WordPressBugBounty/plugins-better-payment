import { useState, useEffect } from "react";
import FinishContent from "./FinishContent";

export default function FinishPreLoader() {
    return (
        <div className="bp-preloader-overlay">
            <div className="bp-preloader">
                <div className="bp-spinner"></div>
                <p>Saving...</p>
            </div>
        </div>
    );
}
