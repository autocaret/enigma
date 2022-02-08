<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

define( 'SECRET_KEY', 'xxxkeyxxx' );

class ApiCore
{
	/* Public */

	public function __construct__()
	{
	}
	
	public function refresh( $data )
	{
		global $DB;
		
		$output = [];
		
		$parent = intval( $data->parent, 10 );
		
		if( $r = $DB->query( '
			SELECT * FROM
			(
				SELECT * FROM 
				(
					SELECT ID, "Article" AS `Type`, Author, `Title`, "" AS Extra, Leadin, `Date` AS DateCreated FROM Articles a WHERE Parent=\'' . $parent . '\' ORDER BY a.Date DESC
				) k
				UNION
				(
					SELECT ID, "TextBlock" AS `Type`, "System", `Name` AS `Title`, "" AS Extra, Text AS `Leadin`, DateUpdated FROM TextBlock t WHERE Parent=\'' . $parent . '\' ORDER BY t.Priority DESC 
				)
				UNION
				(
					SELECT ID, "Image" AS `Type`, "System", `Filename` AS Title, `Url` AS Extra, "" AS `Leadin`, DateCreate AS DateCreated FROM Images y WHERE Parent=\'' . $parent . '\' ORDER BY DateCreated DESC
				)
			) z
			ORDER BY z.DateCreated DESC
		' ) )
		{
			while( $row = $r->fetch_object() )
			{
				$output[] = $row;
			}
		}
		else
		{
			die( 'fail<!--separate-->' );
		}
		return 'ok<!--separate-->' . json_encode( $output );
	}
	
	public function getimage( $data )
	{
		global $DB, $Config;
		
		if( file_exists( $Config->Upload . '/' . $data->hash ) )
		{
			$d = getimagesize( $Config->Upload . '/' . $data->hash );
			if( $d[2] == IMAGETYPE_GIF )
				$t = 'image/gif';
			else if( $d[2] == IMAGETYPE_JPEG )
				$t = 'image/jpeg';
			else if( $d[2] == IMAGETYPE_PNG )
				$t = 'image/png';
			header( 'Content-type: ' . $t );
			die( file_get_contents( $Config->Upload . '/' . $data->hash ) );
		}
		die();
	}
	
	// Will use an API key later
	private function test_security()
	{
		// Idiot security for now
		if( $_REQUEST[ 'secret' ] != SECRET_KEY )
		{
			die( '404' );
		}
	}
	
	public function deleteimage( $data )
	{
		global $DB, $Config;
		
		$this->test_security();
		
		// Idiot security for now
		if( $_REQUEST[ 'secret' ] != SECRET_KEY )
		{
			die( '404' );
		}
		
		if( $r = $DB->query( 'SELECT * FROM Images WHERE ID=\'' . intval( $data->imageId, 10 ) . '\'' ) )
		{
			if( $row = $r->fetch_object() )
			{
				$hash = explode( ':', $row->Url );
				if( file_exists( $Config->Upload . '/' . $hash[1] ) )
				{
					unlink( $Config->Upload . '/' . $hash[1] );
					if( $DB->query( 'DELETE FROM Images WHERE ID=\'' . intval( $data->imageId, 10 ) . '\'' ) )
					{
						die( 'ok<!--separate-->' );
					}
				}
			}
		}
		die( 'fail' );
	}
	
	/*
		Imports a file with external url into the image database
	*/
	public function importfile( $data )
	{
		global $DB, $Config;
		
		$this->test_security();
		
		$url = $data->url;
		
		// We need this folder
		if( !file_exists( '/tmp/enigma' ) )
		{
			if( !mkdir( '/tmp/enigma' ) )
			{
				die( 'fail<!--separate-->{"response",-1,"message":"Could not create temporary directory."}' );
			}
			if( !file_exists( '/tmp/enigma' ) )
			{
				die( 'fail<!--separate-->{"response",-1,"message":"Failed to make directory."}' );
			}
		}
				
		// Create unique temp name
		$fname = $DB->real_escape_string( $data->filename );
		while( file_exists( '/tmp/enigma/' . $fname ) )
		{
			$fname = $data->filename . rand( 0, 9999 ) . rand( 0, 9999 );
		}
		
		$response = new stdClass();
		
		// Load file
		$opts = array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		$raw = file_get_contents( $data->url, false, stream_context_create( $opts ) );
		if( $fp = fopen( '/tmp/enigma/' . $fname, 'w+' ) )
		{
			fwrite( $fp, $raw );
			fclose( $fp );
			
			$extension = explode( '.', $data->filename );
			$extension = end( $extension );
			$extension = strtolower( $extension );
			$extension = $DB->real_escape_string( $extension );
			$filename = $DB->real_escape_string( $data->filename );
			
			$filesize = filesize( '/tmp/enigma/' . $fname );
			
			if( copy( '/tmp/enigma/' . $fname, $Config->Upload . '/' . $data->hash ) )
			{
				// Wee!
				unlink( '/tmp/enigma/' . $fname );
				
				if( $DB->query( 'INSERT INTO Images ( `Parent`, `Url`, `Filename`, `Extension`, `Filesize`, `DateCreate` ) VALUES ( \'' . intval( $data->parent, 10 ) . '\', \'Upload:' . $data->hash . '\', \'' . $filename . '\', \'' . $extension . '\', \'' . intval( $filesize, 10 ) . '\', NOW() )' ) )
				{
					$response->response = 1;
					$response->message = 'Successfully imported image.';
					
					die( 'ok<!--separate-->' . json_encode( $response ) );
				}
				
				$response->response = -1;
				$response->message = 'Could not insert image into database.';
				
				if( file_exists( $Config->Upload . '/' . $data->hash ) )
					unlink( $Config->Upload . '/' . $data->hash );
				
				die( 'fail<!--separate-->' . json_encode( $response ) );
			}
			else
			{
				// Clean up
				unlink( '/tmp/enigma/' . $fname );
			}
			
			$response->response = -1;
			$response->message = 'Could not move uploaded file.';
		}
		else
		{
			$response->response = -1;
			$response->message = 'Could not open target file for writing.';
		}
		
		die( 'fail<!--separate-->' . json_encode( $response ) );
	}
	
	public function savetextblock( $data )
	{
		global $DB;
		
		$this->test_security();
		
		$userId = intval( $data->userId, 10 );
		$name = $DB->real_escape_string( $data->name );
		$priority = intval( $data->priority, 10 );
		$text = $DB->real_escape_string( $data->text );
		$parent = $data->parent > 0 ? intval( $data->parent, 10 ) : '0';
		$date = $DB->real_escape_string( $data->date );
		
		if( !trim( $data->date ) )
		{
			$date = date( 'Y-m-d H:i:s' );
		}
		
		// Just update
		if( $data->articleId > 0 )
		{
			$data->articleId = intval( $data->articleId, 10 );
			if( $r = $DB->query( '
				UPDATE TextBlock 
					SET Priority=\'' . $priority .'\', Parent=\'' . $parent .'\', `Name`="' . $name . '", Text="' . $text . '", DateUpdated="' . $date . '"
				WHERE ID=\'' . $data->articleId . '\'
			' ) )
			{
				die( 'ok<!--separate-->' );
			}
		}
		// Create new
		else if( $r = $DB->query( 'INSERT INTO TextBlock ( 
			UserID, Priority, Parent, `Name`, `Text`, DateCreated, DateUpdated 
		) 
		VALUES ( 
			\'' . $userId . '\', \'' . $priority . '\', \'' . $parent . '\', "' . $name . '", "' . $text . '", NOW(), "' . $date . '" 
		)' ) )
		{
			die( 'ok<!--separate-->' );
		}
		
		die( 'fail' );
	}
	
	public function savequote( $data )
	{
		global $DB;
		
		$this->test_security();
		
		$userId = intval( $data->userId, 10 );
		$title = $DB->real_escape_string( $data->title );
		$leadin = $DB->real_escape_string( $data->text );
		$author = $DB->real_escape_string( $data->author );
		$parent = $data->parent > 0 ? intval( $data->parent, 10 ) : '0';
		
		// Just update
		if( $data->articleId > 0 )
		{
			$data->articleId = intval( $data->articleId, 10 );
			if( $r = $DB->query( 'UPDATE Articles SET Author="' . $author . '", Parent=\'' . $parent .'\', Title="' . $title . '", Leadin="' . $leadin . '", DateUpdated=NOW() WHERE ID=\'' . $data->articleId . '\'' ) )
			{
				die( 'ok<!--separate-->' );
			}
		}
		// Create new
		else if( $r = $DB->query( 'INSERT INTO Articles ( UserID, Author, Parent, Title, `Type`, Leadin, Article, Date, DateUpdated ) VALUES ( \'' .
			$userId . '\', "' . $author . '", \'' . $parent . '\', "' . $title . '", "quote", "' . $leadin . '", "", NOW(), NOW() )' ) )
		{
			die( 'ok<!--separate-->' );
		}
		
		die( 'fail' );
	}
	
	public function savearticle( $data )
	{
		global $DB;
		
		$this->test_security();
		
		$userId = intval( $data->userId, 10 );
		$title = $DB->real_escape_string( $data->title );
		$leadin = $DB->real_escape_string( $data->text );
		$article = $DB->real_escape_string( $data->article );
		$author = $DB->real_escape_string( $data->author );
		$parent = $data->parent > 0 ? intval( $data->parent, 10 ) : '0';
		
		// Just update
		if( $data->articleId && intval( $data->articleId, 10 ) > 0 )
		{
			$data->updateId = intval( $data->articleId, 10 );
			if( $r = $DB->query( 'UPDATE Articles SET Author="' . $author . '", Title="' . $title . '", Leadin="' . $leadin . '", Article="' . $article . '", DateUpdated=NOW(), Parent=\'' . $parent . '\' WHERE ID=\'' . $data->updateId . '\'' ) )
			{
				die( 'ok<!--separate-->' );
			}
		}
		// Create new
		else if( $r = $DB->query( 'INSERT INTO Articles ( UserID, Parent, Author, Title, Leadin, Article, Date, DateUpdated ) VALUES ( ' .
			$userId . ', \'' . $parent . '\', "' . $author . '", "' . $title . '", "' . $leadin . '", "' . $article . '", NOW(), NOW() )' ) )
		{
			die( 'ok<!--separate-->' );
		}
		
		die( 'fail' );
	}
	
	public function edittextblock( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->articleId ) && isset( $data->userId ) )
		{
			$result = $DB->query( $q = 'SELECT * FROM TextBlock WHERE ID=\'' . intval( $data->articleId, 10 ) . '\' LIMIT 1' );
			if( $result && ( $row = $result->fetch_object() ) )
			{
				die( 'ok<!--separate-->' . json_encode( $row ) );
			}
		}
		die( 'fail<!--separate-->' );
	}
	
	public function editarticle( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->articleId ) && isset( $data->userId ) )
		{
			$result = $DB->query( $q = 'SELECT * FROM Articles WHERE ID=\'' . intval( $data->articleId, 10 ) . '\' LIMIT 1' );
			if( $result && ( $row = $result->fetch_object() ) )
			{
				die( 'ok<!--separate-->' . json_encode( $row ) );
			}
		}
		die( 'fail<!--separate-->' );
	}
	
	public function deletearticle( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->articleid ) && isset( $data->userId ) )
		{
			// TODO: Add userID for security...
			if( $r = $DB->query( $q = 'DELETE FROM Articles WHERE ID=\'' . intval( $data->articleid, 10 ) . '\''  )) 
			{
				die( 'ok<!--separate-->' . $q );
			}
		}
		die( 'fail' );
	}
	
	public function deletetextblock( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->textblockid ) && isset( $data->userId ) )
		{
			// TODO: Add userID for security...
			if( $r = $DB->query( $q = 'DELETE FROM TextBlock WHERE ID=\'' . intval( $data->textblockid, 10 ) . '\''  )) 
			{
				die( 'ok<!--separate-->' . $q );
			}
		}
		die( 'fail' );
	}
	
	public function modulecall( $data )
	{
		global $DB;
		
		$data->module = str_replace( '.', '', $data->module );
		if( file_exists( '../modules/' . $data->module . '/module.php' ) )
		{
			$output = '';
			include_once( '../modules/' . $data->module . '/module.php' );
			die( $output );
		}
		die( 'fail' );
	}
	
	public function getpages( $data )
	{
		global $DB;
		
		// TODO: Perhaps security later
		
		if( $r = $DB->query( 'SELECT * FROM Pages ORDER BY Priority ASC' ) )
		{
			$out = [];
			while( $row = $r->fetch_object() )
			{
				$out[] = $row;
			}
			die( 'ok<!--separate-->' . json_encode( $out ) );
		}
		die( 'fail' );
	}
	
	public function savepage( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->pageId ) )
		{
			if( $r = $DB->query( 'SELECT * FROM Pages WHERE ID=\'' . intval( $data->pageId, 10 ) . '\'' ) )
			{
				$pageName     = $DB->real_escape_string( $data->name );
				$pageMenuName = $DB->real_escape_string( $data->menuname );
				$pagePriority = intval( $data->priority, 10 );
				$pageId       = intval( $data->pageId, 10 );
				$type         = $DB->real_escape_string( $data->type );
				$published    = intval( $data->published, 10 );
				
				$DB->query( 'UPDATE Pages 
					SET Name="' . $pageName . '", 
						MenuName="' . $pageMenuName . '", 
						Priority=\'' . $pagePriority . '\',
						`Type`="' . $type . '", 
						`Published`=\'' . $published . '\'
					WHERE ID=\'' . $pageId . '\'
				' );
				die( 'ok' );
			}
		}
		die( 'fail' );
	}
	
	public function addpage( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->pageId ) )
		{
			if( $r = $DB->query( 'SELECT * FROM Pages WHERE ID=\'' . intval( $data->pageId, 10 ) . '\'' ) )
			{
				// Put new page on the bottom
				$row = null;
				if( $par = $r->fetch_object() )
				{
					$b = $DB->query( 'SELECT MAX(Priority) MAXXI FROM Pages WHERE Parent=\'' . $par->ID . '\'' );
					if( $b ) $row = $b->fetch_object();
				}
				$priority = '0';
				if( $row ) $priority = $row->MAXXI + 1;
				$priority = intval( $priority, 10 );
				$type = '';
				
				$DB->query( 'INSERT INTO Pages ( Name, MenuName, Parent, Type, Priority, Published, Template ) VALUES ( "New subpage", "new_subpage", \'' . intval( $data->pageId, 10 ) . '\', "' . $type . '", \'' . $priority . '\', 0, "" )' );
				die( 'ok' );
			}
		}
		die( 'fail' );
	}
	
	public function deletepage( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->pageId ) )
		{
			// Just move to trash	
			if( $r = $DB->query( 'SELECT * FROM Pages WHERE ID=\'' . intval( $data->pageId, 10 ) . '\'' ) )
			{
				if( $page = $r->fetch_object() )
				{
					if( $r = $DB->query( 'UPDATE Pages SET Parent=-1, Published=0 WHERE ID=\'' . intval( $data->pageId, 10 ) . '\'' ) )
					{
						$response = new stdClass();
						$response->response = 1;
						$response->message = 'Successfully moved page to trash.';
						$response->pageId = $page->Parent;
						die( 'ok<!--separate-->' . json_encode( $response ) );
					}
				}
			}
		}
		die( 'fail' );
	}
	
	public function getpageproperties( $data )
	{
		global $DB;
		
		$this->test_security();
		
		if( isset( $data->pageId ) )
		{
			if( $r = $DB->query( 'SELECT * FROM Pages WHERE ID=\'' . intval( $data->pageId, 10 ) . '\'' ) )
			{
				if( $pag = $r->fetch_object() )
				{
					die( 'ok<!--separate-->' . json_encode( $pag ) );
				}
			}
		}
		die( 'fail' );
	}
	
	/* Private */
}

?>
