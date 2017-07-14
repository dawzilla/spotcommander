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

// Project

define('project_name', 'SpotCommander');
define('project_version', 13.4);
define('project_serial', 13400);
define('project_website', 'http://www.olejon.net/code/spotcommander/');
define('project_website_https', 'https://www.olejon.net/code/spotcommander/');
define('project_developer', 'Ole Jon Bjørkum');
define('project_android_app_minimum_version', 13.4);

// Configuration

require_once('config.php');

// Functions

require_once('functions.php');

// Daemon

define('daemon_socket', __DIR__ . '/run/daemon-user-' . daemon_user() . '.socket');

// Remote control

if(isset($_POST['action']))
{
	$action = $_POST['action'];
	$data = (isset($_POST['data'])) ? $_POST['data'] : '';

	if($action == 'launch_quit')
	{
		$action = (get_spotify_running()) ? 'spotify_quit' : 'spotify_launch';
		clear_queue();
		remote_control($action, $data);
	}
	elseif($action == 'play_pause')
	{
		remote_control($action, $data);
	}
	elseif($action == 'play')
	{
		if(get_spotify_subscription_premium())
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/play', 'PUT', null);
		}
		else
		{
			remote_control($action, $data);
		}
	}
	elseif($action == 'pause')
	{
		if(get_spotify_subscription_premium())
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/pause', 'PUT', null);
		}
		else
		{
			remote_control($action, $data);
		}
	}
	elseif($action == 'previous' || $action == 'next')
	{
		if(get_spotify_subscription_premium())
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/' . $action, 'POST', null);
		}
		else
		{
			remote_control($action, $data);
		}
	}
	elseif($action == 'seek_back' || $action == 'seek_forward')
	{
		if(!get_spotify_running())
		{
			echo 'spotify_not_running';
		}
		else
		{
			$current_seek_position = get_current_seek_position();
			$position = ($action == 'seek_back') ? $current_seek_position - 30000 : $current_seek_position + 30000;
			$position = ($position < 0) ? 0 : $position;

			remote_control_spotify('https://api.spotify.com/v1/me/player/seek?position_ms='. $position, 'PUT', null);
		}
	}
	elseif($action == 'toggle_shuffle' || $action == 'toggle_shuffle_on' || $action == 'toggle_shuffle_off')
	{
		if(!get_spotify_running())
		{
			echo 'spotify_not_running';
		}
		elseif($action == 'toggle_shuffle_on')
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/shuffle?state=true', 'PUT', null);
		}
		elseif($action == 'toggle_shuffle_off')
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/shuffle?state=false', 'PUT', null);
		}
		else
		{
			remote_control($action, $data);
		}
	}
	elseif($action == 'toggle_repeat' || $action == 'toggle_repeat_all' || $action == 'toggle_repeat_track' || $action == 'toggle_repeat_off')
	{
		if(!get_spotify_running())
		{
			echo 'spotify_not_running';
		}
		elseif($action == 'toggle_repeat_all')
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/repeat?state=context', 'PUT', null);
		}
		elseif($action == 'toggle_repeat_track')
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/repeat?state=track', 'PUT', null);
		}
		elseif($action == 'toggle_repeat_off')
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/repeat?state=off', 'PUT', null);
		}
		else
		{
			remote_control($action, $data);
		}
	}
	elseif($action == 'adjust_volume')
	{
		$current_volume = get_current_volume();

		if(is_numeric($data))
		{
			$data = intval($data);
		}
		elseif($data == 'mute')
		{
			$data = ($current_volume == 0) ? 50 : 0;
		}
		elseif($data == 'down')
		{
			$data = $current_volume - 10;
		}
		elseif($data == 'up')
		{
			$data = $current_volume + 10;
		}

		if($data < 0)
		{
			$data = 0;
		}
		elseif($data > 100)
		{
			$data = 100;
		}

		if(get_spotify_subscription_premium())
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/volume?volume_percent=' . $data, 'PUT', null);
		}
		else
		{
			remote_control($action, $data);
		}

		set_current_volume($data);

		echo $data;
	}
	elseif($action == 'play_uri')
	{
		clear_queue();

		if(get_spotify_subscription_premium())
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/play', 'PUT', json_encode(array('context_uri' => $data)));
		}
		else
		{
			remote_control($action, $data);
		}
	}
	elseif($action == 'play_uris')
	{
		clear_queue();

		$data = json_decode($data, true);

		$uri = $data['uri'];
		$uris = $data['uris'];

		if(get_spotify_subscription_premium())
		{
			remote_control_spotify('https://api.spotify.com/v1/me/player/play', 'PUT', json_encode(array('uris' => $uris, 'offset' => array('uri' => $uri))));
		}
		else
		{
			remote_control($action, $uri);
		}
	}
	elseif($action == 'clear_cache')
	{
		clear_cache();
	}
	elseif($action == 'suspend_computer' || $action == 'shut_down_computer')
	{
		remote_control($action, $data);
	}
}
elseif(isset($_GET['get_cover_art']))
{
	echo get_cover_art($_POST['uri'], $_POST['size']);
}
elseif(isset($_GET['check_for_updates']))
{
	echo check_for_updates();
}
elseif(isset($_GET['global_variables']))
{
	$global_variables = array(
		'project_name' => project_name,
		'project_version' => project_version,
		'project_serial' => project_serial,
		'project_website' => project_website,
		'project_website_https' => project_website_https,
		'project_developer' => project_developer,
		'project_error_code' => get_errors(),
		'project_authorized_with_spotify' => get_authorized_with_spotify(),
		'project_spotify_subscription_premium' => get_spotify_subscription_premium(),
		'project_spotify_premium_tracks_limit' => get_spotify_premium_tracks_limit(),
		'project_spotify_new' => get_spotify_new(),
		'project_android_app_minimum_version' => project_android_app_minimum_version
	);

	echo json_encode($global_variables);
}
elseif(isset($_GET['hostname']))
{
	echo php_uname('n');
}

?>