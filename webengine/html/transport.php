<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

require_once( '../config.php' );
require_once( '../api/apicore.php' );

// Initialize database
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$DB = new mysqli( $Database->Host, $Database->User, $Database->Pass, $Database->Db, $Database->Port )
	or die( 'What happened?' );
register_shutdown_function( function()
{
	global $DB;
	$DB->close();
} );

// Initialize
if( isset( $_REQUEST[ 'apimode' ] ) )
{
	require( '../api/js.php' );
	die();
}
// Receive request queries
else
{
	$core = new ApiCore();
	if( isset( $_REQUEST[ 'data' ] ) )
		$data = json_decode( $_REQUEST[ 'data' ] );
	if( method_exists( $core, $_REQUEST[ 'query' ] ) )
	{
		die( $core->{$_REQUEST[ 'query' ]}( $data ) );
	}
	die( 'fail<!--separate-->' );
}

// 
die( 'ok<!--separate-->....' );

?>
