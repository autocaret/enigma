/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

let articleEd = null;

// Let's be ugly!
ClassicEditor
	.create( document.querySelector( '#article' ) )
	.then( editor => {
		articleEd = editor;
	} )
	.catch( error => {
		console.error( error );
	} );

// How to save stuff
function SaveArticle()
{
	// 
	Application.sendMessage( {
		command: 'savearticle',
		data: {
			title: ge( 'title' ).value,
			author: ge( 'author' ).value,
			text: ge( 'leadin' ).value,
			article: articleEd.getData(),
			articleId: ge( 'articleId' ).value,
			parent: ge( 'parent' ).value
		},
		callback: addCallback( function()
		{
			CloseView();
		} ),
		targetViewId: ge( 'parentView' ).value
	} );
}

Application.receiveMessage = function( msg )
{
	if( !msg.command ) return;
	
	switch( msg.command )
	{
		case 'drop':
			handleDrop( msg );
			break;
	}
}

function handleDrop( msg )
{
	if( msg.data.length )
	{
		for( let a = 0; a < msg.data.length; a++ )
		{
			
		}
	}
}
