import './main.css';

( function () {
	'use strict';

	// ── Row expand ───────────────────────────────────────────────
	document.querySelectorAll( '.outradar-row-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const id = btn.getAttribute( 'data-id' );
			const row = document.getElementById( 'outradar-detail-' + id );
			if ( row ) {
				row.style.display = 'none' === row.style.display ? '' : 'none';
			}
		} );
	} );

	// ── Select all checkbox ──────────────────────────────────────
	const selectAll = document.getElementById( 'outradar-select-all' );
	if ( selectAll ) {
		selectAll.addEventListener( 'change', function () {
			document.querySelectorAll( '.outradar-row-check' ).forEach( function ( cb ) {
				cb.checked = selectAll.checked;
			} );
		} );
	}

	// ── Bulk delete confirmation ─────────────────────────────────
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

	// ── Purge all confirmation ───────────────────────────────────
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

	// ── Stacked context bar chart ────────────────────────────────
	const CTX_COLORS = {
		cron: '#8c5fb8',
		frontend: '#2271b1',
		admin: '#d63638',
		cli: '#00a32a',
	};
	const CTX_ORDER = [ 'cron', 'frontend', 'admin', 'cli' ];

	const canvas = document.getElementById( 'outradar-chart' );
	if ( canvas && window.outradarData ) {
		const datasets = {
			7: window.outradarData.chartData7,
			30: window.outradarData.chartData30,
		};

		if ( datasets[ 7 ] ) {
			drawStackedChart( canvas, datasets[ 7 ] );
		}

		document.querySelectorAll( '.outradar-range-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				document.querySelectorAll( '.outradar-range-btn' ).forEach( function ( b ) {
					b.classList.remove( 'active' );
				} );
				btn.classList.add( 'active' );
				const data = datasets[ btn.getAttribute( 'data-range' ) ];
				if ( data ) {
					drawStackedChart( canvas, data );
				}
			} );
		} );
	}

	function drawStackedChart( canvas, data ) {
		const labels = data.labels || [];
		const series = data.series || {};
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

		let max = 1;
		labels.forEach( function ( _, i ) {
			let total = 0;
			CTX_ORDER.forEach( function ( key ) {
				total += ( series[ key ] ? series[ key ][ i ] : 0 ) || 0;
			} );
			if ( total > max ) {
				max = total;
			}
		} );

		const barW = Math.floor( ( chartW / labels.length ) * 0.6 );
		const gap = chartW / labels.length;

		ctx.fillStyle = '#ffffff';
		ctx.fillRect( 0, 0, width, height );

		ctx.strokeStyle = '#f0f0f1';
		ctx.lineWidth = 1;
		for ( let g = 0; g <= 4; g++ ) {
			const gy = padTop + chartH - ( g / 4 ) * chartH;
			ctx.beginPath();
			ctx.moveTo( padLeft, gy );
			ctx.lineTo( padLeft + chartW, gy );
			ctx.stroke();
			ctx.fillStyle = '#646970';
			ctx.font = '11px sans-serif';
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			ctx.fillText( String( Math.round( ( g / 4 ) * max ) ), padLeft - 6, gy );
		}

		const showTotals = labels.length <= 14;
		const labelStep = labels.length > 14 ? Math.ceil( labels.length / 10 ) : 1;

		labels.forEach( function ( label, i ) {
			const x = padLeft + i * gap + Math.floor( ( gap - barW ) / 2 );
			let baseY = padTop + chartH;
			let dayTotal = 0;

			CTX_ORDER.forEach( function ( key ) {
				const val = ( series[ key ] ? series[ key ][ i ] : 0 ) || 0;
				dayTotal += val;
				if ( val === 0 ) {
					return;
				}
				const segH = ( val / max ) * chartH;
				ctx.fillStyle = CTX_COLORS[ key ];
				ctx.fillRect( x, baseY - segH, barW, segH );
				baseY -= segH;
			} );

			if ( showTotals && dayTotal > 0 ) {
				ctx.fillStyle = '#1d2327';
				ctx.font = '11px sans-serif';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'bottom';
				ctx.fillText(
					String( dayTotal ),
					x + barW / 2,
					padTop + chartH - ( dayTotal / max ) * chartH - 2
				);
			}

			if ( i % labelStep === 0 ) {
				const parts = label ? label.split( '-' ) : [];
				const labelText = parts.length === 3 ? parts[ 1 ] + '/' + parts[ 2 ] : label;
				ctx.fillStyle = '#646970';
				ctx.font = '11px sans-serif';
				ctx.textAlign = 'center';
				ctx.textBaseline = 'top';
				ctx.fillText( labelText, x + barW / 2, padTop + chartH + 6 );
			}
		} );
	}
} )();
