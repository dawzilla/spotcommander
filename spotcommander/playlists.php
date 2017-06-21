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

if(isset($_GET['import_playlists']))
{
	echo import_playlists($_POST['uris']);
}
elseif(isset($_GET['refresh_spotify_playlists']))
{
	echo refresh_spotify_playlists();
}
elseif(isset($_GET['create_playlist']))
{
	echo create_playlist($_POST['name'], $_POST['make_public'], $_POST['make_collaborative']);
}
elseif(isset($_GET['edit_playlist']))
{
	echo edit_playlist($_POST['name'], $_POST['uri'], $_POST['make_public'], $_POST['make_collaborative']);
}
elseif(isset($_GET['add_uris_to_playlist']))
{
	echo add_uris_to_playlist($_POST['uri'], $_POST['uris']);
}
elseif(isset($_GET['delete_uris_from_playlist']))
{
	echo delete_uris_from_playlists($_POST['uri'], $_POST['uris'], intval($_POST['positions']), $_POST['snapshot_id']);
}
elseif(isset($_GET['cache_playlist']))
{
	get_playlist($_POST['uri']);
}
elseif(isset($_GET['refresh_playlist']))
{
	echo refresh_playlist($_POST['uri']);
}
elseif(isset($_GET['remove_playlist']))
{
	echo remove_playlist($_POST['id'], $_POST['uri']);
}
elseif(isset($_GET['remove_all_playlists']))
{
	echo remove_all_playlists();
}
elseif(isset($_GET['get_playlists_as_json']))
{
	echo get_playlists_as_json(false);
}
elseif(isset($_GET['get_playlists_with_access_as_json']))
{
	echo get_playlists_as_json(true);
}
elseif(isset($_GET['create']))
{
	$activity['title'] = 'Create Playlist';
	$activity['actions'][] = array('action' => array('Create', 'check_white_24_img_div'), 'keys' => array('actions', 'form'), 'values' => array('submit_form', 'form#create_playlist_form'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="activity_form_div">
		<form method="post" action="." id="create_playlist_form" autocomplete="off" autocapitalize="off">
		<div class="input_text_div"><div><div class="img_div img_18_div pencil_grey_18_img_div"></div></div><div><input type="text" id="create_playlist_name_input" value="Name&hellip;" data-hint="Name&hellip;"></div></div>
		<div class="input_checkbox_div"><div><input type="checkbox" id="create_playlist_make_public_input"></div><div><label for="create_playlist_make_public_input">Make public</label></div></div>
		<div class="input_checkbox_div"><div><input type="checkbox" id="create_playlist_make_collaborative_input"></div><div><label for="create_playlist_make_collaborative_input">Make collaborative</label></div></div>
		<div class="invisible_div"><input type="submit" value="Create"></div>
		</form>
		</div>

		</div>
	';
}
elseif(isset($_GET['edit']))
{
	$uri = str_replace('%3A', ':', rawurlencode($_GET['uri']));
	$playlist = get_playlist($uri);

	$activity['title'] = 'Edit Playlist';

	if(empty($playlist))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get playlist. Try again.</div></div>

			</div>
		';
	}
	else
	{
		$name = $playlist['name'];
		$is_public = ($playlist['public'] == 'Yes') ? ' checked="checked"' : '';
		$is_collaborative = ($playlist['collaborative'] == 'Yes') ? ' checked="checked"' : '';

		$activity['actions'][] = array('action' => array('Edit', 'check_white_24_img_div'), 'keys' => array('actions', 'form'), 'values' => array('submit_form', 'form#edit_playlist_form'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_form_div">
			<form method="post" action="." id="edit_playlist_form" autocomplete="off" autocapitalize="off">
			<div class="input_text_div"><div><div class="img_div img_18_div pencil_grey_18_img_div"></div></div><div><input type="text" class="focused_text_input" id="edit_playlist_name_input" value="' . hsc($name) . '" data-hint="Name&hellip;"></div></div>
			<div class="input_checkbox_div"><div><input type="checkbox" id="edit_playlist_make_public_input"' . $is_public . '></div><div><label for="edit_playlist_make_public_input">Make public</label></div></div>
			<div class="input_checkbox_div"><div><input type="checkbox" id="edit_playlist_make_collaborative_input"' . $is_collaborative . '></div><div><label for="edit_playlist_make_collaborative_input">Make collaborative</label></div></div>
			<div class="invisible_div"><input type="hidden" id="edit_playlist_uri_input" value="' . $uri . '"><input type="submit" value="Edit"></div>
			</form>
			</div>

			</div>
		';
	}
}
elseif(isset($_GET['import-manually']))
{
	$activity['title'] = 'Import Manually';
	$activity['actions'][] = array('action' => array('Import', 'check_white_24_img_div'), 'keys' => array('actions', 'form'), 'values' => array('submit_form', 'form#import_playlists_form'));

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="activity_form_div">
		<form method="post" action="." id="import_playlists_form" autocomplete="off" autocapitalize="off">
		<div class="input_text_div"><div><div class="img_div img_18_div pencil_grey_18_img_div"></div></div><div><input type="text" id="import_playlists_uris_input" value="URIs&hellip;" data-hint="URIs&hellip;"></div></div>
		<div class="input_information_div">Separate multiple URIs by space</div>
		<div class="invisible_div"><input type="submit" value="Import"></div>
		</form>
		</div>

		</div>
	';
}
elseif(isset($_GET['browse']))
{
	$playlist_uri = str_replace('%3A', ':', rawurlencode($_GET['uri']));
	$metadata = get_playlist($playlist_uri);

	if(empty($metadata))
	{
		$activity['title'] = 'Error';
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get playlist. Try again.</div></div>

			</div>
		';
	}
	else
	{
		$name = $metadata['name'];
		$description = rawurldecode($_GET['description']);
		$description = (empty($description)) ? 'No description available.' : $description;
		$user = $metadata['user'];
		$public = $metadata['public'];
		$collaborative = $metadata['collaborative'];
		$snapshot_id = $metadata['snapshot_id'];
		$tracks = (empty($metadata['tracks'])) ? null : $metadata['tracks'];
		$tracks_count = $metadata['tracks_count'];
		$total_length = $metadata['total_length'];
		$cover_art_uri = $metadata['cover_art_uri'];

		$activity['title'] = $name;

		if(empty($tracks))
		{
			$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

			echo '
				<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

				<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Empty playlist</div></div>

				</div>
			';
		}
		else
		{
			$is_saved = playlist_is_saved(null, $playlist_uri);

			$details_dialog = array();
			$details_dialog['title'] = hsc($name);
			$details_dialog['details'][] = array('detail' => 'Created By', 'value' => '<span class="actions_span" data-actions="hide_dialog get_user" data-username="' . $user . '" data-highlightclass="actions_span_highlight" onclick="void(0)">' . $user . '</span>');
			$details_dialog['details'][] = array('detail' => 'Tracks', 'value' => $tracks_count);
			$details_dialog['details'][] = array('detail' => 'Total Length', 'value' => $total_length);
			$details_dialog['details'][] = array('detail' => 'Public', 'value' => $public);
			$details_dialog['details'][] = array('detail' => 'Collaborative', 'value' => $collaborative);

			$queue_uris = array();
			$n = 0;

			foreach($tracks as $track)
			{
				if($n == 226) break;

				$queue_uris[$n] = $track['uri'];

				$n++;
			}

			$activity['queueuris'] = json_encode($queue_uris);

			$activity['actions'][] = array('action' => array('Refresh', ''), 'keys' => array('actions', 'uri'), 'values' => array('refresh_playlist', $playlist_uri));

			if($is_saved) $activity['actions'][] = array('action' => array('Edit', ''), 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'playlists', 'edit', 'uri=' . $playlist_uri));

			$activity['actions'][] = array('action' => array('Go to User', ''), 'keys' => array('actions', 'username'), 'values' => array('get_user', $user));
			$activity['actions'][] = array('action' => array('Details', ''), 'keys' => array('actions', 'dialogdetails'), 'values' => array('show_details_dialog', base64_encode(json_encode($details_dialog))));
			$activity['actions'][] = array('action' => array('Share', ''), 'keys' => array('actions', 'title', 'uri'), 'values' => array('share_uri', hsc($name), rawurlencode(uri_to_url($playlist_uri))));

			$tracks_count = ($tracks_count == 1) ? '<span id="playlist_tracks_count_span">' . $tracks_count . '</span> track' : '<span id="playlist_tracks_count_span">' . $tracks_count . '</span> tracks';

			echo '
				<div id="cover_art_div">
				<div id="cover_art_art_div" class="actions_div" data-actions="resize_cover_art" data-imageuri="' . $cover_art_uri . '" data-resized="false" data-width="640" data-height="640" data-uri="' . $playlist_uri . '" style="background-image: url(\'' . $cover_art_uri . '\')" onclick="void(0)"></div>
				<div id="cover_art_information_div" class="shadow_up_black_48_img_div"><div>' . $tracks_count . ' / Playlist by ' . hsc(is_facebook_user($user)) . '</div></div>
				<div title="Play" id="cover_art_fab_div" class="actions_div play_white_24_img_div" data-actions="show_cover_art_fab_animation play_uri" data-uri="' . $playlist_uri . '" data-highlightclass="light_green_highlight" onclick="void(0)"></div>
				</div>
			';

			echo '<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">';

			if(!$is_saved) echo '<div id="playlist_description_div"><div id="playlist_description_title_div">Description</div><div id="playlist_description_body_div">' . hsc($description) . '</div><div id="playlist_description_buttons_div"><div class="actions_div" data-actions="import_playlist" data-uri="' . $playlist_uri . '" data-highlightclass="light_grey_highlight" onclick="void(0)">ADD TO MY PLAYLISTS</div></div></div>';

			$actions_dialog = array();
			$actions_dialog['title'] = 'Sort By';
			$actions_dialog['actions'][] = array('text' => 'Track Order', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlist_tracks', 'default', 3650));
			$actions_dialog['actions'][] = array('text' => 'Added', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlist_tracks', 'added', 3650));
			$actions_dialog['actions'][] = array('text' => 'Added By', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlist_tracks', 'added_by', 3650));
			$actions_dialog['actions'][] = array('text' => 'Artist', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlist_tracks', 'artist', 3650));
			$actions_dialog['actions'][] = array('text' => 'Title', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlist_tracks', 'title', 3650));

			$class = ($is_saved) ? 'list_header_below_cover_art_div' : '';

			echo '
				<div class="list_header_div ' . $class . '"><div>Tracks</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

				<div class="list_div">
			';

			$sort = $_COOKIE['settings_sort_playlist_tracks'];

			if($sort != 'default')
			{
				function tracks_cmp($a, $b)
				{
					global $sort;

					if($sort == 'added')
					{
						return strcasecmp($b['added'], $a['added']);
					}
					elseif($sort == 'added_by')
					{
						return strcasecmp($a['added_by'], $b['added_by']);
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

			$hide_local_files = (string_to_boolean($_COOKIE['settings_hide_local_files']));

			$i = 0;

			foreach($tracks as $track)
			{
				$i++;

				$artist = $track['artist'];
				$title = $track['title'];
				$album = $track['album'];
				$length = $track['length'];
				$uri = $track['uri'];
				$position = $track['position'];
				$added = $track['added'];
				$added_by = $track['added_by'];

				if($hide_local_files && get_uri_type($uri) == 'local') continue;

				$list_item_div = 'list_item_' . $i . '_div';

				$details_dialog = array();
				$details_dialog['title'] = hsc($title);
				$details_dialog['details'][] = array('detail' => 'Album', 'value' => $album);
				$details_dialog['details'][] = array('detail' => 'Length', 'value' => $length);
				$details_dialog['details'][] = array('detail' => 'Added', 'value' => substr($added, 0, 10));
				$details_dialog['details'][] = array('detail' => 'Added By', 'value' => '<span class="actions_span" data-actions="hide_dialog get_user" data-username="' . $added_by . '" data-highlightclass="actions_span_highlight" onclick="void(0)">' . $added_by . '</span>');

				$actions_dialog = array();
				$actions_dialog['title'] = hsc($title);
				$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
				$actions_dialog['actions'][] = array('text' => 'Go to Album', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_album', $uri));
				$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
				$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog get_recommendations', $uri));
				$actions_dialog['actions'][] = array('text' => 'Lyrics', 'keys' => array('actions', 'artist', 'title'), 'values' => array('hide_dialog get_lyrics', rawurlencode($artist), rawurlencode($title)));
				$actions_dialog['actions'][] = array('text' => 'Delete from Playlist', 'keys' => array('actions', 'uri', 'uris', 'positions', 'snapshotid', 'div'), 'values' => array('hide_dialog delete_uris_from_playlist', $playlist_uri, $uri, $position, $snapshot_id, $list_item_div));
				$actions_dialog['actions'][] = array('text' => 'Details', 'keys' => array('actions', 'dialogdetails'), 'values' => array('hide_dialog show_details_dialog', base64_encode(json_encode($details_dialog))));
				$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', hsc($title), rawurlencode(uri_to_url($uri))));

				echo '
					<div id="' . $list_item_div . '" class="list_item_div">
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
					<div title="Save to/Remove from Library" class="actions_div" data-actions="save" data-artist="' . rawurlencode($artist) . '" data-title="' . rawurlencode($title) . '" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div ' . save_remove_icon($uri) . '_grey_24_img_div"></div></div>
					<div title="Go to Artist" class="actions_div" data-actions="browse_artist" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
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
else
{
	$activity['title'] = 'Playlists';

	$cover_art_cache = get_cover_art_cache('small');

	$activity['fab'] = array('label' => 'Create', 'icon' => 'plus_white_24_img_div', 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'playlists', 'create', ''));

	$activity['actions'][] = array('action' => array('Recently Played', ''), 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'recently-played', '', ''));
	$activity['actions'][] = array('action' => array('Refresh from Spotify', ''), 'keys' => array('actions'), 'values' => array('confirm_refresh_spotify_playlists'));
	$activity['actions'][] = array('action' => array('Import Manually', ''), 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'playlists', 'import-manually', ''));
	$activity['actions'][] = array('action' => array('Recover Playlists', ''), 'keys' => array('actions', 'uri'), 'values' => array('open_external_activity', 'https://www.spotify.com/account/recover-playlists/'));

	echo '<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">';

	$actions_dialog = array();
	$actions_dialog['title'] = 'Sort By';
	$actions_dialog['actions'][] = array('text' => 'Default', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlists', 'default', 3650));
	$actions_dialog['actions'][] = array('text' => 'Name', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlists', 'name', 3650));
	$actions_dialog['actions'][] = array('text' => 'User', 'keys' => array('actions', 'cookieid', 'cookievalue', 'cookieexpires'), 'values' => array('hide_dialog set_cookie refresh_activity', 'settings_sort_playlists', 'user', 3650));

	echo '
		<div class="list_header_div"><div>Playlists</div><div title="Sort" class="actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_18_div sort_grey_18_img_div"></div></div></div>

		<div class="list_div">
	';

	$sort = $_COOKIE['settings_sort_playlists'];

	$order1 = 'id';
	$order2 = 'name';

	if($sort == 'name')
	{
		$order1 = 'name';
		$order2 = 'uri';
	}
	elseif($sort == 'user')
	{
		$order1 = 'uri';
		$order2 = 'name';
	}

	$playlists = get_playlists($order1, $order2);

	if(empty($playlists))
	{
		echo '<div class="list_empty_div">No playlists.</div>';
	}
	else
	{
		foreach($playlists as $playlist)
		{
			$id = $playlist['id'];
			$name = $playlist['name'];
			$uri = $playlist['uri'];
			$user = get_playlist_user($uri);

			$actions_dialog = array();
			$actions_dialog['title'] = hsc($name);
			$actions_dialog['actions'][] = array('text' => 'Go to User', 'keys' => array('actions', 'username'), 'values' => array('hide_dialog get_user', $user));
			$actions_dialog['actions'][] = array('text' => 'Edit', 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('hide_dialog change_activity', 'playlists', 'edit', 'uri=' . $uri));
			$actions_dialog['actions'][] = array('text' => 'Delete', 'keys' => array('actions', 'id', 'name', 'uri'), 'values' => array('hide_dialog confirm_remove_playlist', $id, $name, $uri));

			$style = (empty($cover_art_cache[$uri])) ? 'color: initial' : 'background-size: cover; background-image: url(\'' . $cover_art_cache[$uri] . '\')';

			echo '
				<div class="list_item_div">
				<div title="' . hsc($name) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_circle_div"><div class="playlist_grey_24_img_div" data-coverarturi="' . $uri . '" style="' . $style . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div">' . hsc($name) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc(is_facebook_user($user)) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Play" class="actions_div" data-actions="play_uri" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div play_grey_24_img_div"></div></div>
				<div title="Browse" class="actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div playlist_grey_24_img_div"></div></div>
				<div title="Share" class="actions_div" data-actions="share_uri" data-title="' . hsc($name) . '" data-uri="' . rawurlencode(uri_to_url($uri)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div share_grey_24_img_div"></div></div>
				<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}
	}

	echo '</div></div>';
}

?>