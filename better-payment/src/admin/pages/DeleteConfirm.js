import { __ } from '@wordpress/i18n';

// Generic destructive-action confirm modal. The copy props default to the
// original transaction-delete wording so existing call sites need no change;
// pass them to repurpose the modal (e.g. subscription delete/cancel).
const DeleteConfirm = ( {
	isOpen,
	onClose,
	onConfirm,
	isDeleting = false,
	title,
	message,
	confirmLabel,
	busyLabel,
} ) => {
	// Handle overlay click to close modal
	const handleOverlayClick = ( e ) => {
		if ( e.target === e.currentTarget && ! isDeleting ) {
			onClose();
		}
	};

	// Handle cancel button click
	const handleCancel = () => {
		if ( ! isDeleting ) {
			onClose();
		}
	};

	// Handle confirm delete button click
	const handleConfirm = () => {
		if ( ! isDeleting ) {
			onConfirm();
		}
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<>
			<div className="bp-popup-overlay-wrapper">
				<div
					className="bp-popup-overlay"
					onClick={ handleOverlayClick }
				>
					<div
						className="bp-popup-receipt-wrapper bp-popup-refund-decision-wrapper"
						onClick={ ( e ) => e.stopPropagation() }
					>
						<h2 className="fx-1 bp-font_ibm bp-popup-decision-title">
							{ title ?? __( 'Are you sure?', 'better-payment' ) }
						</h2>
						<p className="bp-font_ibm bp-popup-decision-sub-title">
							{ message ??
								__(
									'You won’t be able to revert this!',
									'better-payment'
								) }
						</p>

						<div className="bp-flex align-items_center justify-content-center bp-popup-decision-btn-wrapper">
							<button
								className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-data-cancel-btn"
								onClick={ handleCancel }
								disabled={ isDeleting }
								type="button"
							>
								{ __( 'Cancel', 'better-payment' ) }
							</button>
							<button
								className="bp-font_ibm fx-4 bp-btn_2 bp-flex align-items_center bp-btn bp-receipt-btn"
								onClick={ handleConfirm }
								disabled={ isDeleting }
								type="button"
							>
								{ isDeleting
									? busyLabel ??
									  __( 'Deleting...', 'better-payment' )
									: confirmLabel ??
									  __( 'Yes, Delete It', 'better-payment' ) }
							</button>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};

export default DeleteConfirm;
