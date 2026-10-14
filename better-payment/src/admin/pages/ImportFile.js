import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { toast } from 'react-toastify';
import { ImguploadLodingbar } from "./ImguploadLodingbar"

const ImportFile = ({ isOpen, onClose }) => {
    const bpAdmin = window.betterPaymentAdmin;
    const fileInputRef = useRef(null);
    const dropZoneRef = useRef(null);

    const [selectedFile, setSelectedFile] = useState(null);
    const [isImporting, setIsImporting] = useState(false);
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState(null);
    const [dragActive, setDragActive] = useState(false);

    // Reset state when modal closes
    useEffect(() => {
        if (!isOpen) {
            setSelectedFile(null);
            setProgress(0);
            setError(null);
            setIsImporting(false);
        }
    }, [isOpen]);

    // Validate CSV file
    const validateFile = (file) => {
        if (!file) {
            toast.error(__('No file selected', 'better-payment'));
            return false;
        }

        const fileName = file.name.toLowerCase();
        if (!fileName.endsWith('.csv')) {
            toast.error(__('Only CSV files are allowed', 'better-payment'));
            return false;
        }

        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            toast.error(__('File size must be less than 10MB', 'better-payment'));
            return false;
        }

        return true;
    };

    // Handle file selection from input
    const handleFileSelect = (e) => {
        const file = e.target.files?.[0];
        if (file && validateFile(file)) {
            setSelectedFile(file);
            setError(null);
            toast.info(__('File selected successfully', 'better-payment'));
        }
    };

    // Handle drag over
    const handleDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(true);
    };

    // Handle drag leave
    const handleDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
    };

    // Handle drop
    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        const file = e.dataTransfer?.files?.[0];
        if (file && validateFile(file)) {
            setSelectedFile(file);
            setError(null);
            toast.info(__('File selected successfully', 'better-payment'));
        }
    };

    // Handle import
    const handleImport = async () => {
        if (!selectedFile) {
            toast.error(__('Please select a file first', 'better-payment'));
            return;
        }

        setIsImporting(true);
        setError(null);
        setProgress(0);

        let progressInterval;

        try {
            const formData = new FormData();
            formData.append('action', 'better-payment-transactions-import');
            formData.append('nonce', bpAdmin?.importNonce);
            formData.append('better-payment-transaction-import-input', selectedFile);

            // Simulate progress updates
            progressInterval = setInterval(() => {
                setProgress(prev => {
                    if (prev >= 90) {
                        clearInterval(progressInterval);
                        return prev;
                    }
                    return prev + Math.random() * 30;
                });
            }, 300);

            // Use admin-post.php endpoint for WordPress admin_post_ actions
            const adminPostUrl = bpAdmin?.adminPostUrl || window.location.origin + '/wp-admin/admin-post.php';

            const response = await fetch(adminPostUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            clearInterval(progressInterval);

            // Check if response is a redirect (successful import)
            // admin_post_ handlers typically redirect on success
            if (response.ok || response.status === 302 || response.status === 301) {
                setProgress(100);
                toast.success(__('File imported successfully', 'better-payment'));
                // Wait a moment before closing to show completion
                setTimeout(() => {
                    setSelectedFile(null);
                    setProgress(0);
                    setIsImporting(false);
                    onClose();
                    // Trigger a refresh of transactions
                    window.location.reload();
                }, 1000);
            } else {
                const errorText = await response.text();
                throw new Error(errorText || __('Import failed', 'better-payment'));
            }
        } catch (err) {
            if (progressInterval) {
                clearInterval(progressInterval);
            }
            const errorMessage = err.message || __('An error occurred during import', 'better-payment');
            toast.error(errorMessage);
            setError(errorMessage);
            setIsImporting(false);
            setProgress(0);
        }
    };

    // Handle close button click
    const handleClose = () => {
        if (!isImporting) {
            onClose();
        }
    };

    // Handle click outside modal
    const handleOverlayClick = (e) => {
        if (e.target === e.currentTarget && !isImporting) {
            onClose();
        }
    };

    if (!isOpen) {
        return null;
    }

    return (
        <>
            <div className="bp-popup-overlay-wrapper" onClick={handleOverlayClick}>
                <div className="bp-popup-overlay">
                    <div className="bp-popup-receipt-wrapper">
                        <div className="bp-flex align-items_center justify-content-space_between bp-popup-receipt-header">
                            <h2 className="fx-1 bp-font_ibm bp-popup-receipt-title">
                                {__('Import CSV File', 'better-payment')}
                            </h2>
                            <button
                                className="line-height_0 bp-popup-receipt-close-btn"
                                onClick={handleClose}
                                disabled={isImporting}
                                aria-label={__('Close', 'better-payment')}
                            >
                                <svg
                                    width="24"
                                    height="25"
                                    viewBox="0 0 24 25"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <g clipPath="url(#clip0_5405_24515)">
                                        <path
                                            d="M18 6.5L6 18.5"
                                            stroke="#B9BFCA"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        />
                                        <path
                                            d="M6 6.5L18 18.5"
                                            stroke="#B9BFCA"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        />
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_5405_24515">
                                            <rect width="24" height="24" fill="white" transform="translate(0 0.5)" />
                                        </clipPath>
                                    </defs>
                                </svg>
                            </button>
                        </div>
                        <div className="bp-popup-csv-body">
                            <div className="img-popup-upload-box-wrapper">
                                <label
                                    className={`img-popup-upload-box ${dragActive ? 'drag-active' : ''}`}
                                    ref={dropZoneRef}
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                    onDrop={handleDrop}
                                >
                                    <input
                                        type="file"
                                        accept=".csv"
                                        hidden
                                        name="better-payment-transaction-import-input"
                                        ref={fileInputRef}
                                        onChange={handleFileSelect}
                                        disabled={isImporting}
                                    />
                                    <div className="upload-content">
                                        <span className="line-height_0 upload-content-wrapper">
                                            <svg
                                                width="33"
                                                height="33"
                                                viewBox="0 0 33 33"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <g clipPath="url(#clip0_5242_24290)">
                                                    <path
                                                        d="M5.8335 23.166V25.8327C5.8335 26.5399 6.11445 27.2182 6.61454 27.7183C7.11464 28.2184 7.79292 28.4993 8.50016 28.4993H24.5002C25.2074 28.4993 25.8857 28.2184 26.3858 27.7183C26.8859 27.2182 27.1668 26.5399 27.1668 25.8327V23.166"
                                                        stroke="#6B59EE"
                                                        strokeWidth="2"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    />
                                                    <path
                                                        d="M9.8335 12.5007L16.5002 5.83398L23.1668 12.5007"
                                                        stroke="#6B59EE"
                                                        strokeWidth="2"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    />
                                                    <path
                                                        d="M16.5 5.83398V21.834"
                                                        stroke="#6B59EE"
                                                        strokeWidth="2"
                                                        strokeLinecap="round"
                                                        strokeLinejoin="round"
                                                    />
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_5242_24290">
                                                        <rect width="32" height="32" fill="white" transform="translate(0.5 0.5)" />
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                        </span>

                                        <p className="bp-font_ibm image-upload-direction">
                                            {__('Drag & Drop or', 'better-payment')} <span>{__('Choose File', 'better-payment')}</span> {__('to Upload', 'better-payment')}
                                        </p>
                                    </div>
                                </label>

                                {selectedFile && (
                                    <ImguploadLodingbar
                                        progress={progress}
                                        fileName={selectedFile.name}
                                    />
                                )}

                                {error && (
                                    <div className="bp-import-error-message">
                                        <p className="bp-font_ibm">{error}</p>
                                    </div>
                                )}
                            </div>

                            <div className="bp-flex align-items_center bp-csv-btn-wrapper">
                                <button
                                    className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-btn bp-receipt-btn"
                                    onClick={handleImport}
                                    disabled={!selectedFile || isImporting}
                                >
                                    {isImporting ? __('Importing...', 'better-payment') : __('Import File', 'better-payment')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}

export default ImportFile;