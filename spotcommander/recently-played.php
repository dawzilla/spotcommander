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

if(isset($_GET['clear']))
{
	echo clear_recently_played();
}
else
{
	$activity = array();
	$activity['project_version'] = project_version;
	$activity['title'] = 'Recently Played';
	$activity['actions'][] = array('action' => array('Clear', 'trash_white_24_img_div'), 'keys' => array('actions'), 'values' => array('clear_recently_played'));

	$tracks = get_db_rows('recently-played', "SELECT artist, title, uri FROM recently_played ORDER BY id DESC", array('artist', 'title', 'uri'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div class="list_header_div"><div>Tracks</div><div></div></div>

		<div class="list_div">
	';

	if(empty($tracks))
	{
		echo '<div class="list_empty_div">No tracks.</div>';
	}
	else
	{
		foreach($tracks as $track)
		{
			$artist = $track['artist'];
			$title = $track['title'];
			$uri = $track['uri'];

			$actions_dialog = array();
			$actions_dialog['title'] = hsc($title);
			$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri', 'isauthorizedwithspotify'), 'values' => array('hide_dialog add_to_playlist', $title, $uri, is_authorized_with_spotify));
			$actions_dialog['actions'][] = array('text' => 'Go to Album', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_album', $uri));
			$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
			$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri', 'isauthorizedwithspotify'), 'values' => array('hide_dialog get_recommendations', $uri, is_authorized_with_spotify));
			$actions_dialog['actions'][] = array('text' => 'Start Track Radio', 'keys' => array('actions', 'uri', 'playfirst'), 'values' => array('hide_dialog start_track_radio', $uri, 'true'));
			$actions_dialog['actions'][] = array('text' => 'Lyrics', 'keys' => array('actions', 'artist', 'title'), 'values' => array('hide_dialog get_lyrics', rawurlencode($artist), rawurlencode($title)));
			$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', hsc($title), rawurlencode(uri_to_url($uri))));

			echo '
				<div class="list_item_div">
				<div title="' . hsc($artist . ' - ' . $title) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_icon_div"><div class="img_div img_24_div unfold_more_grey_24_img_div ' . track_is_playing($uri, 'icon') . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div ' . track_is_playing($uri, 'text') . '">' . hsc($title) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($artist) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Play" class="actions_div" data-actions="play_uri" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
				<div title="Queue" class="actions_div" data-actions="queue_uri" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div queue_grey_24_img_div"></div></div>
				<div title="Save to/Remove from Library" class="actions_div" data-actions="save" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-isauthorizedwithspotify="' . boolean_to_string(is_authorized_with_spotify) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div ' . save_remove_icon($uri) . '_grey_24_img_div"></div></div>
				<div title="Go to Artist" class="actions_div" data-actions="browse_artist" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
				<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	$playlists = get_db_rows('recent-playlists', "SELECT id, name, uri FROM recent_playlists ORDER BY id DESC", array('id', 'name', 'uri'));

	echo '</div><div class="cards_vertical_title_div">Playlists</div>';

	if(empty($playlists))
	{
		echo '<div class="cards_vertical_empty_div">No playlists.</div>';
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

			echo '<div class="card_vertical_div"><div title="' . hsc($name) . '" class="card_vertical_inner_div actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="' . rawurlencode($name) . '" data-isauthorizedwithspotify="' . boolean_to_string(is_authorized_with_spotify) . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" data-coverarturi="' . $uri . '" style="' . $style . '"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">' . $user . '</div></div></div>';
		}

		echo '<div class="clear_float_div"></div></div>';
	}

	echo '</div>';
}

?>