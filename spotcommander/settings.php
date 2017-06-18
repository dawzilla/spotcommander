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
$activity['title'] = 'Settings';
$activity['actions'][] = array('action' => array('Apply', 'check_white_24_img_div'), 'keys' => array('actions'), 'values' => array('apply_settings'));

?>

<div id="activity_inner_div" data-activitydata="<?php echo base64_encode(json_encode($activity)); ?>">

<div class="list_header_div"><div>Updates</div><div></div></div>

<div class="list_div">

<?php

$latest_version = (!empty($_COOKIE['latest_version']) && is_numeric($_COOKIE['latest_version'])) ? $_COOKIE['latest_version'] : 'unknown';

if(floatval($latest_version) > project_version)
{
	echo '
		<div class="setting_div actions_div" data-actions="open_external_activity" data-uri="' . project_website . '?download" onclick="void(0)">
		<div class="setting_text_div"><div><b>Update Available</b></div><div>You are running version ' . number_format(project_version, 1) . '. The latest version is ' . $latest_version . '. Tap here to download the latest version</div></div>
		</div>
	';
}
else
{
	echo '
		<div class="setting_div actions_div" data-actions="check_for_updates" onclick="void(0)">
		<div class="setting_text_div"><div>No Updates Available</div><div>You are running version ' . number_format(project_version, 1) . '. The latest version is ' . $latest_version . '. Tap here to check for updates now</div></div>
		</div>
	';
}

?>

</div>

<div class="list_header_div"><div>Links</div><div></div></div>

<div class="list_div">

<div class="setting_div actions_div" data-actions="open_external_activity" data-uri="<?php echo project_website; ?>?use" onclick="void(0)">
<div class="setting_text_div"><div>Help</div><div>Tap here to get usage help, like keyboard gestures and shortcuts</div></div>
</div>

<div class="setting_div actions_div" data-actions="open_external_activity" data-uri="<?php echo project_website; ?>?issues" onclick="void(0)">
<div class="setting_text_div"><div>Report Issue</div><div>Tap here to report an issue</div></div>
</div>

<div class="setting_div actions_div" data-actions="make_donation" onclick="void(0)">
<div class="setting_text_div"><div>Make a Donation</div><div>Tap here to support the development of <?php echo project_name; ?></div></div>
</div>

</div>

<div id="settings_android_app_div">

<div class="list_header_div"><div>Android Extras</div><div></div></div>

<div class="list_div">

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_keep_screen_on">Keep Screen On</label></div><div>Dim screen instead of turning it off automatically. Will drain the battery faster</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_keep_screen_on" name="settings_keep_screen_on"<?php echo setting_checkbox_status('settings_keep_screen_on'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_pause_on_incoming_call">Pause on Incoming Call</label></div><div>Pause music when receiving a call</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_pause_on_incoming_call" name="settings_pause_on_incoming_call"<?php echo setting_checkbox_status('settings_pause_on_incoming_call'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_pause_on_outgoing_call">Pause on Outgoing Call</label></div><div>Pause music when making a call</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_pause_on_outgoing_call" name="settings_pause_on_outgoing_call"<?php echo setting_checkbox_status('settings_pause_on_outgoing_call'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_flip_to_pause">Flip to Pause</label></div><div>Pause music when the device is flipped screen-down</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_flip_to_pause" name="settings_flip_to_pause"<?php echo setting_checkbox_status('settings_flip_to_pause'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_shake_to_skip">Shake to Skip</label></div><div>Shake device to play next track</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_shake_to_skip" name="settings_shake_to_skip"<?php echo setting_checkbox_status('settings_shake_to_skip'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div>Shake Sensitivity</div><div>Sensitivity for shake to skip</div></div>
<div class="setting_edit_div">

<?php

$setting = 'settings_shake_sensitivity';
$options = array('higher' => 'Higher', 'high' => 'High', 'normal' => 'Normal', 'low' => 'Low', 'lower' => 'Lower');

echo get_setting_dropdown($setting, $options);

?>

</div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_persistent_notification">Persistent Notification</label></div><div>Remote control when the app is not open. If this device is paired with an Android Wear device, a regular notification is shown anyway. Android 4.1 and newer</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_persistent_notification" name="settings_persistent_notification"<?php echo setting_checkbox_status('settings_persistent_notification'); ?>></div>
</div>

<div class="setting_div actions_div" data-actions="change_native_app_computer" onclick="void(0)">
<div class="setting_text_div"><div>Change Computer</div><div>Tap here to change computer</div></div>
</div>

</div>

</div>

<div class="list_header_div"><div>App Settings</div><div></div></div>

<div class="list_div">

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_hide_local_files">Hide Local Files</label></div><div>Hide local files from playlists</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_hide_local_files" name="settings_hide_local_files"<?php echo setting_checkbox_status('settings_hide_local_files'); ?>></div>
</div>

<div id="setting_notifications_div" class="setting_div">
<div class="setting_text_div"><div><label for="settings_notifications">Notifications</label></div><div>Notify when track changes. After checking the checkbox, you must allow notifications in your browser</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_notifications" name="settings_notifications"<?php echo setting_checkbox_status('settings_notifications'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_update_lyrics">Update Lyrics</label></div><div>When viewing lyrics, update lyrics when track changes</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_update_lyrics" name="settings_update_lyrics"<?php echo setting_checkbox_status('settings_update_lyrics'); ?>></div>
</div>

<div class="setting_div">
<div class="setting_text_div"><div><label for="settings_keyboard_shortcuts">Keyboard Shortcuts</label></div><div>Tap help in the menu for a complete list of keyboard shortcuts</div></div>
<div class="setting_edit_div"><input type="checkbox" class="setting_checkbox" id="settings_keyboard_shortcuts" name="settings_keyboard_shortcuts"<?php echo setting_checkbox_status('settings_keyboard_shortcuts'); ?>></div>
</div>

</div>

<div class="list_header_div"><div>Advanced</div><div></div></div>

<div class="list_div">

<div class="setting_div actions_div" data-actions="confirm_remove_all_playlists" onclick="void(0)">
<div class="setting_text_div"><div>Remove All Playlists</div><div>Start fresh. This will not delete your playlists from Spotify</div></div>
</div>

<div class="setting_div actions_div" data-actions="confirm_clear_cache" onclick="void(0)">
<div class="setting_text_div"><div>Clear Cache</div><div>Tap here to clear various cache files</div></div>
</div>

<div class="setting_div actions_div" data-actions="confirm_restore_to_default" onclick="void(0)">
<div class="setting_text_div"><div>Restore</div><div>Tap here to restore settings, messages, etc</div></div>
</div>

</div>

<div class="list_header_div"><div>About</div><div></div></div>

<div class="list_div">

<div class="setting_div actions_div" data-actions="open_external_activity" data-uri="<?php echo project_website; ?>?olejondotnet" onclick="void(0)">
<div class="setting_text_div"><div>By</div><div><?php echo project_developer; ?></div></div>
</div>

<div class="setting_div actions_div" data-actions="open_external_activity" data-uri="<?php echo project_website; ?>?license" onclick="void(0)">
<div class="setting_text_div"><div>License</div><div>GPLv3</div></div>
</div>

</div>

</div>