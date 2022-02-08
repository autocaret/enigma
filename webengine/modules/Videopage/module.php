<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

global $DB;

// Prevent default page behavior
$GLOBALS[ 'prevent_default' ] = true;

$output .= '
    <hr/>
';

// Fetch video
if( preg_match( '/.*?\/([0-9]*?)\/index\.html/', $_SERVER[ 'REQUEST_URI' ], $matches ) )
{
    
    if( $r = $DB->query( 'SELECT t.ID, t.Name, t.DateUpdated, t.DateCreated, t.Text FROM Pages p, TextBlock t WHERE t.Parent = p.ID AND p.Name="Saklig" AND t.ID = \'' . intval( $matches[1], 10 ) . '\'' ) )
    {
        if( $row = $r->fetch_object() )
        {
            $text = $row->Text;
	        $image = '';
	        if( preg_match( '/<figure.*?\>(.*?)\<\/figure\>/i', $text, $matches ) )
	        {
	            $image = $matches[1];
	            $text = str_replace( $matches[0], '', $text );
	            if( preg_match( '/src=\"(.*?)\"/i', $image, $matches ) )
	            {
	                $image = $matches[1];
	            }
	        }
	        $rumble = '';
	        // Remove rumble link
            if( preg_match( '/https\:\/\/rumble\.com\/embed\/(.*?)\/\?pub\=4/i', $text, $matches ) )
            {
                $text = str_replace( $matches[0], '', $text );
                $rumble = $matches[0];
            }
            
            $output .= '
            <h1>
                ' . $row->Name . '
            </h1>
            <p class="Date">Posted ' . date( 'd/m/Y', strtotime( $row->DateCreated ) ) . '</p>
            <iframe class="Video" src="' . $rumble . '"></iframe>
            <p>' . $text . '</p><br><br><br>
            ';
            return;
        }
    }
}


function nameToLink( $nam )
{
    $nam = strtolower( $nam );
    $nam = str_replace(
        array( 'ø', 'æ', 'å', ' ' ),
        array( 'oe', 'ae', 'aa', '_' ),
        $nam
    );
    return $nam;
}

$str = '<h2>Videos</h2><div class="Videos">';

if( $r = $DB->query( 'SELECT t.ID, t.Name, t.DateUpdated, t.DateCreated, t.Text FROM Pages p, TextBlock t WHERE t.Parent = p.ID AND p.Name="Videcategory"' ) )
{
	while( $row = $r->fetch_object() )
	{
	    $text = $row->Text;
	    $image = '';
	    if( preg_match( '/<figure.*?\>(.*?)\<\/figure\>/i', $text, $matches ) )
	    {
	        $image = $matches[1];
	        $text = str_replace( $matches[0], '', $text );
	        if( preg_match( '/src=\"(.*?)\"/i', $image, $matches ) )
	        {
	            $image = $matches[1];
	        }
	    }
	    // Remove rumble link
        if( preg_match( '/https\:\/\/rumble\.com\/embed\/(.*?)\/\?pub\=4/i', $text, $matches ) )
        {
            $text = str_replace( $matches[0], '', $text );
        }
	
	    $link = '/saklig/' . nameToLink( $row->Name ) . '/' . $row->ID . '/index.html';
	
		$str .= '
		<div class="Video">
		    <p class="Image" onclick="document.location.href=\'' . $link . '\';" style="background-image: url(\'' . $image . '\')">
		    </p>
		    <p>
		        <a href="' . $link . '">' . $row->Name . ', ' . date( 'd/m/Y', strtotime( $row->DateCreated ) ) . '</a>
		    </p>
		    <p>' . str_replace( array( '<p>', '</p>' ), '', $text ) . '</p>
		</div>';
	}
}

$str .= '</div>';

$output .= $str;

?>
