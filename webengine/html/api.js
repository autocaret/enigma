/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

// Create main Javascript Object
window.Enigma = window.Enigma ? window.Enigma : {};
Enigma.Callbacks = {};

// Create a callback
Enigma.CreateCallback = function( cbk )
{
	let rand = false;
	while( !rand || Enigma.Callbacks[ rand ] )
	{
		rand = Math.random() * 9999 + ( Math.random() * 9999 ) + ( new Date() ).getTime();
	}
	Enigma.Callbacks[ rand ] = cbk;
	return rand;
};

// Run a callback and remove from stack
Enigma.ExecuteCallback = function( cbk, data )
{
	// Find callback and clean up
	let out = {};
	let c = false;
	for( let a in Enigma.Callbacks )
	{
		if( a == cbk )
		{
			c = Enigma.Callbacks[ a ];
		}
		else
		{
			out[ a ] = Enigma.Callbacks[ a ];
		}
	}
	Enigma.Callbacks = out;
	
	// Run Callback
	if( c )
	{
		try
		{
			c( JSON.parse( decodeURIComponent( data ) ) );
		}
		catch( e )
		{
			c( false, 'ERR_NO_DATA' );
		}
	}
};

// Just instantiate access to Enigma API
console.log( 'Welcome to Enigma API.' );

let d = document.createElement( 'iframe' );
d.src = 'https://website.com/transport.php?apimode=true';
d.style.position = 'absolute';
d.style.pointerEvents = 'none';
d.style.visibility = 'hidden';
window.EnigmaSite = d;
d.onload = function()
{
	if( window.Application && Application.EnigmaRun )
	{
		console.log( 'Running Enigma now.' );
		Application.EnigmaRun();
	}
	else console.log( 'Please create an Application.EnigmaRun function!' );
}
document.body.appendChild( d );

window.addEventListener( 'message', function( msg )
{
	if( msg.data.callback )
	{
		Enigma.ExecuteCallback( msg.data.callback, msg.data.data );
	}
} );

