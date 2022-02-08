/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

let Config = {
	site: 'https://website.com',
	viewMode: 'list',
	dataTypes: 'all',
	secret: 'xxsecretxx'
};

Application.EnigmaRun = function()
{	
	refreshStructure();
	refreshPages();
	getPageProperties();
}


/**/

function refreshStructure()
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'refresh',
		secret: Config.secret,
		data: {
			parent: Application.currentPage
		},
		callback: Enigma.CreateCallback( function( data )
		{
			let str = '';
			// List view mode
			if( Config.viewMode == 'list' )
			{
				document.querySelector( '.IconSmall.fa-list' ).classList.add( 'Active' );
				document.querySelector( '.IconSmall.fa-table' ).classList.remove( 'Active' );
				str += '<div class="List">';
				let sw = 1;
				for( let a = 0; a < data.length; a++ )
				{
					let type  = 'edit' + ( data[a].Type == 'TextBlock' ? 'TextBlock' : 'Article' );
					let delty = 'delete' + ( data[a].Type == 'TextBlock' ? 'TextBlock' : 'Article' );
					let edit = '<button class="IconSmall fa-edit" onclick="' + type + '(' + data[a].ID + ')"></button>';
					
					if( data[a].Type == 'Image' )
					{
						delty = 'removeImage';
						edit = '';
					}
				
					let d = new Date( data[a].DateCreated );
					let date = (
						StrPad( d.getDate(), 2, '0' ) + '.' + 
						StrPad( d.getMonth() + 1, 2, '0' ) + '.' + 
						d.getFullYear() + ' ' + 
						StrPad( d.getHours(), 2, '0' ) + ':' + 
						StrPad( d.getMinutes(), 2, '0' )
					);
					
					let i = {
						hash: data[a].Extra.split( ':' )[1]
					};
					let ext = data[a].Title.split( '.' ).pop();
					
					let url = Config.site + '/files/' + data[a].ID + '/' + encodeURIComponent( data[a].Title );
					
					let tit = data[a].Title ? data[a].Title : data[a].Leadin;
					
					str += '<div class="HRow sw' + sw + '">\
						<div class="HContent50 Ellipsis FloatLeft PaddingSmall TextLeft">\
							' + tit + '\
						</div>\
						<div class="HContent30 Ellipsis FloatLeft PaddingSmall">\
							' + date + '\
						</div>\
						<div class="HContent20 TextRight FloatLeft PaddingSmall">\
							' + edit + '\
							<button class="IconSmall fa-remove" onclick="' + delty + '(' + data[a].ID + ')"></button>\
						</div>\
					</div>';
					sw = sw == 2 ? 1 : 2;
				}
				str += '</div>';
			}
			// Grid view mode 
			else if( Config.viewMode == 'grid' )
			{
				document.querySelector( '.IconSmall.fa-table' ).classList.add( 'Active' );
				document.querySelector( '.IconSmall.fa-list' ).classList.remove( 'Active' );
				for( let a = 0; a < data.length; a++ )
				{
					let d = new Date();
					let date = (
						StrPad( d.getDate(), 2, '0' ) + '.' + 
						StrPad( d.getMonth() + 1, 2, '0' ) + '.' + 
						d.getFullYear() + ' ' + 
						StrPad( d.getHours(), 2, '0' ) + ':' + 
						StrPad( d.getMinutes(), 2, '0' )
					);
					
					let tit = data[a].Title ? data[a].Title : data[a].Leadin;
					
					if( data[a].Type == 'Image' )
					{
						let i = {
							hash: data[a].Extra.split( ':' )[1]
						};
						let ext = data[a].Title.split( '.' ).pop();
						
						let url = Config.site + '/files/' + data[a].ID + '/' + encodeURIComponent( data[a].Title );
						
						if( ext == 'docx' || ext == 'doc' || ext == 'ppt' || ext == 'pptx' || ext == 'xls' || ext == 'xlsx' || ext == 'pdf' )
						{
							str += '\
						<div class="PageItem">\
							<div class="Buttons">\
								<!--<button class="IconSmall fa-clipboard" onclick="copyToClipboard(\'' + url + '\',\'' + ext + '\')"></button>-->\
								<button class="IconSmall fa-remove" onclick="removeImage(' + data[a].ID + ')"></button>\
							</div>\
							<div class="Title">' + tit + '</div>\
							<div class="Content Image">\
								[DOKUMENT]<br>\
								<input type="text" value="' + url + '" style="width: 100%"/>\
							</div>\
						</div>';
						}
						else
						{	
							str += '\
						<div class="PageItem">\
							<div class="Buttons">\
								<!--<button class="IconSmall fa-clipboard" onclick="copyToClipboard(\'' + url + '\',\'' + ext + '\')"></button>-->\
								<button class="IconSmall fa-remove" onclick="removeImage(' + data[a].ID + ')"></button>\
							</div>\
							<div class="Title">' + tit + '</div>\
							<div class="Content Image" style="background-image: url(\'' + url + '\')">\
								<img src="' + url + '"/>\
							</div>\
						</div>';
						}
					}
					else
					{
						let type  = 'edit' + ( data[a].Type == 'TextBlock' ? 'TextBlock' : 'Article' );
						let delty = 'delete' + ( data[a].Type == 'TextBlock' ? 'TextBlock' : 'Article' );
						str += '\
					<div class="PageItem">\
						<div class="Buttons">\
							<button class="IconSmall fa-edit" onclick="' + type + '(' + data[a].ID + ')"></button>\
							<button class="IconSmall fa-remove" onclick="' + delty + '(' + data[a].ID + ')"></button>\
						</div>\
						<div class="Title">' + tit + '</div>\
						<div class="Content">\
							<p class="Author">By ' + data[a].Author + '</p>\
							<p class="Date">' + date + '</p>\
						</div>\
					</div>';
					}
				}
			}
			document.querySelector( '.History' ).innerHTML = str;
		} )
	}, '*' );
}

async function copyToClipboard( image, ext )
{
	if( ext == 'jpg' ) ext = 'jpeg';
	let str = 'image/' + ext;
		
	const img = new Image();
	const c = document.createElement('canvas');
	const ctx = c.getContext('2d');
	function setCanvasImage(path,func){
		img.onload = function(){
			c.width = this.naturalWidth
			c.height = this.naturalHeight
			ctx.drawImage(this,0,0)
			c.toBlob(blob=>{
				func(blob)
			},str);
		}
		img.src = path
	}
	setCanvasImage(img,(imgBlob)=>{
		let cl = {}; cl[str] = imgBlob;
		navigator.clipboard.write(
			[
				new ClipboardItem({cl: imgBlob})
			]
		)
		.then(e=>{console.log('Image copied to clipboard')})
		.catch(e=>{console.log(e)})
	});
	
	/*try
	{
		const re = await fetch( image );
		const bl = await re.blob();
		
		await navigator.clipboard.write( [ new ClipboardItem( cl ) ] );
	}
	catch( e )
	{
		//...
		console.log( 'What the bleep: ' + image, ext, e );
	}*/
}

/* Quotes */

function addQuote()
{
	let v = new View( {
		title: 'Add quote',
		width: 600,
		height: 500
	} );
	
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'getpages',
		secret: Config.secret,
		userId: Application.userId,
		callback: Enigma.CreateCallback( function( pageData ){
			let p = new File( 'Progdir:Library/Layout/quote.html' );
			p.replacements = {
				quote: '',
				tags: '',
				title: '',
				author: Application.fullName,
				id: '0',
				pages: generatePageSelectOptions( Application.currentPage, pageData, 0, 0 ),
				viewId: Application.viewId
			};
			p.onLoad = function( data )
			{
				v.setContent( data );
			}
			p.load();
		} )
	}, '*' );
}


/* Images */

function removeImage( imageId )
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'deleteimage',
		secret: Config.secret,
		userId: Application.userId,
		data: {
			imageId: imageId,
			userId: Application.userId
		},
		callback: Enigma.CreateCallback( function(){
			refreshStructure();
		} )
	}, '*' );
}

/* Text blocks */

function addTextBlock()
{
	let v = new View( {
		title: 'Add new textblock',
		width: 700,
		height: 700
	} );
	
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'getpages',
		userId: Application.userId,
		secret: Config.secret,
		callback: Enigma.CreateCallback( function( pageData ){
			let p = new File( 'Progdir:Library/Layout/textblock.html' );
			p.replacements = {
				textblock: '',
				name: '',
				date: '',
				priority: '0',
				id: '0',
				pages: generatePageSelectOptions( Application.currentPage, pageData, 0, 0 ),
				author: Application.fullName,
				viewId: Application.viewId
			};
			p.onLoad = function( data )
			{
				v.setContent( data );
			}
			p.load();
		} )
	}, '*' );
}

function deleteTextBlock( id )
{
	Confirm( 'Are you sure?', 'This will delete your text block, making it unreadable on the website and here in this admin tool.', function( response )
	{
		if( response.data == true )
		{
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'deletetextblock',
				secret: Config.secret,
				userId: Application.userId,
				data: {
					textblockid: id,
					userId: Application.userId
				},
				callback: Enigma.CreateCallback( function(){
					refreshStructure();
				} )
			}, '*' );
		}
	} );
}

/* Articles */

function addArticle()
{
	let v = new View( {
		title: 'Add new article',
		width: 700,
		height: 700
	} );
	
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'getpages',
		secret: Config.secret,
		userId: Application.userId,
		callback: Enigma.CreateCallback( function( pageData ){
			let p = new File( 'Progdir:Library/Layout/article.html' );
			p.replacements = {
				article: '',
				leadin: '',
				title: '',
				id: '0',
				pages: generatePageSelectOptions( Application.currentPage, pageData, 0, 0 ),
				author: Application.fullName,
				viewId: Application.viewId
			};
			p.onLoad = function( data )
			{
				v.setContent( data );
			}
			p.load();
		} )
	}, '*' );
}

function deleteArticle( id )
{
	Confirm( 'Are you sure?', 'This will delete your article, making it unreadable on the website and here in this admin tool.', function( response )
	{
		if( response.data == true )
		{
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'deletearticle',
				secret: Config.secret,
				userId: Application.userId,
				data: {
					articleid: id,
					userId: Application.userId
				},
				callback: Enigma.CreateCallback( function(){
					refreshStructure();
				} )
			}, '*' );
		}
	} );
}


function generatePageSelectOptions( curr, pdata, par, dep )
{
	// Add the unpublished level at init
	if( dep === 0 && pdata )
	{
		pdata.push( {
			Parent: 0,
			Name: 'Unpublished',
			ID: 0
		} );
	}
	
	let str = '';
	let spa = '';
	
	if( dep > 0 )
	{
		for( let a = 0; a < dep; a++ )
			spa += '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	
	let subdep = dep + 1;
	for( let a = 0; a < pdata.length; a++ )
	{
		if( parseInt( pdata[a].Parent ) === parseInt( par ) )
		{
			let cu = parseInt( curr ) == parseInt( pdata[a].ID ) ? ' selected="selected"' : '';
			str += '<option value="' + pdata[a].ID + '"' + cu + '>' + spa + pdata[a].Name + '</option>';
			if( pdata[a].ID > 0 )
				str += generatePageSelectOptions( curr, pdata, pdata[a].ID, subdep );
		}
	}
	return str;
}

function editTextBlock( id )
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'edittextblock',
		secret: Config.secret,
		userId: Application.userId,
		data: { articleId: id, userId: Application.userId },
		callback: Enigma.CreateCallback( function( data ){
			if( !data )
			{
				return Alert( 'Could not edit text block', 'The database gave a negative response when fetching the text block.' );
			}
			
			let v = new View( {
				title: 'Edit text block',
				width: 700,
				height: 700
			} );
			
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'getpages',
				secret: Config.secret,
				userId: Application.userId,
				callback: Enigma.CreateCallback( function( pageData ){ 
					
					let p = new File( 'Progdir:Library/Layout/textblock.html' );
					p.replacements = {
						priority: data.Priority,
						name: data.Name,
						date: data.DateUpdated.split( ' ' )[0],
						time: data.DateUpdated.split( ' ' )[1],
						textblock: data.Text,
						pages: generatePageSelectOptions( data.Parent, pageData, 0, 0 ),
						id: data.ID,
						viewId: Application.viewId
					};
					p.onLoad = function( data )
					{
						v.setContent( data );
					};
					p.load();
					
				} )
			}, '*' );
			
			
		} )
	}, '*' );
}

function editArticle( id )
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'editarticle',
		secret: Config.secret,
		userId: Application.userId,
		data: { articleId: id, userId: Application.userId },
		callback: Enigma.CreateCallback( function( data ){
			if( !data )
			{
				return Alert( 'Could not edit article', 'The database gave a negative response when fetching the article.' );
			}
			
			let v = new View( {
				title: 'Edit ' + ( data.Type == 'quote' ? 'quote' : 'article' ),
				width: ( data.Type == 'quote' ? 600 : 700 ),
				height: ( data.Type == 'quote' ? 500 : 700 )
			} );
			
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'getpages',
				secret: Config.secret,
				userId: Application.userId,
				callback: Enigma.CreateCallback( function( pageData ){ 
					
					let p = new File( 'Progdir:Library/Layout/' + ( data.Type == 'quote' ? 'quote' : 'article' ) + '.html' );
					p.replacements = {
						article: data.Article,
						leadin: data.Leadin,
						title: data.Title,
						quote: data.Leadin,
						tags: data.Title,
						author: data.Author,
						pages: generatePageSelectOptions( data.Parent, pageData, 0, 0 ),
						id: data.ID,
						viewId: Application.viewId
					};
					p.onLoad = function( data )
					{
						v.setContent( data );
					};
					p.load();
					
				} )
			}, '*' );
			
			
		} )
	}, '*' );
}

Application.currentPage = 0;

function refreshPages()
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'getpages',
		secret: Config.secret,
		userId: Application.userId,
		callback: Enigma.CreateCallback( function( data ){ 
			let str = doRefreshPages( data, 0 );
			if( str.length )
			{
				document.querySelector( '.Pages' ).innerHTML = str;
			}
		} )
	}, '*' );
	
	function doRefreshPages( data, parent )
	{
		let str = '';
		if( !parent )
		{
			let cl = !Application.currentPage ? ' class="Current"' : '';
			str += '<ul><li onclick="setPage(0)"' + cl + '>Unpublished</li></ul><hr class="Divider"/>';
		}
		for( let a = 0; a < data.length; a++ )
		{
			if( data[a].Parent == parent )
			{
				let subs = doRefreshPages( data, data[a].ID );
				if( subs.length ) subs = '<ul>' + subs + '</ul>';
				let ty = data[a].Type == 'normal' ? '' : data[a].Type;
				let cl = Application.currentPage == data[a].ID ? ' class="Current ' + ty + '"' : ' class="' + ty + '"';
				str += '<ul><li onclick="setPage(' + data[a].ID + ')"' + cl + '>' + data[a].Name + ' <span class="AddPage IconSmall fa-plus" onclick="addPage( ' + data[a].ID + ', event )"></span></li>' + subs + '</ul>';
			}
		}
		if( str.length ) return str;
		return '';
	}
}

function getPageProperties( pageId )
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'getpageproperties',
		secret: Config.secret,
		userId: Application.userId,
		data: {
			pageId: pageId
		},
		callback: Enigma.CreateCallback( function( data ){ 
		
			let types = [ 'Normal', 'Header', 'Category', 'Index', 'Videopage' ];
			let topts = '';
			for( let a = 0; a < types.length; a++ )
			{
				let typeVal = ( types[a].toLowerCase() == 'normal' ? '' : types[a].toLowerCase() );
				let sel = data.Type == typeVal ? ' selected="selected"' : '';
				topts += '<option value="' + typeVal + '"' + sel + '>' + types[a] + '</option>';
			}
			let published = data.Published == 1 ? true : false;
			
			if( !pageId )
			{
				document.querySelector( '.Properties' ).innerHTML = '<div class="Archived"></div><h2>Unpublished content</h2><p>This is where you will find all of your unpublished content.</p>';
				document.querySelector( '.TopPaletteHeading' ).innerHTML = 'Create new unpublished content';
			}
			else
			{
				document.querySelector( '.TopPaletteHeading' ).innerHTML = 'New content in ' + data.Name;
				
				let str = '<p><strong>Title:</strong></p>\
				<p><input type="text" id="pageName" class="FullWidth InputHeight" value="' + data.Name + '"/></p>\
				<p><strong>Menu title:</strong></p>\
				<p><input type="text" id="pageMenuName" class="FullWidth InputHeight" value="' + data.MenuName + '"/></p>\
				<div class="HRow">\
					<div class="HContent33 FloatLeft">\
						<p><strong>Priority:</strong></p>\
						<p><input type="text" id="pagePriority" class="InputHeight TextCenter" size="4" value="' + data.Priority + '"/></p>\
					</div>\
					<div class="HContent33 FloatLeft">\
						<p><strong>Type:</strong></p>\
						<p><select id="pageType" class="InputHeight">' + topts + '</select></p>\
					</div>\
					<div class="HContent33 FloatLeft">\
						<p><strong>Published:</strong></p>\
						<p><input type="checkbox" id="pagePublished" ' + ( published ? ' checked="checked"' : '' ) + '/></p>\
					</div>\
				</div>';
				
				str += '<hr class="Divider"/><p>\
					<button type="button" class="FloatRight IconSmall fa-remove" onclick="deletePage(\'' + data.ID + '\')">Delete page</button>\
					<button type="button" class="FloatLeft IconSmall fa-save" onclick="savePage(\'' + data.ID + '\')">Save page</button>\
				</p>';
				document.querySelector( '.Properties' ).innerHTML = str;
			}
		} )
	}, '*' );
}

function deletePage( pageId )
{
	Confirm( 'Are you sure?', 'Yes, remove page for good.', function( d )
	{
		if( d.data )
		{
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'deletepage',
				secret: Config.secret,
				userId: Application.userId,
				data: {
					pageId: pageId
				},
				callback: Enigma.CreateCallback( function( data ){ 
					setPage( data.pageId );
					refreshPages();
				} )
			}, '*' );
		}
	} );
}

function setPage( pageId )
{
	Application.currentPage = pageId;
	
	refreshPages();
	refreshStructure();
	
	getPageProperties( pageId );
}

function addPage( pageId, e )
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'addpage',
		secret: Config.secret,
		userId: Application.userId,
		data: {
			pageId: pageId
		},
		callback: Enigma.CreateCallback( function( data ){ 
			refreshPages();
		} )
	}, '*' );
	
	return e.stopPropagation();
}

function savePage( pageId, e )
{
	window.EnigmaSite.contentWindow.postMessage( {
		query: 'savepage',
		secret: Config.secret,
		userId: Application.userId,
		data: {
			pageId: pageId,
			name: ge( 'pageName' ).value,
			priority: ge( 'pagePriority' ).value,
			type: ge( 'pageType' ).value,
			menuname: ge( 'pageMenuName' ).value,
			published: ge( 'pagePublished' ).checked ? 1 : 0
		},
		callback: Enigma.CreateCallback( function( data ){ 
			refreshPages();
		} )
	}, '*' );
	
	if( e )
		return e.stopPropagation();
	return;
}

/* Messages */

Application.receiveMessage = function( msg )
{
	if( !msg.command ) return;
	
	switch( msg.command )
	{
		case 'drop':
			checkFiles( msg );
			break;
		case 'savetextblock':
			msg.data.userid = Application.userId;
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'savetextblock',
				secret: Config.secret,
				userId: Application.userId,
				data: msg.data,
				callback: Enigma.CreateCallback( function(){ 
					refreshStructure();
					Application.sendMessage( {
						targetViewId: msg.viewId,
						callback: msg.callback
					} );
				} )
			}, '*' );
			break;
		case 'savequote':
			msg.data.userid = Application.userId;
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'savequote',
				secret: Config.secret,
				userId: Application.userId,
				data: msg.data,
				callback: Enigma.CreateCallback( function(){ 
					refreshStructure();
					Application.sendMessage( {
						targetViewId: msg.viewId,
						callback: msg.callback
					} );
				} )
			}, '*' );
			break;
		case 'savearticle':
			msg.data.userid = Application.userId;
			window.EnigmaSite.contentWindow.postMessage( {
				query: 'savearticle',
				secret: Config.secret,
				userId: Application.userId,
				data: msg.data,
				callback: Enigma.CreateCallback( function(){ 
					refreshStructure();
					Application.sendMessage( {
						targetViewId: msg.viewId,
						callback: msg.callback
					} );
				} )
			}, '*' );
			break;
	}
}

/* 
	Check dropped files
*/
function checkFiles( msg )
{
	if( msg.data )
	{
		let accept = [ 'jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'doc', 'xls', 'xlsx', 'ppt', 'pptx' ];
		let process = [];
		for( let a = 0; a < msg.data.length; a++ )
		{
			let found = false;
			let ext = msg.data[a].Filename.split( '.' );
			ext = ext[ ext.length - 1 ].toLowerCase();
			for( let b = 0; b < accept.length; b++ )
			{
				if( ext == accept[b] )
				{
					found = true;
					break;
				}
			}
			if( found )
			{
				process.push( msg.data[a] );
			}
		}
		if( !process.length ) return false;
		for( let a = 0; a < process.length; a++ )
		{
			let m = new Library( 'system.library' );
			m.onExecuted = function( e, d )
			{
				if( e == 'ok' )
				{
					let str = JSON.parse( d );
					let out = {
						hash: str.hash,
						filename: str.name,
						url: document.location.origin + '/sharedfile/' + str.hash + '/' + str.name,
						parent: Application.currentPage
					};
					window.EnigmaSite.contentWindow.postMessage( {
						query: 'importfile',
						secret: Config.secret,
						userId: Application.userId,
						data: out,
						callback: Enigma.CreateCallback( function( data ){ 
							refreshStructure();
						} )
					}, '*' );
				}
				
			}
			m.execute( 'file/expose', { path: process[ a ].Path } );
		}
	}
}
