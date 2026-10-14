import bpVideoImage from './../../images/video-image-3-min.jpg';
import { dashboardAPI } from './../../utils/api';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import dismissibleRightSideImg from './../../images/right-side-img.webp';
import dismissibleRightSideImgDark from './../../images/darkmood-right-side-img.webp';
import DismissibleChecked from './../../svg/DismissibleChecked';
import DismissibleUnchecked from './../../svg/DismissibleUnchecked';
import { DASHBOARD_VIDEO_URL } from '../../utils/dashboardVideo';

/**
 * Placeholder for the hero while its two chained requests are in flight.
 *
 * This exists to hold the hero's height. Rendering `null` here (as this
 * component used to) collapsed the whole section to zero and then snapped the
 * real hero in — the biggest layout shift on the page.
 *
 * It mirrors the **video** variant, not the onboarding one: which of the two
 * renders isn't known until the fetch resolves, and the video section is where
 * every install ends up permanently once the onboarding checklist is dismissed.
 * The card chrome (padding, radius, background) is real and paints instantly —
 * it's decoration, not data. Only the contents shimmer.
 */
const HeroSkeleton = () => (
	<div className="section bp-video_section">
		<div className="bp-container">
			<div
				className="border-radius_12 margin-bottom_20 bp-video_section-wrapper bp-video_section-wrapper_skeleton"
				role="status"
				aria-busy="true"
				aria-label={ __( 'Loading…', 'better-payment' ) }
			>
				<div className="bp-row align-items_center">
					<div className="bp-col bp-col_3 bp-col_50-active_992 bp-col_100-active_600">
						<div className="wp-skeleton wp-skeleton_media"></div>
					</div>
					<div className="bp-col bp-col_9 bp-col_50-active_992 bp-col_100-active_600">
						<div className="bp-text_wrapper">
							<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-90"></div>
							<div className="wp-skeleton wp-skeleton_heading wp-skeleton_w-40"></div>
							<div className="wp-skeleton wp-skeleton_text wp-skeleton_w-75"></div>
							<div className="wp-skeleton wp-skeleton_text wp-skeleton_w-60"></div>
							<div className="bp-flex bp-hero_skeleton-btns">
								<div className="wp-skeleton wp-skeleton_btn"></div>
								<div className="wp-skeleton wp-skeleton_btn"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
);

const Hero = () => {
	const [ dismissibleSectionData, setDismissibleSectionData ] =
		useState( null );
	const [ sectionDismissed, setSectionDismissed ] = useState( true );
	const [ isVideoPopupOpen, setIsVideoPopupOpen ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	const [ currentVideoSrc, setCurrentVideoSrc ] = useState( '' );
	const iframeRef = useRef( null );

	useEffect( () => {
		dashboardAPI.showDismissibleSection().then( ( data ) => {
			setSectionDismissed( data?.sectionDismissed );
			setLoading( false );
		} );
	}, [] );

	useEffect( () => {
		if ( sectionDismissed ) {
			dashboardAPI.getDismissibleSectionData().then( ( res ) => {
				setDismissibleSectionData( res?.data );
				setLoading( false );
			} );
		}
	}, [ sectionDismissed ] );

	const handleSectionDismiss = () => {
		dashboardAPI.dismissDismissibleSection().then( ( data ) => {
			if ( data?.success ) {
				setSectionDismissed( true );
			}
		} );
	};

	// Both sections play the same product video. It is a YouTube embed, loaded
	// only after the admin clicks play; see DASHBOARD_VIDEO_URL.
	const videoSources = {
		mainSection: DASHBOARD_VIDEO_URL,
		dismissibleSection: DASHBOARD_VIDEO_URL,
	};

	const handleVideoClose = () => {
		setIsVideoPopupOpen( false );
		setCurrentVideoSrc( '' );
		// Reset iframe src to stop video playback
		if ( iframeRef.current ) {
			iframeRef.current.src = '';
		}
	};

	const handleVideoPlay = ( e, videoType ) => {
		e.preventDefault();
		const videoSrc = videoSources[ videoType ] || videoSources.mainSection;
		setCurrentVideoSrc( videoSrc );
		setIsVideoPopupOpen( true );
	};

	// Handle clicking outside the video to close popup
	const handlePopupBackgroundClick = ( e ) => {
		if ( e.target.classList.contains( 'bp-dismissible_video-popup_bg' ) ) {
			handleVideoClose();
		}
	};

	// Handle escape key to close popup
	useEffect( () => {
		const handleEscapeKey = ( e ) => {
			if ( e.key === 'Escape' && isVideoPopupOpen ) {
				handleVideoClose();
			}
		};

		if ( isVideoPopupOpen ) {
			document.addEventListener( 'keydown', handleEscapeKey );
			return () =>
				document.removeEventListener( 'keydown', handleEscapeKey );
		}
	}, [ isVideoPopupOpen ] );

	return (
		<>
			{ loading ? (
				<HeroSkeleton />
			) : (
				<>
					{ sectionDismissed ? (
						<div className="section bp-video_section">
							<div className="bp-container">
								<div className="border-radius_12 margin-bottom_20 bp-video_section-wrapper">
									<div className="bp-row align-items_center">
										<div className="bp-col bp-col_3 bp-col_50-active_992 bp-col_100-active_600">
											<div
												className="line-height_0 bp-video_wrapper"
												onClick={ ( e ) =>
													handleVideoPlay(
														e,
														'mainSection'
													)
												}
											>
												<img
													src={ bpVideoImage }
													alt=""
												/>
											</div>
										</div>
										<div className="bp-col bp-col_9 bp-col_50-active_992 bp-col_100-active_600">
											<div className="bp-text_wrapper">
												<h3 className="bp-font_inter fx-1 video-section_header">
													{ __(
														'1-Click WordPress Payment & Donation Management Solution For You',
														'better-payment'
													) }
												</h3>
												<p className="bp-font_inter fx-5 video-section_text">
													{ __(
														'Run fundraising campaigns, manage donations, subscriptions, sell products, automate membership payment processing, and more with a single plugin.',
														'better-payment'
													) }
												</p>
												<div className="bp-flex">
													<a
														href={
															'//betterpayment.co/docs/'
														}
														className="bp-btn bp-font_inter fx-4 documentation-btn"
														target="_blank"
													>
														{ __(
															'Documentation',
															'better-payment'
														) }
													</a>
													<a
														href={
															'//wpdeveloper.com/support'
														}
														className="bp-btn bp-font_inter fx-4 initiate-btn"
														target="_blank"
													>
														{ __(
															'Initiate a Chat',
															'better-payment'
														) }
														<span className="bp-btn_icon">
															<svg
																width="6"
																height="10"
																viewBox="0 0 6 10"
																fill="none"
																xmlns="http://www.w3.org/2000/svg"
															>
																<path
																	d="M1.5 1.5L4.5 5L1.5 8.5"
																	strokeWidth="1.5"
																	strokeLinecap="round"
																	strokeLinejoin="round"
																></path>
															</svg>
														</span>
													</a>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					) : (
						<div className="section margin-bottom_20 bp-dismissible_section">
							<div className="bp-container">
								<div className="bp-dismissible_section-wrapper">
									<div className="bp-row">
										<div className="bp-col bp-col_7 bp-custome_col-100">
											<div className="bp-left_box-wrapper">
												<h2 className="bp-dismissible_section-header fx-1 bp-font-figtree margin-bottom_8 ">
													{ __(
														'One-Click Easy WordPress Payment',
														'better-payment'
													) }
												</h2>
												<div className="progress-line">
													<div
														className="progress-fill"
														style={ {
															width: `${ dismissibleSectionData?.percentage }%`,
														} }
													></div>
												</div>
												<div className="bp-dismissible_list-wrapper">
													<ul className="bp-dismissible_list">
														{ dismissibleSectionData?.steps.map(
															( step, index ) => (
																<li
																	className="bp-flex  bp-dismissible_list-item"
																	key={
																		index
																	}
																>
																	<span className="bp-dismissible_list-item_icon">
																		{ step.completed ? (
																			<DismissibleChecked />
																		) : (
																			<DismissibleUnchecked />
																		) }
																	</span>
																	<h4
																		className="fx-4 bp-font-dm bp-dismissible_list-item_header"
																		dangerouslySetInnerHTML={ {
																			__html: step.text,
																		} }
																	></h4>
																</li>
															)
														) }
													</ul>
												</div>
											</div>
										</div>
										<div className="bp-col bp-col_5 bp-custome_col-100">
											<div className="bp-right_box-wrapper">
												<div className="bp-flex bp-video-img-wrapper">
													<div
														className="bp-video_popup-trigger"
														onClick={ ( e ) =>
															handleVideoPlay(
																e,
																'dismissibleSection'
															)
														}
													>
														<img
															src={
																dismissibleRightSideImg
															}
															alt=""
															className="light-mood-img"
														/>
														<img
															src={
																dismissibleRightSideImgDark
															}
															alt=""
															className="dark-mood-img"
														/>
													</div>
												</div>
												<button
													className="fx-6 bp-font-dm bp-dismissible_btn"
													onClick={
														handleSectionDismiss
													}
												>
													{ __(
														'Dismiss',
														'better-payment'
													) }
												</button>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					) }
				</>
			) }

			{ isVideoPopupOpen && (
				<div
					className="bp-dismissible_video-popup_bg"
					onClick={ handlePopupBackgroundClick }
					style={ { display: 'flex' } }
				>
					<div className="bp-dismissible_video-popup">
						<button
							className="bp-dismissible_close-btn"
							onClick={ handleVideoClose }
						>
							&times;
						</button>
						<iframe
							ref={ iframeRef }
							width="560"
							height="315"
							src={ currentVideoSrc }
							title="YouTube video player"
							style={ { border: 'none' } }
							allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
							referrerPolicy="strict-origin-when-cross-origin"
							allowFullScreen
						></iframe>
					</div>
				</div>
			) }
		</>
	);
};

export default Hero;
