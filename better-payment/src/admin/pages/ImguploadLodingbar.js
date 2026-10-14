export const ImguploadLodingbar = ({ progress, fileName = 'your_file_name.csv' }) => {
    const remaining = Math.max(0, 100 - Math.round(progress));

    return (
        <div className="img-upload-loadingbar">
            <div className="bp-flex align-items_center justify-content-space_between progress-bar-header" >
                <h4 className="bp-font_ibm img-file-name">{fileName}</h4>
                <p className="remaining">{remaining}% Remaining</p>

            </div>

            <div className="progress-bar">
                <div
                    className="bp-font_ibm  fx-6 progress"
                    style={{ width: `${Math.round(progress)}%` }}
                ></div>
            </div>
        </div>
    );
};
