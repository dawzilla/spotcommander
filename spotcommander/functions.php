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

// Spotify

function spotify_is_running()
{
	$commands = array('spotify', 'spotify-bin', 'spotify-client');

	foreach($commands as $command)
	{
		if(trim(shell_exec('pgrep -x ' . $command . ' 1>/dev/null 2>&1 && echo 1')) == 1) return true;
	}

	return false;
}

function spotify_is_new()
{
	return (trim(file_get_contents(__DIR__ . '/run/spotify.version')) == 'new');
}

function get_spotify_username()
{
	return trim(file_get_contents(__DIR__ . '/run/spotify.username'));
}

function get_spotify_country()
{
	$country = trim(file_get_contents(__DIR__ . '/run/spotify.country'));
	return (empty($country)) ? 'US' : $country;
}

function get_spotify_token()
{
	$return = '';

	$token = file_get_contents(__DIR__ . '/run/spotify.token');

	if(!empty($token))
	{
		$token = json_decode($token, true);

		if(!empty($token['access_token']) && !empty($token['refresh_token']) && !empty($token['expires']))
		{
			$return = $token['access_token'];

			$time = time();
			$expires = intval($token['expires']);

			if($time > $expires)
			{
				$files = get_external_files(array(project_website . 'api/1/spotify/token/?refresh_token=' . $token['refresh_token']), null, null);
				$new_token = $files[0];

				if(!empty($new_token))
				{
					$new_token = json_decode($new_token, true);

					$write = array();
					$write['access_token'] = $new_token['access_token'];
					$write['refresh_token'] = $token['refresh_token'];
					$write['expires'] = time() + intval($new_token['expires_in']);

					file_write(__DIR__ . '/run/spotify.token', json_encode($write));

					$return = $new_token['access_token'];
				}
			}
		}
	}

	return $return;
}

function save_spotify_token($token)
{
	if(!empty($token))
	{
		$token = json_decode(base64_decode($token), true);

		$write = array();
		$write['access_token'] = $token['access_token'];
		$write['refresh_token'] = $token['refresh_token'];
		$write['expires'] = time() + intval($token['expires_in']);

		file_write(__DIR__ . '/run/spotify.token', json_encode($write));

		$profile = get_profile();

		if(empty($profile))
		{
			deauthorize_from_spotify();
		}
		else
		{
			file_write(__DIR__ . '/run/spotify.username', trim($profile['username']));
			file_write(__DIR__ . '/run/spotify.country', trim($profile['country']));
			file_write(__DIR__ . '/run/spotify.subscription', trim($profile['subscription']));
		}
	}
}

function deauthorize_from_spotify()
{
	$time = time();

	if(!empty($_COOKIE['last_refresh_playlists'])) setcookie('last_refresh_playlists', '', $time - 3600);
	if(!empty($_COOKIE['last_refresh_library'])) setcookie('last_refresh_library', '', $time - 3600);

	file_write(__DIR__ . '/run/spotify.token', '');
	file_write(__DIR__ . '/run/spotify.username', '');
	file_write(__DIR__ . '/run/spotify.country', '');

	clean_library('track');
	clean_library('album');
	clean_library('artist');

	clear_cache();
}

function is_authorized_with_spotify()
{
	$token = file_get_contents(__DIR__ . '/run/spotify.token');

	if(!empty($token))
	{
		$token = json_decode($token, true);
		if(!empty($token['access_token']) && !empty($token['refresh_token']) && !empty($token['expires'])) return true;
	}

	return false;
}

function is_spotify_subscription_premium()
{
	return (config_enable_spotify_premium_features) ? (trim(file_get_contents(__DIR__ . '/run/spotify.subscription')) == 'premium') : false;
}

// Daemon

function daemon_start($user)
{
	daemon_stop();

	sleep(2);

	$qdbus = daemon_qdbus_select();

	$log_file = __DIR__ . '/run/daemon.log';
	$user_file = __DIR__ . '/run/daemon.user';

	file_write($user_file, $user);

	$log = (daemon_spotify_is_new()) ? "\nSpotify is a new version.\n\n" : "\nSpotify is an old version.\n\n";

	file_write($log_file, $log);

	exec(__DIR__ . '/bin/spotcommander-remote 1>>' . $log_file . ' 2>>' . $log_file . ' &');
	exec(__DIR__ . '/bin/spotcommander-inotifywait ' . __DIR__ . ' 1>>' . $log_file . ' 2>>' . $log_file . ' &');
	exec(__DIR__ . '/bin/spotcommander-queue ' . __DIR__ . ' ' . $qdbus . ' 1>>' . $log_file . ' 2>>' . $log_file . ' &');
}

function daemon_stop()
{
	exec('pkill -f "php ' . __DIR__ . '/bin/spotcommander-remote"');
	exec('pkill -f "inotifywait -e modify ' . __DIR__ . '/run/daemon.inotify"');
	exec('pkill -f "dbus-monitor --profile type=\'signal\',path=\'/org/mpris/MediaPlayer2\',interface=\'org.freedesktop.DBus.Properties\',member=\'PropertiesChanged\'"');

	if(file_exists(daemon_socket)) unlink(daemon_socket);
}

function daemon_is_running()
{
	@$socket_connect = stream_socket_client('unix://' . daemon_socket, $errno, $errstr);

	if($socket_connect)
	{
		fwrite($socket_connect, json_encode(array('', '')) . "\n");
		$contents = stream_get_contents($socket_connect);
		fclose($socket_connect);
	}

	return $socket_connect;
}

function daemon_inotifywait($action)
{
	file_write(__DIR__ . '/run/daemon.inotify', $action);
}

function daemon_spotify_is_new()
{
	$version_file = __DIR__ . '/run/spotify.version';
	$version = (trim(shell_exec('grep -c autologin\.saved_credentials /home/' . daemon_user() . '/.config/spotify/prefs')) == 1) ? 'new' : 'old';

	file_write($version_file, $version);

	return ($version == 'new');
}

function daemon_user()
{
	return trim(file_get_contents(__DIR__ . '/run/daemon.user'));
}

function daemon_qdbus_select()
{
	$commands = array('qdbus', 'qdbus-qt4', '/usr/lib/x86_64-linux-gnu/qt4/bin/qdbus', '/usr/lib/i386-linux-gnu/qt4/bin/qdbus');

	foreach($commands as $command)
	{
		if(trim(shell_exec('command -v ' . $command . ' 1>/dev/null 2>&1 && echo 1')) == 1) return $command;
	}

	return '';
}

function daemon_logind_check($qdbus)
{
	return (trim(shell_exec($qdbus . ' --system org.freedesktop.login1 1>/dev/null 2>&1 && echo 1')) == 1);
}

function daemon_get_nowplaying()
{
	global $qdbus;

	$return = array();

	$return['playbackstatus'] = trim(shell_exec($qdbus . ' org.mpris.MediaPlayer2.spotify /org/mpris/MediaPlayer2 org.freedesktop.DBus.Properties.Get org.mpris.MediaPlayer2.Player PlaybackStatus'));

	if($return['playbackstatus'] == 'Playing' || $return['playbackstatus'] == 'Paused')
	{
		$metadata = trim(shell_exec($qdbus . ' org.mpris.MediaPlayer2.spotify /org/mpris/MediaPlayer2 org.freedesktop.DBus.Properties.Get org.mpris.MediaPlayer2.Player Metadata'));
		$lines = explode("\n", $metadata);

		foreach($lines as $line)
		{
			$line = trim($line);
			$line = explode(': ', $line, 2);

			if(!isset($line[1])) continue;

			$type = explode(':', $line[0]);
			$type = isset($type[1]) ? $type[1] : $type[0];

			$return[$type] = $line[1];
		}
	}

	return $return;
}

// Remote control

function remote_control($action, $data)
{
	if(spotify_is_running() || $action == 'spotify_launch' || $action == 'suspend_computer' || $action == 'shut_down_computer')
	{
		@$socket_connect = stream_socket_client('unix://' . daemon_socket, $errno, $errstr);

		if($socket_connect)
		{
			fwrite($socket_connect, json_encode(array($action, $data)) . "\n");
			$contents = stream_get_contents($socket_connect);
			fclose($socket_connect);

			return json_decode($contents, true);
		}
	}
}

function remote_control_spotify($api_uri, $action, $data)
{
	get_external_files(array($api_uri), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), array($action, $data));
	usleep(250000);
}

function get_current_seek_position()
{
	$files = get_external_files(array('https://api.spotify.com/v1/me/player?market=' . get_spotify_country()), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);
	$metadata = json_decode($files[0], true);

	return (empty($metadata)) ? 0 : intval($metadata['progress_ms']);
}

function get_current_volume()
{
	return intval(trim(file_get_contents(__DIR__ . '/run/volume.save')));
}

function set_current_volume($volume)
{
	file_write(__DIR__ . '/run/volume.save', intval($volume));
}

// Now playing

function get_nowplaying()
{
	return remote_control('get_nowplaying', '');
}

function track_is_playing($uri, $div)
{
	$uri = strtolower($uri);

	if($div == 'icon')
	{
		return (!empty($_COOKIE['nowplaying_uri']) && $_COOKIE['nowplaying_uri'] == $uri) ? 'volume_up_grey_24_img_div' : '';
	}
	elseif($div == 'text')
	{
		return (!empty($_COOKIE['nowplaying_uri']) && $_COOKIE['nowplaying_uri'] == $uri) ? 'bold_text' : '';
	}
}

// Cover art

function get_cover_art($uri, $size)
{
	$files = get_external_files(array('http://www.olejon.net/code/spotcommander/api/1/cover-art-spotify/?uri=' . $uri . '&token=' . get_spotify_token()), null, null);
	$metadata = json_decode($files[0], true);

	if(!empty($metadata['cover_art'][$size]))
	{
		$uri = str_replace('%253F', '%3F', $uri);

		$cache = get_cover_art_cache($size);

		$cache[$uri] = $metadata['cover_art'][$size];

		save_cover_art_cache($size, $cache);

		return $metadata['cover_art'][$size];
	}

	return '';
}

function get_cover_art_cache($size)
{
	return json_decode(file_get_contents(__DIR__ . '/run/cover-art-' . $size . '.cache'), true);
}

function save_cover_art_cache($size, $cache)
{
	file_write(__DIR__ . '/run/cover-art-' . $size . '.cache', json_encode($cache));
}

// Queue

function queue_uri($artist, $title, $uri)
{
	if(!spotify_is_running()) return 'spotify_is_not_running';

	$count = get_db_count('queue', "SELECT COUNT(id) as count FROM queue");
	$sortorder = $count + 1;

	db_exec('queue', "INSERT INTO queue (artist, title, uri, sortorder) VALUES ('" . sqlite_escape($artist) . "', '" . sqlite_escape($title) . "', '" . sqlite_escape($uri) . "', '" . sqlite_escape($sortorder) . "')");
}

function move_queued_uri($id, $sortorder, $direction)
{
	$sortorder = intval($sortorder);
	$count = get_db_count('queue', "SELECT COUNT(id) as count FROM queue");

	if($direction == 'up' && $sortorder != 1)
	{
		$sortorder = $sortorder - 1;

		db_exec('queue', "UPDATE queue SET sortorder = sortorder + 1 WHERE sortorder = " . $sortorder);
		db_exec('queue', "UPDATE queue SET sortorder = sortorder - 1 WHERE id = '" . sqlite_escape($id) . "'");
	}
	elseif($direction == 'down' && $sortorder != $count)
	{
		$sortorder = $sortorder + 1;

		db_exec('queue', "UPDATE queue SET sortorder = sortorder - 1 WHERE sortorder = " . $sortorder);
		db_exec('queue', "UPDATE queue SET sortorder = sortorder + 1 WHERE id = '" . sqlite_escape($id) . "'");
	}
	elseif($direction == 'top' && $sortorder != 1)
	{
		db_exec('queue', "UPDATE queue SET sortorder = sortorder + 1 WHERE sortorder < " . $sortorder);
		db_exec('queue', "UPDATE queue SET sortorder = 1 WHERE id = '" . sqlite_escape($id) . "'");
	}
}

function remove_from_queue($id, $sortorder)
{
	$sortorder = intval($sortorder);

	db_exec('queue', "DELETE FROM queue WHERE id = '" . sqlite_escape($id) . "'");
	db_exec('queue', "UPDATE queue SET sortorder = sortorder - 1 WHERE sortorder > " . $sortorder);
}

function queue_action($action, $sortorder)
{
	$artist = 'Pause music';
	$title = 'Pause';
	$uri = $action;

	if($action == 'stop')
	{
		$artist = 'Stop music';
		$title = 'Stop';
	}
	elseif($action == 'suspend')
	{
		$artist = 'Suspend computer';
		$title = 'Suspend';
	}
	elseif($action == 'shutdown')
	{
		$artist = 'Shut down computer';
		$title = 'Shut down';
	}

	if($sortorder == 'top')
	{
		$sortorder = 1;

		db_exec('queue', "UPDATE queue SET sortorder = sortorder + 1");
	}
	else
	{
		$sortorder = intval($sortorder) + 1;

		db_exec('queue', "UPDATE queue SET sortorder = sortorder + 1 WHERE sortorder >= '" . sqlite_escape($sortorder) . "'");
	}

	db_exec('queue', "INSERT INTO queue (artist, title, uri, sortorder) VALUES ('" . sqlite_escape($artist) . "', '" . sqlite_escape($title) . "', '" . sqlite_escape($uri) . "', '" . sqlite_escape($sortorder) . "')");
}

function clear_queue()
{
	db_exec('queue', "DELETE FROM queue");
}

function queue_is_empty()
{
	$count = get_db_count('queue', "SELECT COUNT(id) as count FROM queue");
	return ($count == 0);
}

// Playlists

function get_playlists($order1, $order2)
{
	return get_db_rows('playlists', "SELECT id, name, uri FROM playlists ORDER BY " . sqlite_escape($order1) . " COLLATE NOCASE, " . sqlite_escape($order2) . " COLLATE NOCASE", array('id', 'name', 'uri'));
}

function get_playlists_as_json($with_access)
{
	$playlists = get_playlists('name', 'uri');
	$username = get_spotify_username();

	$return = array();

	foreach($playlists as $playlist)
	{
		$name = $playlist['name'];
		$uri = $playlist['uri'];

		if($with_access)
		{
			if(preg_match('/^spotify:user:' . $username . ':playlist:/', $uri)) $return[$name] = $uri;
		}
		else
		{
			$return[$name] = $uri;
		}
	}

	return json_encode($return);
}

function get_playlist($playlist_uri)
{
	$playlist_id = uri_to_id($playlist_uri);

	$user = get_playlist_user($playlist_uri);

	$api_uri = 'https://api.spotify.com/v1/users/' . $user . '/playlists/' . $playlist_id . '?fields=images,name,owner(id),public,collaborative,snapshot_id,tracks(items,total,limit)&market=' . get_spotify_country();
	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	$files = get_external_files(array($api_uri), $api_headers, null);
	$metadata = json_decode($files[0], true);

	$return = null;
	$error = false;

	if(empty($metadata['name']))
	{
		$error = true;
	}
	else
	{
		$return = array();

		$return['name'] = $metadata['name'];
		$return['user'] = $metadata['owner']['id'];
		$return['public'] = ($metadata['public']) ? 'Yes' : 'No';
		$return['collaborative'] = ($metadata['collaborative']) ? 'Yes' : 'No';
		$return['snapshot_id'] = $metadata['snapshot_id'];
		$return['cover_art_uri'] = (empty($metadata['images'][0]['url'])) ? '' : $metadata['images'][0]['url'];

		$return['tracks'] = array();

		$tracks = $metadata['tracks']['items'];
		$tracks_limit = $metadata['tracks']['limit'];
		$tracks_count = $metadata['tracks']['total'];

		$return['tracks_count'] = $tracks_count;

		$total_length = 0;
		$i = 0;

		foreach($tracks as $track)
		{
			if(!empty($track['track']))
			{
				$return['tracks'][$i]['artist'] = (empty($track['track']['artists'][0]['name'])) ? 'Unknown' : get_artists($track['track']['artists']);
				$return['tracks'][$i]['title'] = (empty($track['track']['name'])) ? 'Unknown' : $track['track']['name'];
				$return['tracks'][$i]['album'] = (empty($track['track']['album']['name'])) ? 'Unknown' : $track['track']['album']['name'];
				$return['tracks'][$i]['length'] = convert_length(intval($track['track']['duration_ms']), 'ms');
				$return['tracks'][$i]['uri'] = ($track['track']['uri'] == 'spotify:track:null') ? 'spotify:local:' . urlencode($return['tracks'][$i]['artist']) . ':' . urlencode($return['tracks'][$i]['album']) . ':' . urlencode($return['tracks'][$i]['title']) . ':' : $track['track']['uri'];
				$return['tracks'][$i]['position'] = $i;
				$return['tracks'][$i]['added'] = (empty($track['added_at'])) ? '1970-01-01' : $track['added_at'];
				$return['tracks'][$i]['added_by'] = (empty($track['added_by']['id'])) ? 'Unknown' : $track['added_by']['id'];

				$total_length = $total_length + intval($track['track']['duration_ms']);
			}

			$i++;
		}

		if($tracks_count > $tracks_limit)
		{
			$api_uri = 'https://api.spotify.com/v1/users/' . $user . '/playlists/' . $playlist_id . '/tracks?fields=items&market=' . get_spotify_country();

			$pages = $tracks_count / $tracks_limit;
			$pages = ceil($pages - 1);

			$get_files = array();
			$offset = 0;

			for($n = 0; $n < $pages; $n++)
			{
				$offset = $offset + $tracks_limit;
				$get_files[$n] = $api_uri . '&offset=' . $offset . '&limit=' . $tracks_limit;
			}

			$files = get_external_files($get_files, $api_headers, null);

			foreach($files as $file)
			{
				$metadata = json_decode($file, true);

				if(empty($metadata['items']))
				{
					$error = true;
				}
				else
				{
					$tracks = $metadata['items'];

					foreach($tracks as $track)
					{
						if(!empty($track['track']))
						{
							$return['tracks'][$i]['artist'] = (empty($track['track']['artists'][0]['name'])) ? 'Unknown' : get_artists($track['track']['artists']);
							$return['tracks'][$i]['title'] = (empty($track['track']['name'])) ? 'Unknown' : $track['track']['name'];
							$return['tracks'][$i]['album'] = (empty($track['track']['album']['name'])) ? 'Unknown' : $track['track']['album']['name'];
							$return['tracks'][$i]['length'] = convert_length(intval($track['track']['duration_ms']), 'ms');
							$return['tracks'][$i]['uri'] = ($track['track']['uri'] == 'spotify:track:null') ? 'spotify:local:' . urlencode($return['tracks'][$i]['artist']) . ':' . urlencode($return['tracks'][$i]['album']) . ':' . urlencode($return['tracks'][$i]['title']) . ':' : $track['track']['uri'];
							$return['tracks'][$i]['position'] = $i;
							$return['tracks'][$i]['added'] = (empty($track['added_at'])) ? '1970-01-01' : $track['added_at'];
							$return['tracks'][$i]['added_by'] = (empty($track['added_by']['id'])) ? 'Unknown' : $track['added_by']['id'];

							$total_length = $total_length + intval($track['track']['duration_ms']);
						}

						$i++;
					}
				}
			}
		}

		$return['total_length'] = convert_length($total_length, 'ms');
	}

	return ($error) ? null : $return;
}

function refresh_spotify_playlists()
{
	$api_uri = 'https://api.spotify.com/v1/users/' . rawurlencode(get_spotify_username()) . '/playlists?limit=50';
	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	$files = get_external_files(array($api_uri), $api_headers, null);
	$metadata = json_decode($files[0], true);

	if(!empty($metadata['items']))
	{
		$last_id = get_db_rows('playlists', "SELECT id FROM playlists ORDER BY id DESC LIMIT 1", array('id'));
		$last_id = (empty($last_id[1]['id'])) ? 0 : intval($last_id[1]['id']);

		$playlists = $metadata['items'];
		$playlists_limit = $metadata['limit'];
		$playlists_count = $metadata['total'];

		$insert = array();
		$i = 0;

		foreach($playlists as $playlist)
		{
			$name = $playlist['name'];
			$uri = $playlist['uri'];

			if(playlist_is_saved(null, $uri) && !playlist_is_saved($name, $uri)) db_exec('playlists', "DELETE FROM playlists WHERE uri = '" . sqlite_escape($uri) . "'");

			if(playlist_is_saved($name, $uri)) continue;

			$insert[$i] = array('name' => $name, 'uri' => $uri);

			$i++;
		}

		if($playlists_count > $playlists_limit)
		{
			$pages = $playlists_count / $playlists_limit;
			$pages = ceil($pages - 1);

			$get_files = array();
			$offset = 0;

			for($n = 0; $n < $pages; $n++)
			{
				$offset = $offset + $playlists_limit;
				$get_files[$n] = $api_uri . '&offset=' . $offset;
			}

			$files = get_external_files($get_files, $api_headers, null);

			foreach($files as $file)
			{
				$metadata = json_decode($file, true);

				if(!empty($metadata['items']))
				{
					$playlists = $metadata['items'];

					foreach($playlists as $playlist)
					{
						$name = $playlist['name'];
						$uri = $playlist['uri'];

						if(playlist_is_saved(null, $uri) && !playlist_is_saved($name, $uri)) db_exec('playlists', "DELETE FROM playlists WHERE uri = '" . sqlite_escape($uri) . "'");

						if(playlist_is_saved($name, $uri)) continue;

						$insert[$i] = array('name' => $name, 'uri' => $uri);

						$i++;
					}
				}
			}
		}

		if(!empty($insert))
		{
			$insert_count = count($insert);
			$insert_limit = 500;

			$insert_id = $last_id + 1;

			$exec = "INSERT INTO playlists SELECT " . $insert_id . " AS id, '" . sqlite_escape($insert[0]['name']) . "' AS name, '" . sqlite_escape($insert[0]['uri']) . "' AS uri ";

			for($f = 1; $f < $insert_count; $f++)
			{
				$insert_id++;

				if($f < $insert_limit) $exec .= "UNION SELECT " . $insert_id . ", '" . sqlite_escape($insert[$f]['name']) . "', '" . sqlite_escape($insert[$f]['uri']) . "' ";
			}

			if($insert_count > $insert_limit) $i = $insert_limit;

			$exec = rtrim($exec);

			db_exec('playlists', $exec);
		}

		return $i;
	}
	elseif(!empty($metadata['href']))
	{
		return 'no_playlists';
	}

	return 'error';
}

function import_playlists($uris)
{
	if(is_string($uris)) $uris = explode(' ', $uris);

	$get_uris = array();
	$i = 0;

	foreach($uris as $uri)
	{
		$uri = url_to_uri($uri);

		if(get_uri_type($uri) == 'playlist' && !playlist_is_saved(null, $uri))
		{
			$user = get_playlist_user($uri);

			$get_uris[$i] = $uri;
			$get_files[$i] = 'https://api.spotify.com/v1/users/' . $user . '/playlists/' . uri_to_id($uri) . '?fields=name,uri&market=' . get_spotify_country();

			$i++;
		}
	}

	$error = false;
	$i = 0;

	if(!empty($get_uris))
	{
		$files = get_external_files($get_files, array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);

		foreach($files as $file)
		{
			$playlist = json_decode($file, true);

			if(empty($playlist['name']) || empty($playlist['uri']))
			{
				$error = true;
			}
			else
			{
				$name = $playlist['name'];
				$uri = $playlist['uri'];

				$user = explode(':', $uri);
				$user = $user[2];

				db_exec('playlists', "INSERT INTO playlists (name, uri) VALUES ('" . sqlite_escape($name) . "', '" . sqlite_escape($uri) . "')");

				$files = get_external_files(array('https://api.spotify.com/v1/users/' . $user . '/playlists/' . uri_to_id($uri) . '/followers'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token(), 'Content-Type: application/json'), array('PUT', json_encode(array('public' => false))));

				$i++;
			}
		}
	}

	return ($error) ? 'error' : $i;
}

function create_playlist($name, $make_public, $make_collaborative)
{
	$make_public = string_to_boolean($make_public);
	$make_collaborative = string_to_boolean($make_collaborative);

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . rawurlencode(get_spotify_username()) . '/playlists'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token(), 'Content-Type: application/json'), array('POST', json_encode(array('name' => $name, 'public' => $make_public, 'collaborative' => $make_collaborative))));
	$playlist = json_decode($files[0], true);

	if(!empty($playlist['name']))
	{
		$name = $playlist['name'];
		$uri = $playlist['uri'];

		db_exec('playlists', "INSERT INTO playlists (name, uri) VALUES ('" . sqlite_escape($name) . "', '" . sqlite_escape($uri) . "')");

		return hsc($name);
	}

	return 'error';
}

function edit_playlist($name, $uri, $make_public, $make_collaborative)
{
	$make_public = string_to_boolean($make_public);
	$make_collaborative = string_to_boolean($make_collaborative);

	$user = get_playlist_user($uri);

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . $user . '/playlists/' . uri_to_id($uri)), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token(), 'Content-Type: application/json'), array('PUT', json_encode(array('name' => $name, 'public' => $make_public, 'collaborative' => $make_collaborative))));
	$response = $files[0];

	if(is_int($response))
	{
		return ($response == 403) ? 'no_access' : 'error';
	}
	else
	{
		db_exec('playlists', "UPDATE playlists SET name = '" . sqlite_escape($name) . "' WHERE uri = '" . sqlite_escape($uri) . "'");

		refresh_playlist($uri);

		return hsc($name);
	}
}

function add_uris_to_playlist($uri, $uris)
{
	$user = get_playlist_user($uri);

	$type = get_uri_type($uris);

	if($type == 'artist')
	{
		$artist = get_artist($uris);

		if(empty($artist['tracks'])) return 'error';

		$tracks = $artist['tracks'];

		$uris = '';

		foreach($tracks as $track)
		{
			$uris .= $track['uri'] . ' ';
		}

		$uris = trim($uris);
	}
	elseif($type == 'album')
	{
		$album = get_album($uris);

		if(empty($album['discs'])) return 'error';

		$discs = $album['discs'];

		$uris = '';

		foreach($discs as $disc)
		{
			$tracks = $disc;

			foreach($tracks as $track)
			{
				$uris .= $track['uri'] . ' ';
			}
		}

		$uris = trim($uris);
	}

	$uris = explode(' ', $uris);

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . $user . '/playlists/' . uri_to_id($uri) . '/tracks'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token(), 'Content-Type: application/json'), array('POST', json_encode(array('uris' => $uris))));
	$response = $files[0];

	if(is_int($response))
	{
		return ($response == 403) ? 'no_access' : 'error';
	}
	else
	{
		refresh_playlist($uri);
		return count($uris);
	}
}

function delete_uris_from_playlists($uri, $uris, $positions, $snapshot_id)
{
	$user = get_playlist_user($uri);

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . $user . '/playlists/' . uri_to_id($uri) . '/tracks'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token(), 'Content-Type: application/json'), array('DELETE', json_encode(array('tracks' => array(array('uri' => $uris, 'positions' => array($positions))), 'snapshot_id' => $snapshot_id))));
	$response = $files[0];

	if(is_int($response))
	{
		return ($response == 403) ? 'no_access' : 'error';
	}
	else
	{
		refresh_playlist($uri);
	}
}

function refresh_playlist($uri)
{
	$cover_art_cache = get_cover_art_cache('small');

	if(!empty($cover_art_cache[$uri]))
	{
		unset($cover_art_cache[$uri]);
		save_cover_art_cache('small', $cover_art_cache);
	}

	$cover_art_cache = get_cover_art_cache('medium');

	if(!empty($cover_art_cache[$uri]))
	{
		unset($cover_art_cache[$uri]);
		save_cover_art_cache('medium', $cover_art_cache);
	}

	$cover_art_cache = get_cover_art_cache('large');

	if(!empty($cover_art_cache[$uri]))
	{
		unset($cover_art_cache[$uri]);
		save_cover_art_cache('large', $cover_art_cache);
	}
}

function remove_playlist($id, $uri)
{
	$user = explode(':', $uri);
	$user = $user[2];

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . $user . '/playlists/' . uri_to_id($uri) . '/followers'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), array('DELETE', null));
	$response = $files[0];

	if(is_int($response))
	{
		return 'error';
	}
	else
	{
		db_exec('playlists', "DELETE FROM playlists WHERE id = '" . sqlite_escape($id) . "'");
	}
}

function remove_all_playlists()
{
	db_exec('playlists', "DELETE FROM playlists");
}

function playlist_is_saved($name, $uri)
{
	$count = ($name == null) ? get_db_count('playlists', "SELECT COUNT(id) as count FROM playlists WHERE uri = '" . sqlite_escape($uri) . "'") : get_db_count('playlists', "SELECT COUNT(id) as count FROM playlists WHERE name = '" . sqlite_escape($name) . "' AND uri = '" . sqlite_escape($uri) . "'");
	return ($count != 0);
}

function is_facebook_user($user)
{
	return (is_numeric($user)) ? 'Facebook user ' . $user : rawurldecode($user);
}

// Library

function save($artist, $title, $uri)
{
	$type = get_uri_type($uri);

	if(is_saved($uri)) return remove($uri);

	$uri_id = uri_to_id($uri);

	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	if($type == 'track')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/tracks?ids=' . $uri_id), $api_headers, array('PUT', null));
	}
	elseif($type == 'artist')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/following?type=artist&ids=' . $uri_id), $api_headers, array('PUT', null));
	}
	elseif($type == 'album')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/albums?ids=' . $uri_id), $api_headers, array('PUT', null));
	}
	elseif($type == 'user')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/following?type=user&ids=' . $uri_id), $api_headers, array('PUT', null));
	}

	if(is_numeric($files[0])) return 'error';

	db_exec('library', "INSERT INTO library (type, artist, title, uri) VALUES ('" . sqlite_escape($type) . "', '" . sqlite_escape($artist) . "', '" . sqlite_escape($title) . "', '" . sqlite_escape($uri) . "')");
	return ucfirst($type) . ' saved to library';
}

function remove($uri)
{
	$type = get_uri_type($uri);

	$uri_id = uri_to_id($uri);

	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	if($type == 'track')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/tracks?ids=' . $uri_id), $api_headers, array('DELETE', null));
	}
	elseif($type == 'artist')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/following?type=artist&ids=' . $uri_id), $api_headers, array('DELETE', null));
	}
	elseif($type == 'album')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/albums?ids=' . $uri_id), $api_headers, array('DELETE', null));
	}
	elseif($type == 'user')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/following?type=user&ids=' . $uri_id), $api_headers, array('DELETE', null));
	}

	if(is_numeric($files[0])) return 'error';

	db_exec('library', "DELETE FROM library WHERE uri = '" . sqlite_escape($uri) . "'");
	return ucfirst($type) . ' removed from library';
}

function is_saved($uri)
{
	$type = get_uri_type($uri);

	$uri_id = uri_to_id($uri);

	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	if($type == 'track')
	{
		$count = get_db_count('library', "SELECT COUNT(id) as count FROM library WHERE uri = '" . sqlite_escape($uri) . "'");
		return ($count > 0);
	}
	elseif($type == 'artist')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/following/contains?type=artist&ids=' . $uri_id), $api_headers, null);
		$response = json_decode($files[0], true);

		return ($response[0]);
	}
	elseif($type == 'album')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/albums/contains?ids=' . $uri_id), $api_headers, null);
		$response = json_decode($files[0], true);

		return ($response[0]);
	}
	elseif($type == 'user')
	{
		$files = get_external_files(array('https://api.spotify.com/v1/me/following/contains?type=user&ids=' . $uri_id), $api_headers, null);
		$response = json_decode($files[0], true);

		return ($response[0]);
	}
}

function refresh_library()
{
	$country = get_spotify_country();

	$api_uris = array('https://api.spotify.com/v1/me/tracks?limit=50&market=' . $country, 'https://api.spotify.com/v1/me/following?type=artist&limit=50', 'https://api.spotify.com/v1/me/albums?limit=50&market=' . $country);
	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	$files = get_external_files($api_uris, $api_headers, null);
	$metadata_tracks = json_decode($files[0], true);
	$metadata_artists = json_decode($files[1], true);
	$metadata_albums = json_decode($files[2], true);

	$count_tracks = get_db_count('library', "SELECT COUNT(id) as count FROM library WHERE type = 'track'");

	if(empty($metadata_tracks['items']))
	{
		clean_library('track');
	}
	else
	{
		$last_id = get_db_rows('library', "SELECT id FROM library ORDER BY id DESC LIMIT 1", array('id'));
		$last_id = (empty($last_id[1]['id'])) ? 0 : intval($last_id[1]['id']);

		clean_library('track');

		$insert = array();
		$i = 0;

		$tracks = array_reverse($metadata_tracks['items']);
		$tracks_limit = $metadata_tracks['limit'];
		$tracks_count = $metadata_tracks['total'];

		foreach($tracks as $track)
		{
			$artist = get_artists($track['track']['artists']);
			$title = $track['track']['name'];
			$uri = $track['track']['uri'];

			$insert[$i] = array('artist' => $artist, 'title' => $title, 'uri' => $uri);

			$i++;
		}

		if($tracks_count > $tracks_limit)
		{
			$pages = $tracks_count / $tracks_limit;
			$pages = ceil($pages - 1);

			$get_files = array();
			$offset = 0;

			for($n = 0; $n < $pages; $n++)
			{
				$offset = $offset + $tracks_limit;
				$get_files[$n] = $api_uris[0] . '&offset=' . $offset;
			}

			$files = get_external_files($get_files, $api_headers, null);

			foreach($files as $file)
			{
				$metadata = json_decode($file, true);

				if(isset($metadata['items']))
				{
					$tracks = array_reverse($metadata['items']);

					foreach($tracks as $track)
					{
						$artist = get_artists($track['track']['artists']);
						$title = $track['track']['name'];
						$uri = $track['track']['uri'];

						$insert[$i] = array('artist' => $artist, 'title' => $title, 'uri' => $uri);

						$i++;
					}
				}
			}
		}

		if(!empty($insert))
		{
			$insert_count = count($insert);
			$insert_limit = 500;

			$insert_execs = ceil($insert_count / $insert_limit);

			$insert_id = $last_id + 1;
			$insert_offset = 0;

			for($n = 1; $n <= $insert_execs; $n++)
			{
				$exec = "INSERT INTO library SELECT " . $insert_id . " AS id, 'track' AS type, '" . sqlite_escape($insert[$insert_offset]['artist']) . "' AS artist, '" . sqlite_escape($insert[$insert_offset]['title']) . "' AS title, '" . sqlite_escape($insert[$insert_offset]['uri']) . "' AS uri ";

				$insert_amount = $insert_offset + $insert_limit;
				$insert_offset++;

				for($f = $insert_offset; $f < $insert_amount; $f++)
				{
					$insert_id++;

					if($f < $insert_count) $exec .= "UNION SELECT " . $insert_id . ", 'track', '" . sqlite_escape($insert[$f]['artist']) . "', '" . sqlite_escape($insert[$f]['title']) . "', '" . sqlite_escape($insert[$f]['uri']) . "' ";
				}

				$insert_offset = $insert_amount;
				$insert_id++;

				db_exec('library', rtrim($exec));
			}
		}
	}

	$count_artists = get_db_count('library', "SELECT COUNT(id) as count FROM library WHERE type = 'artist'");

	$artists_i = 0;

	if(empty($metadata_artists['artists']['items']))
	{
		clean_library('artist');
	}
	else
	{
		clean_library('artist');

		$artists = array_reverse($metadata_artists['artists']['items']);

		foreach($artists as $artist)
		{
			$name = $artist['name'];
			$uri = $artist['uri'];

			db_exec('library', "INSERT INTO library (type, artist, uri) VALUES ('artist', '" . sqlite_escape($name) . "', '" . sqlite_escape($uri) . "')");

			$artists_i++;
		}
	}

	$count_albums = get_db_count('library', "SELECT COUNT(id) as count FROM library WHERE type = 'album'");

	$albums_i = 0;

	if(empty($metadata_albums['items']))
	{
		clean_library('album');
	}
	else
	{
		clean_library('album');

		$albums = array_reverse($metadata_albums['items']);

		foreach($albums as $album)
		{
			$artist = $album['album']['artists'][0]['name'];
			$title = $album['album']['name'];
			$uri = $album['album']['uri'];

			db_exec('library', "INSERT INTO library (type, artist, title, uri) VALUES ('album', '" . sqlite_escape($artist) . "', '" . sqlite_escape($title) . "', '" . sqlite_escape($uri) . "')");

			$albums_i++;
		}
	}

	if(!empty($metadata_tracks['href']) && !empty($metadata_artists['artists']['href']) && !empty($metadata_albums['href']))
	{
		$new_tracks_count = $i - $count_tracks;
		$new_artists_count = $artists_i - $count_artists;
		$new_albums_count = $albums_i - $count_albums;

		if($new_tracks_count == 0 && $new_artists_count == 0 && $new_albums_count == 0)
		{
			return 'success';
		}
		elseif($new_tracks_count != 0 || $new_artists_count != 0 || $new_albums_count != 0)
		{
			return 'new_items';
		}

		return 'no_items';
	}

	return 'error';
}

function clean_library($type)
{
	db_exec('library', "DELETE FROM library WHERE type = '" . $type . "'");
}

function save_remove_icon($uri)
{
	return (is_saved($uri)) ? 'check' : 'plus';
}

// Search

function get_search($string)
{
	$return = null;

	$country = get_spotify_country();

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . rawurlencode($string), 'https://api.spotify.com/v1/search?q=' . rawurlencode($string) . '&type=track&market=' . $country . '&limit=50', 'https://api.spotify.com/v1/search?q=' . rawurlencode($string) . '&type=album&market=' . $country . '&limit=24', 'https://api.spotify.com/v1/search?q=' . rawurlencode($string) . '&type=artist&market=' . $country . '&limit=12', 'https://api.spotify.com/v1/search?q=' . rawurlencode($string) . '&type=playlist&market=' . $country . '&limit=50'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);

	$user = json_decode($files[0], true);
	$tracks = json_decode($files[1], true);
	$albums = json_decode($files[2], true);
	$artists = json_decode($files[3], true);
	$playlists = json_decode($files[4], true);

	if(isset($tracks['tracks']['items']) && isset($albums['albums']['items']) && isset($artists['artists']['items']) && isset($playlists['playlists']['items']))
	{
		$tracks = $tracks['tracks']['items'];
		$albums = $albums['albums']['items'];
		$artists = $artists['artists']['items'];
		$playlists = $playlists['playlists']['items'];

		$return = array();

		$return['user'] = array();

		if(!empty($user['id']))
		{
			$return['user']['username'] = $user['id'];
			$return['user']['name'] = (empty($user['display_name'])) ? 'Unknown' : $user['display_name'];
			$return['user']['image'] = (empty($user['images'][0]['url'])) ? '' : $user['images'][0]['url'];
			$return['user']['followers'] = $user['followers']['total'];
		}

		$return['tracks'] = array();

		if(!empty($tracks))
		{
			$i = 0;

			foreach($tracks as $track)
			{
				$return['tracks'][$i]['artist'] = get_artists($track['artists']);
				$return['tracks'][$i]['artist_uri'] = (empty($track['artists'][0]['uri'])) ? '' : $track['artists'][0]['uri'];
				$return['tracks'][$i]['title'] = $track['name'];
				$return['tracks'][$i]['length'] = convert_length($track['duration_ms'], 'ms');
				$return['tracks'][$i]['popularity'] = $track['popularity'] . ' %';
				$return['tracks'][$i]['uri'] = $track['uri'];
				$return['tracks'][$i]['album'] = $track['album']['name'];
				$return['tracks'][$i]['album_uri'] = $track['album']['uri'];

				$i++;
			}
		}

		$return['albums'] = array();

		if(!empty($albums))
		{
			$i = 0;

			foreach($albums as $album)
			{
				if(empty($album['images'][1]['url'])) continue;

				$return['albums'][$i]['title'] = $album['name'];
				$return['albums'][$i]['type'] = ucfirst($album['type']);
				$return['albums'][$i]['uri'] = $album['uri'];
				$return['albums'][$i]['cover_art'] = $album['images'][1]['url'];

				$i++;
			}
		}

		$return['artists'] = array();

		if(!empty($artists))
		{
			$i = 0;

			foreach($artists as $artist)
			{
				if(empty($artist['images'][1]['url'])) continue;

				$return['artists'][$i]['artist'] = $artist['name'];
				$return['artists'][$i]['popularity'] = $artist['popularity'] . ' %';
				$return['artists'][$i]['uri'] = $artist['uri'];
				$return['artists'][$i]['cover_art'] = $artist['images'][1]['url'];

				$i++;
			}
		}

		$return['playlists'] = array();

		if(!empty($playlists))
		{
			$i = 0;

			foreach($playlists as $playlist)
			{
				if(empty($playlist['images'][0]['url'])) continue;

				$return['playlists'][$i]['name'] = $playlist['name'];
				$return['playlists'][$i]['user'] = $playlist['owner']['id'];
				$return['playlists'][$i]['uri'] = $playlist['uri'];
				$return['playlists'][$i]['cover_art'] = $playlist['images'][0]['url'];

				$i++;
			}
		}
	}

	return $return;
}

function get_search_title($string)
{
	if(preg_match('/^(artist|track|album|year|genre|label|isrc|upc|tag):[^"][^ ]+[^"]$/', $string) || preg_match('/^(artist|track|album|genre|label|isrc|upc|tag):"[^"]+"$/', $string))
	{
		$string = explode(':', $string, 2);
		$type = ($string[0] == 'isrc' || $string[0] == 'upc') ? strtoupper($string[0]) : ucfirst($string[0]);
		$query = ucfirst(trim($string[1], '"'));

		$title = $type . ': ' . $query;
	}
	elseif(preg_match('/^(artist|track|album|year|genre|label|isrc|upc|tag):/', $string))
	{
		$title = $string;
	}
	else
	{
		$title = ucfirst($string);
	}

	return $title;
}

function get_search_type($string)
{
	$type = 'unknown';

	if(preg_match('/^tag:(new|"new")$/', $string))
	{
		$type = 'tag_new';
	}
	elseif(preg_match('/^track:/', $string))
	{
		$type = 'track';
	}
	elseif(preg_match('/^isrc:/', $string))
	{
		$type = 'isrc';
	}
	elseif(preg_match('/^upc:/', $string))
	{
		$type = 'upc';
	}

	return $type;
}

function save_recent_searches($string)
{
	$count = get_db_count('recent-searches', "SELECT COUNT(id) as count FROM recent_searches WHERE string = '" . sqlite_escape($string) . "' COLLATE NOCASE");
	if($count > 0) db_exec('recent-searches', "DELETE FROM recent_searches WHERE string = '" . sqlite_escape($string) . "' COLLATE NOCASE");

	$count = get_db_count('recent-searches', "SELECT COUNT(id) as count FROM recent_searches");
	if($count >= 10) db_exec('recent-searches', "DELETE FROM recent_searches WHERE id = (SELECT id FROM recent_searches ORDER BY id LIMIT 1)");

	db_exec('recent-searches', "INSERT INTO recent_searches (string) VALUES ('" . sqlite_escape($string) . "')");
}

function clear_recent_searches()
{
	db_exec('recent-searches', "DELETE FROM recent_searches");
}

// Artists

function get_artist($uri)
{
	$return = null;
	$error = false;

	$uri_id = uri_to_id($uri);

	$country = get_spotify_country();

	$artist_api_uri = 'https://api.spotify.com/v1/artists/' . $uri_id;
	$tracks_api_uri = 'https://api.spotify.com/v1/artists/' . $uri_id . '/top-tracks?country=' . $country;
	$albums_api_uri = 'https://api.spotify.com/v1/artists/' . $uri_id . '/albums?album_type=album,single&market=' . $country . '&limit=50';
	$related_artists_api_uri = 'https://api.spotify.com/v1/artists/' . $uri_id . '/related-artists';

	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	$files = get_external_files(array($artist_api_uri, $tracks_api_uri, $albums_api_uri, $related_artists_api_uri), $api_headers, null);

	$artist_metadata = json_decode($files[0], true);
	$artist_tracks = json_decode($files[1], true);
	$artist_albums = json_decode($files[2], true);
	$artist_related_artists = json_decode($files[3], true);

	if(empty($artist_metadata['uri']))
	{
		$error = true;
	}
	else
	{
		$return = array();
		$return['artist'] = $artist_metadata['name'];
		$return['popularity'] = $artist_metadata['popularity'] . ' %';
		$return['uri'] = $artist_metadata['uri'];
		$return['followers'] = number_format($artist_metadata['followers']['total'], 0, '.', ',');
		$return['cover_art_uri'] = (empty($artist_metadata['images'][0]['url'])) ? null : $artist_metadata['images'][0]['url'];
		$return['cover_art_width'] = (empty($artist_metadata['images'][0]['width'])) ? null : $artist_metadata['images'][0]['width'];
		$return['cover_art_height'] = (empty($artist_metadata['images'][0]['height'])) ? null : $artist_metadata['images'][0]['height'];

		$return['tracks'] = array();

		if(empty($artist_tracks['tracks']))
		{
			$error = true;
		}
		else
		{
			$tracks = $artist_tracks['tracks'];

			$i = 0;

			foreach($tracks as $track)
			{
				$return['tracks'][$i]['artist'] = get_artists($track['artists']);
				$return['tracks'][$i]['title'] = $track['name'];
				$return['tracks'][$i]['length'] = convert_length($track['duration_ms'], 'ms');
				$return['tracks'][$i]['popularity'] = $track['popularity'] . ' %';
				$return['tracks'][$i]['uri'] = $track['uri'];
				$return['tracks'][$i]['album'] = $track['album']['name'];
				$return['tracks'][$i]['album_uri'] = $track['album']['uri'];

				$i++;
			}
		}

		$albums_limit = $artist_albums['limit'];
		$albums_count = $artist_albums['total'];

		$return['albums_count'] = $albums_count;

		$return['albums'] = array();

		if(empty($artist_albums['items']))
		{
			$error = true;
		}
		else
		{
			$albums = $artist_albums['items'];

			$i = 0;

			foreach($albums as $album)
			{
				if(empty($album['images'][1]['url'])) continue;

				$return['albums'][$i]['title'] = $album['name'];
				$return['albums'][$i]['type'] = ucfirst($album['album_type']);
				$return['albums'][$i]['uri'] = $album['uri'];
				$return['albums'][$i]['cover_art'] = $album['images'][1]['url'];

				$i++;
			}

			if($albums_count > $albums_limit)
			{
				$pages = $albums_count / $albums_limit;
				$pages = ceil($pages - 1);

				$get_files = array();
				$offset = 0;

				for($n = 0; $n < $pages; $n++)
				{
					$offset = $offset + $albums_limit;
					$get_files[$n] = $albums_api_uri . '&offset=' . $offset;
				}

				$files = get_external_files($get_files, $api_headers, null);

				foreach($files as $file)
				{
					$metadata = json_decode($file, true);

					if(empty($metadata['items']))
					{
						$error = true;
					}
					else
					{
						$albums = $metadata['items'];

						foreach($albums as $album)
						{
							if(empty($album['images'][1]['url'])) continue;

							$return['albums'][$i]['title'] = $album['name'];
							$return['albums'][$i]['type'] = ucfirst($album['album_type']);
							$return['albums'][$i]['uri'] = $album['uri'];
							$return['albums'][$i]['cover_art'] = $album['images'][1]['url'];

							$i++;
						}
					}
				}
			}
		}

		$return['related_artists'] = array();

		if(empty($artist_related_artists['artists']))
		{
			$error = true;
		}
		else
		{
			$related_artists = $artist_related_artists['artists'];

			$total_results = 12;
			$i = 0;

			foreach($related_artists as $related_artist)
			{
				if(empty($related_artist['images'][1]['url'])) continue;

				$return['related_artists'][$i]['artist'] = $related_artist['name'];
				$return['related_artists'][$i]['popularity'] = $related_artist['popularity'] . ' %';
				$return['related_artists'][$i]['uri'] = $related_artist['uri'];
				$return['related_artists'][$i]['cover_art'] = $related_artist['images'][1]['url'];

				$i++;

				if($i == $total_results) break;
			}
		}
	}

	return ($error) ? null : $return;
}

function get_artists($artists)
{
	$return = '';

	foreach($artists as $artist)
	{
		$return .= (empty($artist['name'])) ? 'Unknown, ' : $artist['name'] . ', ';
	}

	return rtrim($return, ', ');
}

// Albums

function get_album($uri)
{
	$return = null;
	$error = false;

	$api_uri = 'https://api.spotify.com/v1/albums/' . uri_to_id($uri) . '?market=' . get_spotify_country();
	$api_headers = array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token());

	$files = get_external_files(array($api_uri), $api_headers, null);
	$metadata = json_decode($files[0], true);

	if(empty($metadata['name']) || empty($metadata['tracks']['items']))
	{
		$error = true;
	}
	else
	{
		$return = array();
		$return['artist'] = get_artists($metadata['artists']);
		$return['artist_uri'] = (empty($metadata['artists'][0]['uri'])) ? '' : $metadata['artists'][0]['uri'];
		$return['title'] = $metadata['name'];
		$return['type'] = ucfirst($metadata['album_type']);
		$return['released'] = (empty($metadata['release_date'])) ? 'Unknown' : $metadata['release_date'];
		$return['popularity'] = $metadata['popularity'] . ' %';
		$return['cover_art_uri'] = $metadata['images'][0]['url'];
		$return['cover_art_width'] = $metadata['images'][0]['width'];
		$return['cover_art_height'] = $metadata['images'][0]['height'];
		$return['countries'] = $metadata['available_markets'];

		$tracks = $metadata['tracks']['items'];
		$tracks_limit = $metadata['tracks']['limit'];
		$tracks_count = $metadata['tracks']['total'];

		$return['tracks_count'] = $tracks_count;

		$total_length = 0;
		$i = 0;

		foreach($tracks as $track)
		{
			$disc_number = $track['disc_number'];

			$return['discs'][$disc_number][$i]['artist'] = get_artists($track['artists']);
			$return['discs'][$disc_number][$i]['artist_uri'] = $track['artists'][0]['uri'];
			$return['discs'][$disc_number][$i]['title'] = $track['name'];
			$return['discs'][$disc_number][$i]['track_number'] = $track['track_number'];
			$return['discs'][$disc_number][$i]['disc_number'] = $disc_number;
			$return['discs'][$disc_number][$i]['length'] = convert_length($track['duration_ms'], 'ms');
			$return['discs'][$disc_number][$i]['uri'] = $track['uri'];

			$total_length = $total_length + intval($track['duration_ms']);

			$i++;
		}

		if($tracks_count > $tracks_limit)
		{
			$pages = $tracks_count / $tracks_limit;
			$pages = ceil($pages - 1);

			$get_files = array();
			$offset = 0;

			for($n = 0; $n < $pages; $n++)
			{
				$offset = $offset + $tracks_limit;
				$get_files[$n] = $api_uri . '/tracks?offset=' . $offset . '&limit=' . $tracks_limit;
			}

			$files = get_external_files($get_files, $api_headers, null);

			foreach($files as $file)
			{
				$metadata = json_decode($file, true);

				if(empty($metadata['items']))
				{
					$error = true;
					break;
				}
				else
				{
					$tracks = $metadata['items'];

					foreach($tracks as $track)
					{
						$disc_number = $track['disc_number'];

						$return['discs'][$disc_number][$i]['artist'] = get_artists($track['artists']);
						$return['discs'][$disc_number][$i]['artist_uri'] = $track['artists'][0]['uri'];
						$return['discs'][$disc_number][$i]['title'] = $track['name'];
						$return['discs'][$disc_number][$i]['track_number'] = $track['track_number'];
						$return['discs'][$disc_number][$i]['disc_number'] = $disc_number;
						$return['discs'][$disc_number][$i]['length'] = convert_length($track['duration_ms'], 'ms');
						$return['discs'][$disc_number][$i]['uri'] = $track['uri'];

						$total_length = $total_length + intval($track['duration_ms']);

						$i++;
					}
				}
			}
		}

		$return['total_length'] = convert_length($total_length, 'ms');
	}

	return ($error) ? null : $return;
}

function get_album_artist($uri)
{
	$album = get_album($uri);
	$return = (empty($album['artist_uri'])) ? null : $album['artist_uri'];

	return $return;
}

// Tracks

function get_tracks($uris)
{
	$urls = array();
	$i = 0;

	foreach($uris as $uri)
	{
		$urls[$i] = 'https://api.spotify.com/v1/tracks/' . uri_to_id($uri) . '?market=' . get_spotify_country();

		$i++;
	}

	$files = get_external_files($urls, array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);
	$tracks = $files;

	$return = array();
	$i = 0;

	foreach($tracks as $track)
	{
		$track = json_decode($track, true);
		$return[$i] = (empty($track['name']) || empty($track['uri'])) ? null : $track;

		$i++;
	}

	return $return;
}

function get_track_album($uri)
{
	$tracks = get_tracks(array($uri));
	return (empty($tracks[0]['album']['uri'])) ? null : $tracks[0]['album']['uri'];
}

function get_track_artist($uri)
{
	$tracks = get_tracks(array($uri));
	return (empty($tracks[0]['artists'][0]['uri'])) ? null : $tracks[0]['artists'][0]['uri'];
}

// Profile

function get_profile()
{
	$return = null;

	$files = get_external_files(array('https://api.spotify.com/v1/me', 'https://api.spotify.com/v1/me/top/tracks?limit=50&time_range=short_term'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);
	$profile = json_decode($files[0], true);
	$profile_top_tracks = json_decode($files[1], true);

	if(!empty($profile['id']) && isset($profile_top_tracks['items']))
	{
		$return = array();
		$return['username'] = $profile['id'];
		$return['name'] = (empty($profile['display_name'])) ? 'Unknown' : $profile['display_name'];
		$return['image'] = (empty($profile['images'][0]['url'])) ? null : $profile['images'][0]['url'];
		$return['country'] = $profile['country'];
		$return['subscription'] = $profile['product'];
		$return['followers'] = number_format($profile['followers']['total'], 0, '.', ',');
		$return['top_tracks'] = $profile_top_tracks['items'];
	}

	return $return;
}

// User

function get_user($username)
{
	$return = null;

	$files = get_external_files(array('https://api.spotify.com/v1/users/' . $username, 'https://api.spotify.com/v1/me/following/contains?type=user&ids=' . $username, 'https://api.spotify.com/v1/users/' . $username . '/playlists?limit=50'), array('Accept: application/json', 'Authorization: Bearer ' . get_spotify_token()), null);
	$profile = json_decode($files[0], true);
	$following = json_decode($files[1], true);
	$playlists = json_decode($files[2], true);

	if(!empty($profile['id']) && !empty($playlists['href']))
	{
		$return = array();

		$return['profile']['username'] = $profile['id'];
		$return['profile']['name'] = (empty($profile['display_name'])) ? 'Unknown' : $profile['display_name'];
		$return['profile']['image'] = (empty($profile['images'][0]['url'])) ? '' : $profile['images'][0]['url'];
		$return['profile']['followers'] = number_format($profile['followers']['total'], 0, '.', ',');
		$return['profile']['following'] = ($following[0]);

		$playlists = $playlists['items'];

		$return['playlists'] = array();
		$i = 0;

		foreach($playlists as $playlist)
		{
			if(!$playlist['public']) continue;

			$return['playlists'][$i]['name'] = $playlist['name'];
			$return['playlists'][$i]['uri'] = $playlist['uri'];

			$i++;
		}
	}

	return $return;
}

// Settings

function get_setting_dropdown($setting, $options)
{
	$return = '<select class="setting_select" name="' . $setting . '">';

	foreach($options as $option_value => $option_name)
	{
		$return .= '<option value="' . $option_value . '"' . setting_dropdown_status($setting, $option_value) . '>' . $option_name . '</option>';
	}

	$return .= '</select>';

	return $return;
}

function setting_checkbox_status($cookie)
{
	if(isset($_COOKIE[$cookie]) && $_COOKIE[$cookie] == 'true') return ' checked="checked"';
}

function setting_dropdown_status($cookie, $value)
{
	if(isset($_COOKIE[$cookie]) && $_COOKIE[$cookie] == $value) return ' selected="selected"';
}

// Clear cache

function clear_cache()
{
	file_write(__DIR__ . '/run/cover-art-small.cache', '');
	file_write(__DIR__ . '/run/cover-art-medium.cache', '');
	file_write(__DIR__ . '/run/cover-art-large.cache', '');
}

// Files

function file_write($file, $content)
{
	if(file_exists($file))
	{
		$fwrite = fopen($file, 'w');
		fwrite($fwrite, $content);
		fclose($fwrite);
	}
}

function delete_dir_files($dir)
{
	$files = glob($dir . '*');

	foreach($files as $file)
	{
		if(file_exists($file) && is_file($file)) unlink($file);
	}
}

function get_external_files($uris, $headers, $post)
{
	$count = count($uris);

	$mh = curl_multi_init();

	for($i = 0; $i < $count; $i++)
	{
		$uri = $uris[$i];
		$ua = (preg_match('/^https?:\/\/open\.spotify\.com\//', $uri)) ? 'Mozilla/5.0 (Android 7.1.2; Mobile; rv:54.0) Gecko/54.0 Firefox/54.0' : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:54.0) Gecko/20100101 Firefox/54.0';

		$ch[$i] = curl_init();

		curl_setopt($ch[$i], CURLOPT_URL, $uri);
		curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch[$i], CURLOPT_TIMEOUT_MS, 10000);
		curl_setopt($ch[$i], CURLOPT_USERAGENT, $ua);

		if(!empty($headers)) curl_setopt($ch[$i], CURLOPT_HTTPHEADER, $headers);

		if(!empty($post))
		{
			curl_setopt($ch[$i], CURLOPT_CUSTOMREQUEST, $post[0]);
			curl_setopt($ch[$i], CURLOPT_POSTFIELDS, $post[1]);
		}

		if(config_proxy)
		{
			curl_setopt($ch[$i], CURLOPT_PROXY, config_proxy_address);
			curl_setopt($ch[$i], CURLOPT_PROXYPORT, config_proxy_port);
		}

		curl_multi_add_handle($mh, $ch[$i]);
	}

	$active = null;

	do
	{
		$mrc = curl_multi_exec($mh, $active);
	}
	while($mrc == CURLM_CALL_MULTI_PERFORM);

	while($active && $mrc == CURLM_OK)
	{
		if(curl_multi_select($mh) === -1) usleep(100000);

		do
		{
			$mrc = curl_multi_exec($mh, $active);
		}
		while($mrc == CURLM_CALL_MULTI_PERFORM);
	}

	$return = array();

	for($i = 0; $i < $count; $i++)
	{
		$status = curl_getinfo($ch[$i], CURLINFO_HTTP_CODE);

		$return[$i] = ($status == 200 || $status == 201 || $status == 204) ? curl_multi_getcontent($ch[$i]) : $status;

		curl_multi_remove_handle($mh, $ch[$i]);
	}

	curl_multi_close($mh);

	return $return;
}

// Databases

function db_exec($db, $exec)
{
	$db = new SQLite3(__DIR__ . '/db/' . $db . '.db');
	@$db->exec($exec);
	$db->close();
}

function get_db_rows($db, $query, $columns)
{
	$db = new SQLite3(__DIR__ . '/db/' . $db . '.db');

	@$query = $db->query($query);

	if(!is_object($query)) return;

	$return = array();
	$w = 0;

	while($row = $query->fetchArray(SQLITE3_ASSOC))
	{
		$w++; 

		foreach($columns as $column)
		{
			$return[$w][$column] = $row[$column];
		}
	}

	$db->close();

	return $return;
}

function get_db_count($db, $query)
{
	$db = new SQLite3(__DIR__ . '/db/' . $db . '.db');

	@$query = $db->query($query);

	if(!is_object($query)) return 0;

	$return = $query->fetchArray(SQLITE3_ASSOC);

	$db->close();

	return intval($return['count']);
}

// Check stuff

function check_for_errors()
{
	$code = 0;

	if(!defined('config_enable_spotify_premium_features') || !defined('config_send_system_information') || !defined('config_proxy') || !defined('config_proxy_address') || !defined('config_proxy_port') || !is_bool(config_enable_spotify_premium_features) || !is_bool(config_send_system_information) || !is_bool(config_proxy) || !is_string(config_proxy_address) || !is_int(config_proxy_port))
	{
		$code = 1;
	}
	elseif(!daemon_is_running())
	{
		$code = 2;
	}
	elseif(!is_writeable(__DIR__ . '/db/playlists.db') || !is_writeable(__DIR__ . '/run/daemon.inotify'))
	{
		$code = 3;
	}
	elseif(daemon_qdbus_select() == '')
	{
		$code = 4;
	}

	return $code;
}

function check_for_updates()
{
	$spotify_is_new = (spotify_is_new()) ? 'New' : 'Old';
	$is_spotify_subscription_premium = (is_spotify_subscription_premium()) ? 'Yes' : 'No';
	$system_information = get_system_information();

	$files = get_external_files(array(project_website . 'api/1/latest-version/?version=' . rawurlencode(project_version) . '&spotify=' . rawurlencode($spotify_is_new) . '&premium=' . rawurlencode($is_spotify_subscription_premium) . '&uname=' . rawurlencode($system_information['uname']) . '&ua=' . rawurlencode($system_information['ua'])), null, null);
	$latest_version = trim($files[0]);

	return (preg_match('/^\d+\.\d+$/', $latest_version)) ? $latest_version : 'error';
}

function get_system_information()
{
	$uname = trim(shell_exec('uname -mrsv'));
	$ua = (empty($_SERVER['HTTP_USER_AGENT'])) ? 'Unknown' : trim($_SERVER['HTTP_USER_AGENT']);

	$return = array();

	$return['uname'] = (defined('config_send_system_information') && config_send_system_information) ? $uname : 'Disabled';
	$return['ua'] = (defined('config_send_system_information') && config_send_system_information) ? $ua : 'Disabled';

	return $return;
}

function get_country_name($code)
{
	$countries = array('AF' => 'Afghanistan', 'AX' => 'Aland Islands', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua And Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia And Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, Democratic Republic', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'Ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island & Mcdonald Islands', 'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic Of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle Of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KR' => 'Korea', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libyan Arab Jamahiriya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States Of', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'AN' => 'Netherlands Antilles', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory, Occupied', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena', 'KN' => 'Saint Kitts And Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin', 'PM' => 'Saint Pierre And Miquelon', 'VC' => 'Saint Vincent And Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome And Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia And Sandwich Isl.', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard And Jan Mayen', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad And Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks And Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UM' => 'United States Outlying Islands', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis And Futuna', 'EH' => 'Western Sahara', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe');
	return (empty($countries[$code])) ? $code : $countries[$code];
}

// Manipulate stuff

function convert_length($length, $from)
{
	$divide = 1;

	if($from == 'ms')
	{
		$divide = 1000;
	}
	elseif($from == 'mc')
	{
		$divide = 1000000;
	}

	$length = intval($length) / $divide;
	$minutes = $length / 60;

	if($minutes >= 60)
	{
		$hours = $minutes / 60;
		$minutes = ($hours - floor($hours)) * 60;
		$length = floor($hours) . ' h ' . round($minutes) . ' m';
	}
	else
	{
		$seconds = sprintf('%02s', $length % 60);
		$length = floor($minutes) . ':' . $seconds;
	}

	return $length;
}

function convert_popularity($popularity)
{
	return round(floatval($popularity) * 100) . ' %';
}

function string_to_boolean($string)
{
	return ($string == 'true');
}

function hsc($string)
{
	return htmlspecialchars(preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $string), ENT_QUOTES);
}

function hscd($string)
{
	return htmlspecialchars_decode($string, ENT_QUOTES);
}

function sqlite_escape($string)
{
	return SQLite3::escapeString($string);
}

// URIs

function get_uri_type($uri)
{
	$type = 'unknown';

	if(preg_match('/^spotify:user:[^:]+:playlist:\w{22}$/', $uri) || preg_match('/^https?:\/\/[^\.]+\.spotify\.com\/user\/[^\/]+\/playlist\/\w{22}$/', $uri))
	{
		$type = 'playlist';
	}
	elseif(preg_match('/^spotify:artist:\w{22}$/', $uri) || preg_match('/^https?:\/\/[^\.]+\.spotify\.com\/artist\/\w{22}$/', $uri))
	{
		$type = 'artist';
	}
	elseif(preg_match('/^spotify:album:\w{22}$/', $uri) || preg_match('/^https?:\/\/[^\.]+\.spotify\.com\/album\/\w{22}$/', $uri))
	{
		$type = 'album';
	}
	elseif(preg_match('/^spotify:track:\w{22}$/', $uri) || preg_match('/^https?:\/\/[^\.]+\.spotify\.com\/track\/\w{22}$/', $uri))
	{
		$type = 'track';
	}
	elseif(preg_match('/^spotify:local:/', $uri) || preg_match('/^https?:\/\/[^\.]+\.spotify\.com\/local\//', $uri))
	{
		$type = 'local';
	}
	elseif(preg_match('/^spotify:user:[^:]+$/', $uri) || preg_match('/^https?:\/\/[^\.]+\.spotify\.com\/user\/[^\/]+$/', $uri))
	{
		$type = 'user';
	}
	elseif(preg_match('/^spotify:app:genre:\w+$/', $uri) || preg_match('/^https?:\/\/spotify:app:genre:\w+$/', $uri))
	{
		$type = 'genre';
	}

	return $type;
}

function uri_to_url($uri)
{
	$type = get_uri_type($uri);

	if($type == 'playlist')
	{
		$uri = preg_replace('/^spotify:user:(.*?):playlist:(.*?)$/', 'http://open.spotify.com/user/$1/playlist/$2', $uri);
	}
	elseif($type == 'artist')
	{
		$uri = preg_replace('/^spotify:artist:(.*?)$/', 'http://open.spotify.com/artist/$1', $uri);
	}
	elseif($type == 'album')
	{
		$uri = preg_replace('/^spotify:album:(.*?)$/', 'http://open.spotify.com/album/$1', $uri);
	}
	elseif($type == 'track')
	{
		$uri = preg_replace('/^spotify:track:(.*?)$/', 'http://open.spotify.com/track/$1', $uri);
	}
	elseif($type == 'local')
	{
		$uri = preg_replace(array('/:/', '/^spotify\/local\/(.*?)$/'), array('/', 'http://open.spotify.com/local/$1'), $uri);
	}

	return $uri;
}

function url_to_uri($uri)
{
	$type = get_uri_type($uri);

	if($type == 'playlist')
	{
		$uri = preg_replace('/^https?:\/\/[^\.]+\.spotify\.com\/user\/(.*?)\/playlist\/(.*?)$/', 'spotify:user:$1:playlist:$2', $uri);
	}
	elseif($type == 'artist')
	{
		$uri = preg_replace('/^https?:\/\/[^\.]+\.spotify\.com\/artist\/(.*?)$/', 'spotify:artist:$1', $uri);
	}
	elseif($type == 'album')
	{
		$uri = preg_replace('/^https?:\/\/[^\.]+\.spotify\.com\/album\/(.*?)$/', 'spotify:album:$1', $uri);
	}
	elseif($type == 'track')
	{
		$uri = preg_replace('/^https?:\/\/[^\.]+\.spotify\.com\/track\/(.*?)$/', 'spotify:track:$1', $uri);
	}
	elseif($type == 'local')
	{
		$uri = preg_replace(array('/^https?:\/\/[^\.]+\.spotify\.com\/local\/(.*?)$/', '/\//'), array('spotify:local:$1', ':'), $uri);
	}

	return $uri;
}

function uri_to_id($uri)
{
	preg_match('/^spotify:.+:(.+)$/', $uri, $ids);
	return (empty($ids[1])) ? '' : $ids[1];
}

function get_playlist_user($uri)
{
	$user = explode(':', $uri);
	return $user[2];
}

?>