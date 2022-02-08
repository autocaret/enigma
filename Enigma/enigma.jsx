/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

Application.run = function( msg )
{
    this.setApplicationName( 'Enigma' );
    
    let v = new View( {
        title: 'Enigma - Version 0.4',
        width: 1280,
        height: 720
    } );
    
    v.setMenuItems( [ {
    	name: 'File',
    	items: [ {
    		name: 'About Enigma',
    		command: 'aboutEnigma'
    	}, {
    		name: 'Quit',
    		command: 'quit'
    	} ]
    } ] );
    
    this.mainView = v;
    
    v.onClose = function()
    {
        Application.quit();
    }
    
    let f = new File( 'Progdir:Library/Layout/gui.html' );
    f.onLoad = function( data )
    {
    	v.setContent( data );
    }
    f.load();
}

Application.receiveMessage = function( msg )
{
	if( !msg.command ) return;
	if( this.messageFuncs[ msg.command ] )
	{
		this.messageFuncs[ msg.command ]( msg );
	}
}

Application.messageFuncs = {
	aboutEnigma: function( msg )
	{
		let v = new View( {
			title: 'About Enigma Web Engine',
			width: 700,
			height: 600
		} );
	}
};
