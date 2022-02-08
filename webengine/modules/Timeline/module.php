<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

global $DB;

if( isset( $data->call ) )
{
	if( $data->call == 'getdata' )
	{
		$objs = [];
	
		if( $r = $DB->query( 'SELECT t.Name, t.DateUpdated, t.DateCreated, t.Text FROM Pages p, TextBlock t WHERE t.Parent = p.ID AND p.Name="Tidslinje"' ) )
		{
			while( $row = $r->fetch_object() )
			{
				$objs[] = $row;
			}
		}
	
		$output = 'ok<!--separate-->' . json_encode( $objs );
	}
	else
	{
		$output = 'fail<!--separate-->';
	}
}
else
{
	$output .= '<div id="TimelineEventBox"></div><div id="Timeline">Generating...</div><script>' . file_get_contents( __DIR__ . '/timeline.js' ) . '</script>';
}

?>
