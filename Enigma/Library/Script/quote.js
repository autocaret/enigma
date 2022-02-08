/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

function SaveQuote()
{
	Application.sendMessage( {
		command: 'savequote',
		data: {
			text: ge( 'quote' ).value,
			title: ge( 'tags' ).value,
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
