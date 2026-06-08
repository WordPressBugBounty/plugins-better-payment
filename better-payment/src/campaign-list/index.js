import './campaign-list.scss';

document.addEventListener( 'DOMContentLoaded', () => {
    const addNewBtn = document.querySelector( 'a.page-title-action' );
    if ( ! addNewBtn ) return;

    const data    = window.betterPaymentCampaignData || {};
    const adminUrl = data.adminUrl || '';

    addNewBtn.addEventListener( 'click', ( e ) => {
        e.preventDefault();
        window.location.href = adminUrl + 'admin.php?page=bp-campaign-builder';
    } );
} );
