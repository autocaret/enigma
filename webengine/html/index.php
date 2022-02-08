<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

require_once( '../config.php' );
require_once( '../classes/template.php' );

// Initialize database
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$DB = new mysqli( $Database->Host, $Database->User, $Database->Pass, $Database->Db, $Database->Port )
	or die( 'What happened?' );
register_shutdown_function( function()
{
	global $DB;
	$DB->close();
} );

// Set up design template
$t = new Template();

$out = $t->render();

$out = str_replace( '//' . $Config->WebAlias . '/', '//' . $Config->WebUrl . '/', $out );
$out = str_replace( '//' . $Config->UploadUrl . '/', '//' . $Config->WebUrl . '/', $out );
$out = str_replace( '//www.' . $Config->WebUrl . '/', '//' . $Config->WebUrl . '/', $out );

// Still exists, remove
$out = str_replace( '{Archive}', '', $out );

// Render HTML
die( $out );

?>
