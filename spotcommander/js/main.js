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

// Global variables

function setGlobalVariables(global_variables)
{
	global_variables = $.parseJSON(global_variables);

	// Project
	project_name = global_variables.project_name;
	project_version = parseFloat(global_variables.project_version);
	project_serial = parseInt(global_variables.project_serial);
	project_website = global_variables.project_website;
	project_website_https = global_variables.project_website_https;
	project_developer = global_variables.project_developer;
	project_android_app_minimum_version = parseFloat(global_variables.project_android_app_minimum_version);
	project_is_authorized_with_spotify = global_variables.project_is_authorized_with_spotify;
	project_error_code = parseInt(global_variables.project_error_code);
	project_spotify_is_unsupported = global_variables.project_spotify_is_unsupported;

	// User agent
	ua = window.navigator.userAgent;

	// Feature detection
	ua_supports_csstransitions = (Modernizr.csstransitions && !shc(ua, 'DISABLE_CSSTRANSITIONS'));
	ua_supports_csstransforms3d = (Modernizr.csstransforms3d && !shc(ua, 'DISABLE_CSSTRANSFORMS3D'));
	ua_supports_inputtype_range = (Modernizr.inputtypes.range);
	ua_supports_notifications = (Modernizr.notification);
	ua_supports_touch = (Modernizr.touchevents && !shc(ua, 'CrOS'));

	if(ua_supports_touch)
	{
		pointer_event = 'touchend';
		pointer_down_event = 'touchstart';
		pointer_move_event = 'touchmove';
		pointer_cancel_event = 'touchcancel';
		pointer_up_event = 'touchend';
	}
	else
	{
		pointer_event = 'mouseup';
		pointer_down_event = 'mousedown';
		pointer_move_event = 'mousemove';
		pointer_cancel_event = 'mousecancel';
		pointer_up_event = 'mouseup';
	}

	// Device & browser
	ua_is_android = (shc(ua, 'Android'));
	ua_is_ios = (shc(ua, 'iPhone') || shc(ua, 'iPod') || shc(ua, 'iPad'));
	ua_is_os_x = (shc(ua, 'Macintosh; Intel Mac OS X'));
	ua_is_standalone = (ua_is_android && shc(ua, project_name) || ua_is_ios && ('standalone' in window.navigator) && window.navigator.standalone || ua_is_os_x && shc(ua, 'FluidApp'));
	ua_is_android_app = (ua_is_android && ua_is_standalone);
	ua_is_msie = (getMSIEVersion() >= 11);
	ua_is_pinnable_msie = (ua_is_msie && window.external && ('msIsSiteMode' in window.external));
	ua_is_integrated_msie = false;

	// Scrolling
	scrolling = false;
	scrolling_black_cover_div = false;
	scroll_position = {};
	scroll_position_save_disable = false;
	scrolling_last_position = 0;
	scrolling_last_list_header = null;

	// Pointer
	pointer_is_down = false;
	pointer_moved = false;
	pointer_moved_sensitivity = 10;

	// XHRs
	xhr_activity = new XMLHttpRequest();
	xhr_remote_control = new XMLHttpRequest();
	xhr_adjust_volume = new XMLHttpRequest();
	xhr_nowplaying = new XMLHttpRequest();
	xhr_cover_art = new XMLHttpRequest();

	// Intervals
	interval_nowplaying_auto_refresh = null;

	// Timeouts
	timeout_window_resize = null;
	timeout_scrolling = null;
	timeout_scroll_position_save_disable = null;
	timeout_activity_loading_1 = null;
	timeout_activity_loading_2 = null;
	timeout_activity_error = null;
	timeout_activity_loaded_input_text = null;
	timeout_remote_control = null;
	timeout_nowplaying_auto_refresh = null;
	timeout_nowplaying_error = null;
	timeout_nowplaying_volume_slider_1 = null;
	timeout_nowplaying_volume_slider_2 = null;
	timeout_show_toast = null;
	timeout_hide_toast_1 = null;
	timeout_hide_toast_2 = null;
	timeout_notification = null;

	// Events
	var prefix = Modernizr.prefixed('transition');

	if(prefix == 'WebkitTransition')
	{
		event_transitionend = 'webkitTransitionEnd';
	}
	else if(prefix == 'msTransition')
	{
		event_transitionend = 'MSTransitionEnd';
	}
	else
	{
		event_transitionend = 'transitionend';
	}

	// Notifications
	notification = null;

	// Activities
	activity_has_cover_art_opacity = false;
	activity_cover_art_opacity = 0;

	// Now playing
	nowplaying_refreshing = false;
	nowplaying_last_data = '';
	nowplaying_last_uri = '';
	nowplaying_cover_art_moving = false;

	// Cover art
	cover_art_rgb = '104, 159, 56';

	// Settings
	var settings = [
		{ setting: 'settings_nowplaying_refresh_interval' , value: '5' },
		{ setting: 'settings_volume_control' , value: 'spotify' },
		{ setting: 'settings_playlists_cache_time' , value: '3600' },
		{ setting: 'settings_hide_local_files' , value: 'false' },
		{ setting: 'settings_keyboard_shortcuts' , value: (ua_supports_touch) ? 'false' : 'true' },
		{ setting: 'settings_notifications' , value: 'false' },
		{ setting: 'settings_update_lyrics' , value: 'false' },
		{ setting: 'settings_keep_screen_on' , value: 'false' },
		{ setting: 'settings_pause_on_incoming_call' , value: 'false' },
		{ setting: 'settings_pause_on_outgoing_call' , value: 'false' },
		{ setting: 'settings_flip_to_pause' , value: 'false' },
		{ setting: 'settings_shake_to_skip' , value: 'false' },
		{ setting: 'settings_shake_sensitivity' , value: 'normal' },
		{ setting: 'settings_persistent_notification' , value: 'false' },
		{ setting: 'settings_sort_playlists' , value: 'default' },
		{ setting: 'settings_sort_playlist_tracks' , value: 'default' },
		{ setting: 'settings_sort_library_tracks' , value: 'default' },
		{ setting: 'settings_sort_library_albums' , value: 'default' },
		{ setting: 'settings_sort_library_artists' , value: 'default' },
		{ setting: 'settings_sort_library_users' , value: 'default' },
		{ setting: 'settings_sort_search_tracks' , value: 'default' }
	];

	for(var i = 0; i < settings.length; i++)
	{
		var cookie = { id: settings[i].setting, value: settings[i].value, expires: 3650 };
		if(!isCookie(cookie.id)) $.cookie(cookie.id, cookie.value, { expires: cookie.expires });
	}

	settings_nowplaying_refresh_interval = parseInt($.cookie('settings_nowplaying_refresh_interval'));
	settings_volume_control = $.cookie('settings_volume_control');
	settings_playlists_cache_time = $.cookie('settings_playlists_cache_time');
	settings_hide_local_files = stringToBoolean($.cookie('settings_hide_local_files'));
	settings_notifications = stringToBoolean($.cookie('settings_notifications'));
	settings_update_lyrics = stringToBoolean($.cookie('settings_update_lyrics'));
	settings_keyboard_shortcuts = stringToBoolean($.cookie('settings_keyboard_shortcuts'));
	settings_keep_screen_on = stringToBoolean($.cookie('settings_keep_screen_on'));
	settings_pause_on_incoming_call = stringToBoolean($.cookie('settings_pause_on_incoming_call'));
	settings_pause_on_outgoing_call = stringToBoolean($.cookie('settings_pause_on_outgoing_call'));
	settings_flip_to_pause = stringToBoolean($.cookie('settings_flip_to_pause'));
	settings_shake_to_skip = stringToBoolean($.cookie('settings_shake_to_skip'));
	settings_shake_sensitivity = $.cookie('settings_shake_sensitivity');
	settings_persistent_notification = stringToBoolean($.cookie('settings_persistent_notification'));
}

// Window load

$(window).load(function()
{
	// Settings
	$.ajaxSetup({ cache: false, timeout: 30000 });
	
	$.base64.utf8encode = true;
	$.base64.utf8decode = true;

	// Load
	$.get('main.php?global_variables', function(xhr_data)
	{
		setGlobalVariables(xhr_data);
		setCss();

		// Check for errors
		var error_code = checkForErrors();

		if(error_code != 0)
		{
			window.location.replace('error.php?code='+error_code);
			return;
		}

		// Resize
		$(window).on('resize', function()
		{
			setWidescreenCss();
			hideNowplayingOverflowActions();
			hideNowplaying();

			clearTimeout(timeout_window_resize);

			timeout_window_resize = setTimeout(function()
			{
				setCss();
				setCoverArtSize();
				setCardVerticalCoverArtSize();
			}, 500);
		});

		// Scrolling
		$(window).on('scroll', function()
		{
			scrolling = true;

			setCoverArtOpacity();

			clearTimeout(timeout_scrolling);

			timeout_scrolling = setTimeout(function()
			{
				scrolling = false;

				saveScrollPosition(null);
			}, 250);
		});

		// Pointer
		if(ua_supports_touch)
		{
			$(document).on(pointer_down_event, function(event)
			{
				pointer_points = event.originalEvent.touches.length;
				pointer_is_down = true;
				pointer_moved = (pointer_points != 1);
				pointer_gesture_done = false;

				pointer_start_x = event.originalEvent.touches[0].pageX;
				pointer_start_y = event.originalEvent.touches[0].pageY;

				pointer_edge = 25;
			});

			$(document).on(pointer_move_event, function(event)
			{
				if(typeof pointer_start_x == 'undefined' || typeof pointer_start_y == 'undefined') return;

				pointer_end_x = event.originalEvent.touches[0].pageX;
				pointer_end_y = event.originalEvent.touches[0].pageY;

				pointer_moved_x = pointer_end_x - pointer_start_x;
				pointer_moved_y = pointer_end_y - pointer_start_y;

				pointer_moved = (Math.abs(pointer_moved_x) > pointer_moved_sensitivity || Math.abs(pointer_moved_y) > pointer_moved_sensitivity);

				if(pointer_start_x < pointer_edge || isDisplayed('div#transparent_cover_div') || isDisplayed('div#black_cover_div') && !scrolling_black_cover_div || isDisplayed('div#black_cover_activity_div'))
				{
					event.preventDefault();
				}

				if(pointer_start_x < pointer_edge)
				{
					var gesture_trigger = window_width / 8;
					var gesture_block = 50;

					if(!pointer_gesture_done && pointer_moved_x > gesture_trigger && Math.abs(pointer_moved_y) < gesture_block)
					{
						showMenu();
						pointer_gesture_done = true;
					}
				}

				var gesture_trigger = - window_width / 8;
				var gesture_block = 50;

				if(!pointer_gesture_done && isDisplayed('div#black_cover_activity_div') && pointer_moved_x < gesture_trigger && Math.abs(pointer_moved_y) < gesture_block)
				{
					hideMenu();
					pointer_gesture_done = true;
				}

				if(ua_is_ios && ua_is_standalone)
				{
					if(pointer_start_x > window_width - pointer_edge)
					{
						event.preventDefault();

						var gesture_trigger = - window_width / 8;
						var gesture_block = 50;

						if(!pointer_gesture_done && !isDisplayed('div#black_cover_activity_div') && pointer_moved_x < gesture_trigger && Math.abs(pointer_moved_y) < gesture_block)
						{
							goBack();
							pointer_gesture_done = true;
						}
					}
				}
			});

			$(document).on(pointer_up_event, function()
			{
				pointer_is_down = false;
			});
		}
		else
		{
			$(document).on(pointer_down_event, function(event)
			{
				pointer_is_down = true;
				pointer_moved = false;

				pointer_start_x = event.pageX;
				pointer_start_y = event.pageY;
			});

			$(document).on(pointer_move_event, function(event)
			{
				if(typeof pointer_start_x == 'undefined' || typeof pointer_start_y == 'undefined') return;

				pointer_end_x = event.pageX;
				pointer_end_y = event.pageY;

				pointer_moved_x = pointer_end_x - pointer_start_x;
				pointer_moved_y = pointer_end_y - pointer_start_y;

				pointer_moved = (Math.abs(pointer_moved_x) > pointer_moved_sensitivity || Math.abs(pointer_moved_y) > pointer_moved_sensitivity);
			});

			$(document).on(pointer_up_event, function()
			{
				pointer_is_down = false;
			});
		}

		if(ua_supports_touch)
		{
			$(document).on(pointer_move_event, 'div#top_actionbar_div, div#bottom_actionbar_div, div#menu_div, div#black_cover_div', function(event)
			{
				event.preventDefault();
			});

			$(document).on(pointer_move_event, 'div#nowplaying_div', function(event)
			{
				if($(event.target).attr('id') != 'nowplaying_volume_slider') event.preventDefault();
			});

			$(document).on(pointer_down_event, 'div#bottom_actionbar_div', function(event)
			{
				pointer_bottombar_start_x = event.originalEvent.touches[0].pageX;
				pointer_bottombar_start_y = event.originalEvent.touches[0].pageY;
			});

			$(document).on(pointer_move_event, 'div#bottom_actionbar_div', function(event)
			{
				pointer_bottombar_end_x = event.originalEvent.touches[0].pageX;
				pointer_bottombar_end_y = event.originalEvent.touches[0].pageY;

				pointer_bottombar_moved_x = pointer_bottombar_end_x - pointer_bottombar_start_x;
				pointer_bottombar_moved_y = pointer_bottombar_end_y - pointer_bottombar_start_y;

				var gesture_trigger = - window_height / 8;
				var gesture_block = 50;

				if(!pointer_gesture_done && pointer_bottombar_moved_y < gesture_trigger && Math.abs(pointer_bottombar_moved_x) < gesture_block)
				{
					showNowplaying();
					pointer_gesture_done = true;
				}
			});

			$(document).on(pointer_down_event, 'div#nowplaying_div', function(event)
			{
				pointer_nowplaying_start_x = event.originalEvent.touches[0].pageX;
				pointer_nowplaying_start_y = event.originalEvent.touches[0].pageY;
			});

			$(document).on(pointer_move_event, 'div#nowplaying_div', function(event)
			{
				pointer_nowplaying_end_x = event.originalEvent.touches[0].pageX;
				pointer_nowplaying_end_y = event.originalEvent.touches[0].pageY;

				pointer_nowplaying_moved_x = pointer_nowplaying_end_x - pointer_nowplaying_start_x;
				pointer_nowplaying_moved_y = pointer_nowplaying_end_y - pointer_nowplaying_start_y;

				var gesture_trigger = window_height / 8;
				var gesture_block = 50;

				if(!pointer_gesture_done && !nowplaying_cover_art_moving && $(event.target).attr('id') != 'nowplaying_volume_slider' && pointer_nowplaying_moved_y > gesture_trigger && Math.abs(pointer_nowplaying_moved_x) < gesture_block)
				{	
					hideNowplaying();
					pointer_gesture_done = true;
				}
			});

			$(document).on(pointer_down_event, 'div#nowplaying_cover_art_div', function(event)
			{
				pointer_cover_art_start_x = event.originalEvent.touches[0].pageX;
				pointer_cover_art_start_y = event.originalEvent.touches[0].pageY;

				pointer_cover_art_moved_x = 0;
				pointer_cover_art_moved_y = 0;

				$(this).css('transition', '').css('transform', '').css('-webkit-transition', '').css('-webkit-transform', '').css('-moz-transition', '').css('-moz-transform', '').css('-ms-transition', '').css('-ms-transform', '').css('left', '');
			});

			$(document).on(pointer_move_event, 'div#nowplaying_cover_art_div', function(event)
			{
				pointer_cover_art_end_x = event.originalEvent.touches[0].pageX;
				pointer_cover_art_end_y = event.originalEvent.touches[0].pageY;

				pointer_cover_art_moved_x = pointer_cover_art_end_x - pointer_cover_art_start_x;
				pointer_cover_art_moved_y = pointer_cover_art_end_y - pointer_cover_art_start_y;

				var cover_art_move_treshold = 25;
				var cover_art_move = pointer_cover_art_moved_x;

				if(Math.abs(cover_art_move) > cover_art_move_treshold || nowplaying_cover_art_moving)
				{
					nowplaying_cover_art_moving = true;

					if(ua_supports_csstransitions && ua_supports_csstransforms3d)
					{
						var scale_variable = Math.abs(cover_art_move);
						var scale_constant = 0.5 / window_width;
						var scale = 1 - (scale_variable * scale_constant);

						$(this).css('transform', 'translate3d('+cover_art_move+'px, 0, 0) scale3d('+scale+', '+scale+', 1)').css('-webkit-transform', 'translate3d('+cover_art_move+'px, 0, 0) scale3d('+scale+', '+scale+', 1)').css('-moz-transform', 'translate3d('+cover_art_move+'px, 0, 0) scale3d('+scale+', '+scale+', 1)').css('-ms-transform', 'translate3d('+cover_art_move+'px, 0, 0) scale3d('+scale+', '+scale+', 1)');
					}
					else
					{
						$(this).css('left', ''+cover_art_move+'px');
					}
				}
			});

			$(document).on(pointer_up_event, 'div#nowplaying_cover_art_div', function(event)
			{
				nowplaying_cover_art_moving = false;

				var gesture_trigger = - window_width / 2;

				if(pointer_cover_art_moved_x < gesture_trigger)
				{
					remoteControl('next');
				}
				else
				{
					if(ua_supports_csstransitions && ua_supports_csstransforms3d)
					{
						$(this).css('transition', 'transform 0.25s cubic-bezier(0.190, 1.000, 0.220, 1.000)').css('transform', 'translate3d(0, 0, 0) scale3d(1, 1, 1)').css('-webkit-transition', '-webkit-transform 0.25s cubic-bezier(0.190, 1.000, 0.220, 1.000)').css('-webkit-transform', 'translate3d(0, 0, 0) scale3d(1, 1, 1)').css('-moz-transition', '-moz-transform 0.25s cubic-bezier(0.190, 1.000, 0.220, 1.000)').css('-moz-transform', 'translate3d(0, 0, 0) scale3d(1, 1, 1)').css('-ms-transition', '-ms-transform 0.25s cubic-bezier(0.190, 1.000, 0.220, 1.000)').css('-ms-transform', 'translate3d(0, 0, 0) scale3d(1, 1, 1)');
					}
					else
					{
						$(this).stop().animate({ 'left': '0' }, 250, 'easeOutExpo');
					}
				}
			});
		}
		else
		{

			// Right-click
			document.addEventListener('contextmenu', function(event)
			{
				event.preventDefault();
			}, false);

			$(document).on(pointer_event, 'div.list_item_main_inner_div', function(event)
			{
				if(pointer_moved || scrolling || typeof event.which == 'undefined' || event.which !== 3) return;

				var element = this;
				var parent = $(element).parent().parent();
				var data = $('div.show_actions_dialog_div', parent).data();

				if(typeof data != 'undefined') showActionsDialog($.parseJSON($.base64.decode(data.dialogactions)));
			});
		}

		// Highlight
		$(document).on(pointer_down_event, 'div.actions_div, span.actions_span', function()
		{
			var element = $(this);
			var data = element.data();

			if(element.attr('data-highlightclass')) element.addClass(data.highlightclass);

			if(element.attr('data-highlightotherelement'))
			{
				var parent = element.parents(data.highlightotherelementparent);
				$(data.highlightotherelement, parent).addClass(data.highlightotherelementclass);
			}
		});

		$(document).on(pointer_move_event+' '+pointer_cancel_event, 'div.actions_div, span.actions_span', function(event)
		{
			if(event.type == pointer_move_event && !pointer_moved && !scrolling) return;

			var element = $(this);
			var data = element.data();

			if(element.attr('data-highlightclass')) element.removeClass(data.highlightclass);

			if(element.attr('data-highlightotherelement'))
			{
				var parent = element.parents(data.highlightotherelementparent);
				$(data.highlightotherelement, parent).removeClass(data.highlightotherelementclass);
			}
		});

		$(document).on(pointer_up_event+' mouseout', 'div.actions_div, span.actions_span', function()
		{
			var element = $(this);
			var data = element.data();

			if(element.attr('data-highlightclass')) element.removeClass(data.highlightclass);

			if(element.attr('data-highlightotherelement'))
			{
				var parent = element.parents(data.highlightotherelementparent);
				$(data.highlightotherelement, parent).removeClass(data.highlightotherelementclass);
			}
		});

		// Actions
		$(document).on(pointer_event, 'div.actions_div, span.actions_span', function(event)
		{
			if(pointer_moved || scrolling || !ua_supports_touch && typeof(event.which) != 'undefined' && event.which !== 1) return;

			var element = $(this);
			var actions = element.data('actions').split(' ');
			var data = element.data();

			for(var i = 0; i < actions.length; i++)
			{
				var action = actions[i];

				if(action == 'change_activity')
				{
					changeActivity(data.activity, data.subactivity, data.args);
				}
				else if(action == 'replace_activity')
				{
					replaceActivity(data.activity, data.subactivity, data.args);
				}
				else if(action == 'refresh_activity')
				{
					refreshActivity();
				}
				else if(action == 'reload_activity')
				{
					reloadActivity();
				}
				else if(action == 'show_activity_overflow_actions')
				{
					showActivityOverflowActions();
				}
				else if(action == 'hide_activity_overflow_actions')
				{
					hideActivityOverflowActions();
				}
				else if(action == 'open_external_activity')
				{
					openExternalActivity(data.uri);
				}
				else if(action == 'scroll_to_top')
				{
					scrollToTop(true);
				}
				else if(action == 'scroll_to_next_list_header')
				{
					scrollToNextListHeader();
				}
				else if(action == 'toggle_menu')
				{
					toggleMenu();
				}
				else if(action == 'show_actions_dialog')
				{
					showActionsDialog($.parseJSON($.base64.decode(data.dialogactions)));
				}
				else if(action == 'show_details_dialog')
				{
					showDetailsDialog($.parseJSON($.base64.decode(data.dialogdetails)));
				}
				else if(action == 'hide_dialog')
				{
					hideDialog();
				}
				else if(action == 'hide_transparent_cover_div')
				{
					hideActivityOverflowActions();
					hideNowplayingOverflowActions();
				}
				else if(action == 'hide_black_cover_activity_div')
				{
					hideMenu();
				}
				else if(action == 'toggle_list_item_actions')
				{
					var list_item_actions_div = $('div.list_item_actions_div', element.parent());
					var is_hidden = !isDisplayed(list_item_actions_div);

					hideListItemActions();

					if(is_hidden) showListItemActions(element);
				}
				else if(action == 'show_all_list_items')
				{
					$(data.showitems).show();
					$(data.hideitem).hide();

					saveScrollPosition({ action: action, showitems: data.showitems, hideitem: data.hideitem });
				}
				else if(action == 'submit_form')
				{
					$(data.form).trigger('submit');
				}
				else if(action == 'confirm_authorize_with_spotify')
				{
					confirmAuthorizeWithSpotify();
				}
				else if(action == 'authorize_with_spotify')
				{
					authorizeWithSpotify();
				}
				else if(action == 'confirm_deauthorize_from_spotify')
				{
					confirmDeauthorizeFromSpotify();
				}
				else if(action == 'deauthorize_from_spotify')
				{
					deauthorizeFromSpotify();
				}
				else if(action == 'apply_settings')
				{
					applySettings();
				}
				else if(action == 'check_for_updates')
				{
					checkForUpdates('manual');
				}
				else if(action == 'make_donation')
				{
					makeDonation();
				}
				else if(action == 'change_native_app_computer')
				{
					changeNativeAppComputer()
				}
				else if(action == 'confirm_change_native_app_computer')
				{
					confirmChangeNativeAppComputer()
				}
				else if(action == 'confirm_remove_all_playlists')
				{
					confirmRemoveAllPlaylists();
				}
				else if(action == 'remove_all_playlists')
				{
					removeAllPlaylists();
				}
				else if(action == 'confirm_clear_cache')
				{
					confirmClearCache();
				}
				else if(action == 'clear_cache')
				{
					clearCache();
				}
				else if(action == 'confirm_restore_to_default')
				{
					confirmRestoreToDefault();
				}
				else if(action == 'restore_to_default')
				{
					restoreToDefault();
				}
				else if(action == 'confirm_suspend_computer')
				{
					confirmSuspendComputer();
				}
				else if(action == 'suspend_computer')
				{
					suspendComputer();
				}
				else if(action == 'confirm_shut_down_computer')
				{
					confirmShutDownComputer();
				}
				else if(action == 'shut_down_computer')
				{
					shutDownComputer();
				}
				else if(action == 'toggle_nowplaying')
				{
					toggleNowplaying();
				}
				else if(action == 'refresh_nowplaying')
				{
					startRefreshNowplaying();
					refreshNowplaying('manual');
				}
				else if(action == 'show_nowplaying_overflow_actions')
				{
					showNowplayingOverflowActions();
				}
				else if(action == 'hide_nowplaying_overflow_actions')
				{
					hideNowplayingOverflowActions();
				}
				else if(action == 'remote_control')
				{
					remoteControl(data.remotecontrol);
				}
				else if(action == 'adjust_volume')
				{
					adjustVolume(data.volume);
				}
				else if(action == 'adjust_volume_control')
				{
					adjustVolumeControl(data.volumecontrol);
				}
				else if(action == 'toggle_shuffle_repeat')
				{
					toggleShuffleRepeat(data.remotecontrol);
				}
				else if(action == 'play_uri')
				{
					playUri(data.uri);
				}
				else if(action == 'play_uri_from_playlist')
				{
					playUriFromPlaylist(data.playlisturi, data.uri);
				}
				else if(action == 'shuffle_play_uri')
				{
					shufflePlayUri(data.uri);
				}
				else if(action == 'show_cover_art_fab_animation')
				{
					showCoverArtFabActionAnimation();
				}
				else if(action == 'clear_recently_played')
				{
					clearRecentlyPlayed();
				}
				else if(action == 'queue_uri')
				{
					queueUri(data.artist, data.title, data.uri);
				}
				else if(action == 'queue_uris')
				{
					queueUris(data.uris, data.randomly);
				}
				else if(action == 'move_queued_uri')
				{
					moveQueuedUri(data.id, data.sortorder, data.direction);
				}
				else if(action == 'remove_from_queue')
				{
					removeFromQueue(data.id, data.sortorder);
				}
				else if(action == 'queue_action')
				{
					queueAction(data.queueaction, data.sortorder);
				}
				else if(action == 'clear_queue')
				{
					clearQueue();
				}
				else if(action == 'add_to_playlist')
				{
					addToPlaylist(data.title, data.uri);
				}
				else if(action == 'add_uris_to_playlist')
				{
					addUrisToPlaylist(data.uri, data.uris);
				}
				else if(action == 'delete_uris_from_playlist')
				{
					deleteUrisFromPlaylist(data.uri, data.uris, data.positions, data.snapshotid, data.div);
				}
				else if(action == 'browse_playlist')
				{
					browsePlaylist(data.uri, data.description);
				}
				else if(action == 'refresh_playlist')
				{
					refreshPlaylist(data.uri);
				}
				else if(action == 'confirm_refresh_spotify_playlists')
				{
					confirmRefreshSpotifyPlaylists();
				}
				else if(action == 'refresh_spotify_playlists')
				{
					refreshSpotifyPlaylists(false);
				}
				else if(action == 'import_playlist')
				{
					importPlaylists(data.uri);
				}
				else if(action == 'confirm_remove_playlist')
				{
					confirmRemovePlaylist(data.id, data.uri);
				}
				else if(action == 'remove_playlist')
				{
					removePlaylist(data.id, data.uri);
				}
				else if(action == 'get_user')
				{
					getUser(data.username);
				}
				else if(action == 'save')
				{
					save(data.artist, data.title, data.uri, element);
				}
				else if(action == 'remove')
				{
					remove(data.uri);
				}
				else if(action == 'confirm_refresh_library')
				{
					confirmRefreshLibrary();
				}
				else if(action == 'refresh_library')
				{
					refreshLibrary();
				}
				else if(action == 'get_search')
				{
					getSearch(data.string);
				}
				else if(action == 'clear_recent_searches')
				{
					clearRecentSearches();
				}
				else if(action == 'browse_album')
				{
					browseAlbum(data.uri);
				}
				else if(action == 'browse_artist')
				{
					browseArtist(data.uri);
				}
				else if(action == 'get_lyrics')
				{
					getLyrics(data.artist, data.title);
				}
				else if(action == 'get_recommendations')
				{
					getRecommendations(data.uri);
				}
				else if(action == 'browse_uri')
				{
					browseUri(data.uri);
				}
				else if(action == 'share_uri')
				{
					shareUri(data.title, data.uri);
				}
				else if(action == 'show_toast')
				{
					showToast(data.text, data.duration);
				}
				else if(action == 'resize_cover_art')
				{
					if(ua_supports_csstransitions) element.addClass('show_hide_cover_art_animation');

					var container_width = $('div#cover_art_div').width();
					var width = element.data('width');
					var height = element.data('height');
					var minimum_height = element.height();
					var resized = element.data('resized');

					if(resized)
					{
						element.height('').data('resized', false);
					}
					else
					{
						if(width > container_width)
						{
							var ratio = container_width / width;
							var height = Math.floor(height * ratio);

							if(height > minimum_height) element.height(height);
						}
						else if(isWidescreen())
						{
							element.height(height);
						}
						else
						{
							element.height(container_width);
						}

						element.data('resized', true);
					}
				}
				else if(action == 'set_cookie')
				{
					$.cookie(data.cookieid, data.cookievalue, { expires: parseInt(data.cookieexpires) });
				}
				else if(action == 'reload_app')
				{
					window.location.replace('.');
				}
			}	
		});

		// Forms
		$(document).on('submit', 'form', function(event)
		{
			event.preventDefault();

			var element = $(this);
			var id = element.attr('id');

			var value = $('input:text', element).val();
			var hint = $('input:text', element).data('hint');

			if(value == hint) return;

			if(id == 'import_playlists_form')
			{
				importPlaylists($('input:text#import_playlists_uris_input').val());
			}
			else if(id == 'create_playlist_form')
			{
				var name = $('input:text#create_playlist_name_input').val();
				var make_public = ($('input:checkbox#create_playlist_make_public_input').prop('checked')) ? 'true' : 'false';

				createPlaylist(name, make_public);
			}
			else if(id == 'edit_playlist_form')
			{
				var name = $('input:text#edit_playlist_name_input').val();
				var uri = $('input:hidden#edit_playlist_uri_input').val();
				var make_public = ($('input:checkbox#edit_playlist_make_public_input').prop('checked')) ? 'true' : 'false';

				editPlaylist(name, uri, make_public);
			}
			else if(id == 'search_form')
			{
				getSearch(encodeURIComponent($('input:text#search_input').val()));
			}
		});

		// Text fields
		$(document).on('focus blur keydown', 'input:text', function(event)
		{
			var element = $(this);
			var div = element.parent().parent();
			var value = element.val();
			var hint = element.data('hint');

			if(event.type == 'focusin' && value == hint)
			{
				div.addClass('input_text_focused_div');
				element.val('').addClass('focused_text_input');
			}
			else if(event.type == 'focusout' && value == '')
			{
				div.removeClass('input_text_focused_div');
				element.val(hint).removeClass('focused_text_input');
			}
			else if(event.type == 'keydown')
			{
				div.removeClass('input_text_error_div');
			}
		});

		// Volume slider
		$(document).on('input change', 'input#nowplaying_volume_slider', function(event)
		{
			if(event.type == 'input' && ua_is_msie || event.type == 'change' && !ua_is_msie) return;

			var element = $(this);
			var value = element.val();

			$('span#nowplaying_volume_level_span').html(value);

			autoRefreshNowplaying('reset');

			clearTimeout(timeout_nowplaying_volume_slider_1);
			clearTimeout(timeout_nowplaying_volume_slider_2);

			timeout_nowplaying_volume_slider_1 = setTimeout(function()
			{
				adjustVolume(value);
			}, 250);

			timeout_nowplaying_volume_slider_2 = setTimeout(function()
			{
				element.blur();
			}, 1000);
		});

		// Drop-down lists and checkboxes
		$(document).on('change', 'select, input:checkbox', function()
		{
			var element = $(this);

			if(element.hasClass('setting_select'))
			{
				var setting = element.attr('name');
				var value = element.val();

				saveSetting(setting, value);
			}
			else if(element.hasClass('setting_checkbox'))
			{
				var setting = element.attr('name');
				var value = (element.prop('checked')) ? 'true' : 'false';

				saveSetting(setting, value);

				if(setting == 'settings_notifications' && value == 'true') requestNotificationsPermission();
			}
		});

		// Hash change
		$(window).on('hashchange', function()
		{
			showActivity();
		});

		// Installed
		var cookie = { id: 'installed_'+project_version, value: getCurrentTime(), expires: 3650 };

		if(!isCookie(cookie.id)) $.cookie(cookie.id, cookie.value, { expires: cookie.expires });

		// Show activity 
		var cookie = { id: 'current_activity_'+project_version };

		if(ua_is_ios && ua_is_standalone && isCookie(cookie.id))
		{
			var a = $.parseJSON($.cookie(cookie.id));

			activityLoading();
			changeActivity(a.activity, a.subactivity, a.args);
		}
		else
		{
			showActivity();
		}

		// Keyboard shortcuts
		if(settings_keyboard_shortcuts) enableKeyboardShortcuts();

		// MSIE integration
		if(ua_is_pinnable_msie) integrateInMSIE();

		// Now playing
		setTimeout(function()
		{
			nativeAppLoad(false);

			startRefreshNowplaying();
			refreshNowplaying('manual');

			autoRefreshNowplaying('start');
		}, 1000);
	});
});