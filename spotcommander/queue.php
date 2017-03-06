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

if(isset($_GET['queue_uri']))
{
	echo queue_uri(rawurldecode($_POST['artist']), rawurldecode($_POST['title']), $_POST['uri']);
}
elseif(isset($_GET['queue_uris']))
{
	echo queue_uris($_POST['uris'], $_POST['randomly']);
}
elseif(isset($_GET['move']))
{
	echo move_queued_uri($_POST['id'], $_POST['sortorder'], $_POST['direction']);
}
elseif(isset($_GET['remove']))
{
	echo remove_from_queue($_POST['id'], $_POST['sortorder']);
}
elseif(isset($_GET['action']))
{
	echo queue_action($_POST['queue_action'], $_POST['sortorder']);
}
elseif(isset($_GET['clear']))
{
	echo clear_queue();
}
else
{
	$activity = array();
	$activity['project_version'] = project_version;
	$activity['title'] = 'Queue';
	$activity['actions'][] = array('action' => array('Clear', 'trash_white_24_img_div'), 'keys' => array('actions'), 'values' => array('clear_queue'));

	$tracks = get_db_rows('queue', "SELECT id, artist, title, uri, sortorder FROM queue ORDER BY sortorder, id", array('id', 'artist', 'title', 'uri', 'sortorder'));

	if(empty($tracks))
	{
		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>No queued tracks</div></div>

			</div>
		';
	}
	else
	{
		echo '
			<div id="activity_inner_div" data-activitydata="' . base64_encode(json_encode($activity)) . '">

			<div class="list_header_div"><div>Tracks</div><div></div></div>

			<div class="list_div">
		';

		foreach($tracks as $track)
		{
			$id = $track['id'];
			$artist = $track['artist'];
			$title = $track['title'];
			$uri = $track['uri'];
			$sortorder = $track['sortorder'];

			$type = get_uri_type($uri);

			$is_action = ($type != 'track' && $type != 'local');

			if($is_action)
			{
				$class = 'underline_text';
				$title = strtoupper($title);
			}
			else
			{
				$class = track_is_playing($uri, 'text');

				$actions_dialog = array();
				$actions_dialog['title'] = hsc($title);
				$actions_dialog['actions'][] = array('text' => 'Pause After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'pause', $sortorder));
				$actions_dialog['actions'][] = array('text' => 'Stop After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'stop', $sortorder));
				$actions_dialog['actions'][] = array('text' => 'Suspend After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'suspend', $sortorder));
				$actions_dialog['actions'][] = array('text' => 'Shut Down After Track', 'keys' => array('actions', 'queueaction', 'sortorder'), 'values' => array('hide_dialog queue_action', 'shutdown', $sortorder));
			}

			echo '
				<div class="list_item_div">
				<div title="' . hsc($artist . ' - ' . $title) . '" class="list_item_main_div actions_div" data-actions="toggle_list_item_actions" onclick="void(0)">
				<div class="list_item_main_actions_arrow_div"></div>
				<div class="list_item_main_inner_div">
				<div class="list_item_main_inner_icon_div"><div class="img_div img_24_div unfold_more_grey_24_img_div ' . track_is_playing($uri, 'icon') . '"></div></div>
				<div class="list_item_main_inner_text_div"><div class="list_item_main_inner_text_upper_div ' . $class . '">' . hsc($title) . '</div><div class="list_item_main_inner_text_lower_div">' . hsc($artist) . '</div></div>
				</div>
				</div>
				<div class="list_item_actions_div">
				<div class="list_item_actions_inner_div">
				<div title="Move Up" class="actions_div" data-actions="move_queued_uri" data-id="' . $id . '" data-sortorder="' . $sortorder . '" data-direction="up" data-highlightclass="dark_grey_highlight" data-highlightotherelement="div.list_item_main_actions_arrow_div" data-highlightotherelementparent="div.list_item_div" data-highlightotherelementclass="up_arrow_dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div up_grey_24_img_div"></div></div>
				<div title="Move Down" class="actions_div" data-actions="move_queued_uri" data-id="' . $id . '" data-sortorder="' . $sortorder . '" data-direction="down" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div down_grey_24_img_div"></div></div>
				<div title="Move to Top" class="actions_div" data-actions="move_queued_uri" data-id="' . $id . '" data-sortorder="' . $sortorder . '" data-direction="top" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div top_grey_24_img_div"></div></div>
				<div title="Remove" class="actions_div" data-actions="remove_from_queue" data-id="' . $id . '" data-sortorder="' . $sortorder . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div trash_grey_24_img_div"></div></div>
			';

			if(!$is_action) echo '<div title="More" class="show_actions_dialog_div actions_div" data-actions="show_actions_dialog" data-dialogactions="' . base64_encode(json_encode($actions_dialog)) . '" data-highlightclass="dark_grey_highlight" onclick="void(0)"><div class="img_div img_24_div more_grey_24_img_div"></div></div>';

			echo '</div></div></div>';
		}

		echo '</div></div>';
	}
}

?>