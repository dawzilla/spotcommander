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

$browse_uri = $_GET['uri'];
$browse_uri_type = get_uri_type($browse_uri);
$album_uri = ($browse_uri_type == 'track') ? get_track_album($browse_uri) : $browse_uri;

$metadata = (empty($album_uri)) ? null : get_album($album_uri);

$activity = array();
$activity['project_version'] = project_version;

if(empty($metadata))
{
	$activity['title'] = 'Error';
	$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get album. Try again.</div></div>

		</div>
	';
}
else
{
	$artist = $metadata['artist'];
	$artist_uri = $metadata['artist_uri'];
	$title = $metadata['title'];
	$type = $metadata['type'];
	$discs = $metadata['discs'];
	$released = $metadata['released'];
	$popularity = $metadata['popularity'];
	$cover_art_uri = $metadata['cover_art_uri'];
	$cover_art_width = $metadata['cover_art_width'];
	$cover_art_height = $metadata['cover_art_height'];
	$tracks_count = $metadata['tracks_count'];
	$total_length = $metadata['total_length'];
	
	$details_dialog = array();
	$details_dialog['title'] = hsc($title);
	$details_dialog['details'][] = array('detail' => 'Type', 'value' => $type);
	$details_dialog['details'][] = array('detail' => 'Released', 'value' => $released);
	$details_dialog['details'][] = array('detail' => 'Popularity', 'value' => $popularity);
	$details_dialog['details'][] = array('detail' => 'Total Length', 'value' => $total_length);

	$library_action = (is_saved($album_uri)) ? 'Remove from Library' : 'Save to Library';

	$activity['title'] = $title;
	$activity['actions'][] = array('action' => array($library_action, ''), 'keys' => array('actions', 'artist', 'title', 'uri'), 'values' => array('save', rawurlencode($artist), rawurlencode($title), $album_uri));
	$activity['actions'][] = array('action' => array('Add to Playlist', ''), 'keys' => array('actions', 'title', 'uri'), 'values' => array('add_to_playlist', $title, $album_uri));
	$activity['actions'][] = array('action' => array('Go to Artist', ''), 'keys' => array('actions', 'uri'), 'values' => array('browse_artist', $artist_uri));
	$activity['actions'][] = array('action' => array('Search Artist', ''), 'keys' => array('actions', 'string'), 'values' => array('get_search', rawurlencode('artist:"' . $artist . '"')));
	$activity['actions'][] = array('action' => array('Details', ''), 'keys' => array('actions', 'dialogdetails'), 'values' => array('show_details_dialog', base64_encode(json_encode($details_dialog))));
	$activity['actions'][] = array('action' => array('Share', ''), 'keys' => array('actions', 'title', 'uri'), 'values' => array('share_uri', hsc($title), rawurlencode(uri_to_url($album_uri))));

	$tracks_count = ($tracks_count == 1) ? $tracks_count . ' track' : $tracks_count . ' tracks';

	echo '
		<div id="cover_art_div">
		<div id="cover_art_art_div" class="actions_div" data-actions="resize_cover_art" data-imageuri="' . $cover_art_uri . '" data-resized="false" data-width="' . $cover_art_width . '" data-height="' . $cover_art_height . '" style="background-image: url(\'' . $cover_art_uri . '\')" onclick="void(0)"></div>
		<div id="cover_art_information_div" class="shadow_up_black_48_img_div"><div>' . $tracks_count . ' / Album by ' . $artist . '</div></div>
		<div title="Play" id="cover_art_fab_div" class="actions_div play_white_24_img_div" data-actions="show_cover_art_fab_animation play_uri" data-uri="' . $album_uri . '" data-highlightclass="light_green_highlight" onclick="void(0)"></div>
		</div>

		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">
	';

	$i = 0;

	foreach($discs as $disc)
	{
		$tracks = $disc;
		$i++;

		$queue_uris = array();
		$n = 0;

		foreach($tracks as $track)
		{
			$queue_uris[$n] = $track['uri'];

			$n++;
		}

		$queue_uris = base64_encode(json_encode($queue_uris));

		echo '
			<div class="list_header_div list_header_below_cover_art_div"><div>Disc ' . $i . '</div><div></div></div>

			<div class="list_div">
		';

		foreach($tracks as $track)
		{
			$artist = $track['artist'];
			$artist_uri = $track['artist_uri'];
			$title = $track['title'];
			$disc_number = $track['disc_number'];
			$track_number = $track['track_number'];
			$length = $track['length'];
			$uri = $track['uri'];

			$details_dialog = array();
			$details_dialog['title'] = hsc($title);
			$details_dialog['details'][] = array('detail' => 'Disc Number', 'value' => $disc_number);
			$details_dialog['details'][] = array('detail' => 'Track Number', 'value' => $track_number);
			$details_dialog['details'][] = array('detail' => 'Length', 'value' => $length);

			$actions_dialog = array();
			$actions_dialog['title'] = hsc($title);
			$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
			$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
			$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog get_recommendations', $uri));
			$actions_dialog['actions'][] = array('text' => 'Lyrics', 'keys' => array('actions', 'artist', 'title'), 'values' => array('hide_dialog get_lyrics', rawurlencode($artist), rawurlencode($title)));
			$actions_dialog['actions'][] = array('text' => 'Details', 'keys' => array('actions', 'dialogdetails'), 'values' => array('hide_dialog show_details_dialog', base64_encode(json_encode($details_dialog))));
			$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', hsc($title), rawurlencode(uri_to_url($uri))));

			echo '
				<div class="list_item_div">
				<div title="' . hsc($artist . ' - ' . $title) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" data-trackuri="' . $uri . '" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_icon_div"><div class="img_div img_24_div unfold_more_grey_24_img_div ' . track_is_playing($uri, 'icon') . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div ' . track_is_playing($uri, 'text') . '">' . hsc($title) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($artist) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Play" class="actions_div" data-actions="play_uris" data-uri="' . $uri . '" data-uris="' . $queue_uris . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
				<div title="Queue" class="actions_div" data-actions="queue_uri" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div queue_grey_24_img_div"></div></div>
				<div title="Save to/Remove from Library" class="actions_div" data-actions="save" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div ' . save_remove_icon($uri) . '_grey_24_img_div"></div></div>
				<div title="Go to Artist" class="actions_div" data-actions="browse_artist" data-uri="' . $artist_uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
				<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}

		echo '</div>';
	}

	echo '</div>';
}

?>