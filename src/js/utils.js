export function qs( root, sel ) {
	return root.querySelector( sel );
}

export function setText( root, sel, val ) {
	const el = qs( root, sel );
	if ( el ) {
		el.textContent = val || '—';
	}
}

export function dim( text ) {
	const s = document.createElement( 'small' );
	s.className = 'outradar-dim';
	s.textContent = ' (' + text + ')';
	return s;
}

export function copyToClipboard( text ) {
	if ( navigator.clipboard && window.isSecureContext ) {
		return navigator.clipboard.writeText( text );
	}
	const ta = document.createElement( 'textarea' );
	ta.value = text;
	ta.style.position = 'fixed';
	ta.style.opacity = '0';
	document.body.appendChild( ta );
	ta.select();
	document.execCommand( 'copy' );
	document.body.removeChild( ta );
	return Promise.resolve();
}
