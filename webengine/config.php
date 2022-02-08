<?php

$Database = new stdClass();
$Database->User = 'sqlusername';
$Database->Pass = 'sqlpass';
$Database->Db   = 'sqldatabase';
$Database->Host = 'localhost';
$Database->Port = 3360;

$Config = new stdClass();
$Config->Upload = __DIR__ . '/upload';


$Config->PageDescription = "Website description";
$Config->WebUrl = 'website.com';                    // Main website url
$Config->WebAlias = 'website.org';                  // Alias (other site)
$Config->UploadUrl = 'upload.website.org';          // Upload subdomain

?>
