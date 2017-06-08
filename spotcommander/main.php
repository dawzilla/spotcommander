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
define('project_version', 13.1);
define('project_serial', 13100);
define('project_website', 'http://www.olejon.net/code/spotcommander/');
define('project_website_https', 'https://www.olejon.net/code/spotcommander/');
define('project_developer', 'Ole Jon Bjørkum');
define('project_android_app_minimum_version', 7.8);

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
		$action = (spotify_is_running()) ? 'spotify_quit' : 'spotify_launch';
		clear_queue();
		remote_control($action, $data);
	}
	elseif($action == 'play_pause' || $action == 'pause')
	{
		remote_control($action, $data);
	}
	elseif($action == 'previous' || $action == 'next')
	{
		remote_control($action, $data);
		if(queue_is_empty()) echo 'queue_is_empty';
	}
	elseif($action == 'toggle_shuffle' || $action == 'toggle_repeat')
	{
		remote_control($action, $data);
		if(!spotify_is_running()) echo 'spotify_is_not_running';
	}
	elseif($action == 'adjust_spotify_volume' || $action == 'adjust_system_volume')
	{
		$volume = $data;
		$current_volume = get_current_volume();

		if(is_numeric($volume))
		{
			$volume = intval($volume);
			if($volume == 0) set_volume_before_mute($current_volume);
		}
		elseif($volume == 'mute')
		{
			if($current_volume == 0)
			{
				$volume = intval(get_volume_before_mute());
			}
			else
			{
				$volume = 0;
				set_volume_before_mute($current_volume);
			}
		}
		elseif($volume == 'down')
		{
			$volume = intval($current_volume - 10);
		}
		elseif($volume == 'up')
		{
			$volume = intval($current_volume + 10);
		}

		$volume = (spotify_is_running() && is_int($volume)) ? $volume : 50;

		if($volume < 0)
		{
			$volume = 0;
		}
		elseif($volume > 100)
		{
			$volume = 100;
		}

		remote_control($action, $volume);

		echo $volume;
	}
	elseif($action == 'play_uri' || $action == 'play_uri_from_playlist' || $action == 'shuffle_play_uri')
	{
		clear_queue();
		remote_control($action, $data);
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
		'project_android_app_minimum_version' => project_android_app_minimum_version,
		'project_error_code' => check_for_errors(),
		'project_is_authorized_with_spotify' => is_authorized_with_spotify(),
		'project_spotify_is_unsupported' => spotify_is_unsupported()
	);

	echo json_encode($global_variables);
}
elseif(isset($_GET['hostname']))
{
	echo php_uname('n');
}

?>