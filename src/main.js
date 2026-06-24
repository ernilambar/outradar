import './main.css';

( function () {
	'use strict';

	// ── Modal ─────────────────────────────────────────────────────
	function createModal() {
		const el = document.createElement( 'div' );
		el.id = 'outradar-modal-overlay';
		el.innerHTML =
			'<div id="outradar-modal" role="dialog" aria-modal="true">' +
			'<div class="outradar-modal-head">' +
			'<div class="outradar-modal-badges">' +
			'<code class="outradar-modal-method"></code>' +
			'<span class="outradar-modal-status outradar-status"></span>' +
			'<span class="outradar-modal-context outradar-context"></span>' +
			'<span class="outradar-modal-duration"></span>' +
			'</div>' +
			'<button class="outradar-modal-close" type="button" aria-label="Close">×</button>' +
			'</div>' +
			'<div class="outradar-modal-url"><code></code></div>' +
			'<div class="outradar-modal-body">' +
			'<div class="outradar-modal-section">' +
			'<h3>General</h3>' +
			'<table class="outradar-modal-table"><tbody>' +
			'<tr><th>Timestamp</th><td class="f-timestamp"></td></tr>' +
			'<tr><th>Domain</th><td class="f-domain"></td></tr>' +
			'<tr><th>Response Size</th><td class="f-size"></td></tr>' +
			'</tbody></table>' +
			'</div>' +
			'<div class="outradar-modal-section">' +
			'<h3>Source</h3>' +
			'<table class="outradar-modal-table"><tbody>' +
			'<tr><th>Plugin</th><td class="f-plugin"></td></tr>' +
			'<tr><th>File</th><td class="f-file"></td></tr>' +
			'<tr class="f-page-url-row"><th>Page URL</th><td class="f-page-url"></td></tr>' +
			'<tr class="f-cron-row"><th>Cron Hook</th><td class="f-cron"></td></tr>' +
			'<tr class="f-duplicate-row"><th>Duplicate of #</th><td class="f-duplicate"></td></tr>' +
			'</tbody></table>' +
			'</div>' +
			'<div class="outradar-modal-section outradar-modal-section--request">' +
			'<h3>Request</h3>' +
			'<details class="f-headers-wrap" open><summary>Headers</summary><pre class="f-headers"></pre></details>' +
			'<details class="f-body-wrap" open><summary>Body</summary><pre class="f-body"></pre></details>' +
			'<p class="f-no-request">—</p>' +
			'</div>' +
			'</div>' +
			'<div class="outradar-modal-loader" hidden>Loading…</div>' +
			'</div>';
		document.body.appendChild( el );
		return el;
	}

	function openModal( overlay ) {
		overlay.classList.add( 'is-open' );
		document.body.classList.add( 'outradar-modal-open' );
		overlay.querySelector( '.outradar-modal-close' ).focus();
	}

	function closeModal( overlay ) {
		overlay.classList.remove( 'is-open' );
		document.body.classList.remove( 'outradar-modal-open' );
	}

	function qs( root, sel ) {
		return root.querySelector( sel );
	}

	function setText( root, sel, val ) {
		const el = qs( root, sel );
		if ( el ) {
			el.textContent = val || '—';
		}
	}

	function dim( text ) {
		const s = document.createElement( 'small' );
		s.className = 'outradar-dim';
		s.textContent = ' (' + text + ')';
		return s;
	}

	function populateModal( modal, row ) {
		qs( modal, '.outradar-modal-url code' ).textContent = row.url || '';

		qs( modal, '.outradar-modal-method' ).textContent = row.method || '';

		const code = parseInt( row.response_code, 10 );
		const statusEl = qs( modal, '.outradar-modal-status' );
		statusEl.textContent = row.response_code || '—';
		statusEl.className =
			'outradar-modal-status outradar-status ' +
			( isNaN( code )
				? ''
				: code >= 400
				? 'outradar-status-error'
				: code >= 300
				? 'outradar-status-redirect'
				: 'outradar-status-ok' );

		const ctxEl = qs( modal, '.outradar-modal-context' );
		ctxEl.textContent = row.context || '';
		ctxEl.className =
			'outradar-modal-context outradar-context outradar-context--' + ( row.context || '' );

		const ms = parseInt( row.duration, 10 );
		qs( modal, '.outradar-modal-duration' ).textContent = ms
			? ( ms / 1000 ).toFixed( 2 ) + 's'
			: '—';

		const tsEl = qs( modal, '.f-timestamp' );
		tsEl.textContent = row.timestamp || '—';
		if ( row.time_ago ) {
			tsEl.appendChild( dim( row.time_ago ) );
		}

		setText( modal, '.f-domain', row.domain );

		const sizeEl = qs( modal, '.f-size' );
		const bytes = parseInt( row.response_size, 10 );
		if ( isNaN( bytes ) || bytes <= 0 ) {
			sizeEl.textContent = '—';
		} else if ( bytes < 1024 ) {
			sizeEl.textContent = bytes.toLocaleString() + ' B';
		} else {
			const formatted =
				bytes >= 1024 * 1024
					? ( bytes / ( 1024 * 1024 ) ).toFixed( 2 ) + ' MB'
					: ( bytes / 1024 ).toFixed( 1 ) + ' KB';
			sizeEl.textContent = formatted;
			sizeEl.appendChild( dim( bytes.toLocaleString() + ' B' ) );
		}

		setText( modal, '.f-plugin', row.source_plugin );

		const fileText = row.source_file
			? row.source_file + ( row.source_line ? ':' + row.source_line : '' )
			: '';
		qs( modal, '.f-file' ).textContent = fileText || '—';

		const pageUrlRow = qs( modal, '.f-page-url-row' );
		pageUrlRow.hidden = ! row.page_url;
		if ( row.page_url ) {
			setText( modal, '.f-page-url', row.page_url );
		}

		const cronRow = qs( modal, '.f-cron-row' );
		cronRow.hidden = ! row.cron_hook;
		if ( row.cron_hook ) {
			setText( modal, '.f-cron', row.cron_hook );
		}

		const dupRow = qs( modal, '.f-duplicate-row' );
		dupRow.hidden = ! row.duplicate_of;
		if ( row.duplicate_of ) {
			setText( modal, '.f-duplicate', String( row.duplicate_of ) );
		}

		const headersWrap = qs( modal, '.f-headers-wrap' );
		const bodyWrap = qs( modal, '.f-body-wrap' );
		const noRequest = qs( modal, '.f-no-request' );

		headersWrap.hidden = ! row.request_headers;
		bodyWrap.hidden = ! row.request_body;
		noRequest.hidden = !! ( row.request_headers || row.request_body );

		if ( row.request_headers ) {
			qs( modal, '.f-headers' ).textContent = row.request_headers;
		}
		if ( row.request_body ) {
			qs( modal, '.f-body' ).textContent = row.request_body;
		}
	}

	if ( document.querySelector( '.outradar-row-toggle' ) ) {
		const overlay = createModal();
		const modal = qs( overlay, '#outradar-modal' );
		const loader = qs( modal, '.outradar-modal-loader' );
		const modalBody = qs( modal, '.outradar-modal-body' );

		qs( modal, '.outradar-modal-close' ).addEventListener( 'click', function () {
			closeModal( overlay );
		} );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				closeModal( overlay );
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && overlay.classList.contains( 'is-open' ) ) {
				closeModal( overlay );
			}
		} );

		document.querySelectorAll( '.outradar-row-toggle' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const id = btn.getAttribute( 'data-id' );
				if ( ! id || ! window.outradarData ) {
					return;
				}

				modalBody.hidden = true;
				loader.hidden = false;
				openModal( overlay );

				const fd = new FormData();
				fd.append( 'action', 'outradar_get_row' );
				fd.append( 'id', id );
				fd.append( 'nonce', window.outradarData.nonce );

				fetch( window.outradarData.ajaxUrl, { method: 'POST', body: fd } )
					.then( function ( r ) {
						return r.json();
					} )
					.then( function ( data ) {
						if ( data.success ) {
							populateModal( modal, data.data );
							loader.hidden = true;
							modalBody.hidden = false;
						}
					} )
					.catch( function () {
						loader.textContent = 'Failed to load.';
					} );
			} );
		} );
	}

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
