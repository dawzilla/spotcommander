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

if(isset($_GET['save']))
{
	echo save(rawurldecode($_POST['artist']), rawurldecode($_POST['title']), $_POST['uri']);
}
elseif(isset($_GET['remove']))
{
	echo remove($_POST['uri']);
}
elseif(isset($_GET['refresh_library']))
{
	echo refresh_library();
}
else
{
	$activity['title'] = 'Library';

	$cover_art_cache = get_cover_art_cache('small');

	$sort = $_COOKIE['settings_sort_library_tracks'];

	$order = 'DESC';
	$order1 = 'id';
	$order2 = 'artist';

	if($sort == 'artist')
	{
		$order = 'ASC';
		$order1 = 'artist';
		$order2 = 'title';
	}
	elseif($sort == 'title')
	{
		$order = 'ASC';
		$order1 = 'title';
		$order2 = 'artist';
	}

	$tracks = get_db_rows('library', "SELECT artist, title, uri FROM library WHERE type = 'track' ORDER BY " . sqlite_escape($order1) . " COLLATE NOCASE " . sqlite_escape($order) . ", " . sqlite_escape($order2) . " COLLATE NOCASE " . sqlite_escape($order), array('artist', 'title', 'uri'));

	if(!empty($tracks))
	{
		$queue_uris = array();
		$n = 0;

		foreach($tracks as $track)
		{
			$queue_uris[$n] = $track['uri'];

			$n++;
		}

		$activity['queueuris'] = json_encode($queue_uris);
	}

	$activity['actions'][] = array('action' => array('Refresh Library', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('confirm_refresh_library'));
	$activity['fab'] = array('label' => 'Go to Next Category', 'icon' => 'down_white_24_img_div', 'keys' => array('actions'), 'values' => array('scroll_to_next_list_header'));

	$actions_dialog = array();
	$actions_dialog['title'] = 'Sort By';
	$actions_dialog['actions'][] = array('text' => 'Added', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_tracks', 'default', 3650));
	$actions_dialog['actions'][] = array('text' => 'Artist', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_tracks', 'artist', 3650));
	$actions_dialog['actions'][] = array('text' => 'Title', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_tracks', 'title', 3650));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="list_header_0_div" class="list_header_div"><div>Tracks</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

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
			$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
			$actions_dialog['actions'][] = array('text' => 'Go to Album', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_album', $uri));
			$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
			$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog get_recommendations', $uri));
			$actions_dialog['actions'][] = array('text' => 'Lyrics', 'keys' => array('actions', 'artist', 'title'), 'values' => array('hide_dialog get_lyrics', rawurlencode($artist), rawurlencode($title)));
			$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', hsc($title), rawurlencode(uri_to_url($uri))));

			echo '
				<div class="list_item_div list_item_track_div">
				<div title="' . hsc($artist . ' - ' . $title) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" data-trackuri="' . $uri . '" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_icon_div"><div class="img_div img_24_div unfold_more_grey_24_img_div ' . track_is_playing($uri, 'icon') . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div ' . track_is_playing($uri, 'text') . '">' . hsc($title) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($artist) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Play" class="actions_div" data-actions="play_uris" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
				<div title="Queue" class="actions_div" data-actions="queue_uri" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div queue_grey_24_img_div"></div></div>
				<div title="Go to Artist" class="actions_div" data-actions="browse_artist" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
				<div title="Remove" class="actions_div" data-actions="remove" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div trash_grey_24_img_div"></div></div>
				<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	$actions_dialog = array();
	$actions_dialog['title'] = 'Sort By';
	$actions_dialog['actions'][] = array('text' => 'Added', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_albums', 'default', 3650));
	$actions_dialog['actions'][] = array('text' => 'Artist', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_albums', 'artist', 3650));
	$actions_dialog['actions'][] = array('text' => 'Title', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_albums', 'title', 3650));

	echo '
		</div>

		<div id="list_header_1_div" class="list_header_div"><div>Albums</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

		<div class="list_div">
	';

	$sort = $_COOKIE['settings_sort_library_albums'];

	$order = 'DESC';
	$order1 = 'id';
	$order2 = 'uri';

	if($sort == 'artist')
	{
		$order = 'ASC';
		$order1 = 'artist';
		$order2 = 'title';
	}
	elseif($sort == 'title')
	{
		$order = 'ASC';
		$order1 = 'title';
		$order2 = 'artist';
	}

	$albums = get_db_rows('library', "SELECT artist, title, uri FROM library WHERE type = 'album' ORDER BY " . sqlite_escape($order1) . " COLLATE NOCASE " . sqlite_escape($order) . ", " . sqlite_escape($order2) . " COLLATE NOCASE " . sqlite_escape($order), array('artist', 'title', 'uri'));

	if(empty($albums))
	{
		echo '<div class="list_empty_div">No albums.</div>';
	}
	else
	{
		foreach($albums as $album)
		{
			$artist = $album['artist'];
			$title = $album['title'];
			$uri = $album['uri'];

			$actions_dialog = array();
			$actions_dialog['title'] = hsc($title);
			$actions_dialog['actions'][] = array('text' => 'Go to Artist', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_artist', $uri));
			$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
			$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', hsc($title), rawurlencode(uri_to_url($uri))));

			$style = (empty($cover_art_cache[$uri])) ? 'color: initial' : 'background-size: cover; background-image: url(\'' . $cover_art_cache[$uri] . '\')';

			echo '
				<div class="list_item_div list_item_album_div">
				<div title="' . hsc($artist . ' - ' . $title) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_circle_div"><div class="album_grey_24_img_div" data-coverarturi="' . $uri . '" style="' . $style . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div">' . hsc($title) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($artist) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Play" class="actions_div" data-actions="play_uri" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
				<div title="Browse" class="actions_div" data-actions="change_activity" data-activity="album" data-subactivity="" data-args="uri=' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div album_grey_24_img_div"></div></div>
				<div title="Remove" class="actions_div" data-actions="remove" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div trash_grey_24_img_div"></div></div>
				<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	$actions_dialog = array();
	$actions_dialog['title'] = 'Sort By';
	$actions_dialog['actions'][] = array('text' => 'Added', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_artists', 'default', 3650));
	$actions_dialog['actions'][] = array('text' => 'Artist', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_artists', 'artist', 3650));

	echo '
		</div>

		<div id="list_header_2_div" class="list_header_div"><div>Artists</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

		<div class="list_div">
	';

	$sort = $_COOKIE['settings_sort_library_artists'];

	$order = 'DESC';
	$order1 = 'id';
	$order2 = 'uri';

	if($sort == 'artist')
	{
		$order = 'ASC';
		$order1 = 'artist';
		$order2 = 'title';
	}

	$artists = get_db_rows('library', "SELECT artist, uri FROM library WHERE type = 'artist' ORDER BY " . sqlite_escape($order1) . " COLLATE NOCASE " . sqlite_escape($order) . ", " . sqlite_escape($order2) . " COLLATE NOCASE " . sqlite_escape($order), array('artist', 'uri'));

	if(empty($artists))
	{
		echo '<div class="list_empty_div">No artists.</div>';
	}
	else
	{
		foreach($artists as $artist)
		{
			$name = $artist['artist'];
			$uri = $artist['uri'];

			$style = (empty($cover_art_cache[$uri])) ? 'color: initial' : 'background-size: cover; background-image: url(\'' . $cover_art_cache[$uri] . '\')';

			echo '
				<div class="list_item_div list_item_album_div">
				<div title="' . hsc($name) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_circle_div"><div class="person_grey_24_img_div" data-coverarturi="' . $uri . '" style="' . $style . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div">' . hsc($name) . '</div><div class="list_item_main_inner_text_lower_div">Artist</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Play" class="actions_div" data-actions="play_uri" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
				<div title="Browse" class="actions_div" data-actions="browse_artist" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
				<div title="Share" class="actions_div" data-actions="share_uri" data-title="' . hsc($name) . '" data-uri="' . rawurlencode(uri_to_url($uri)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div share_grey_24_img_div"></div></div>
				<div title="Remove" class="actions_div" data-actions="remove" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div trash_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	$actions_dialog = array();
	$actions_dialog['title'] = 'Sort By';
	$actions_dialog['actions'][] = array('text' => 'Added', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_users', 'default', 3650));
	$actions_dialog['actions'][] = array('text' => 'Name', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_users', 'title', 3650));
	$actions_dialog['actions'][] = array('text' => 'Username', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_library_users', 'artist', 3650));

	echo '
		</div>

		<div id="list_header_3_div" class="list_header_div"><div>Users</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

		<div class="list_div">
	';

	$sort = $_COOKIE['settings_sort_library_users'];

	$order = 'DESC';
	$order1 = 'id';
	$order2 = 'uri';

	if($sort == 'artist')
	{
		$order = 'ASC';
		$order1 = 'artist';
		$order2 = 'title';
	}
	if($sort == 'title')
	{
		$order = 'ASC';
		$order1 = 'title';
		$order2 = 'artist';
	}

	$users = get_db_rows('library', "SELECT artist, title, uri FROM library WHERE type = 'user' ORDER BY " . sqlite_escape($order1) . " COLLATE NOCASE " . sqlite_escape($order) . ", " . sqlite_escape($order2) . " COLLATE NOCASE " . sqlite_escape($order), array('artist', 'title', 'uri'));

	if(empty($users))
	{
		echo '<div class="list_empty_div">No users.</div>';
	}
	else
	{
		foreach($users as $user)
		{
			$username = $user['artist'];
			$name = $user['title'];
			$uri = $user['uri'];

			$style = (empty($cover_art_cache[$uri])) ? 'color: initial' : 'background-size: cover; background-image: url(\'' . $cover_art_cache[$uri] . '\')';

			echo '
				<div class="list_item_div list_item_album_div">
				<div title="' . hsc($name) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_circle_div"><div class="person_grey_24_img_div" data-coverarturi="' . $uri . '" style="' . $style . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div">' . hsc($name) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc(is_facebook_user($username)) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Go to User" class="actions_div" data-actions="get_user" data-username="' . rawurlencode($username) . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
				<div title="Remove" class="actions_div" data-actions="remove" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div trash_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	echo '</div></div>';
}

?>