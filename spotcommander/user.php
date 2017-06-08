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

$user = get_user(rawurlencode($_GET['username']));

$activity = array();
$activity['project_version'] = project_version;

if(empty($user))
{
	$activity['title'] = 'Error';
	$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get user. Try again.</div></div>

		</div>
	';
}
else
{
	$profile = $user['profile'];
	$playlists = $user['playlists'];

	$activity['title'] = hsc($profile['name']);

	$user_uri = 'spotify:user:' . rawurlencode($profile['username']);

	$is_saved = is_saved($user_uri);

	$library_action = ($is_saved) ? 'Remove from Library' : 'Save to Library';
	$library_icon = ($is_saved) ? 'check_white_24_img_div' : 'plus_white_24_img_div';

	$activity['actions'][] = array('action' => array($library_action, $library_icon), 'keys' => array('actions', 'artist', 'title', 'uri'), 'values' => array('save', rawurlencode($profile['username']), rawurlencode($profile['name']), $user_uri));

	$style = (empty($profile['image'])) ? '' : 'background-size: cover; background-image: url(\'' . $profile['image'] . '\')';
	$text = ($profile['followers'] == 1) ? 'follower' : 'followers';

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="user_div">
		<div>
		<div class="person_grey_72_img_div" style="' . $style . '"></div>
		</div>
		<div>
		<div>' . hsc($profile['name']) . '</div>
		<div>' . is_facebook_user($profile['username']) . ' / ' . $profile['followers'] . ' ' . $text . '</div>
		</div>
		</div>

		<div class="cards_vertical_title_div">Public Playlists</div>
	';

	if(empty($playlists))
	{
		echo '<div class="cards_vertical_empty_div">No public playlists.</div>';
	}
	else
	{
		echo '<div class="cards_vertical_div">';

		$cover_art_cache = get_cover_art_cache('medium');

		foreach($playlists as $playlist)
		{
			$name = $playlist['name'];
			$uri = $playlist['uri'];
			$user = is_facebook_user(get_playlist_user($uri));

			$style = (empty($cover_art_cache[$uri])) ? 'color: initial' : 'background-image: url(\'' . $cover_art_cache[$uri] . '\')';

			echo '<div class="card_vertical_div"><div title="' . hsc($name) . '" class="card_vertical_inner_div actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="' . rawurlencode($name) . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" data-coverarturi="' . $uri . '" style="' . $style . '"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">' . $user . '</div></div></div>';
		}

		echo '<div class="clear_float_div"></div></div>';
	}

	echo '</div>';
}

?>