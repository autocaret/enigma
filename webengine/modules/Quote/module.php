<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

global $DB;

if( isset( $author ) )
{
	$author = str_replace( '_', ' ', $author );
	
	// TODO: Refactor to do this intelligently!
	if( $r = $DB->query( 'SELECT * FROM Articles a WHERE a.Author = \'' . $DB->real_escape_string( $author ) . '\' AND a.Type=\'quote\' ORDER BY a.Date DESC' ) )
	{
		$str = '';
		while( $row = $r->fetch_object() )
		{
			$tags = explode( ',', $row->Title );
			$tags_out = '';
			if( count( $tags ) )
			{
				$i = 0;
				foreach( $tags as $tag )
				{
					if( $i > 0 ) $tags_out .= ', ';
					$tags_out .= '<a href="/?tag=' . $tag . '">' . $tag . '</a>';
					$i++;
				}
			}
			
			$linka = 'Quote/' . $row->ID . '/' . preg_replace( array( '[\?\!\.]', '/[^A-Z0-9a-z]/' ), array( '', '_' ), substr( $row->Leadin, 0, 40 ) ) . '.html';
			$date = date( 'd/m/Y - \k\l\. H:i \C\E\T', strtotime( $row->Date ) );
			$nick = str_replace( ' ', '_', $row->Author );
			$str .= '<div class="QuoteRow">
				<div class="User">
					<div class="UserImage ' . $nick . '"></div>
					<div class="Share">
						<input type="text" class="Clip" value="' . $linka . '"/>
						<a href="javascript:void(0)" onclick="cpLink(this)" class="mdi mdi-paperclip">Del Quoteen</a>
					</div>
					<div>' . $row->Author . '</div>
					<div class="Checkmark"></div>
					<div class="Nickname"><a href="/Quote/' . $nick . '/">@' . $nick . '</a></div>
					<div class="Date">' . $date . '</div>
				</div>
				<div class="Leadin">
					' . $row->Leadin . '
				</div>
				<div class="Tags">' . $tags_out . '</div>
			</div>';
		}
		$output .= $str;
	}
}
if( isset( $object->Parent ) )
{
	if( $r = $DB->query( 'SELECT * FROM Articles a WHERE a.Type=\'quote\' AND a.Parent=\'' . $object->Parent . '\' ORDER BY a.Date DESC' ) )
	{
		$str = '';
		while( $row = $r->fetch_object() )
		{
			$tags = explode( ',', $row->Title );
			$tags_out = '';
			if( count( $tags ) )
			{
				$i = 0;
				foreach( $tags as $tag )
				{
					if( $i > 0 ) $tags_out .= ', ';
					$tags_out .= '<a href="/?tag=' . $tag . '">' . $tag . '</a>';
					$i++;
				}
			}
			
			$linka = 'Quote/' . $row->ID . '/' . preg_replace( array( '[\?\!\.]', '/[^A-Z0-9a-z]/' ), array( '', '_' ), substr( $row->Leadin, 0, 40 ) ) . '.html';
			$date = date( 'd/m/Y - \k\l\. H:i \C\E\T', strtotime( $row->Date ) );
			$nick = str_replace( ' ', '_', $row->Author );
			$str .= '<div class="QuoteRow">
				<div class="User">
					<div class="UserImage ' . $nick . '"></div>
					<div class="Share">
						<input type="text" class="Clip" value="' . $linka . '"/>
						<a href="javascript:void(0)" onclick="cpLink(this)" class="mdi mdi-paperclip">Del Quoteen</a>
					</div>
					<div>' . $row->Author . '</div>
					<div class="Checkmark"></div>
					<div class="Nickname"><a href="/Quote/' . $nick . '/">@' . $nick . '</a></div>
					<div class="Date">' . $date . '</div>
				</div>
				<div class="Leadin">
					' . $row->Leadin . '
				</div>
				<div class="Tags">' . $tags_out . '</div>
			</div>';
		}
		$output .= $str;
	}
}

?>
