<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

class Template
{
	function __construct()
	{
		global $DB, $Config;
		
		$this->replacements = new stdClass();
		$this->replacements->page_name = 'home';
		$this->replacements->page_title = 'Hjem';
		$this->replacements->page_class = '';
		$this->replacements->page_description = $Config->PageDescription;
		
		if( trim( $_SERVER[ 'REQUEST_URI' ] ) != '/' )
		{
			$pages = explode( '/', $_SERVER[ 'REQUEST_URI' ] );

			// Just a file
			if( $pages[1] == 'files' && intval( $pages[2], 10 ) > 0 )
			{
				if( $r = $DB->query( 'SELECT * FROM Images WHERE ID=\'' . intval( $pages[2], 10 ) . '\'' ) )
				{
					if( $row = $r->fetch_object() )
					{
						$ext = explode( '.', $row->Filename );
						$ext = $ext[count($ext) - 1];
						
						$file = explode( ':', $row->Url );
						
						if( file_exists( $Config->Upload . '/' . $file[1] ) )
						{
							// Image support
							if( 
								strtolower( $ext ) == 'jpg' ||
								strtolower( $ext ) == 'jpeg' ||
								strtolower( $ext ) == 'gif' ||
								strtolower( $ext ) == 'png'
							)
							{
								if( strtolower( $ext ) == 'jpg' )
								{
									$ext = 'jpeg';
								}
								header( 'Content-type: image/' . $ext );
							}
							else
							{
								header( 'Content-type: application/octet-stream' );
							}
							die( file_get_contents( $Config->Upload . '/' . $file[1] ) );
						}
					}
				}
				die( '404' );
			}
			// Just an article
			else if( $pages[1] == 'articles' && intval( $pages[2], 10 ) > 0 )
			{
				$this->mode = 'article';
				$this->article = intval( $pages[2], 10 );
			}
			// Just an article
			else if( ( $pages[1] == 'quote' ) )
			{
				$this->mode = 'quote';
				if( isset( $pages[2] ) && intval( $pages[2], 10 ) > 0 )
				{
					$this->quote = intval( $pages[2], 10 );
				}
				else
				{
					$this->quote = $pages[2];
				}
			}
			// Search
			else if( $pages[1] == 'search' )
			{
				$this->mode = 'search';
			}
			
			if( count( $pages ) > 1 )
			{
				$ml = array_pop( $pages );
				$this->replacements->page_name = $pages[ count( $pages ) - 1 ];
			}
		}
		
		// Get current page
		if( !( $r = $DB->query( 'SELECT * FROM `Pages` p WHERE p.MenuName = "' . $this->replacements->page_name . '" LIMIT 1' ) ) )
		{
			die( '404' );
		}
		
		if( $row = $r->fetch_object() )
		{
			$this->page =& $row;
			if( !isset( $this->replacements->page_name ) )
				$this->replacements->page_name = $row->Name;
			
			if( isset( $row->Template ) && trim( $row->Template ) )
			{
				$this->template = file_get_contents( '../templates/' . $row->Template . '.html' );
			}
			else
			{
				$this->template = file_get_contents( '../templates/page.html' );
				$this->page->Template = 'page';
			}
		}
		else
		{
		    // Recognize query
		    $pages = explode( '/', $_SERVER[ 'REQUEST_URI' ] );
		    if( ( $r = $DB->query( 'SELECT * FROM `Pages` p WHERE p.Parent=0 AND p.MenuName = "' . $pages[1] . '" LIMIT 1' ) ) )
		    {
			    if( $row = $r->fetch_object() )
			    {
			        $this->page =& $row;
		            if( !isset( $this->replacements->page_name ) )
			            $this->replacements->page_name = $row->Name;
		
		            if( isset( $row->Template ) && trim( $row->Template ) )
		            {
			            $this->template = file_get_contents( '../templates/' . $row->Template . '.html' );
		            }
		            else
		            {
			            $this->template = file_get_contents( '../templates/page.html' );
			            $this->page->Template = 'page';
		            }
			    }
		    }
		    
		    // Catch all
		    if( !isset( $this->page ) )
		    {
			    $this->page = new stdClass();
			    $this->page->ID = 0;
			    $this->page->Type = 'search';
			    $this->template = file_get_contents( '../templates/page.html' );
			    $this->page->Template = 'page';
			}
		}
	}
	
	function render()
	{
		global $DB;
		
		if( !isset( $this->template ) ) return '';
		$t = $this->template;
		
		$this->replacements->menu_toplevel = $this->renderMenu( 0 );
		$this->parseContent();
		
		$content = '';
		
		// We want to fetch an article
		if( isset( $this->mode ) && $this->mode == 'article' )
		{
			if( $r = $DB->query( 'SELECT * FROM `Articles` WHERE ID=\'' . $this->article . '\'' ) )
			{
				$arto = $r->fetch_object();
				
				$this->replacements->page_name = $this->safeUrl( $arto->Title );
				$this->replacements->page_title = $arto->Title;
				$this->replacements->page_description = $arto->Leadin;
				$this->replacements->page_class = ' ArticleDetails';
				
				foreach( $this->replacements as $k=>$v )
				{
					switch( $k )
					{
						case 'page_class':
						case 'page_title':
						case 'page_name':
						case 'page_description':
						case 'menu_toplevel':
							$t = str_replace( '{' . $k . '}', $v, $t );
							break;
						default:
							$content .= '<div class="' . str_replace( ' ', '_', $k ) . '">' . $v . '</div>';
							break;
					}
				}
				
				$archive = '<h2>Other articles</h2>';
				
				if( $res = $DB->query( 'SELECT a.* FROM `Articles` a, `Pages` p WHERE a.ID != \'' . $this->article . '\' AND a.Parent = p.ID AND p.Parent = 0 AND p.Published = 1 ORDER BY a.Date DESC' ) )
				{
					while( $row = $res->fetch_object() )
					{
						$linka = 'articles/' . $row->ID . '/' . preg_replace( array( '[\?\!\.]', '/[^A-Z0-9a-z]/' ), array( '', '_' ), $row->Title ) . '.html';
						$archive .= '<p><a href="' . $linka . '">' . $row->Title . '</a> - ' . date( 'd/m/Y - \k\l\. H:i \C\E\T', strtotime( $row->Date ) ) . '</p>';
					}
				}
				
				$article = '<div class="ArticleReading"><h1>' . $arto->Title . '</h1>';
				$article .= '<p class="Date">Publisert ' .  date( 'd/m/Y - \k\l\. H:i \C\E\T', strtotime( $arto->Date ) ) . '</p>';
				$article .= '<p class="Date">Forfatter: <strong>' .  $arto->Author . '</strong></p>';
				$article .= '<p class="Leadin">' . $arto->Leadin . '</p>';
				
				$template = '<iframe src="{src}" style="position: relative; width: 100%; height: 400px; top: 0; left: 0;" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen=""></iframe>';
				
				if( preg_match( '/\<oembed\ url\=\"(.*?)\"\>\<\/oembed\>/i', $arto->Article, $matches ) )
				{
					$link = explode( '.be/', $matches[1] );
					$link = 'https://www.youtube.com/embed/' . $link[1];
					$template = str_replace( '{src}', $link, $template );
					$arto->Article = str_replace( $matches[0], $template, $arto->Article );
				}
				
				$article .= $arto->Article . '</div>';
				
				$content .= str_replace( '{Content}', $article, str_replace( '{Archive}', $archive, $t ) );
				return $content;
			}
		}
		// We want to fetch an article
		// TODO: Needs to be moved to module quote!
		else if( isset( $this->mode ) && $this->mode == 'quote' )
		{
			if( intval( $this->quote, 10 ) > 0 )
			{
				if( $r = $DB->query( 'SELECT * FROM `Articles` WHERE ID=\'' . $this->quote . '\'' ) )
				{
					$content = '';
					foreach( $this->replacements as $k=>$v )
					{
						switch( $k )
						{
							case 'page_class':
							case 'page_title':
							case 'page_name':
							case 'page_description':
							case 'menu_toplevel':
								$t = str_replace( '{' . $k . '}', $v, $t );
								break;
							default:
								$content .= '<div class="' . str_replace( ' ', '_', $k ) . '">' . $v . '</div>';
								break;
						}
					}
					$arto = $r->fetch_object();
					$nick = str_replace( ' ', '_', $arto->Author );
					$linka = '/quote/' . $nick . '/';
					$article = '<div class="ArticleReading"><div class="Author"><div class="UserImage ' . $nick . '"></div><h1><a href="' . $linka . '">' . $arto->Author . '</a></h1>';
					$article .= '<p class="Date">' .  date( 'd/m/Y - \k\l\. H:i \C\E\T', strtotime( $arto->Date ) ) . '</p></div>';
					$article .= '<p class="Leadin">' . $arto->Leadin . '</p>';
					$article .= '</div>';
					$content .= str_replace( '{Content}', $article, $t );
					return $content;
				}
			}
			else
			{
				$r = $DB->query( 'SELECT * FROM Pages WHERE `Type` IS NOT NULL AND Published=1 AND Parent=0 ORDER BY Priority ASC LIMIT 1' );
				$page = $r->fetch_object();
				
				$out = '<div>';
				$out .= $this->executeModule( 'Quote', $page, $this->quote );
				$content = '';
				
				$this->replacements->page_title = $this->page->Name;
				
				foreach( $this->replacements as $k=>$v )
				{
					switch( $k )
					{
						case 'page_class':
						case 'page_title':
						case 'page_name':
						case 'page_description':
						case 'menu_toplevel':
							$t = str_replace( '{' . $k . '}', $v, $t );
							break;
						default:
							$content .= '<div class="' . str_replace( ' ', '_', $k ) . '">' . $v . '</div>';
							break;
					}
				}
				$out .= '</div>';
				$content .= str_replace( '{Content}', $out, $t );
				return $content;
			}
		}
		else if( isset( $this->mode ) && $this->mode == 'search' )
		{
			$out = '';
			$content = '';
			
			foreach( $this->replacements as $k=>$v )
			{
				switch( $k )
				{
					case 'page_class':
					case 'page_title':
					case 'page_name':
					case 'page_description':
					case 'menu_toplevel':
						$t = str_replace( '{' . $k . '}', $v, $t );
						break;
					default:
						$content .= '<div class="' . str_replace( ' ', '_', $k ) . '">' . $v . '</div>';
						break;
				}
			}
			
			// Find content by key
			$out .= $this->search( $_REQUEST[ 'keys' ] );
			$content .= str_replace( '{Content}', $out, $t );
			return $content;
		}
		
		// TODO: Make dynamic!
		if( $this->page->Type == 'index' )
		{
			$this->replacements->page_title = $this->page->Name;
			
			$content = '';
			foreach( $this->replacements as $k=>$v )
			{
				switch( $k )
				{
					case 'page_class':
					case 'page_title':
					case 'page_name':
					case 'page_description':
					case 'menu_toplevel':
						$t = str_replace( '{' . $k . '}', $v, $t );
						break;
					default:
						$content .= '<div class="' . str_replace( ' ', '_', $k ) . '">' . $v . '</div>';
						break;
				}
			}
			if( $r = $DB->query( 'SELECT * FROM `Pages` p WHERE p.Published = \'1\' AND p.Parent = \'' . intval( $this->page->ID, 10 ) . '\' ORDER BY p.Name ASC' ) )
			{
				while( $d = $r->fetch_object() )
				{
					$link = '/' . $this->safeUrl( $d->MenuName ) . '/index.html';
					$content .= '<div class="Category"><h2><a href="' . $link . '">' . $d->Name . '</a></h2></div>';
				}
			}
			$content = str_replace( '{Content}', $content, $t );
			return $content;
		}
		else if( $this->page->Template == 'videopage' )
		{
		    return '';
		}
		else if( $this->page->Template == 'page' )
		{
			$this->replacements->page_title = $this->page->Name;
			
			$content = '';
			foreach( $this->replacements as $k=>$v )
			{
				switch( $k )
				{
					case 'page_class':
					case 'page_title':
					case 'page_name':
					case 'page_description':
					case 'menu_toplevel':
						$t = str_replace( '{' . $k . '}', $v, $t );
						break;
					default:
						$content .= '<div class="' . str_replace( ' ', '_', $k ) . '">' . $v . '</div>';
						break;
				}
			}
			$content = str_replace( '{Content}', $content, $t );
			return $content;
		}
		else
		{
			foreach( $this->replacements as $k=>$v )
			{
				$t = str_replace( '{' . $k . '}', $v, $t );
			}
		}
		return $t;
	}
	
	/* Search engine */
	function search( $keys )
	{
		global $DB;
		
		$out = '<h2>Search results</h2><hr/>';
		$count = 0;
		
		$keys = $DB->real_escape_string( $_REQUEST[ 'keys' ] );
		
		// Do the actual searching:
		if( $r = $DB->query( '
			SELECT * FROM Pages WHERE ( Name LIKE "%' . $keys . '%" OR MenuName LIKE "%' . $keys . '%" ) AND Published=1 AND `Type` IS NOT NULL
		' ) )
		{
			while( $row = $r->fetch_object() )
			{
				$out .= '<div class="SearchResult">';
				$out .= '<p><strong>' . $row->Name . '</strong> - <a href="/' . $row->MenuName . '/index.html">Read more</a></p>';
				$out .= '</div>';
				$count++;
			}
		}
		
		// Search for articles
		if( $r = $DB->query( '
		    SELECT a.* FROM Articles a, Pages p WHERE a.Type IS NULL AND a.Parent = p.ID AND p.Published=1 AND p.Type IS NOT NULL AND  ( a.Title LIKE "%' . $keys . '%" OR a.Leadin LIKE "%' . $keys . '%" OR a.Article LIKE "%' . $keys . '%" ) ORDER BY a.Date DESC
		' ) )
		{
		    while( $row = $r->fetch_object() )
		    {
		        $linka = 'articles/' . $row->ID . '/' . preg_replace( array( '[\?\!\.]', '/[^A-Z0-9a-z]/' ), array( '', '_' ), $row->Title ) . '.html';
		        $out .= '<div class="SearchResult">';
		        $out .= '<p><a href="' . $linka . '"><strong>' . $row->Title . '</strong></a></p>';
		        $out .= '<p class="SearchLeadin">' . $row->Leadin . '</p>';
		        $out .= '</div>';
		        $count++;
		    }
		}
		
		
		if( $count == 0 )
		{
			$out .= '<p>No search results available for your query.</p>';
		}
		return $out;
	}
	
	/* Content */
	function parseContent( $obj = false )
	{
		global $DB;
		
		if( isset( $GLOBALS[ 'prevent_default' ] ) )
	    {
	        return '';
	    }
		
		if( $obj )
		{
			if( isset( $obj->Type ) && isset( $obj->Parent ) )
			{
				if( $obj->Type == 'Articles' )
				{
					// Parse limit
					$lim = isset( $obj->Limit ) && $obj->Limit > 0 ? ( ' LIMIT ' . ( isset( $obj->Start ) ? ( $obj->Start . ',' ) : '' ) . $obj->Limit ) : 
						( isset( $obj->Start ) ? ( ' LIMIT ' . ( $obj->Start . ',0' ) ) : '' );
					if( $r = $DB->query( $q = 'SELECT * FROM Articles a WHERE a.Type IS NULL AND a.Parent = \'' . (string)$obj->Parent . '\' ORDER BY a.Date DESC' . $lim ) )
					{
						$output = '';
						while( $row = $r->fetch_object() )
						{
							$output .= $this->renderArticle( $row );
						}
						return $output;
					}
				}
				else if( $obj->Type == 'Quote' )
				{
					// Parse limit
					$lim = isset( $obj->Limit ) && $obj->Limit > 0 ? ( ' LIMIT ' . ( isset( $obj->Start ) ? ( $obj->Start . ',' ) : '' ) . $obj->Limit ) : 
						( isset( $obj->Start ) ? ( ' LIMIT ' . ( $obj->Start . ',0' ) ) : '' );
					if( $r = $DB->query( 'SELECT * FROM Articles a WHERE a.Type = "quote" AND a.Parent = \'' . (string)$obj->Parent . '\' ORDER BY a.Date DESC' . $lim ) )
					{
						$output = '';
						while( $row = $r->fetch_object() )
						{
							$output .= $this->renderQuote( $row );
						}
						return $output;
					}
				}
				else if( $obj->Type == 'TextBlock' )
				{
					return $obj->Text;
				}
			}
			return '';
		}
		
		$this->content = [];
		
		if( $r = $DB->query( '
			SELECT * FROM
			( 
				SELECT pc.Type, pc.Priority, pc.Name, "" AS `Text`, pc.Parent, pc.Limit, pc.Start FROM PageContent pc WHERE PageID=\'' . $this->page->ID . '\'
			) z
			UNION
			(
				SELECT "TextBlock" AS `Type`, tb.Priority, tb.Name, tb.Text, tb.Parent, 0 AS `Limit`, 0 AS `Start` FROM TextBlock tb WHERE Parent=\'' . $this->page->ID . '\'
			)
			ORDER BY Priority ASC
		' ) )
		{
			while( $row = $r->fetch_object() )
			{
				if( strstr( $row->Type, ':' ) )
				{
					list( $type, $name ) = explode( ':', $row->Type );
					if( strtolower( $type ) == 'module' )
					{
						if( !isset( $row->Name ) ) continue;
						$this->replacements->{$row->Name} = $this->executeModule( $name, $row );
						continue;
					}
				}
				$this->replacements->{$row->Name} = $this->parseContent( $row );
			}
		}
	}
	
	function executeModule( $name, $object, $author = false )
	{
		if( is_dir( '../modules/' . $name . '/' ) && file_exists( '../modules/' . $name . '/module.php' ) )
		{
			$output = '';
			include_once( '../modules/' . $name . '/module.php' );
			return $output;
		}
	}
	
	function renderArticle( $arti )
	{
		global $DB;
		$image = '<div class="Image"><div class="Empty"></div></div>';
		
		$linka = 'articles/' . $arti->ID . '/' . preg_replace( array( '[\?\!\.]', '/[^A-Z0-9a-z]/' ), array( '', '_' ), $arti->Title ) . '.html';
		
		if( $arti->Image )
		{
			if( $r = $DB->query( 'SELECT * FROM Images WHERE ID=\'' . $arti->Image . '\' LIMIT 1' ) )
			{
				if( $image = $r->fetch_object() )
				{
					if( strstr( $image->Url, ':' ) )
					{
						list( $type, $data ) = explode( ':', $image->Url );
						
						// Support file library type
						if( $type == 'file' && file_exists( '../upload/images/' . $data ) )
						{
							$i = getimagesize( '../upload/images/' . $data );
							$blob = 'data:image;base64,' . base64_encode( file_get_contents( '../upload/images/' . $data ) );
							$image = '<div class="Image"><a href="' . $linka . '"><img src="' . $blob . '"/></a></div>';
						}
					}
				}
			}
		}
		else if( preg_match( '/(\<img.*?\>)/i', $arti->Article, $matches ) )
		{
			$image = $matches[1];
			preg_match( '/\<img.*?src\=\"(.*?)\"/', $image, $imageMatch );
			$image = '<div class="Image"><a href="' . $linka . '"><img src="' . $imageMatch[1] . '" width="640" height="auto"/></a></div>';
		}
		
		$date = date( 'd/m/Y - \k\l\. H:i \C\E\T', strtotime( $arti->Date ) );
		
		return '<div class="Article">' . $image . '<h2><a href="' . $linka . '">' . $arti->Title . '</a></h2><p class="Date">' . $date . '</p><p class="Author">Author: <strong>' . $arti->Author . '</strong></p><p>' . $arti->Leadin . '</p><div class="Read"><input class="Clip" type="text" value="' . $linka . '"/><a class="Share mdi mdi-paperclip" href="javascript:void(0)" onclick="cpLink(this)">Del</a><a href="' . $linka . '">Read more...</a></div></div>';
	}
	
	function renderQuote( $quote )
	{
		global $DB;
		$image = '<div class="Image"><div class="Empty"></div></div>';
		if( $quote->Image )
		{
			if( $r = $DB->query( 'SELECT * FROM Images WHERE ID=\'' . $quote->Image . '\' LIMIT 1' ) )
			{
				if( $quote = $r->fetch_object() )
				{
					if( strstr( $quote->Url, ':' ) )
					{
						list( $type, $data ) = explode( ':', $quote->Url );
						
						// Support file library type
						if( $type == 'file' && file_exists( '../upload/images/' . $quote ) )
						{
							$i = getimagesize( '../upload/images/' . $data );
							$blob = 'data:image;base64,' . base64_encode( file_get_contents( '../upload/images/' . $data ) );
							$image = '<div class="Image"><img src="' . $blob . '"/></div>';
						}
					}
				}
			}
		}
		return '<div class="Article">' . $image . '<h2>' . $aquoterti->Title . '</h2><p>' . $quote->Leadin . '</p></div>';
	}
	
	/* Menu items */
	
	function renderMenu( $parent = 0, $pages = false, $depth = 0 )
	{
		global $DB;
		
		if( $depth > 1 ) return '';
		
		$str = '';
		if( !$pages )
		{
			$pages = [];
			if( !( $r = $DB->query( 'SELECT * FROM `Pages` p WHERE p.Type IS NOT NULL AND p.Published=1 ORDER BY p.Priority ASC' ) ) )
				return '';
			
			while( $row = $r->fetch_object() )
			{
				if( $row->Type == 'category' ) continue;
				$pages[] = $row;
			}
		}
		$a = 0;
		foreach( $pages as $page )
		{
			if( $page->Parent == $parent )
			{
				$sn = $this->safeUrl( $page->MenuName );
				$ac = '';
				if( isset( $this->page->MenuName ) && $sn == $this->page->MenuName )
					$ac = ' active';
				$link = '/' . $sn . '/index.html';
				if( $a == 0 && $parent == 0 )
				{
					$link = '/';
				}
				$str .= '<li class="' . $sn . $ac . '"><a href="' . $link . '">' . $page->Name . '</a>' . $this->renderMenu( $page->ID, $pages, $depth + 1 ) . '</li>';
				$a++;
			}
		}
		return '<ul>' . $str . '</ul>';
	}
	function safeUrl( $menuName )
	{
		return str_replace( ' ', '_', preg_replace( array( '/å|æ|ø|Å|Æ|Ø/', '/\?|\.|\:/', '/![a-z0-9]/' ), array( 'a', '', '_' ), strtolower( $menuName ) ) );
	}
	
}

?>
