<?php

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at https://mozilla.org/MPL/2.0/.
 */

global $DB;

session_start();

if( !isset( $_REQUEST[ 'submit' ] ) )
{
	$output = file_get_contents( __DIR__ . '/form.html' );
	$output = str_replace( '{code}', md5( rand( 0, 99999 ) . mktime() ), $output );
}
// Form processing
else
{
	if( isset( $_SESSION[ 'form_security' ] ) && $_SESSION[ 'form_security' ] == $_REQUEST[ 'Code' ] )
	{
		$output = '<hr/><h2>Double post</h2><p>You can only send once.</p>';
	}
	else
	{
		$person = isset($_REQUEST[ 'Name' ] ) ? $DB->real_escape_string( $_REQUEST[ 'Name' ] ) : '';
		$mobile = isset( $_REQUEST[ 'Mobile' ] ) ? $DB->real_escape_string( $_REQUEST[ 'Mobile' ] ) : '';
		$email = isset( $_REQUEST[ 'Email' ] ) ? $DB->real_escape_string( $_REQUEST[ 'Email' ] ) : '';
		$message = isset( $_REQUEST[ 'Message' ] ) ? $DB->real_escape_string( $_POST[ 'Message' ] ) : '';
		if( $r = $DB->query( 'INSERT INTO ContactMessages( Person, Email, Mobile, Message, Date, Seen ) VALUES ( "' . $person . '", "' . $email . '", "' . $mobile . '", "' . $message . '", NOW(), 0 )' ) )
		{
			$output = '<hr/><h2>Message sent</h2><p>Thank you for your message!</p>';
		}
		else
		{
			$output = '<hr/><h2>An error occured</h2><p>Sorry, the message was not sent!</p>';
		}
		$_SESSION[ 'form_security' ] = $_REQUEST[ 'Code' ];
	}
}

?>
