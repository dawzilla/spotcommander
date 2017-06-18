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

$activity = array();
$activity['project_version'] = project_version;
$activity['title'] = 'Devices';

if(isset($_GET['transfer_device']))
{
	remote_control('pause', '');

	usleep(250000);

	$deviceid = $_POST['deviceid'];

	get_external_files(array('https://api.spotify.com/v1/me/player'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token(), 'Content-Type: application/json'), array('PUT', json_encode(array('device_ids' => array($deviceid), 'play' => true))));
}
elseif(!is_spotify_subscription_premium())
{
	$activity['actions'][] = array('action' => array('Authorize with Spotify', 'lock_open_white_24_img_div'), 'keys' => array('actions'), 'values' => array('confirm_authorize_with_spotify'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>This is a Spotify Premium feature. Tap the top right icon to authorize if you have Spotify Premium.</div></div>

		</div>
	';
}
else
{
	$files = get_external_files(array('https://api.spotify.com/v1/me/player/devices'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);
	$metadata = json_decode($files[0], true);
	$devices = $metadata['devices'];

	if(empty($devices))
	{
		$activity['actions'][] = array('action' => array('Refresh', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>No active devices. Tap the top right icon to refresh.</div></div>

			</div>
		';
	}
	else
	{
		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="list_header_div"><div>Devices</div><div></div></div>

			<div class="list_div">
		';

		foreach($devices as $device)
		{
			$name = $device['name'];
			$active = ($device['is_active']) ? 'Active Device' : 'Available Device';
			$id = $device['id'];

			$img_class = 'headphones_grey_24_img_div';
			$text_class = '';

			if($device['is_active'])
			{
				$img_class = 'volume_up_grey_24_img_div';
				$text_class = 'bold_text';
			}

			echo '
				<div class="list_item_div">
				<div title="' . hsc($name) . '" class="list_item_main_div actions_div" data-actions="transfer_device" data-devicename="' . hsc($name) . '" data-deviceid="' . $id . '" data-highlightclass="light_grey_highlight" onclick="void(0)">
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_circle_div"><div class="' . $img_class . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div ' . $text_class . '">' . hsc($name) . '</div><div class="list_item_main_inner_text_lower_div">' . $active . '</div></div>
				</div>
				</div>
				</div>
			';
		}

		echo '</div></div>';
	}
}

?>