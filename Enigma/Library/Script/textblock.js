/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

let articleEd = null;

// Let's be ugly!
ClassicEditor
	.create( document.querySelector( '#textblock' ) )
	.then( editor => {
		articleEd = editor;
	} )
	.catch( error => {
		console.error( error );
	} );
	
function SaveTextBlock()
{
	Application.sendMessage( {
		command: 'savetextblock',
		data: {
			name: ge( 'textblockName' ).value,
			date: ge( 'textblockDate' ).value + ' ' + ge( 'textblockTime' ).value,
			text: articleEd.getData(),
			priority: ge( 'priority' ).value,
			articleId: ge( 'articleId' ).value,
			author: ge( 'author' ).value,
			parent: ge( 'parent' ).value
		},
		callback: addCallback( function()
		{
			CloseView();
		} ),
		targetViewId: ge( 'parentView' ).value
	} );
}

