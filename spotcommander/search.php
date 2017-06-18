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

if(isset($_GET['clear']))
{
	echo clear_recent_searches();
}
elseif(isset($_GET['search']))
{
	$string = rawurldecode($_GET['string']);

	if(preg_match('/^(.+?):"(.+?)"$/', $string, $matches)) $string = $matches[1] . ':"' . str_replace('"', '', $matches[2]) . '"';
	if(preg_match('/^artist:"(.+?), .+"$/', $string, $matches)) $string = 'artist:"' . $matches[1] . '"';

	save_recent_searches($string);

	$search = get_search($string);

	$activity['title'] = get_search_title($string);

	if(empty($search))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get search results. Try again.</div></div>

			</div>
		';
	}
	else
	{
		echo '<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">';

		$user = $search['user'];
		$tracks = $search['tracks'];
		$albums = $search['albums'];
		$artists = $search['artists'];
		$playlists = $search['playlists'];

		$total_results = 50;
		$initial_results = (get_search_type($string) == 'track' || get_search_type($string) == 'isrc') ? 50 : 5;

		if(!empty($user))
		{
			$style = (empty($user['image'])) ? '' : 'background-size: cover; background-image: url(\'' . $user['image'] . '\')';
			$text = ($user['followers'] == 1) ? 'follower' : 'followers';

			echo '
				<div class="list_header_div"><div>User</div><div></div></div>

				<div class="list_div">

				<div class="list_item_div">
				<div title="' . hsc($user['name']) . '" class="list_item_main_div actions_div" data-actions="get_user" data-username="' . rawurlencode($user['username']) . '" data-highlightclass="light_grey_highlight" onclick="void(0)">
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_circle_div"><div class="person_grey_24_img_div" style="' . $style . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div">' . hsc($user['name']) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($user['username']) . ' / ' . $user['followers'] . ' ' . $text . '</div></div>
				</div>
				</div>
				</div>

				</div>
			';
		}

		$i = 0;

		if(is_array($tracks))
		{
			$queue_uris = array();
			$n = 0;

			foreach($tracks as $track)
			{
				$queue_uris[$n] = $track['uri'];

				$n++;
			}

			$queue_uris = base64_encode(json_encode($queue_uris));

			$actions_dialog = array();
			$actions_dialog['title'] = 'Sort By';
			$actions_dialog['actions'][] = array('text' => 'Default', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_search_tracks', 'default', 3650));
			$actions_dialog['actions'][] = array('text' => 'Popularity', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_search_tracks', 'popularity', 3650));
			$actions_dialog['actions'][] = array('text' => 'Artist', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_search_tracks', 'artist', 3650));
			$actions_dialog['actions'][] = array('text' => 'Title', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_search_tracks', 'title', 3650));

			echo '
				<div class="list_header_div"><div>Tracks</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

				<div class="list_div">
			';

			$sort = $_COOKIE['settings_sort_search_tracks'];

			if($sort != 'default')
			{
				function tracks_cmp($a, $b)
				{
					global $sort;

					if($sort == 'popularity')
					{
						return strcasecmp($b['popularity'], $a['popularity']);
					}
					elseif($sort == 'artist')
					{
						return strcasecmp($a['artist'], $b['artist']);
					}
					elseif($sort == 'title')
					{
						return strcasecmp($a['title'], $b['title']);
					}
				}

				usort($tracks, 'tracks_cmp');
			}

			foreach($tracks as $track)
			{
				$i++;
				
				$artist = $track['artist'];
				$artist_uri = $track['artist_uri'];
				$album = $track['album'];
				$album_uri = $track['album_uri'];
				$title = $track['title'];
				$length = $track['length'];
				$popularity = $track['popularity'];
				$uri = $track['uri'];

				$details_dialog = array();
				$details_dialog['title'] = hsc($title);
				$details_dialog['details'][] = array('detail' => 'Album', 'value' => $album);
				$details_dialog['details'][] = array('detail' => 'Length', 'value' => $length);
				$details_dialog['details'][] = array('detail' => 'Popularity', 'value' => $popularity);

				$actions_dialog = array();
				$actions_dialog['title'] = hsc($title);
				$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
				$actions_dialog['actions'][] = array('text' => 'Go to Album', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_album', $album_uri));
				$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
				$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog get_recommendations', $uri));
				$actions_dialog['actions'][] = array('text' => 'Lyrics', 'keys' => array('actions', 'artist', 'title'), 'values' => array('hide_dialog get_lyrics', rawurlencode($artist), rawurlencode($title)));
				$actions_dialog['actions'][] = array('text' => 'Details', 'keys' => array('actions', 'dialogdetails'), 'values' => array('hide_dialog show_details_dialog', base64_encode(json_encode($details_dialog))));
				$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', hsc($title), rawurlencode(uri_to_url($uri))));

				$class = ($i > $initial_results) ? 'hidden_div' : '';

				echo '
					<div class="list_item_div list_item_track_div ' . $class . '">
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

			if($i == 0)
			{
				echo '<div class="list_empty_div">No tracks.</div>';
			}
			elseif($i > $initial_results)
			{
				echo '<div id="show_all_search_results_button_div" class="green_button_div actions_div" data-actions="show_all_list_items" data-showitems="div.list_item_track_div" data-hideitem="div#show_all_search_results_button_div" data-highlightclass="light_green_highlight" onclick="void(0)">SHOW ALL TRACKS</div>';
			}

			echo '</div>';
		}

		$i = 0;

		if(!empty($albums))
		{
			echo '<div class="cards_vertical_title_div">Albums</div><div class="cards_vertical_div">';

			foreach($albums as $album)
			{
				$title = $album['title'];
				$type = $album['type'];
				$uri = $album['uri'];
				$cover_art = $album['cover_art'];

				echo '<div class="card_vertical_div"><div title="' . hsc($title) . '" class="card_vertical_inner_div actions_div" data-actions="browse_album" data-uri="' . $uri . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($title) . '</div><div class="card_vertical_lower_div">' . $type . '</div></div></div>';
			}

			echo '<div class="clear_float_div"></div></div>';
		}

		if(!empty($artists))
		{
			echo '<div class="cards_vertical_title_div">Artists</div><div class="cards_vertical_div">';

			foreach($artists as $artist)
			{
				$name = $artist['artist'];
				$popularity = $artist['popularity'];
				$uri = $artist['uri'];
				$cover_art = $artist['cover_art'];

				echo '<div class="card_vertical_div"><div title="' . hsc($name) . '" class="card_vertical_inner_div actions_div" data-actions="browse_artist" data-uri="' . $uri . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">Popularity: ' . hsc($popularity) . '</div></div></div>';
			}

			echo '<div class="clear_float_div"></div></div>';
		}

		if(!empty($playlists))
		{
			echo '<div class="cards_vertical_title_div">Playlists</div><div class="cards_vertical_div">';

			foreach($playlists as $playlist)
			{
				$name = $playlist['name'];
				$user = $playlist['user'];
				$uri = $playlist['uri'];
				$cover_art = $playlist['cover_art'];

				echo '<div class="card_vertical_div"><div title="' . hsc($name) . '" class="card_vertical_inner_div actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="' . rawurlencode($name) . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">Playlist by ' . hsc(is_facebook_user($user)) . '</div></div></div>';
			}

			echo '<div class="clear_float_div"></div></div>';
		}

		echo '</div>';
	}
}
else
{
	$recent_searches = get_db_rows('recent-searches', "SELECT string FROM recent_searches ORDER BY id DESC", array('string'));

	$activity['title'] = 'Search';
	$activity['actions'][] = array('action' => array('Clear Recent Searches', 'trash_white_24_img_div'), 'keys' => array('actions'), 'values' => array('clear_recent_searches'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="search_form_div">
		<form method="post" action="." id="search_form" autocomplete="off" autocapitalize="off">
		<div class="input_text_div input_text_transparent_div"><div><div class="img_div img_24_div search_grey_24_img_div"></div></div><div><input type="text" id="search_input" value="Search music &amp; usernames&hellip;" data-hint="Search music &amp; usernames&hellip;"></div></div>
		<div class="invisible_div"><input type="submit" value="Search"></div>
		</form>
		</div>

		<div class="list_header_div"><div>Recent Searches</div><div></div></div>

		<div class="list_div">
	';

	if(empty($recent_searches))
	{
		echo '<div class="list_empty_div">No recent searches.</div>';
	}
	else
	{
		foreach($recent_searches as $recent_search)
		{
			$string = $recent_search['string'];
			$title = get_search_title($string);

			echo '
				<div class="list_item_div">
				<div title="' . hsc($title) . '" class="list_item_main_div actions_div" data-actions="get_search" data-string="' . rawurlencode($string) . '" data-highlightclass="light_grey_highlight" onclick="void(0)">
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_icon_div"><div class="img_div img_24_div search_grey_24_img_div"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div">' . hsc($title) . '</div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	echo '</div></div>';
}

?>