export function initTable() {
	const selectAll = document.getElementById( 'outradar-select-all' );
	if ( selectAll ) {
		selectAll.addEventListener( 'change', function () {
			document.querySelectorAll( '.outradar-row-check' ).forEach( function ( cb ) {
				cb.checked = selectAll.checked;
			} );
		} );
	}

	const bulkSubmit = document.getElementById( 'outradar-bulk-submit' );
	if ( bulkSubmit ) {
		bulkSubmit.addEventListener( 'click', function ( e ) {
			const select = bulkSubmit.closest( 'form' ).querySelector( '[name="bulk_action"]' );
			if ( select && 'delete' === select.value ) {
				const checked = document.querySelectorAll( '.outradar-row-check:checked' ).length;
				if (
					checked > 0 &&
					! window.confirm(
						( window.outradarData && window.outradarData.confirmDelete ) ||
							'Delete selected items?'
					)
				) {
					e.preventDefault();
				}
			}
		} );
	}

	const purgeBtn = document.getElementById( 'outradar-purge-btn' );
	if ( purgeBtn ) {
		purgeBtn.addEventListener( 'click', function ( e ) {
			if (
				! window.confirm(
					( window.outradarData && window.outradarData.confirmPurge ) ||
						'Delete all logs?'
				)
			) {
				e.preventDefault();
			}
		} );
	}
}
