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

header('Content-Type: application/json');

require_once('main.php');

$nowplaying = get_nowplaying();

$playbackstatus = $nowplaying['playbackstatus'];

$play_pause = 'play';
$volume = 50;
$artist = 'Unknown';
$title = 'Spotify is Not Running';
$album = '';
$cover_art = 'img/no-cover-art-640.png?' . project_serial;
$uri = '';
$local = false;
$length = '';
$popularity = '';

$actions = array();
$actions[] = array('action' => array('Recently Played', ''), 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'recently-played', '', ''));
$actions[] = array('action' => array('Suspend Computer', ''), 'keys' => array('actions'), 'values' => array('confirm_suspend_computer'));
$actions[] = array('action' => array('Shut Down Computer', ''), 'keys' => array('actions'), 'values' => array('confirm_shut_down_computer'));

if(get_spotify_running())
{
	if($playbackstatus == 'Playing' || $playbackstatus == 'Paused')
	{
		$local = (get_uri_type($nowplaying['url']) == 'local');

		$play_pause = ($playbackstatus == 'Playing') ? 'pause' : 'play';
		$volume = get_current_volume();
		$artist = (empty($nowplaying['artist'])) ? $artist : $nowplaying['artist'];
		$title = (empty($nowplaying['title'])) ? $title : $nowplaying['title'];
		$album = (empty($nowplaying['album'])) ? $album : $nowplaying['album'];
		$uri = url_to_uri($nowplaying['url']);
		$length = convert_length($nowplaying['length'], 'mc');
		$cover_art = (empty($nowplaying['artUrl'])) ? 'img/no-cover-art-640.png?' . project_serial : str_replace(array('http:', 'open.spotify.com', '/thumb/'), array('https:', 'i.scdn.co', '/image/'), $nowplaying['artUrl']);
		$popularity = (empty($nowplaying['autoRating'])) ? 'Unknown' : convert_popularity($nowplaying['autoRating']);

		$details_dialog = array();
		$details_dialog['title'] = $title;
		$details_dialog['details'][] = array('detail' => 'Album', 'value' => $album);
		$details_dialog['details'][] = array('detail' => 'Length', 'value' => $length);
		$details_dialog['details'][] = array('detail' => 'Popularity', 'value' => $popularity);

		$actions_dialog = array();
		$actions_dialog['title'] = $title;
		$actions_dialog['actions'][] = array('text' => 'Add to Playlist', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog add_to_playlist', $title, $uri));
		$actions_dialog['actions'][] = array('text' => 'Go to Artist', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog browse_artist', $uri));
		$actions_dialog['actions'][] = array('text' => 'Search Artist', 'keys' => array('actions', 'string'), 'values' => array('hide_dialog get_search', rawurlencode('artist:"' . $artist . '"')));
		$actions_dialog['actions'][] = array('text' => 'Recommendations', 'keys' => array('actions', 'uri'), 'values' => array('hide_dialog get_recommendations', $uri));
		$actions_dialog['actions'][] = array('text' => 'Share', 'keys' => array('actions', 'title', 'uri'), 'values' => array('hide_dialog share_uri', $title, rawurlencode(uri_to_url($uri))));
		$actions_dialog['actions'][] = array('text' => 'YouTube', 'keys' => array('actions', 'uri'), 'values' => array('open_external_activity', 'https://www.youtube.com/results?search_query=' . rawurlencode($artist . ' ' . $title)));
		$actions_dialog['actions'][] = array('text' => 'Last.fm', 'keys' => array('actions', 'uri'), 'values' => array('open_external_activity', 'http://www.last.fm/music/' . urlencode($artist) . '/_/' . urlencode($title)));
		$actions_dialog['actions'][] = array('text' => 'Wikipedia', 'keys' => array('actions', 'uri'), 'values' => array('open_external_activity', 'https://en.wikipedia.org/wiki/Special:Search?search=' . rawurlencode($artist)));
		$actions_dialog['actions'][] = array('text' => 'Pause After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'pause', 'top'));
		$actions_dialog['actions'][] = array('text' => 'Stop After track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'stop', 'top'));
		$actions_dialog['actions'][] = array('text' => 'Suspend After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'suspend', 'top'));
		$actions_dialog['actions'][] = array('text' => 'Shut Down After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'shutdown', 'top'));
		$actions_dialog['actions'][] = array('text' => 'Suspend Computer', 'keys' => array('actions'), 'values' => array('hide_dialog confirm_suspend_computer'));
		$actions_dialog['actions'][] = array('text' => 'Shut Down Computer', 'keys' => array('actions'), 'values' => array('hide_dialog confirm_shut_down_computer'));

		$library_action = (saved($uri)) ? 'Remove from Library' : 'Save to Library';

		$actions = array();
		$actions[] = array('action' => array('Recently Played', ''), 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'recently-played', '', ''));
		$actions[] = array('action' => array('Show Queue', ''), 'keys' => array('actions', 'activity', 'subactivity', 'args'), 'values' => array('change_activity', 'queue', '', ''));
		$actions[] = array('action' => array($library_action, ''), 'keys' => array('actions', 'artist', 'title', 'uri'), 'values' => array('save', rawurlencode($artist), rawurlencode($title), $uri));
		$actions[] = array('action' => array('Lyrics', ''), 'keys' => array('actions', 'artist', 'title'), 'values' => array('get_lyrics', rawurlencode($artist), rawurlencode($title)));
		$actions[] = array('action' => array('Queue', ''), 'keys' => array('actions', 'artist', 'title', 'uri'), 'values' => array('queue_uri', rawurlencode($artist), rawurlencode($title), $uri));
		$actions[] = array('action' => array('Details', ''), 'keys' => array('actions', 'dialogdetails'), 'values' => array('show_details_dialog', base64_encode(json_encode($details_dialog))));
		$actions[] = array('action' => array('More&hellip;', ''), 'keys' => array('actions', 'dialogactions'), 'values' => array('show_actions_dialog', base64_encode(json_encode($actions_dialog))));
	}
	else
	{
		$title = 'No Music is Playing';
	}
}

echo json_encode(array(
	'project_version' => project_version,
	'play_pause' => $play_pause,
	'artist' => $artist,
	'title' => $title,
	'album' => $album,
	'cover_art' => $cover_art,
	'uri' => $uri,
	'local' => $local,
	'tracklength' => $length,
	'current_volume' => $volume,
	'actions' => $actions
));

?>