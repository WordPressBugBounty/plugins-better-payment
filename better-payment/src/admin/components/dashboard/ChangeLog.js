import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import ChangeLogHeader from '../../svg/ChangeLogHeader';
import ChangeLogItems from '../../svg/ChangeLogItems';
import { changeLogEntries } from '../../utils/changeLog';

/**
 * One entry's text, with its `<link>` tag turned into a real link.
 *
 * @param {Object} props       Props.
 * @param {Object} props.entry Entry from changeLogEntries().
 * @return {JSX.Element|string} Text.
 */
const EntryText = ( { entry } ) => {
	if ( ! entry.href ) {
		return entry.text;
	}

	// createInterpolateElement() puts the linked words inside the element it is
	// given, so the link itself carries the bold (a <strong> wrapper would take
	// the words and leave the <a> empty).
	return createInterpolateElement( entry.text, {
		link: (
			// eslint-disable-next-line jsx-a11y/anchor-has-content -- filled by createInterpolateElement().
			<a
				href={ entry.href }
				style={ { display: 'inline-block', fontWeight: 700 } }
				{ ...( entry.external
					? { target: '_blank', rel: 'noopener noreferrer' }
					: {} ) }
			/>
		),
	} );
};

const ChangeLog = () => {
	const { highlight, items } = changeLogEntries();

	return (
		<div className="bp-col_active-992 bp-custome_col-6_active-992 ">
			<div className="margin-bottom_20 padding-20 bp-card">
				<div className="bp-tag_wrapper">
					<div className="tag">
						<span className="line-height_0">
							<ChangeLogHeader />
						</span>{ ' ' }
						<span className="bp-font_inter fx-4 tag-type">
							{ __( 'New Release', 'better-payment' ) }
						</span>
					</div>
				</div>
				<div className="bp-card_header-wrapper">
					<h4 className="bp-font_ibm fx-3 card-header">
						{ __(
							'What’s New In Latest Better Payment?',
							'better-payment'
						) }
					</h4>
				</div>

				<ul className="payment-box_list">
					<li
						className="bp-font_inter bp-flex payment-box_list-item payment-box_form-gb"
						data-entry={ highlight.id }
					>
						<span className="line-height_0"></span>

						<span className="bp-font_ibm fx-4 feature-list_sub-title">
							<span className="bp-font_ibm fx-4 feature-list_title">
								<span aria-hidden="true">🎉 </span>
								{ highlight.label }&nbsp;
							</span>
							<EntryText entry={ highlight } />
						</span>
					</li>

					{ items.map( ( entry ) => (
						<li
							className="bp-font_inter bp-flex payment-box_list-item"
							key={ entry.id }
							data-entry={ entry.id }
						>
							<span className="line-height_0">
								<ChangeLogItems />
							</span>

							<span className="bp-font_ibm fx-4 feature-list_sub-title">
								<span className="bp-font_ibm fx-4 feature-list_title">
									{ entry.label }&nbsp;
								</span>
								<EntryText entry={ entry } />
							</span>
						</li>
					) ) }
				</ul>
				<a
					href={ 'https://betterpayment.co/changelog' }
					className="bp-font_inter fx-4 bp-btn_2 bp-learn_more-btn"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'View Changelog', 'better-payment' ) }
				</a>
			</div>
		</div>
	);
};

export default ChangeLog;
