<?php

/*

Copyright 2017 Ole Jon Bjørkum

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see http://www.gnu.org/licenses/.

*/

require_once('main.php');

$code = (isset($_GET['code'])) ? intval($_GET['code']) : 0;
$message = 'Unknown error.';
$help_uri = project_website . '?wiki';
$issues_uri = project_website . '?issues';

if($code == 1)
{
	$message = 'There is something wrong with your config.php file. Start over with a fresh config.php, and make sure that you read the text for each option carefully.';
}
elseif($code == 2)
{
	$message = 'Can not connect to the daemon. Tap Help below to find out how to start it.';
	$help_uri = project_website . '?start_daemon';
}
elseif($code == 3)
{
	$message = 'Files and/or folders that should be writeable can not be written to. Follow the installation instructions carefully.';
	$help_uri = project_website . '?install';
}
elseif($code == 4)
{
	$message = 'QDBus can not be found. Please report an issue.';
}
elseif($code == 5)
{
	$message = 'You must enable cookies in your browser.';
}
elseif($code == 6)
{
	$message = 'You must enable JavaScript in your browser.';
}

$error_code = $code;
$system_information = get_system_information();

get_external_files(array(project_website . 'api/1/error/?version=' . rawurlencode(project_version) . '&error_code=' . rawurlencode($error_code) . '&uname=' . rawurlencode($system_information['uname']) . '&ua=' . rawurlencode($system_information['ua'])), null, null);

?>

<!DOCTYPE html>

<html>

<head>

<title><?php echo project_name; ?></title>

<meta http-equiv="content-type" content="text/html; charset=utf-8">

<meta name="viewport" content="user-scalable=no, initial-scale=1.0">

<link rel="shortcut icon" href="img/favicon.ico?<?php echo project_serial ?>">

<link rel="stylesheet" href="css/style-fonts.css?<?php echo project_serial; ?>">
<link rel="stylesheet" href="css/style-images.css?<?php echo project_serial; ?>">
<link rel="stylesheet" href="css/style-error.css?<?php echo project_serial ?>">

</head>

<body>

<div id="background_div"></div>

<div id="card_div">

<div id="card_title_div">Oops!</div>

<div id="card_body_div"><?php echo $message; ?><br><br>When the problem is fixed, tap Retry below.</div>

<div id="card_buttons_div">
<a id="first_a" href="<?php echo $help_uri; ?>" target="_blank">HELP</a>
<a href="<?php echo $issues_uri; ?>" target="_blank">REPORT</a>
<a id="highlighted_a" href="." onclick="window.location.replace('.'); event.preventDefault()">RETRY</a>
<div id="clear_div"></div>
</div>

</div>

</body>

</html>