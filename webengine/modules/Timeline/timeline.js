/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

//

let separator = '<!--separate-->';
let items = [];
let palette = [ '#60BBAB', '#FCE06B', '#71AFD9', '#DADADA' ];
let pcolors = [ '#FFFFFF', '#314252', '#FFFFFF', '#67BCAD' ];
let range = new Date().getFullYear();
let tim = document.querySelector( '#Timeline' );

// Draw year slots
function redrawTimeline( data )
{
	let output = '';
	
	// Check years
	for( let a = -15; a <= 15; a++ )
	{
		let q = '';
		let year = range + a;
		// Check quarter
		for( let b = 0; b < 4; b++ )
		{
			let months = '';
			// Check month
			for( let c = 0; c < 3; c++ )
			{
				let month = b * 3 + c + 1;
				let items = [];
				for( let d = 0; d < data.length; d++ )
				{
					let dateRow = data[d].DateUpdated.split( ' ' )[0];
					dateRow = dateRow.split( '-' );
					let strMonth = month + '';
					if( strMonth.length < 2 ) strMonth = '0' + strMonth;
					if( dateRow[0] == year && dateRow[1] == strMonth )
					{
						items.push( data[d] );
					}
				}
				
				let out = '';
				if( items.length )
				{
					for( let d = 0; d < items.length; d++ )
					{
						let dt = items[d].DateUpdated.split( ' ' )[0];
						dt = dt.split( '-' );
						dt = dt[2] + '/' + dt[1] + '/' + dt[0];
						out += '<div class="Event"><div class="Info"><div>' + items[d].Name + '</div><div>' + dt + '</div><div>' + items[d].Text + '</div></div></div>';
					}
				}
				
				months += '<div class="Month MonthNum' + month + '">' + out + '</div>';
			}
			q += '<div class="Quarter"><span>Q' + ( b + 1 ) + '</span><div>' + months + '</div></div>';
		}
		output += '<div class="Slab" year="' + ( range + a ) + '" now="' + ( a == 0 ? 'yes' : 'no' ) + '"><div class="Year">' + ( range + a ) + '</div><div class="Quarters">' + q + '</div></div>';
	}

	tim.innerHTML = output;
	
	let events = tim.getElementsByClassName( 'Event' );
	for( let a = 0; a < events.length; a++ )
	{
		events[a].onclick = function( e )
		{
			e.stopPropagation();
			let self = this;
			document.getElementById( 'TimelineEventBox' ).classList.remove( 'Showing' );
			setTimeout( function()
			{
				document.getElementById( 'TimelineEventBox' ).innerHTML = self.getElementsByClassName( 'Info' )[0].innerHTML;
				document.getElementById( 'TimelineEventBox' ).classList.add( 'Showing' );
				document.getElementById( 'TimelineEventBox' ).style.top = '20px';
				document.getElementById( 'TimelineEventBox' ).style.left = '20px';
				document.getElementById( 'TimelineEventBox' ).style.height = 'calc(100% - 40px)';
			}, 250 );
		};
		events[a].ontouchstart = function( e )
		{
			this.onclick( e );
		}
	}

	let slabs = tim.getElementsByClassName( 'Slab' );
	let pos = 0;
	for( let a = 0; a < slabs.length; a++ )
	{
		slabs[a].style.left = pos + 'px';
		if( slabs[a].getAttribute( 'now' ) == 'yes' )
		{
			tim.style.left = ( ( tim.parentNode.offsetWidth >> 1 ) - ( slabs[a].offsetLeft + ( slabs[a].offsetWidth >> 1 ) ) ) + 'px';
		}
		slabs[a].style.backgroundColor = palette[ a % palette.length ];
		slabs[a].style.color = pcolors[ a % pcolors.length ];
		pos += 300; 
	}
}

// Global click
window.addEventListener( 'click', function( e )
{
	document.getElementById( 'TimelineEventBox' ).classList.remove( 'Showing' );
} );

// Add arrows
let arrLeft = document.createElement( 'div' );
arrLeft.classList.add( 'ArrowLeft' );
arrLeft.onclick = function()
{
	let le = tim.offsetLeft;
	let maxs = 0 - ( ( tim.lastChild.offsetLeft + tim.lastChild.offsetWidth ) - tim.parentNode.offsetWidth - 1 );
	
	le += 300;
	
	if( le > 0 ) le = 0;
	else if ( le < maxs ) le = maxs;
	
	tim.style.left = le + 'px';
}
let arrRight = document.createElement( 'div' );
arrRight.classList.add( 'ArrowRight' );
arrRight.onclick = function()
{
	let le = tim.offsetLeft;
	let maxs = 0 - ( ( tim.lastChild.offsetLeft + tim.lastChild.offsetWidth ) - tim.parentNode.offsetWidth - 1 );
	
	le -= 300;
	
	if( le > 0 ) le = 0;
	else if ( le < maxs ) le = maxs;
	
	tim.style.left = le + 'px';
}
tim.parentNode.appendChild( arrLeft );
tim.parentNode.appendChild( arrRight );

/*// Animate event box
window.addEventListener( 'mousemove', function( e )
{
	let tim = document.querySelector( '.Timeline' );
	let tbx = document.getElementById( 'TimelineEventBox' );
	let px = ( e.clientX - tim.offsetLeft - ( tbx.offsetWidth >> 1 ) ) + 'px';
	let py = ( e.clientY - tim.offsetTop + 20 + document.body.parentNode.scrollTop );
	if( py + tbx.offsetHeight > tim.offsetHeight - 20 )
	{
		py = tim.offsetHeight - tbx.offsetHeight - 20;
	}
	tbx.style.left = px;
	tbx.style.top = py + 'px';
} );*/

function timDOWN( e )
{
	let clx = e.clientX ? e.clientX : e.touches[0].clientX;
	tim.classList.add( 'Activated' );
	tim.offx = clx - tim.parentNode.offsetLeft;
	tim.olef = tim.offsetLeft;
	tim.mouse = true;
}

tim.parentNode.addEventListener( 'mousedown', timDOWN );
tim.parentNode.addEventListener( 'touchstart', timDOWN );

function timMUP( e )
{
	tim.classList.remove( 'Activated' );
	tim.mouse = false;
	tim.offx = false;
};

window.addEventListener( 'mouseup', timMUP );
window.addEventListener( 'touchend', timMUP );

function timMove( e )
{
	if( tim.mouse )
	{
		let clx = e.clientX ? e.clientX : e.touches[0].clientX;
		let skew = ( clx - tim.parentNode.offsetLeft ) - tim.offx;
		let maxs = 0 - ( ( tim.lastChild.offsetLeft + tim.lastChild.offsetWidth ) - tim.parentNode.offsetWidth - 1 );
		let cand = tim.olef + skew;
		if( cand > 0 ) cand = 0;
		if( cand < maxs )
			cand = maxs;
		tim.style.left = Math.floor( cand ) + 'px';
	}
};

tim.parentNode.addEventListener( 'mousemove', timMove );
tim.parentNode.addEventListener( 'touchmove', timMove );

// Load data
let j = new XMLHttpRequest();
j.open( 'POST', 'transport.php', true );
j.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
j.onload = function()
{
	if( this.responseText.substr( 0, 2 ) == 'ok' )
	{
		let data = this.responseText.split( separator );
		let js = JSON.parse( data[1] );
		redrawTimeline( js );
		tim.classList.add( 'Showing' );
	}
	else
	{
		time.classList.add( 'Empty' );
	}
}
let js = { 'module': 'Timeline', 'call': 'getdata' };
js = JSON.stringify( js );
j.send( 'query=modulecall&data=' + encodeURIComponent( js ) );

