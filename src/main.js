import './main.css';

( function () {
	'use strict';

	// ── Row expand ───────────────────────────────────────────────
	document.querySelectorAll( '.outpulse-row-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const id = btn.getAttribute( 'data-id' );
			const row = document.getElementById( 'outpulse-detail-' + id );
			if ( row ) {
				row.style.display = 'none' === row.style.display ? '' : 'none';
			}
		} );
	} );

	// ── Select all checkbox ──────────────────────────────────────
	const selectAll = document.getElementById( 'outpulse-select-all' );
	if ( selectAll ) {
		selectAll.addEventListener( 'change', function () {
			document.querySelectorAll( '.outpulse-row-check' ).forEach( function ( cb ) {
				cb.checked = selectAll.checked;
			} );
		} );
	}

	// ── Bulk delete confirmation ─────────────────────────────────
	const bulkSubmit = document.getElementById( 'outpulse-bulk-submit' );
	if ( bulkSubmit ) {
		bulkSubmit.addEventListener( 'click', function ( e ) {
			const select = bulkSubmit.closest( 'form' ).querySelector( '[name="bulk_action"]' );
			if ( select && 'delete' === select.value ) {
				const checked = document.querySelectorAll( '.outpulse-row-check:checked' ).length;
				if (
					checked > 0 &&
					! window.confirm(
						( window.outpulseData && window.outpulseData.confirmDelete ) ||
							'Delete selected items?'
					)
				) {
					e.preventDefault();
				}
			}
		} );
	}

	// ── Purge all confirmation ───────────────────────────────────
	const purgeBtn = document.getElementById( 'outpulse-purge-btn' );
	if ( purgeBtn ) {
		purgeBtn.addEventListener( 'click', function ( e ) {
			if (
				! window.confirm(
					( window.outpulseData && window.outpulseData.confirmPurge ) ||
						'Delete all logs?'
				)
			) {
				e.preventDefault();
			}
		} );
	}

	// ── 7-day bar chart ──────────────────────────────────────────
	const canvas = document.getElementById( 'outpulse-chart' );
	if ( canvas && window.outpulseData && window.outpulseData.chartData ) {
		drawBarChart( canvas, window.outpulseData.chartData );
	}

	function drawBarChart( canvas, data ) {
		const labels = data.labels || [];
		const values = data.values || [];
		if ( ! labels.length ) {
			return;
		}

		const dpr = window.devicePixelRatio || 1;
		const width = canvas.offsetWidth || 800;
		const height = 220;

		canvas.width = width * dpr;
		canvas.height = height * dpr;
		canvas.style.width = width + 'px';
		canvas.style.height = height + 'px';

		const ctx = canvas.getContext( '2d' );
		ctx.scale( dpr, dpr );

		const padTop = 20;
		const padBottom = 40;
		const padLeft = 48;
		const padRight = 16;

		const chartW = width - padLeft - padRight;
		const chartH = height - padTop - padBottom;

		const max = Math.max.apply( null, values.concat( [ 1 ] ) );
		const barW = Math.floor( ( chartW / labels.length ) * 0.6 );
		const gap = Math.floor( chartW / labels.length );

		// Background
		ctx.fillStyle = '#ffffff';
		ctx.fillRect( 0, 0, width, height );

		// Grid lines
		ctx.strokeStyle = '#f0f0f1';
		ctx.lineWidth = 1;
		const gridLines = 4;
		for ( let g = 0; g <= gridLines; g++ ) {
			const gy = padTop + chartH - ( g / gridLines ) * chartH;
			ctx.beginPath();
			ctx.moveTo( padLeft, gy );
			ctx.lineTo( padLeft + chartW, gy );
			ctx.stroke();

			// Y-axis label
			ctx.fillStyle = '#646970';
			ctx.font = '11px sans-serif';
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			ctx.fillText( String( Math.round( ( g / gridLines ) * max ) ), padLeft - 6, gy );
		}

		// Bars
		for ( let i = 0; i < labels.length; i++ ) {
			const x = padLeft + i * gap + Math.floor( ( gap - barW ) / 2 );
			const val = values[ i ] || 0;
			const barH = Math.max( 1, ( val / max ) * chartH );
			const y = padTop + chartH - barH;

			ctx.fillStyle = val > 0 ? '#2271b1' : '#dcdcde';
			ctx.fillRect( x, y, barW, barH );

			// Value label above bar
			if ( val > 0 ) {
				ctx.fillStyle = '#1d2327';
				ctx.font = '11px sans-serif';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'bottom';
				ctx.fillText( String( val ), x + barW / 2, y - 2 );
			}

			// X-axis label
			const labelParts = labels[ i ] ? labels[ i ].split( '-' ) : [];
			const labelText =
				labelParts.length === 3 ? labelParts[ 1 ] + '/' + labelParts[ 2 ] : labels[ i ];
			ctx.fillStyle = '#646970';
			ctx.font = '11px sans-serif';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'top';
			ctx.fillText( labelText, x + barW / 2, padTop + chartH + 6 );
		}
	}
} )();
