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

$artist = rawurldecode($_GET['artist']);
$title = rawurldecode($_GET['title']);

$files = get_external_files(array(project_website . 'api/1/lyrics/?artist=' . rawurlencode($artist) . '&title=' . rawurlencode($title)), null, null);
$lyrics = json_decode($files[0], true);

$activity = array();
$activity['project_version'] = project_version;
$activity['title'] = hsc($title);

if(empty($lyrics['lyrics']))
{
	$activity['actions'][] = array('action' => array('Search the Web', 'globe_white_24_img_div'), 'keys' => array('actions', 'uri'), 'values' => array('open_external_activity', 'https://www.google.com/search?q=' . rawurlencode($artist . ' ' . $title . ' lyrics')));
	$content = '<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>No match</div></div>';
}
else
{
	$lyrics = str_replace("\n", '<br>', $lyrics['lyrics']);
	$content = '<div id="lyrics_div">' . $lyrics . '</div>';
}

echo '<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">' . $content . '</div>';

?>