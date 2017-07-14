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

if(isset($_GET['featured-playlists']))
{
	$files = get_external_files(array(project_website . 'api/1/browse/featured-playlists/?version=' . rawurlencode(project_version) . '&time=' . $_GET['time'] . '&country=' . $_GET['country'] . '&fields=' . rawurlencode('description,playlists') . '&token=' . get_spotify_token()), null, null);
	$metadata = json_decode($files[0], true);

	if(empty($metadata['metadata']))
	{
		$activity['title'] = 'Error';
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get playlists. Try again.</div></div>

			</div>
		';
	}
	else
	{
		$metadata = $metadata['metadata'];
		$playlists = $metadata['playlists'];

		$activity['title'] = $metadata['description'];

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="cards_vertical_div">
		';

		foreach($playlists as $playlist)
		{
			$name = $playlist['name'];
			$description = $playlist['description'];
			$tracks = $playlist['tracks'];
			$followers = $playlist['followers_formatted'];
			$uri = $playlist['uri'];
			$cover_art = $playlist['cover_art'];

			echo '<div class="card_vertical_div"><div title="' . hsc($description) . '" class="card_vertical_inner_div actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="' . rawurlencode($description) . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">Tracks: ' . $tracks . '</div></div></div>';
		}

		echo '<div class="clear_float_div"></div></div></div>';
	}
}
elseif(isset($_GET['genres']))
{
	$country = get_spotify_country();

	if(isset($_GET['name']) && isset($_GET['genre']))
	{
		$activity['title'] = rawurldecode($_GET['name']);

		$files = get_external_files(array(project_website . 'api/1/browse/genre/?version=' . rawurlencode(project_version) . '&genre=' . $_GET['genre'] . '&country=' . $country . '&token=' . get_spotify_token()), null, null);
		$playlists = json_decode($files[0], true);

		if(!is_array($playlists))
		{
			$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

			echo '
				<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

				<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get genre. Try again.</div></div>

				</div>
			';
		}
		else
		{
			echo '
				<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

				<div class="cards_vertical_div">
			';

			foreach($playlists as $playlist)
			{
				$name = $playlist['name'];
				$description = $playlist['description'];
				$tracks = $playlist['tracks'];
				$uri = $playlist['uri'];
				$cover_art = $playlist['cover_art'];

				echo '<div class="card_vertical_div"><div title="' . hsc($name . ': ' . $description) . '" class="card_vertical_inner_div actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="' . rawurlencode($description) . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">Tracks: ' . $tracks . '</div></div></div>';
			}

			echo '<div class="clear_float_div"></div></div></div>';
		}
	}
	else
	{
		$activity['title'] = 'Genres &amp; Moods';

		$files = get_external_files(array(project_website . 'api/1/browse/genres/?version=' . rawurlencode(project_version) . '&country=' . $country . '&token=' . get_spotify_token()), null, null);
		$genres = json_decode($files[0], true);

		if(!is_array($genres))
		{
			$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

			echo '
				<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

				<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get genres. Try again.</div></div>

				</div>
			';
		}
		else
		{
			echo '
				<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

				<div class="cards_vertical_div">
			';

			foreach($genres as $genre)
			{
				$name = $genre['name'];
				$space = $genre['space'];
				$cover_art = $genre['cover_art'];

				echo '<div class="card_vertical_div"><div title="' . hsc($name) . '" class="card_vertical_inner_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="genres" data-args="name=' . rawurlencode($name) . '&amp;genre=' . $space . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">Genre</div></div></div>';
			}

			echo '<div class="clear_float_div"></div></div></div>';
		}
	}
}
elseif(isset($_GET['popular-playlists']))
{
	$activity['title'] = 'Popular Playlists';

	$files = get_external_files(array(project_website . 'api/2/browse/popular-playlists/?version=' . rawurlencode(project_version)), null, null);
	$playlists = json_decode($files[0], true);

	if(!is_array($playlists))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get playlists. Try again.</div></div>

			</div>
		';
	}
	else
	{
		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="cards_vertical_div">
		';

		foreach($playlists as $playlist)
		{
			$name = $playlist['name'];
			$description = $playlist['description'];
			$genre = $playlist['genre'];
			$uri = $playlist['uri'];
			$cover_art = $playlist['cover_art'];

			echo '<div class="card_vertical_div"><div title="' . hsc($description) . '" class="card_vertical_inner_div actions_div" data-actions="browse_playlist" data-uri="' . $uri . '" data-description="' . rawurlencode($description) . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($name) . '</div><div class="card_vertical_lower_div">' . hsc($genre) . '</div></div></div>';
		}

		echo '<div class="clear_float_div"></div></div></div>';
	}
}
elseif(isset($_GET['new-releases']))
{
	$country = get_spotify_country();

	$activity['title'] = 'New Releases in ' . get_country_name($country);

	$files = get_external_files(array(project_website . 'api/1/browse/new-releases/?version=' . rawurlencode(project_version) . '&country=' . $country . '&token=' . get_spotify_token()), null, null);
	$albums = json_decode($files[0], true);

	if(!is_array($albums))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get releases. Try again.</div></div>

			</div>
		';
	}
	else
	{
		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="cards_vertical_div">
		';

		foreach($albums as $album)
		{
			$artist = $album['artist'];
			$title = $album['title'];
			$released = $album['released'];
			$uri = $album['uri'];
			$cover_art = $album['cover_art'];

			echo '<div class="card_vertical_div"><div title="' . hsc($artist . ' - ' . $title) . ' (' . $released . ')" class="card_vertical_inner_div actions_div" data-actions="browse_album" data-uri="' . $uri . '" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_vertical_cover_art_div" style="background-image: url(\'' . $cover_art . '\')"></div><div class="card_vertical_upper_div">' . hsc($title) . '</div><div class="card_vertical_lower_div">' . hsc($artist) . '</div></div></div>';
		}

		echo '<div class="clear_float_div"></div></div></div>';
	}
}
elseif(isset($_GET['charts']))
{
	$chart = $_GET['chart'];

	$country = get_spotify_country();

	$activity['title'] = 'Most ' . ucfirst($chart) . ' in ' . get_country_name($country);

	$files = get_external_files(array(project_website . 'api/1/browse/charts/?version=' . rawurlencode(project_version) . '&chart=' . $chart . '&country=' . $country . '&token=' . get_spotify_token()), null, null);
	$metadata = json_decode($files[0], true);

	if(empty($metadata['tracks']))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get chart. Try again.</div></div>

			</div>
		';
	}
	else
	{
		$tracks = $metadata['tracks'];

		$queue_uris = '';
		$add_to_playlist_uris = '';

		foreach($tracks as $track)
		{
			$track_uri = $track['uri'];
			$queue_uris .= $track_uri . ' ';
			$add_to_playlist_uris .= $track_uri . ' ';
		}

		$queue_uris = rtrim($queue_uris);
		$add_to_playlist_uris = rtrim($add_to_playlist_uris);

		$activity['fab'] = array('label' => 'Play', 'icon' => 'play_white_24_img_div', 'keys' => array('actions', 'uri', 'queueuris'), 'values' => array('play_uris', strstr($queue_uris, ' ', true), $queue_uris));

		$activity['actions'][] = array('action' => array('Add to Playlist', 'plus_white_24_img_div'), 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $activity['title'], $add_to_playlist_uris));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="list_header_div"><div>Tracks</div><div></div></div>

			<div class="list_div">
		';

		foreach($tracks as $track)
		{
			$artist = $track['artist'];
			$title = $track['title'];
			$uri = url_to_uri($track['uri']);
			$plays = $track['plays_formatted'];

			$details_dialog = array();
			$details_dialog['title'] = $title;
			$details_dialog['details'][] = array('detail' => 'Plays', 'value' => $plays);

			$actions_dialog = array();
			$actions_dialog['title'] = $title;
			$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
			$actions_dialog['actions'][] = array('text' => 'Go to Album', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_album', $uri));
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
				<div title="Go to Artist" class="actions_div" data-actions="browse_artist" data-uri="' . $uri . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div person_grey_24_img_div"></div></div>
				<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>
				</div>
				</div>
				</div>
			';
		}

		echo '</div></div>';
	}
}
elseif(isset($_GET['recommendations']))
{
	$id = uri_to_id($_GET['uri']);

	$activity['title'] = 'Recommendations';

	$files = get_external_files(array('https://api.spotify.com/v1/recommendations?seed_tracks=' . $id . '&min_popularity=50&market=' . get_spotify_country() . '&limit=100'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);
	$metadata = json_decode($files[0], true);

	if(empty($metadata['tracks']))
	{
		$activity['actions'][] = array('action' => array('Retry', 'refresh_white_24_img_div'), 'keys' => array('actions'), 'values' => array('reload_activity'));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Could not get recommendations. Try again.</div></div>

			</div>
		';
	}
	else
	{
		$tracks = $metadata['tracks'];

		$queue_uris = '';
		$add_to_playlist_uris = '';

		foreach($tracks as $track)
		{
			$track_uri = $track['uri'];
			$queue_uris .= $track_uri . ' ';
			$add_to_playlist_uris .= $track_uri . ' ';
		}

		$queue_uris = rtrim($queue_uris);
		$add_to_playlist_uris = rtrim($add_to_playlist_uris);

		$activity['fab'] = array('label' => 'Play', 'icon' => 'play_white_24_img_div', 'keys' => array('actions', 'uri', 'queueuris'), 'values' => array('play_uris', strstr($queue_uris, ' ', true), $queue_uris));

		$activity['actions'][] = array('action' => array('Add to Playlist', 'plus_white_24_img_div'), 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $activity['title'], $add_to_playlist_uris));

		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="list_header_div"><div>Tracks</div><div></div></div>

			<div class="list_div">
		';

		foreach($tracks as $track)
		{
			$artist = get_artists($track['artists']);
			$artist_uri = $track['artists'][0]['uri'];
			$album = $track['album']['name'];
			$album_uri = $track['album']['uri'];
			$title = $track['name'];
			$uri = $track['uri'];
			$length = convert_length($track['duration_ms'], 'ms');
			$popularity = $track['popularity'];

			$details_dialog = array();
			$details_dialog['title'] = $title;
			$details_dialog['details'][] = array('detail' => 'Album', 'value' => $album);
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

		echo '</div></div>';
	}
}
else
{
	$country = get_spotify_country();
	$country_code = $country;
	$country = get_country_name($country);

	$activity['title'] = 'Browse';

	echo '
		<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

		<div id="browse_div">

		<div id="browse_featured_playlists_div" data-country="' . $country_code . '" data-spotifytoken="' . get_spotify_token() . '" data-highlightclass="card_highlight" onclick="void(0)"><div><div></div></div></div>

		<div class="cards_div">
		<div>
		<div>
		<div class="card_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="genres" data-args="name=Top%20Lists&genre=toplists" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_icon_div"><div class="img_div img_24_div star_grey_24_img_div"></div></div><div class="card_text_div"><div>Top Lists</div><div>Currently in ' . $country . '.</div></div></div>
		<div class="card_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="genres" data-args="" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_icon_div"><div class="img_div img_24_div label_grey_24_img_div"></div></div><div class="card_text_div"><div>Genres &amp; Moods</div><div>Playlists based on genres and moods.</div></div></div>
		<div class="card_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="popular-playlists" data-args="" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_icon_div"><div class="img_div img_24_div heart_grey_24_img_div"></div></div><div class="card_text_div"><div>Popular Playlists</div><div>Updated weekly.</div></div></div>
		</div>
		<div>
		<div class="card_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="new-releases" data-args="" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_icon_div"><div class="img_div img_24_div album_grey_24_img_div"></div></div><div class="card_text_div"><div>New Releases</div><div>In ' . $country . '.</div></div></div>
		<div class="card_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="charts" data-args="chart=regional" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_icon_div"><div class="img_div img_24_div headphones_grey_24_img_div"></div></div><div class="card_text_div"><div>Most Streamed</div><div>Weekly in ' . $country . '.</div></div></div>
		<div class="card_div actions_div" data-actions="change_activity" data-activity="browse" data-subactivity="charts" data-args="chart=viral" data-highlightclass="card_highlight" onclick="void(0)"><div class="card_icon_div"><div class="img_div img_24_div share_grey_24_img_div"></div></div><div class="card_text_div"><div>Most Viral</div><div>Weekly in ' . $country . '.</div></div></div>
		</div>
		</div>
		</div>

		</div>

		</div>
	';
}

?>