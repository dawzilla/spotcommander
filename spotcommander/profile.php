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
$activity['title'] = 'Profile';

if(isset($_GET['deauthorize_from_spotify']))
{
	echo deauthorize_from_spotify();
}
elseif(isset($_GET['spotify_token']))
{
	save_spotify_token($_GET['spotify_token']);

	if(get_authorized_with_spotify())
	{
		$activity['actions'][] = array('action' => array('Reload ' . project_name, 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_app'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Success! Tap the top right icon to reload ' . project_name . '.</div></div>

			</div>
		';
	}
	else
	{
		$activity['actions'][] = array('action' => array('Authorize with Spotify', 'lock_open_white_24_img_div'), 'keys' => array('actions'), 'values' => array('confirm_authorize_with_spotify'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not authorize with Spotify. Tap the top right icon to try again.</div></div>

			</div>
		';
	}
}
elseif(!get_authorized_with_spotify())
{
	$activity['actions'][] = array('action' => array('Authorize with Spotify', 'lock_open_white_24_img_div'), 'keys' => array('actions'), 'values' => array('confirm_authorize_with_spotify'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>You must authorize with Spotify. Tap the top right icon to continue.</div></div>

		</div>
	';
}
else
{
	$profile = get_profile();

	if(empty($profile))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get profile. Try again.</div></div>

			</div>
		';
	}
	else
	{
		$activity['actions'][] = array('action' => array('Deauthorize with Spotify', 'exit_white_24_img_div'), 'keys' => array('actions'), 'values' => array('confirm_deauthorize_from_spotify'));

		$style = (empty($profile['image'])) ? '' : 'background-size: cover; background-image: url(\'' . $profile['image'] . '\')';

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="profile_div">
			<div id="profile_image_div" class="person_grey_72_img_div" style="' . $style . '"></div>
			<div id="profile_details_div">
			<div>Name: ' . hsc($profile['name']) . '</div>
			<div>Username: ' . hsc($profile['username']) . '</div>
			<div>Country: ' . get_country_name($profile['country']) . '</div>
			<div>Subscription: ' . ucfirst($profile['subscription']) . '</div>
			<div>Followers: ' . $profile['followers'] . '</div>
			</div>
			</div>

			<div class="list_header_div"><div>Top Tracks</div><div></div></div>

			<div class="list_div">
		';

		if(empty($profile['top_tracks']))
		{
			echo '<div class="list_empty_div">No tracks.</div>';
		}
		else
		{
			$tracks = $profile['top_tracks'];

			$queue_uris = '';

			foreach($tracks as $track)
			{
				$queue_uris .= $track['uri'] . ' ';
			}

			$queue_uris = rtrim($queue_uris);

			foreach($tracks as $track)
			{
				$artist = get_artists($track['artists']);
				$artist_uri = $track['artists'][0]['uri'];
				$album_name = $track['album']['name'];
				$album_uri = $track['album']['uri'];
				$title = $track['name'];
				$uri = $track['uri'];
				$length = convert_length($track['duration_ms'], 'ms');
				$popularity = $track['popularity'];

				$details_dialog = array();
				$details_dialog['title'] = $title;
				$details_dialog['details'][] = array('detail' => 'Album', 'value' => $album_name);
				$details_dialog['details'][] = array('detail' => 'Length', 'value' => $length);
				$details_dialog['details'][] = array('detail' => 'Popularity', 'value' => $popularity . ' %');

				$actions_dialog = array();
				$actions_dialog['title'] = $title;
				$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
				$actions_dialog['actions'][] = array('text' => 'Go to Album', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_album', $album_uri));
				$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
				$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog get_recommendations', $uri));
				$actions_dialog['actions'][] = array('text' => 'Lyrics', 'keys' => array('actions', 'artist', 'title'), 'values' => array('hide_dialog get_lyrics', rawurlencode($artist), rawurlencode($title)));
				$actions_dialog['actions'][] = array('text' => 'Details', 'keys' => array('actions', 'dialogdetails'), 'values' => array('hide_dialog show_details_dialog', base64_encode(json_encode($details_dialog))));
				$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', $title, rawurlencode(uri_to_url($uri))));

				echo '
					<div class="list_item_div">
					<div title="' . hsc($artist . ' - ' . $title) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" data-trackuri="' . $uri . '" onclick="void(0)">
					<div class="list_item_main_actions_arrow_div"></div>
					<div class="list_item_main_inner_div">
					<div class="list_item_main_inner_icon_div"><div class="img_div img_24_div unfold_more_grey_24_img_div ' . track_playing($uri, 'icon') . '"></div></div>
					<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div ' . track_playing($uri, 'text') . '">' . hsc($title) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($artist) . '</div></div>
					</div>
					</div>
					<div class="list_item_actions_div">
					<div class="list_item_actions_inner_div">
					<div title="Play" class="actions_div" data-actions="play_uris" data-uri="' . $uri . '" data-queueuris="' . $queue_uris . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
					<div title="Queue" class="actions_div" data-actions="queue_uri" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div queue_grey_24_img_div"></div></div>
					<div title="Save to/Remove from Library" class="actions_div" data-actions="save" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div ' . save_remove_icon($uri) . '_grey_24_img_div"></div></div>
					<div title="Go to Artist" class="actions_div" data-actions="browse_artist" data-uri="' . $artist_uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
					<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
					</div>
					</div>
					</div>
				';
			}
		}

		echo '</div></div>';
	}
}

?>
