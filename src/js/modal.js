import { qs, setText, dim, copyToClipboard } from './utils.js';

function t( key ) {
	return (
		( window.outradarData && window.outradarData.i18n && window.outradarData.i18n[ key ] ) ||
		key
	);
}

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
		'<button class="outradar-modal-close" type="button" aria-label="' +
		t( 'close' ) +
		'">×</button>' +
		'</div>' +
		'<div class="outradar-modal-url"><code></code></div>' +
		'<div class="outradar-modal-body">' +
		'<div class="outradar-modal-section">' +
		'<h3>' +
		t( 'general' ) +
		'</h3>' +
		'<table class="outradar-modal-table"><tbody>' +
		'<tr><th>' +
		t( 'timestamp' ) +
		'</th><td class="f-timestamp"></td></tr>' +
		'<tr><th>' +
		t( 'domain' ) +
		'</th><td class="f-domain"></td></tr>' +
		'<tr><th>' +
		t( 'responseSize' ) +
		'</th><td class="f-size"></td></tr>' +
		'</tbody></table>' +
		'</div>' +
		'<div class="outradar-modal-section">' +
		'<h3>' +
		t( 'origin' ) +
		'</h3>' +
		'<table class="outradar-modal-table"><tbody>' +
		'<tr><th>' +
		t( 'source' ) +
		'</th><td class="f-plugin"></td></tr>' +
		'<tr><th>' +
		t( 'file' ) +
		'</th><td class="f-file"></td></tr>' +
		'<tr class="f-page-url-row"><th>' +
		t( 'pagePath' ) +
		'</th><td class="f-page-url"></td></tr>' +
		'<tr class="f-cron-row"><th>' +
		t( 'cronHook' ) +
		'</th><td class="f-cron"></td></tr>' +
		'<tr class="f-duplicate-row"><th>' +
		t( 'duplicateOf' ) +
		'</th><td class="f-duplicate"></td></tr>' +
		'</tbody></table>' +
		'</div>' +
		'<div class="outradar-modal-section outradar-modal-section--request">' +
		'<h3>' +
		t( 'request' ) +
		'</h3>' +
		'<details class="f-headers-wrap" open>' +
		'<summary>' +
		'<span class="outradar-summary-label">' +
		t( 'headers' ) +
		'</span>' +
		'<span class="outradar-content-actions">' +
		'<span class="outradar-toggle f-headers-toggle" hidden>' +
		'<button type="button" class="outradar-toggle-btn is-active" data-mode="pretty">' +
		t( 'pretty' ) +
		'</button>' +
		'<button type="button" class="outradar-toggle-btn" data-mode="raw">' +
		t( 'raw' ) +
		'</button>' +
		'</span>' +
		'<button type="button" class="outradar-copy-btn f-headers-copy" title="' +
		t( 'copy' ) +
		'"><span class="dashicons dashicons-clipboard"></span></button>' +
		'</span>' +
		'</summary>' +
		'<pre class="f-headers"></pre>' +
		'</details>' +
		'<details class="f-body-wrap" open>' +
		'<summary>' +
		'<span class="outradar-summary-label">' +
		t( 'body' ) +
		'</span>' +
		'<span class="outradar-content-actions">' +
		'<span class="outradar-toggle f-body-toggle" hidden>' +
		'<button type="button" class="outradar-toggle-btn is-active" data-mode="pretty">' +
		t( 'pretty' ) +
		'</button>' +
		'<button type="button" class="outradar-toggle-btn" data-mode="raw">' +
		t( 'raw' ) +
		'</button>' +
		'</span>' +
		'<button type="button" class="outradar-copy-btn f-body-copy" title="' +
		t( 'copy' ) +
		'"><span class="dashicons dashicons-clipboard"></span></button>' +
		'</span>' +
		'</summary>' +
		'<pre class="f-body"></pre>' +
		'</details>' +
		'<p class="f-no-request">—</p>' +
		'</div>' +
		'</div>' +
		'<div class="outradar-modal-loader" hidden>' +
		t( 'loading' ) +
		'</div>' +
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

function setContentBlock( modal, raw, wrapSel, preSel, toggleSel, copySel ) {
	const wrap = qs( modal, wrapSel );
	const pre = qs( modal, preSel );
	const toggle = qs( modal, toggleSel );
	const copyBtn = qs( modal, copySel );

	if ( ! raw ) {
		wrap.hidden = true;
		return;
	}

	wrap.hidden = false;

	let prettyStr = null;
	try {
		const parsed = JSON.parse( raw );
		const isEmpty =
			( Array.isArray( parsed ) && parsed.length === 0 ) ||
			( parsed !== null &&
				typeof parsed === 'object' &&
				! Array.isArray( parsed ) &&
				Object.keys( parsed ).length === 0 );
		if ( isEmpty ) {
			toggle.hidden = true;
			copyBtn.hidden = true;
			pre.textContent = raw;
			return;
		}
		prettyStr = JSON.stringify( parsed, null, 2 );
	} catch ( e ) {}

	copyBtn.hidden = false;

	if ( prettyStr !== null ) {
		toggle.hidden = false;
		pre.textContent = prettyStr;
		toggle.querySelectorAll( '.outradar-toggle-btn' ).forEach( function ( btn ) {
			btn.classList.toggle( 'is-active', 'pretty' === btn.dataset.mode );
			btn.onclick = function ( e ) {
				e.stopPropagation();
				toggle.querySelectorAll( '.outradar-toggle-btn' ).forEach( function ( b ) {
					b.classList.toggle( 'is-active', b === btn );
				} );
				pre.textContent = 'pretty' === btn.dataset.mode ? prettyStr : raw;
			};
		} );
	} else {
		toggle.hidden = true;
		pre.textContent = raw;
	}

	copyBtn.onclick = function ( e ) {
		e.stopPropagation();
		copyToClipboard( raw ).then( function () {
			copyBtn.classList.add( 'is-copied' );
			setTimeout( function () {
				copyBtn.classList.remove( 'is-copied' );
			}, 1500 );
		} );
	};
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

	setContentBlock(
		modal,
		row.request_headers || '',
		'.f-headers-wrap',
		'.f-headers',
		'.f-headers-toggle',
		'.f-headers-copy'
	);
	setContentBlock(
		modal,
		row.request_body || '',
		'.f-body-wrap',
		'.f-body',
		'.f-body-toggle',
		'.f-body-copy'
	);
	qs( modal, '.f-no-request' ).hidden = !! ( row.request_headers || row.request_body );
}

export function initModal() {
	if ( ! document.querySelector( '.outradar-row-toggle' ) ) {
		return;
	}

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
					loader.textContent = t( 'loadFailed' );
				} );
		} );
	} );
}
