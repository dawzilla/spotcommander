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

// Activities

function showActivity()
{
	var a = getActivity();

	activityLoading();

	xhr_activity = $.get(a.activity+'.php?'+a.subactivity+'&'+a.args, function(xhr_data)
	{
		clearTimeout(timeout_activity_loading_1);
		clearTimeout(timeout_activity_loading_2);

		hideDiv('div#activity_div');

		setActivityContent(xhr_data);

		setCoverArtSize();
		setCardVerticalCoverArtSize();

		restoreScrollPosition();

		fadeInDiv('div#activity_div');

		activityLoaded();

		if(ua_is_ios && ua_is_standalone)
		{
			var cookie = { id: 'current_activity_'+project_version, value: JSON.stringify({ activity: a.activity, subactivity: a.subactivity, args: a.args, scroll_position: 0 }), expires: 1 };
			$.cookie(cookie.id, cookie.value, { expires: cookie.expires });
		}
	}).fail(function()
	{
		timeout_activity_error = setTimeout(function()
		{
			clearTimeout(timeout_activity_loading_1);
			clearTimeout(timeout_activity_loading_2);

			hideDiv('div#activity_div');

			setActivityTitle('Error');
			setActivityActions('<div title="Retry" class="actions_div" data-actions="reload_activity" data-highlightclass="light_green_highlight" onclick="void(0)"><div class="img_div img_24_div refresh_white_24_img_div"></div></div>');
			setActivityActionsVisibility('visible');

			var append = (ua_is_android_app) ? ' Long-press the back button on your device to go back to the list of computers.' : '';

			setActivityContent('<div id="activity_inner_div"><div id="activity_message_div"><div><div class="img_div img_48_div information_grey_48_img_div"></div></div><div>Request failed. Make sure you are connected. Tap the top right icon to retry.'+append+'</div></div></div>');

			fadeInDiv('div#activity_div');
		}, 1000);
	});
}

function activityLoading()
{
	xhr_activity.abort();

	hideActivityOverflowActions();
	hideMenu();
	hideNowplayingOverflowActions();
	hideNowplaying();

	hideDiv('div#activity_div');
	$('div#activity_fab_div').hide().removeClass('hide_activity_fab_animation show_activity_fab_animation show_activity_fab_bump_animation').css('bottom', '');

	scroll_position_save_disable = true;

	scrollToTop(false);

	clearTimeout(timeout_scroll_position_save_disable);

	timeout_scroll_position_save_disable = setTimeout(function()
	{
		scroll_position_save_disable = false;
	}, 500);

	clearTimeout(timeout_activity_loading_1);
	clearTimeout(timeout_activity_loading_2);

	timeout_activity_loading_1 = setTimeout(function()
	{
		setActivityTitle('Wait&hellip;');

		$('div#activity_div').empty();
	}, 500);

	if(!activity_has_cover_art_opacity)
	{
		timeout_activity_loading_2 = setTimeout(function()
		{
				setActivityActionsVisibility('visible');
				setActivityActions('<div><div class="img_div img_24_div loading_white_24_img_div"></div></div>');
		}, 1500);
	}
}

function activityLoaded()
{
	// All
	clearTimeout(timeout_activity_error);

	var data = getActivityData();

	if(typeof data.project_version != 'undefined') checkIfNewVersionIsInstalled(data.project_version);

	var title = (typeof data.title == 'undefined') ? 'Unknown' : data.title;

	setActivityTitle(title);

	if(typeof data.actions == 'undefined')
	{
		setActivityActionsVisibility('hidden');
	}
	else
	{
		if(data.actions.length == 1)
		{
			setActivityActions('<div title="'+data.actions[0].action[0]+'" class="actions_div" data-highlightclass="light_green_highlight" onclick="void(0)"><div class="img_div img_24_div '+data.actions[0].action[1]+'"></div></div>');

			for(var i = 0; i < data.actions[0].keys.length; i++)
			{
				$('div.actions_div', 'div#top_actionbar_inner_right_div > div').data(data.actions[0].keys[i], data.actions[0].values[i]);
			}
		}
		else
		{
			setActivityActions('<div title="More" class="actions_div" data-actions="show_activity_overflow_actions" data-highlightclass="light_green_highlight" onclick="void(0)"><div class="img_div img_24_div more_white_24_img_div"></div></div>');

			$('div#top_actionbar_overflow_actions_inner_div').empty();

			for(var i = 0; i < data.actions.length; i++)
			{
				$('div#top_actionbar_overflow_actions_inner_div').append('<div class="actions_div" data-highlightclass="light_grey_highlight" onclick="void(0)">'+data.actions[i].action[0]+'</div>');

				for(var f = 0; f < data.actions[i].keys.length; f++)
				{
					$('div.actions_div', 'div#top_actionbar_overflow_actions_inner_div').last().data(data.actions[i].keys[f], data.actions[i].values[f]);
				}
			}
		}

		setActivityActionsVisibility('visible');
	}

	if(typeof data.fab != 'undefined') showActivityFab(data.fab);

	getCoverArt();
	showMenuIndicator();

	checkForDialogs();
	checkForUpdates('auto');

	// Activities
	if(isActivity('playlists', '') && typeof data.is_authorized_with_spotify != 'undefined')
	{
		if(data.is_authorized_with_spotify)
		{
			var cookie = { id: 'last_refresh_playlists' };

			if(isCookie(cookie.id))
			{
				var last_refresh_playlists = parseInt($.cookie(cookie.id));

				if(getCurrentTime() - last_refresh_playlists > 1000 * 300) refreshSpotifyPlaylists(true);
			}
			else
			{
				showToast('Getting your playlists&hellip;', 2);

				refreshSpotifyPlaylists(true);
			}
		}
	}
	else if(isActivity('library', ''))
	{
		if(data.is_authorized_with_spotify)
		{
			var cookie = { id: 'last_refresh_library' };

			if(isCookie(cookie.id))
			{
				var last_refresh_library = parseInt($.cookie(cookie.id));

				if(getCurrentTime() - last_refresh_library > 1000 * 300) refreshLibrary();
			}
			else
			{
				showToast('Getting your library&hellip;', 2);

				refreshLibrary();
			}
		}
	}
	else if(isActivity('browse', ''))
	{
		var card_divs = $('div#browse_div div.card_div');

		if(ua_supports_csstransitions && ua_supports_csstransforms3d)
		{
			var last_index = card_divs.length - 1;

			setTimeout(function()
			{
				card_divs.addClass('prepare_browse_card_animation').each(function(index)
				{
					var element = $(this);
					var timeout = index * 75;

					setTimeout(function()
					{
						element.addClass('show_browse_card_animation');

						if(index == last_index)
						{
							element.one(event_transitionend, function()
							{
								fadeInDiv('div#browse_featured_playlists_div > div');

								card_divs.css('opacity', '1').removeClass('prepare_browse_card_animation show_browse_card_animation');
							});
						}
					}, timeout);
				});

				getFeaturedPlaylists(false);
			}, 250);
		}
		else
		{
			showDiv(card_divs);
			getFeaturedPlaylists(true);
		}
	}
	else if(isActivity('artist', ''))
	{
		getArtistBiography();
	}
	else if(isActivity('settings', ''))
	{
		if(ua_is_android_app) $('div#settings_android_app_div').show();
		if(!ua_supports_notifications) disableSetting('div#setting_notifications_div');
	}

	// Text fields
	if(!ua_supports_touch)
	{
		if($('input:text#create_playlist_name_input').length)
		{
			focusTextInput('input:text#create_playlist_name_input');
		}
		else if($('input:text#edit_playlist_name_input').length)
		{
			var element = $('input:text#edit_playlist_name_input');
			var length = element.val().length;

			focusTextInput(element);

			element[0].setSelectionRange(length, length);
		}
		else if($('input:text#search_input').length)
		{
			focusTextInput('input:text#search_input');
		}
	}
}

function changeActivity(activity, subactivity, args)
{
	var args = args.replace(/%26/g, '%2526').replace(/&amp;/g, '&').replace(/%2F/g, '%252F').replace(/%5C/g, '%255C');
	var hash = '#'+activity+'/'+subactivity+'/'+args+'/'+getCurrentTime();

	window.location.href=hash;
}

function replaceActivity(activity, subactivity, args)
{
	var args = args.replace(/%26/g, '%2526').replace(/&amp;/g, '&').replace(/%2F/g, '%252F').replace(/%5C/g, '%255C');
	var hash = '#'+activity+'/'+subactivity+'/'+args+'/'+getCurrentTime();

	window.location.replace(hash);
}

function reloadActivity()
{
	var a = getActivity();
	replaceActivity(a.activity, a.subactivity, a.args);
}

function refreshActivity()
{
	var a = getActivity();

	$.get(a.activity+'.php?'+a.subactivity+'&'+a.args, function(xhr_data)
	{
		if(isActivityWithArgs(a.activity, a.subactivity, a.args))
		{
			setActivityContent(xhr_data);

			getCoverArt();
			setCoverArtSize();
			setCoverArtOpacity();
			setCardVerticalCoverArtSize();
		}
	});
}

function getActivity()
{
	var hash = window.location.hash.slice(1);

	if(hash == '')
	{
		var a = getDefaultActivity();
		var activity = [a.activity, a.subactivity, a.args, a.time];
	}
	else
	{
		var activity = hash.split('/');
	}

	return { activity: activity[0], subactivity: activity[1], args: activity[2], time: activity[3] };
}

function getActivityData()
{
	return ($('div#activity_inner_div').length && $('div#activity_inner_div').attr('data-activitydata')) ? $.parseJSON($.base64.decode($('div#activity_inner_div').data('activitydata'))) : '';
}

function getDefaultActivity()
{
	var cookie = { id: 'hide_first_time_activity', value: 'true', expires: 3650 };

	if(!isCookie(cookie.id))
	{
		var activity = { activity: 'first-time', subactivity: '', args: '', time: 'default' };
		$.cookie(cookie.id, cookie.value, { expires: cookie.expires });
	}
	else
	{
		var activity = { activity: 'playlists', subactivity: '', args: '', time: 'default' };
	}

	return activity;
}

function setActivityTitle(title)
{
	$('div#top_actionbar_inner_center_div > div').attr('title', title).html(title);
}

function setActivityActions(actions)
{
	$('div#top_actionbar_inner_right_div > div').html(actions);
}

function setActivityActionsVisibility(visibility)
{
	$('div#top_actionbar_inner_right_div > div').css('visibility', visibility);
}

function setActivityContent(content)
{
	$('div#activity_div').html(content);
}

function isActivity(activity, subactivity)
{
	var a = getActivity();
	return (a.activity == activity && a.subactivity == subactivity);
}

function isActivityWithArgs(activity, subactivity, args)
{
	var a = getActivity();
	return (a.activity == activity && a.subactivity == subactivity && a.args == args);
}

function goBack()
{
	if(ua_is_ios && ua_is_standalone)
	{
		if(!isDisplayed('div#transparent_cover_div') && !isDisplayed('div#black_cover_div') && !isDisplayed('div#black_cover_activity_div') && !isVisible('div#nowplaying_div') && !textInputHasFocus()) history.back();
	}
	else
	{
		if(isDisplayed('div#dialog_div'))
		{
			closeDialog();
		}
		else if(isDisplayed('div#top_actionbar_overflow_actions_div'))
		{
			hideActivityOverflowActions();
		}
		else if(isDisplayed('div#nowplaying_actionbar_overflow_actions_div'))
		{
			hideNowplayingOverflowActions();
		}
		else if(!isWidescreen() && menuIsVisible())
		{
			hideMenu();
		}
		else if(isVisible('div#nowplaying_div'))
		{
			hideNowplaying();
		}
		else
		{
			history.back();
		}
	}
}

function openExternalActivity(uri)
{
	if(ua_is_android_app)
	{
		if(shc(uri, 'https://www.youtube.com/results?search_query='))
		{
			var query = decodeURIComponent(uri.replace('https://www.youtube.com/results?search_query=', ''));
			var query = Android.JSsearchApp('com.google.android.youtube', query);

			if(query == 0) showToast('App not installed', 4);
		}
		else if(shc(uri, 'market://'))
		{
			Android.JSopenUri(uri);
		}
		else
		{
			Android.JSopenUri(project_website+'?redirect&uri='+encodeURIComponent(uri));
		}
	}
	else
	{
		if(ua_is_android && shc(ua, 'Android 2') || ua_is_ios && ua_is_standalone)
		{
			var a = document.createElement('a');
			a.setAttribute('href', project_website+'?redirect&uri='+encodeURIComponent(uri));
			a.setAttribute('target', '_blank');
			var dispatch = document.createEvent('HTMLEvents');
			dispatch.initEvent('click', true, true);
			a.dispatchEvent(dispatch);
		}
		else
		{
			window.open(project_website+'?redirect&uri='+encodeURIComponent(uri));
		}
	}
}

// Menus

function toggleMenu()
{
	if(menuIsVisible())
	{
		hideMenu();
	}
	else
	{
		showMenu();
	}
}

function showMenu()
{
	if(menuIsVisible() || isDisplayed('div#transparent_cover_div') || isDisplayed('div#black_cover_div') || isDisplayed('div#black_cover_activity_div') || isVisible('div#nowplaying_div') || textInputHasFocus()) return;

	var menu_div = $('div#menu_div');
	var cover_div = $('div#black_cover_activity_div');
	var img_div = $('div#top_actionbar_inner_left_div > div > div > div');

	menu_div.css('visibility', 'visible');
	cover_div.show();

	setTimeout(function()
	{
		if(ua_supports_csstransitions && ua_supports_csstransforms3d)
		{
			menu_div.addClass('show_menu_animation');
			cover_div.addClass('show_black_cover_activity_div_animation');
			img_div.addClass('show_menu_img_animation');

			setTimeout(function()
			{
				img_div.removeClass('menu_white_24_img_div').addClass('forward_white_24_img_div');
			}, 25);
		}
		else
		{
			menu_div.stop().animate({ 'left': '0' }, 375, 'easeOutExpo');

			cover_div.stop().animate({ 'opacity': '0.5' }, 375, 'easeOutExpo', function()
			{
				img_div.removeClass('menu_white_24_img_div').addClass('back_white_24_img_div');
			});
		}
	}, 25);

	if(activity_has_cover_art_opacity) $('div#top_actionbar_inner_div').removeClass('top_actionbar_inner_opacity_div shadow_down_black_56_img_div').css('background-color', 'rgba('+cover_art_rgb+', 1)');

	nativeAppCanCloseCover();
}

function hideMenu()
{
	if(!menuIsVisible() || isDisplayed('div#dialog_div')) return;

	var menu_div = $('div#menu_div');
	var cover_div = $('div#black_cover_activity_div');
	var img_div = $('div#top_actionbar_inner_left_div > div > div > div');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		menu_div.addClass('hide_menu_animation').one(event_transitionend, function()
		{
			menu_div.css('visibility', '').removeClass('show_menu_animation hide_menu_animation');

			nativeAppCanCloseCover();
		});

		cover_div.addClass('hide_black_cover_activity_div_animation').one(event_transitionend, function()
		{
			cover_div.hide().removeClass('show_black_cover_activity_div_animation hide_black_cover_activity_div_animation');
		});

		img_div.addClass('hide_menu_img_animation').one(event_transitionend, function()
		{
			img_div.removeClass('show_menu_img_animation hide_menu_img_animation');
		});

		setTimeout(function()
		{
			img_div.removeClass('forward_white_24_img_div').addClass('menu_white_24_img_div');
		}, 25);
	}
	else
	{
		menu_div.stop().animate({ 'left': menu_div.data('cssleft') }, 375, 'easeOutExpo', function()
		{
			menu_div.css('visibility', '').css('left', '');

			img_div.removeClass('back_white_24_img_div').addClass('menu_white_24_img_div');

			nativeAppCanCloseCover();
		});

		cover_div.stop().animate({ 'opacity': '0' }, 375, 'easeOutExpo', function()
		{
			cover_div.hide().css('opacity', '');
		});
	}

	if(activity_has_cover_art_opacity && !isDisplayed('div#top_actionbar_shadow_div')) $('div#top_actionbar_inner_div').addClass('shadow_down_black_56_img_div').css('background-color', 'rgba('+cover_art_rgb+', '+activity_cover_art_opacity+')');
}

function showMenuIndicator()
{
	var items = $('div.menu_big_item_div');

	items.removeClass('bold_text');
	$('div.menu_big_item_indicator_div').removeClass('menu_big_item_indicator_active_div');

	var a = getActivity();
	var current_activity = a.activity;

	items.each(function()
	{
		var element = $(this);
		var activity = element.data('activity');

		if(activity == current_activity)
		{
			element.addClass('bold_text');
			$('div.menu_big_item_indicator_div', element).addClass('menu_big_item_indicator_active_div');
		}
	});
}

function menuIsVisible()
{
	return (isWidescreen() && isVisible('div#menu_div') || isVisible('div#menu_div'))
}

function toggleActivityOverflowActions()
{
	if(isDisplayed('div#top_actionbar_overflow_actions_div'))
	{
		hideActivityOverflowActions();
	}
	else
	{
		showActivityOverflowActions();
	}
}

function showActivityOverflowActions()
{
	if(isDisplayed('div#top_actionbar_overflow_actions_div')) return;

	hideMenu();

	$('div#transparent_cover_div').show();

	var div = $('div#top_actionbar_overflow_actions_div');

	div.show();

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		div.addClass('prepare_overflow_actions_animation');

		setTimeout(function()
		{
			div.addClass('show_overflow_actions_animation');

			var divs = $('div#top_actionbar_overflow_actions_inner_div > div');

			divs.each(function(index)
			{
				var element = this;
				var timeout = index * 50;

				setTimeout(function()
				{
					fadeInDiv(element);
				}, timeout);
			});
		}, 25);
	}
	else
	{
		fadeInDiv('div#top_actionbar_overflow_actions_div');
		showDiv('div#top_actionbar_overflow_actions_inner_div > div');
	}

	nativeAppCanCloseCover();
}

function hideActivityOverflowActions()
{
	if(!isDisplayed('div#top_actionbar_overflow_actions_div')) return;

	var div = $('div#top_actionbar_overflow_actions_div');

	div.hide();

	$('div', div).removeClass('light_grey_highlight');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		div.removeClass('prepare_overflow_actions_animation show_overflow_actions_animation');

		hideDiv('div#top_actionbar_overflow_actions_inner_div > div');
	}
	else
	{
		hideDiv('div#top_actionbar_overflow_actions_div');
		hideDiv('div#top_actionbar_overflow_actions_inner_div > div');
	}

	$('div#transparent_cover_div').hide();

	nativeAppCanCloseCover();
}

// Remote control

function remoteControl(action)
{
	xhr_remote_control.abort();

	clearTimeout(timeout_remote_control);

	if(action == 'launch_quit' || action == 'next' || action == 'previous') startRefreshNowplaying();

	xhr_remote_control = $.post('main.php?'+getCurrentTime(), { action: action }, function(xhr_data)
	{
		if(action == 'launch_quit')
		{
			refreshNowplaying('manual');
		}
		else if(action == 'play_pause' || action == 'pause')
		{
			refreshNowplaying('silent');
		}
		else if(action == 'next' || action == 'previous')
		{
			var timeout = (xhr_data == 'queue_is_empty') ? 500 : 1000;

			timeout_remote_control = setTimeout(function()
			{
				refreshNowplaying('manual');
			}, timeout);
		}
	});
}

function adjustVolume(volume)
{
	xhr_adjust_volume.abort();

	autoRefreshNowplaying('reset');

	var cookie = { id: 'settings_volume_control' };
	var control = $.cookie(cookie.id);
	var action = (control == 'spotify') ? 'adjust_spotify_volume' : 'adjust_system_volume';

	xhr_adjust_volume = $.post('main.php?'+getCurrentTime(), { action: action, data: volume }, function(xhr_data)
	{
		$('input#nowplaying_volume_slider').val(xhr_data);
		$('span#nowplaying_volume_level_span').html(xhr_data);

		if(!isVisible('#nowplaying_div'))
		{
			if(xhr_data == 0)
			{
				showToast('Volume muted', 1);
			}
			else
			{
				showToast('Volume: '+xhr_data+' %', 1);
			}
		}
	});
}

function adjustVolumeControl(control)
{
	var cookie = { id: 'hide_adjust_volume_control_dialog', value: 'true', expires: 3650 };

	if(!isCookie(cookie.id))
	{
		showDialog({ title: 'Adjust Volume', body_class: 'dialog_message_div', body_content: 'With this action you can toggle between adjusting Spotify\'s volume and the system volume.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions', 'volumecontrol'], values: ['hide_dialog adjust_volume_control', control] }, cookie: cookie });
	}
	else
	{
		var cookie = { id: 'settings_volume_control', value: control, expires: 3650 };
		$.cookie(cookie.id, cookie.value, { expires: cookie.expires });

		if(control == 'spotify')
		{
			showToast('Controlling Spotify\'s volume', 2);
		}
		else
		{
			showToast('Controlling the system volume', 2);
		}

		refreshNowplaying('silent');
	}
}

function toggleShuffleRepeat(action)
{
	var cookie = { id: 'hide_shuffle_repeat_dialog', value: 'true', expires: 3650 };

	if(!isCookie(cookie.id))
	{
		showDialog({ title: 'Shuffle &amp; Repeat', body_class: 'dialog_message_div', body_content: 'Shuffle and repeat can be toggled, but it is not possible to get the current status.<br><br>Spotify must be the active window. Advertisements may stop this from working.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions', 'remotecontrol'], values: ['hide_dialog toggle_shuffle_repeat', action] }, cookie: cookie });
	}
	else
	{
		$.post('main.php?'+getCurrentTime(), { action: action }, function(xhr_data)
		{
			if(xhr_data == 'spotify_is_not_running')
			{
				showToast('Spotify is not running', 2);
			}
			else if(action == 'toggle_shuffle')
			{
				showToast('Shuffle toggled', 2);
			}
			else if(action == 'toggle_repeat')
			{
				showToast('Repeat toggled', 2);
			}		
		});
	}
}

function playUri(uri)
{
	var type = getUriType(uri);

	startRefreshNowplaying();

	$.post('main.php?'+getCurrentTime(), { action: 'play_uri', data: uri }, function()
	{
		refreshNowplaying('manual');
	});

	if(type == 'playlist') saveRecentPlaylist(uri);
}

function playUriFromPlaylist(playlist_uri, uri)
{
	startRefreshNowplaying();

	var data = JSON.stringify([playlist_uri, uri]);

	$.post('main.php?'+getCurrentTime(), { action: 'play_uri_from_playlist', data: data }, function()
	{
		refreshNowplaying('manual');
	});

	saveRecentPlaylist(playlist_uri);
}

function shufflePlayUri(uri)
{
	if(project_spotify_is_unsupported)
	{
		showToast('Shuffle must already be enabled', 2);

		playUri(uri);

		return;
	}

	var type = getUriType(uri);

	var cookie = { id: 'hide_shuffle_play_uri_dialog', value: 'true', expires: 3650 };

	if(!isCookie(cookie.id))
	{
		showDialog({ title: 'Shuffle Play', body_class: 'dialog_message_div', body_content: 'This action plays the media, toggles shuffle off/on and skips one track to ensure random playback.<br><br>Shuffle must already be enabled. Spotify must be the active window. Advertisements may stop this from working.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions', 'uri'], values: ['hide_dialog shuffle_play_uri', uri] }, cookie: cookie });
	}
	else
	{
		startRefreshNowplaying();

		$.post('main.php?'+getCurrentTime(), { action: 'shuffle_play_uri', data: uri }, function()
		{
			refreshNowplaying('manual');
		});

		if(type == 'playlist') saveRecentPlaylist(uri);
	}
}

function startTrackRadio(uri, play_first)
{
	var cookie = { id: 'hide_start_track_radio_dialog', value: 'true', expires: 3650 };

	if(getUriType(uri) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else if(!isCookie(cookie.id))
	{
		showDialog({ title: 'Start Track Radio', body_class: 'dialog_message_div', body_content: 'Spotify must be the active window. It may not work on all tracks. Advertisements may stop this from working.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys: ['actions', 'uri', 'playfirst'], values: ['hide_dialog start_track_radio', uri, play_first] }, cookie: cookie });
	}
	else
	{
		showToast('Starting track radio', 2);

		var data = JSON.stringify([uri, play_first]);

		$.post('main.php?'+getCurrentTime(), { action: 'start_track_radio', data: data }, function()
		{
			startRefreshNowplaying();

			setTimeout(function()
			{
				refreshNowplaying('manual');
			}, 2000);
		});
	}
}

function confirmSuspendComputer()
{
	showDialog({ title: 'Suspend Computer', body_class: 'dialog_message_div', body_content: 'This will suspend the computer running Spotify, and you will lose connection to '+project_name+'.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog suspend_computer'] }, cookie: null });
}

function suspendComputer()
{
	remoteControl('suspend_computer');

	if(ua_is_android_app) Android.JSfinishActivity();
}

function confirmShutDownComputer()
{
	showDialog({ title: 'Shut Down Computer', body_class: 'dialog_message_div', body_content: 'This will shut down the computer running Spotify, and you will lose connection to '+project_name+'.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog shut_down_computer'] }, cookie: null });
}

function shutDownComputer()
{
	remoteControl('shut_down_computer');

	if(ua_is_android_app) Android.JSfinishActivity();
}

// Now playing

function toggleNowplaying()
{
	if(isVisible('div#nowplaying_div'))
	{
		hideNowplayingOverflowActions();
		hideNowplaying();
	}
	else
	{
		hideActivityOverflowActions();
		showNowplaying();
	}
}

function showNowplaying()
{
	if(isVisible('div#nowplaying_div') || textInputHasFocus()) return;

	hideMenu();

	$('div#nowplaying_div').css('visibility', 'visible');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		$('div#nowplaying_div').addClass('show_nowplaying_animation');
	}
	else
	{
		$('div#nowplaying_div').stop().animate({ 'bottom': '0' }, 500, 'easeOutExpo');
	}

	setNativeAppStatusBarColor('#212121');

	nativeAppCanCloseCover();
}

function hideNowplaying()
{
	if(!isVisible('div#nowplaying_div') || isDisplayed('div#transparent_cover_div')) return;

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		$('div#nowplaying_div').addClass('hide_nowplaying_animation').one(event_transitionend, function()
		{
			$('div#nowplaying_div').css('visibility', '').removeClass('show_nowplaying_animation hide_nowplaying_animation');
			nativeAppCanCloseCover();
		});
	}
	else
	{
		$('div#nowplaying_div').stop().animate({ 'bottom': $('div#nowplaying_div').data('cssbottom') }, 500, 'easeOutExpo', function()
		{
			$('div#nowplaying_div').css('visibility', '');
			nativeAppCanCloseCover();
		});
	}

	resetNativeAppStatusBarColor('nowplaying');
}

function toggleNowplayingOverflowActions()
{
	if(isDisplayed('div#nowplaying_actionbar_overflow_actions_div'))
	{
		hideNowplayingOverflowActions();
	}
	else
	{
		showNowplayingOverflowActions();
	}
}

function showNowplayingOverflowActions()
{
	if(isDisplayed('div#nowplaying_actionbar_overflow_actions_div')) return;

	$('div#transparent_cover_div').show();
	$('div#nowplaying_actionbar_overflow_actions_div').show();

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		$('div#nowplaying_actionbar_overflow_actions_div').addClass('prepare_overflow_actions_animation');

		setTimeout(function()
		{
			$('div#nowplaying_actionbar_overflow_actions_div').addClass('show_overflow_actions_animation');

			var divs = $('div#nowplaying_actionbar_overflow_actions_inner_div > div');

			divs.each(function(index)
			{
				var element = this;
				var timeout = index * 50;

				setTimeout(function()
				{
					fadeInDiv(element);
				}, timeout);
			});
		}, 25);
	}
	else
	{
		fadeInDiv('div#nowplaying_actionbar_overflow_actions_div');
		showDiv('div#nowplaying_actionbar_overflow_actions_inner_div > div');
	}

	$('input#nowplaying_volume_slider').attr('disabled', 'disabled');

	nativeAppCanCloseCover();
}

function hideNowplayingOverflowActions()
{
	if(!isDisplayed('div#nowplaying_actionbar_overflow_actions_div')) return;

	var div = $('div#nowplaying_actionbar_overflow_actions_div');

	div.hide();

	$('div', div).removeClass('light_grey_highlight');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		div.removeClass('prepare_overflow_actions_animation show_overflow_actions_animation');

		hideDiv('div#nowplaying_actionbar_overflow_actions_inner_div > div');
	}
	else
	{
		hideDiv('div#nowplaying_actionbar_overflow_actions_div');
		hideDiv('div#nowplaying_actionbar_overflow_actions_inner_div > div');
	}

	$('div#transparent_cover_div').hide();

	setTimeout(function()
	{
		$('input#nowplaying_volume_slider').removeAttr('disabled');
	}, 250);

	nativeAppCanCloseCover();
}

function startRefreshNowplaying()
{
	nowplaying_refreshing = true;

	xhr_nowplaying.abort();

	clearTimeout(timeout_nowplaying_error);

	$('div#bottom_actionbar_inner_center_div > div').html('Refreshing&hellip;');

	if(!isVisible('div#nowplaying_div')) return;

	hideNowplayingOverflowActions();

	$('div#nowplaying_actionbar_right_div > div').removeClass('actions_div').css('opacity', '0.5');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		$('div#nowplaying_cover_art_div').css('transition', '').css('transform', '').css('-webkit-transition', '').css('-webkit-transform', '').css('-moz-transition', '').css('-moz-transform', '').css('-ms-transition', '').css('-ms-transform', '').off(event_transitionend).removeClass('prepare_nowplaying_cover_art_animation show_nowplaying_cover_art_animation hide_nowplaying_cover_art_animation').addClass('hide_nowplaying_cover_art_animation');
	}
	else
	{
		$('div#nowplaying_cover_art_div').stop().animate({ 'left': '-'+window_width+'px' }, 500, 'easeOutExpo');
	}
}

function refreshNowplaying(type)
{
	nowplaying_refreshing = true;

	xhr_nowplaying.abort();

	autoRefreshNowplaying('reset');

	clearTimeout(timeout_nowplaying_error);

	xhr_nowplaying = $.get('nowplaying.php', function(xhr_data)
	{
		nowplaying_refreshing = false;

		clearTimeout(timeout_nowplaying_error);

		if(type == 'manual' || nowplaying_last_data != xhr_data)
		{
			nowplaying_last_data = xhr_data;

			var nowplaying = $.parseJSON(xhr_data);

			checkIfNewVersionIsInstalled(nowplaying.project_version);

			$('div#nowplaying_actionbar_right_div > div').addClass('actions_div').data('actions', 'show_nowplaying_overflow_actions').css('opacity', '');

			var overflow_actions_div = $('div#nowplaying_actionbar_overflow_actions_inner_div');

			overflow_actions_div.empty();

			for(var i = 0; i < nowplaying.actions.length; i++)
			{
				overflow_actions_div.append('<div class="actions_div" data-highlightclass="light_grey_highlight" onclick="void(0)">'+nowplaying.actions[i].action[0]+'</div>');

				for(var f = 0; f < nowplaying.actions[i].keys.length; f++)
				{
					$('div.actions_div', overflow_actions_div).last().data(nowplaying.actions[i].keys[f], nowplaying.actions[i].values[f]);
				}
			}

			$('input#nowplaying_volume_slider').val(nowplaying.current_volume);
			$('span#nowplaying_volume_level_span').html(nowplaying.current_volume);

			$('div#nowplaying_play_pause_div').removeClass('play_circle_white_72_img_div pause_circle_white_72_img_div').addClass(nowplaying.play_pause+'_circle_white_72_img_div');

			$('div#bottom_actionbar_play_pause_div').removeClass('play_white_24_img_div pause_white_24_img_div').addClass(nowplaying.play_pause+'_white_24_img_div');

			if(type == 'manual' || nowplaying_last_uri != nowplaying.uri)
			{
				nowplaying_last_uri = nowplaying.uri;

				$.cookie('nowplaying_uri', nowplaying.uri.toLowerCase(), { expires: 3650 });

				var cover_art_div = $('div#nowplaying_cover_art_div');

				cover_art_div.data('uri', nowplaying.uri).attr('title', nowplaying.album+' ('+nowplaying.released+')');

				if(nowplaying.is_local)
				{
					$.getJSON(project_website_https+'api/1/cover-art/?type=album&artist='+encodeURIComponent(nowplaying.artist)+'&album='+encodeURIComponent(nowplaying.album)+'&callback=?', function(metadata)
					{
						var lastfm_cover_art = (typeof metadata.mega == 'undefined' || metadata.mega == '') ? 'img/no-cover-art-640.png?'+project_serial : metadata.mega;

						$('img#nowplaying_cover_art_preload_img').attr('src', 'img/album-grey-24.png?'+project_serial).attr('src', lastfm_cover_art).on('load error', function(event)
						{
							$(this).off('load error');

							var cover_art = (event.type == 'load') ? lastfm_cover_art : 'img/no-cover-art-640.png?'+project_serial;
							cover_art_div.css('background-image', 'url("'+cover_art+'")');

							if(type == 'manual') endRefreshNowplaying();

							showNotification(nowplaying.title, nowplaying.artist+' (click to skip)', cover_art, 'remote_control_next', 4);
						});
					});
				}
				else
				{
					$('img#nowplaying_cover_art_preload_img').attr('src', 'img/album-grey-24.png?'+project_serial).attr('src', nowplaying.cover_art).on('load error', function(event)
					{
						$(this).off('load error');

						var cover_art = (event.type == 'load') ? nowplaying.cover_art : 'img/no-cover-art-640.png?'+project_serial;
						cover_art_div.css('background-image', 'url("'+cover_art+'")');

						if(type == 'manual') endRefreshNowplaying();

						if(nowplaying.uri != '') showNotification(nowplaying.title, nowplaying.artist+' (click to skip)', cover_art, 'remote_control_next', 4);
					});
				}

				$('div#nowplaying_artist_div').attr('title', nowplaying.artist).html(hsc(nowplaying.artist));
				$('div#nowplaying_title_div').attr('title', nowplaying.title+' ('+nowplaying.tracklength+')').html(hsc(nowplaying.title));

				hideDiv('div#bottom_actionbar_inner_center_div > div');
				$('div#bottom_actionbar_inner_center_div > div').attr('title', nowplaying.artist+' - '+nowplaying.title+' ('+nowplaying.tracklength+')').html(hsc(nowplaying.title));
				fadeInDiv('div#bottom_actionbar_inner_center_div > div');

				highlightNowplayingListItem();
				refreshRecentlyPlayedActivity();
				refreshQueueActivity();

				if(settings_update_lyrics && isActivity('lyrics', '') && nowplaying.uri != '') replaceActivity('lyrics', '', 'artist='+encodeURIComponent(nowplaying.artist)+'&title='+encodeURIComponent(nowplaying.title));

				if(ua_is_android_app)
				{
					Android.JSsetSharedString('NOWPLAYING_ARTIST', nowplaying.artist);
					Android.JSsetSharedString('NOWPLAYING_TITLE', nowplaying.title);
				}
			}

			if(ua_is_integrated_msie)
			{
				if(nowplaying.play_pause == 'play')
				{
					window.external.msSiteModeShowButtonStyle(ie_thumbnail_button_play_pause, ie_thumbnail_button_style_play);
				}
				else
				{
					window.external.msSiteModeShowButtonStyle(ie_thumbnail_button_play_pause, ie_thumbnail_button_style_pause);
				}
			}
		}
	}).fail(function()
	{
		timeout_nowplaying_error = setTimeout(function()
		{
			nowplaying_refreshing = false;
			nowplaying_last_data = '';
			nowplaying_last_uri = '';

			$('div#bottom_actionbar_inner_center_div > div').html('Connection failed');
		}, 1000);
	});
}

function endRefreshNowplaying()
{
	var cover_art_div = $('div#nowplaying_cover_art_div');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		if(isVisible('div#nowplaying_div'))
		{
			cover_art_div.removeClass('hide_nowplaying_cover_art_animation').addClass('prepare_nowplaying_cover_art_animation');

			setTimeout(function()
			{
				cover_art_div.addClass('show_nowplaying_cover_art_animation').one(event_transitionend, function()
				{
					cover_art_div.removeClass('prepare_nowplaying_cover_art_animation show_nowplaying_cover_art_animation hide_nowplaying_cover_art_animation');
				});
			}, 25);
		}
		else
		{
			cover_art_div.removeClass('prepare_nowplaying_cover_art_animation show_nowplaying_cover_art_animation hide_nowplaying_cover_art_animation');
		}
	}
	else
	{
		if(isVisible('div#nowplaying_div'))
		{
			var changeside = parseInt(window_width * 2);
			cover_art_div.stop().css('left', changeside+'px').animate({ 'left': '0' }, 500, 'easeOutExpo');
		}
		else
		{
			cover_art_div.css('left', '');
		}
	}
}

function autoRefreshNowplaying(action)
{
	if(action == 'start' && settings_nowplaying_refresh_interval >= 5)
	{
		var cookie = { id: 'nowplaying_last_update', expires: 3650 };
		$.cookie(cookie.id, getCurrentTime(), { expires: cookie.expires });

		interval_nowplaying_auto_refresh = setInterval(function()
		{
			var current_time = getCurrentTime();
			var last_update_time = parseInt($.cookie(cookie.id));

			if(current_time - last_update_time > settings_nowplaying_refresh_interval * 1000)
			{
				$.cookie(cookie.id, current_time, { expires: cookie.expires });

				timeout_nowplaying_auto_refresh = setTimeout(function()
				{
					if(!nowplaying_refreshing && !nowplaying_cover_art_moving && !isDisplayed('div#black_cover_div') && !isDisplayed('div#transparent_cover_div')) refreshNowplaying('silent');
				}, 2000);
			}
		}, 1000);
	}
	else if(action == 'reset' && interval_nowplaying_auto_refresh != null)
	{
		clearInterval(interval_nowplaying_auto_refresh);
		clearTimeout(timeout_nowplaying_auto_refresh);

		autoRefreshNowplaying('start');
	}
}

function highlightNowplayingListItem()
{
	var cookie = { id: 'nowplaying_uri' };

	if(!$('div.list_item_main_div').length || !isCookie(cookie.id)) return;

	var nowplaying_uri = $.cookie(cookie.id);

	$('div.list_item_main_div').each(function()
	{
		var item = $(this);

		if(item.attr('data-trackuri'))
		{
			var uri = item.data('trackuri').toLowerCase();

			var icon = $('div.list_item_main_inner_icon_div > div', item);
			var text = $('div.list_item_main_inner_text_upper_div', item);

			if(icon.hasClass('volume_up_grey_24_img_div') && text.hasClass('bold_text'))
			{
				icon.removeClass('volume_up_grey_24_img_div');
				text.removeClass('bold_text');
			}

			if(uri == nowplaying_uri)
			{
				icon.addClass('volume_up_grey_24_img_div');
				text.addClass('bold_text');
			}
		}
	});
}

// Cover art

function getCoverArt()
{
	var div = $('div#cover_art_art_div');

	if(div.length)
	{
		if(div.attr('data-uri') && !shc(div.attr('style'), 'background-image'))
		{
			var uri = div.data('uri');

			if(uri == '') return;

			xhr_cover_art.abort();

			xhr_cover_art = $.post('main.php?get_cover_art&'+getCurrentTime(), { uri: uri, size: 'large' }, function(xhr_data)
			{
				if(xhr_data != '')
				{
					$('div#cover_art_art_div').css('background-image', 'url("'+xhr_data+'")');

					setNativeAppStatusBarColorFromImage(xhr_data);
				}
			});
		}
		else
		{
			var image_uri = div.data('imageuri');

			setNativeAppStatusBarColorFromImage(image_uri);
		}

		if(!ua_is_android_app) showCoverArtFabAnimation();
	}
	else
	{
		setNativeAppStatusBarColor('primary');
	}

	var divs = $('div.card_vertical_cover_art_div');

	if(divs.length)
	{
		xhr_cover_art.abort();

		divs.each(function(index)
		{
			var element = $(this);

			if(element.attr('data-coverarturi') && !shc(element.attr('style'), 'background-image'))
			{
				var uri = element.data('coverarturi');
				var timeout = index * 125;

				setTimeout(function()
				{
					xhr_cover_art = $.post('main.php?get_cover_art&'+getCurrentTime(), { uri: uri, size: 'medium' }, function(xhr_data)
					{
						if(xhr_data != '') element.css('background-image', 'url("'+xhr_data+'")');
					});
				}, timeout);
			}
		});	
	}

	var divs = $('div.list_item_main_inner_circle_div > div');

	if(divs.length)
	{
		xhr_cover_art.abort();

		divs.each(function(index)
		{
			var element = $(this);

			if(element.attr('data-coverarturi') && !shc(element.attr('style'), 'background-image'))
			{
				var uri = element.data('coverarturi');
				var timeout = index * 125;

				setTimeout(function()
				{
					xhr_cover_art = $.post('main.php?get_cover_art&'+getCurrentTime(), { uri: uri, size: 'small' }, function(xhr_data)
					{
						if(xhr_data != '') element.css('background-image', 'url("'+xhr_data+'")').css('background-size', 'cover');
					});
				}, timeout);
			}
		});
	}
}

function setCoverArtSize()
{
	var cover_art_div = $('div#cover_art_art_div');
	var is_widescreen = isWidescreen();

	if(cover_art_div.length)
	{
		var container_width = cover_art_div.parent().width();
		var cover_art_width = cover_art_div.data('width');
		var cover_art_height = cover_art_div.data('height');

		cover_art_div.css('background-size', '');

		if(cover_art_width > container_width)
		{
			var ratio = container_width / cover_art_width;
			var cover_art_height = Math.floor(cover_art_height * ratio);
			var minimum_height = cover_art_div.height();

			var size = (cover_art_height < minimum_height) ? 'auto '+minimum_height+'px' : container_width+'px auto';

			cover_art_div.css('background-size', size);
		}
		else if(is_widescreen)
		{
			cover_art_div.css('background-size', cover_art_width+'px auto');
		}
	}

	if(cover_art_div.length && !is_widescreen)
	{
		activity_has_cover_art_opacity = true;

		$('div#top_actionbar_shadow_div').hide();
		$('div#top_actionbar_inner_div').addClass('top_actionbar_inner_opacity_div shadow_down_black_56_img_div');

		$('div#activity_div').addClass('activity_cover_art_div');
	}
	else
	{
		activity_has_cover_art_opacity = false;

		$('div#top_actionbar_shadow_div').show();
		$('div#top_actionbar_inner_div').removeClass('top_actionbar_inner_opacity_div shadow_down_black_56_img_div').css('background-color', '');

		$('div#activity_div').removeClass('activity_cover_art_div');
	}
}

function setCoverArtOpacity()
{
	if(!activity_has_cover_art_opacity) return;

	var scroll_position = getScrollPosition();
	var height = $('div#cover_art_art_div').height();

	if(scroll_position < height)
	{
		activity_cover_art_opacity = (scroll_position / height).toFixed(2);

		$('div#top_actionbar_shadow_div').hide();
		$('div#top_actionbar_inner_div').addClass('shadow_down_black_56_img_div').removeClass('top_actionbar_inner_opacity_div').css('background-color', 'rgba('+cover_art_rgb+', '+activity_cover_art_opacity+')');
	}
	else
	{
		$('div#top_actionbar_shadow_div').show();
		$('div#top_actionbar_inner_div').removeClass('shadow_down_black_56_img_div top_actionbar_inner_opacity_div').css('background-color', 'rgba('+cover_art_rgb+', 1)');
	}
}

function setCoverArtFabColor(color)
{
	var rgb = hexToRgb(color);

	if(rgb != null) cover_art_rgb = rgb;

	$('div#cover_art_fab_div').css('background-color', color);

	showCoverArtFabAnimation();
}

// Recently played

function refreshRecentlyPlayedActivity()
{
	if(isActivity('recently-played', '')) refreshActivity();
}

function clearRecentlyPlayed()
{
	$.get('recently-played.php?clear', function()
	{
		refreshRecentlyPlayedActivity();
	});
}

// Queue

function queueUri(artist, title, uri)
{
	var type = getUriType(uri);

	$.post('queue.php?queue_uri&'+getCurrentTime(), { artist: artist, title: title, uri: uri }, function(xhr_data)
	{
		if(xhr_data == 'spotify_is_not_running')
		{
			showToast('Spotify is not running', 2);
		}
		else
		{
			showToast('Track queued', 2);
			refreshQueueActivity();
		}
	});
}

function queueUris(uris, randomly)
{
	$.post('queue.php?queue_uris&'+getCurrentTime(), { uris: uris, randomly: randomly }, function(xhr_data)
	{
		if(xhr_data == 'spotify_is_not_running')
		{
			showToast('Spotify is not running', 2);
		}
		else if(xhr_data == 'error')
		{
			showToast('Could not queue tracks', 2);
		}
		else
		{
			var number = parseInt(xhr_data);
			var toast = (number == 1) ? 'track' : 'tracks';

			showToast(number+' '+toast+' queued', 2);
			refreshQueueActivity();
		}
	});
}

function moveQueuedUri(id, sortorder, direction)
{
	$.post('queue.php?move&'+getCurrentTime(), { id: id, sortorder: sortorder, direction: direction }, function(xhr_data)
	{
		refreshQueueActivity();
	});
}

function removeFromQueue(id, sortorder)
{
	$.post('queue.php?remove&'+getCurrentTime(), { id: id, sortorder: sortorder }, function()
	{
		refreshQueueActivity();
	});
}

function queueAction(queue_action, sortorder)
{
	$.post('queue.php?action&'+getCurrentTime(), { queue_action: queue_action, sortorder: sortorder }, function()
	{
		if(sortorder == 'top')
		{
			changeActivity('queue', '', '');
		}
		else
		{
			refreshQueueActivity();
		}

		showToast('Action added to queue', 2);
	});
}

function clearQueue()
{
	$.get('queue.php?clear', function()
	{
		refreshQueueActivity();
	});
}

function refreshQueueActivity()
{
	if(isActivity('queue', '')) refreshActivity();
}

// Playlists

function browsePlaylist(uri, description, is_authorized_with_spotify)
{
	if(is_authorized_with_spotify)
	{
		changeActivity('playlists', 'browse', 'uri='+uri+'&description='+description);
	}
	else
	{
		changeActivity('profile', '', '');
	}
}

function addToPlaylist(title, uri, is_authorized_with_spotify)
{
	if(is_authorized_with_spotify)
	{
		if(getUriType(uri) == 'local')
		{
			showToast('Not possible for local files', 4);
		}
		else
		{
			$.get('playlists.php?get_playlists_with_access_as_json', function(xhr_data)
			{
				var playlists = $.parseJSON(xhr_data);

				var actions = [];

				var i = 0;

				for(var playlist in playlists)
				{
					actions[i] = { text: hsc(playlist), keys: ['actions', 'uri', 'uris'], values: ['hide_dialog add_uris_to_playlist', playlists[playlist], uri] };

					i++;
				}

				showActionsDialog({ title: hsc(title), actions: actions });
			});
		}
	}
	else
	{
		changeActivity('profile', '', '');
	}
}

function addUrisToPlaylist(uri, uris)
{
	$.post('playlists.php?add_uris_to_playlist&'+getCurrentTime(), { uri: uri, uris: uris }, function(xhr_data)
	{
		if(xhr_data == 'no_access')
		{
			showToast('No access to playlist', 4);
		}
		else if(xhr_data == 'error')
		{
			showToast('Could not add to playlist', 4);
		}
		else
		{
			var toast = (parseInt(xhr_data) == 1) ? 'track' : 'tracks';
			showToast(xhr_data+' '+toast+' added to playlist', 2);

			refreshBrowsePlaylistActivity(uri);
		}
	});
}

function deleteUrisFromPlaylist(uri, uris, positions, snapshot_id, div)
{
	if(getUriType(uris) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else
	{
		$.post('playlists.php?delete_uris_from_playlist&'+getCurrentTime(), { uri: uri, uris: uris, positions: positions, snapshot_id: snapshot_id }, function(xhr_data)
		{
			if(xhr_data == 'no_access')
			{
				showToast('No access to playlist', 4);
			}
			else if(xhr_data == 'error')
			{
				showToast('Could not delete from playlist', 4);
			}
			else
			{
				$('div#'+div).remove();

				showToast('Track deleted from playlist', 2);

				var span = $('span#playlist_tracks_count_span');
				var count = parseInt(span.html()) - 1;

				span.html(count);
			}
		});
	}
}

function confirmRefreshSpotifyPlaylists()
{
	showDialog({ title: 'Refresh from Spotify', body_class: 'dialog_message_div', body_content: 'Your playlists are refreshed every five minutes. This action does it manually.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog refresh_spotify_playlists'] }, cookie: null });
}

function refreshSpotifyPlaylists(refresh)
{
	if(!refresh) activityLoading();

	xhr_activity = $.get('playlists.php?refresh_spotify_playlists', function(xhr_data)
	{
		var number = parseInt(xhr_data);
		var toast = (number == 1) ? 'playlist' : 'playlists';

		if(refresh)
		{
			if(xhr_data == 'no_playlists')
			{
				showToast('No playlists found', 4);
			}
			else if(xhr_data == 'error')
			{
				showToast('Could not refresh playlists', 4);
			}
			else if(number != 0)
			{
				showToast(number+' '+toast+' added', 2);

				refreshPlaylistsActivity();
			}
		}
		else
		{
			changeActivity('playlists', '', '');

			if(xhr_data == 'no_playlists')
			{
				showToast('No playlists found', 4);
			}
			else if(xhr_data == 'error')
			{
				showToast('Could not refresh playlists', 4);
			}
			else
			{
				showToast(number+' '+toast+' imported', 2);
			}
		}
	});

	$.cookie('last_refresh_playlists', getCurrentTime(), { expires: 3650 });
}

function importPlaylists(uris)
{
	var uris = $.trim(uris);
	var validate = uris.split(' ');

	var invalid = false;

	for(var i = 0; i < validate.length; i++)
	{
		var uri = urlToUri(validate[i]);

		if(getUriType(uri) == 'playlist')
		{
			cachePlaylist(uri);
		}
		else
		{
			invalid = true;
		}
	}

	if(invalid)
	{
		if(uris != '') showToast('One or more invalid playlist URIs', 4);

		$('div.input_text_div').addClass('input_text_error_div');

		focusTextInput('input:text#import_playlists_uris_input');
	}
	else
	{
		var div = $('div#playlist_description_div');
		var header_div = $('div.list_header_div');

		if(div.length)
		{
			showToast('Adding playlist...', 2);
		}
		else
		{
			blurTextInput();
			activityLoading();
		}

		$.post('playlists.php?import_playlists&'+getCurrentTime(), { uris: uris }, function(xhr_data)
		{
			if(xhr_data == 'error')
			{
				showToast('Could not import one or more playlists. Try again.', 4);
			}
			else
			{
				if(div.length)
				{
					if(ua_supports_csstransitions && ua_supports_csstransforms3d)
					{
						div.addClass('show_import_playlist_animation').one(event_transitionend, function()
						{
							div.hide();
							header_div.addClass('list_header_below_cover_art_div');
						});
					}
					else
					{
						div.hide();
						header_div.addClass('list_header_below_cover_art_div');
					}
				}

				var toast = (parseInt(xhr_data) == 1) ? 'playlist' : 'playlists';
				showToast(xhr_data+' '+toast+' imported', 2);
			}

			if(!div.length) changeActivity('playlists', '', '');
		});
	}
}

function createPlaylist(name, make_public)
{
	if(name == '')
	{
		$('div.input_text_div').addClass('input_text_error_div');

		focusTextInput('input:text#create_playlist_name_input');
	}
	else
	{
		activityLoading();

		$.post('playlists.php?create_playlist&'+getCurrentTime(), { name: name, make_public: make_public }, function(xhr_data)
		{
			changeActivity('playlists', '', '');

			if(xhr_data == 'error')
			{
				showToast('Could not create playlist', 4);
			}
			else
			{
				showToast('Playlist "'+xhr_data+'" created', 2);
			}
		});
	}
}

function editPlaylist(name, uri, make_public)
{
	if(name == '')
	{
		showToast('Playlist name can not be empty', 4);
		focusTextInput('input:text#edit_playlist_name_input');
	}
	else
	{
		activityLoading();

		$.post('playlists.php?edit_playlist&'+getCurrentTime(), { name: name, uri: uri, make_public: make_public }, function(xhr_data)
		{
			if(xhr_data == 'no_access')
			{
				changeActivity('playlists', '', '');

				showToast('No access to playlist', 4);
			}
			else if(xhr_data == 'error')
			{
				changeActivity('playlists', '', '');

				showToast('Could not edit playlist', 4);
			}
			else
			{
				changeActivity('playlists', '', '');

				showToast('Playlist "'+xhr_data+'" edited', 2);

				cachePlaylist(uri);
			}
		});
	}
}

function cachePlaylist(uri)
{
	$.post('playlists.php?cache_playlist&'+getCurrentTime(), { uri: uri });
}

function confirmRemovePlaylist(id, uri)
{
	showDialog({ title: 'Delete Playlist', body_class: 'dialog_message_div', body_content: 'Are you sure you want to delete this playlist?<br><br>This can not be undone. If this is not your playlist, you will simply unfollow it.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'DELETE', keys : ['actions', 'id', 'uri'], values: ['hide_dialog remove_playlist', id, uri] }, cookie: null });
}

function removePlaylist(id, uri)
{
	$.post('playlists.php?remove_playlist&'+getCurrentTime(), { id: id, uri: uri }, function(xhr_data)
	{
		if(xhr_data == 'error')
		{
			showToast('Something went wrong. Try again.', 4);
		}
		else
		{
			refreshPlaylistsActivity();
		}
	});
}

function saveRecentPlaylist(uri)
{
	$.post('playlists.php?save_recent_playlists&'+getCurrentTime(), { uri: uri }, function()
	{
		cachePlaylist(uri);
	});
}

function refreshPlaylist(uri)
{
	$.post('playlists.php?refresh_playlist&'+getCurrentTime(), { uri: uri }, function()
	{
		reloadActivity();
	});
}

function refreshPlaylistsActivity()
{
	if(isActivity('playlists', '')) refreshActivity();
}

function refreshBrowsePlaylistActivity(uri)
{
	var a = getActivity();

	if(isActivity('playlists', 'browse') && shc(a.args, uri))
	{
		refreshActivity();
	}
	else
	{
		cachePlaylist(uri);
	}
}

// Library

function save(artist, title, uri, is_authorized_with_spotify, element)
{
	if(getUriType(uri) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else if(is_authorized_with_spotify)
	{
		$.post('library.php?save&'+getCurrentTime(), { artist: artist, title: title, uri: uri }, function(xhr_data)
		{
			if(xhr_data == 'error')
			{
				showToast('Could not save item to library', 4);
			}
			else if(shc(xhr_data, 'removed'))
			{
				if($('div.img_div', element).length)
				{
					$('div.img_div.check_grey_24_img_div', element).removeClass('check_grey_24_img_div').addClass('plus_grey_24_img_div');
					$('div.img_div.check_white_24_img_div', element).removeClass('check_white_24_img_div').addClass('plus_white_24_img_div');
				}
				else
				{
					var html = $(element).html();
					element.html(html.replace('Remove from Library', 'Save to Library'));
					showToast(xhr_data, 2);
				}
			}
			else
			{
				if($('div.img_div', element).length)
				{
					$('div.img_div.plus_grey_24_img_div', element).removeClass('plus_grey_24_img_div').addClass('check_grey_24_img_div');
					$('div.img_div.plus_white_24_img_div', element).removeClass('plus_white_24_img_div').addClass('check_white_24_img_div');
				}
				else
				{
					var html = $(element).html();
					element.html(html.replace('Save to Library', 'Remove from Library'));
					showToast(xhr_data, 2);
				}
			}

			refreshLibraryActivity();
		});
	}
	else
	{
		changeActivity('profile', '', '');
	}
}

function remove(uri, is_authorized_with_spotify)
{
	if(is_authorized_with_spotify)
	{
		$.post('library.php?remove&'+getCurrentTime(), { uri: uri }, function(xhr_data)
		{
			refreshLibraryActivity();

			if(xhr_data == 'error') showToast('Could not remove item from library', 4);
		});
	}
	else
	{
		changeActivity('profile', '', '');
	}
}

function confirmRefreshLibrary(is_authorized_with_spotify)
{
	if(is_authorized_with_spotify)
	{
		showDialog({ title: 'Refresh Library', body_class: 'dialog_message_div', body_content: 'Your library is refreshed every five minutes. This action does it manually.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog refresh_library'] }, cookie: null });
	}
	else
	{
		changeActivity('profile', '', '');
	}
}

function refreshLibrary()
{
	xhr_activity = $.get('library.php?refresh_library', function(xhr_data)
	{
		if(xhr_data == 'new_items')
		{
			showToast('Library refreshed', 2);

			reloadActivity();
		}
		else if(xhr_data == 'no_items')
		{
			showToast('No saved items found', 4);
		}
		else if(xhr_data == 'error')
		{
			showToast('Could not refresh saved items', 4);
		}
	});

	$.cookie('last_refresh_library', getCurrentTime(), { expires: 3650 });
}

function refreshLibraryActivity()
{
	if(isActivity('library', '')) refreshActivity();
}

// Browse

function getFeaturedPlaylists(fade_in_div)
{
	var date = new Date();
	var offset = parseInt(date.getTimezoneOffset() * 60);
	var time = parseInt(date.getTime() / 1000 - offset);

	var div = $('div#browse_featured_playlists_div');

	var country = div.data('country');
	var spotify_token = div.data('spotifytoken');

	$.getJSON(project_website+'api/1/browse/featured-playlists/?time='+time+'&country='+country+'&fields='+encodeURIComponent('description,cover_art')+'&token='+spotify_token+'&callback=?', function(data)
	{
		if(typeof data.metadata == 'undefined' || data.metadata == '')
		{
			showToast('Could not get featured playlists', 4);
		}
		else
		{
			var metadata = data.metadata;

			div.addClass('actions_div').data('actions', 'change_activity').data('activity', 'browse').data('subactivity', 'featured-playlists').data('args', 'time='+time+'&country='+country);
			div.children().css('background-image', 'url("'+metadata.cover_art+'")');
			div.children().children().html(metadata.description);

			if(fade_in_div) fadeInDiv('div#browse_featured_playlists_div > div');
		}
	});
}

function getRecommendations(uri, is_authorized_with_spotify)
{
	if(getUriType(uri) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else if(is_authorized_with_spotify)
	{
		changeActivity('browse', 'recommendations', 'uri='+uri);
	}
	else
	{
		changeActivity('profile', '', '');
	}
}

// Search

function getSearch(string)
{
	if(string == '')
	{
		$('div.input_text_div').addClass('input_text_error_div');
		
		focusTextInput('input:text#search_input');
	}
	else
	{
		blurTextInput();

		$.cookie('settings_sort_search_tracks', 'default', { expires: 3650 });

		changeActivity('search', 'search', 'string='+string);
	}
}

function clearRecentSearches()
{
	$.get('search.php?clear', function()
	{
		refreshSearchActivity();
	});
}

function refreshSearchActivity()
{
	if(isActivity('search', '')) refreshActivity();
}

// Artists

function browseArtist(uri)
{
	if(getUriType(uri) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else
	{
		changeActivity('artist', '', 'uri='+uri);
	}
}

function getArtistBiography()
{
	div = $('div#artist_biography_body_div');

	if(!div.length) return;

	var artist = div.data('artist');

	$.getJSON(project_website_https+'api/1/artist/biography/?artist='+artist+'&callback=?', function(metadata)
	{
		var biography = (metadata.biography == '') ? 'No biography available.' : metadata.biography;

		div.html(biography);
	});
}

// Albums

function browseAlbum(uri)
{
	if(uri == '') return;

	if(getUriType(uri) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else
	{
		changeActivity('album', '', 'uri='+uri);
	}
}

// Profile

function authorizeWithSpotify()
{
	if(ua_is_ios && ua_is_standalone) $.removeCookie('current_activity_'+project_version);

	var uri = window.location.href.replace(window.location.hash, '')+'#profile//spotify_token=';
	var installed = parseInt($.cookie('installed_'+project_version));

	window.location.href = project_website+'api/1/spotify/authorize/?redirect_uri='+encodeURIComponent(uri)+'&state='+installed;
}

function confirmAuthorizeWithSpotify()
{
	showDialog({ title: 'Authorize with Spotify', body_class: 'dialog_message_div', body_content: 'This will redirect you to Spotify\'s website where you must log in as the same user you are logged in as in the Spotify desktop client.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog authorize_with_spotify'] }, cookie: null });
}

function deauthorizeFromSpotify()
{
	$.get('profile.php?deauthorize_from_spotify', function()
	{
		replaceActivity('profile', '', '');
	});
}

function confirmDeauthorizeFromSpotify()
{
	showDialog({ title: 'Deauthorize from Spotify', body_class: 'dialog_message_div', body_content: 'This will deauthorize you from Spotify. Normally you only do this if you are going to authorize as another Spotify user.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog deauthorize_from_spotify'] }, cookie: null });
}

// User

function getUser(username)
{
	changeActivity('user', '', 'username='+username);
}

// Settings

function saveSetting(setting, value)
{
	var cookie = { id: setting, value: value, expires: 3650 };
	$.cookie(cookie.id, cookie.value, { expires: cookie.expires });
	showToast('Tap top right icon to apply', 4);
}

function applySettings()
{
	if(ua_is_ios && ua_is_standalone) $.removeCookie('current_activity_'+project_version);
	window.location.replace('.');
}

function disableSetting(div)
{
	$(div+' > div.setting_text_div > div:first-child').addClass('setting_disabled_div');
	$(div+' > div.setting_text_div > div:last-child').html('Not supported on this device');
	$(div+' > div.setting_edit_div').css('visibility', 'hidden');
}

function makeDonation()
{
	if(ua_is_android_app && 'JSmakeDonation' in window.Android)
	{
		Android.JSmakeDonation();
	}
	else
	{
		openExternalActivity(project_website+'?donate');
	}
}

function confirmRemoveAllPlaylists()
{
		showDialog({ title: 'Remove All Playlists', body_class: 'dialog_message_div', body_content: 'This will remove all playlists from '+project_name+'. They will not be deleted from Spotify.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog remove_all_playlists'] }, cookie: null });
}

function removeAllPlaylists()
{
	$.get('playlists.php?remove_all_playlists', function()
	{
		showToast('All playlists removed', 2);

		$.removeCookie('last_refresh_playlists');
	});
}

function confirmClearCache()
{
	showDialog({ title: 'Clear Cache', body_class: 'dialog_message_div', body_content: 'This will clear the cache for playlists, albums, etc.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog clear_cache'] }, cookie: null });
}

function clearCache()
{
	$.post('main.php?'+getCurrentTime(), { action: 'clear_cache' }, function()
	{
		showToast('Cache cleared', 2);
	});
}

function confirmRestoreToDefault()
{
	showDialog({ title: 'Restore', body_class: 'dialog_message_div', body_content: 'This will restore settings, messages, etc.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys : ['actions'], values: ['hide_dialog restore_to_default'] }, cookie: null });
}

function restoreToDefault()
{
	activityLoading();

	setTimeout(function()
	{
		removeAllCookies();
		window.location.replace('.');
	}, 2000);
}

// Lyrics

function getLyrics(artist, title)
{
	changeActivity('lyrics', '', 'artist='+artist+'&title='+title);
}

// Share

function shareUri(title, uri)
{
	if(getUriType(decodeURIComponent(uri)) == 'local')
	{
		showToast('Not possible for local files', 4);
	}
	else if(ua_is_android_app)
	{
		setTimeout(function()
		{
			Android.JSshare(title, decodeURIComponent(uri));
		}, 250);
	}
	else
	{
		showDialog({ title: title, body_class: 'dialog_share_div', body_content: '<div title="Share on Facebook" class="actions_div" data-actions="open_external_activity" data-uri="https://www.facebook.com/sharer/sharer.php?u='+uri+'" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_48_div facebook_grey_48_img_div"></div></div><div title="Share on Twitter" class="actions_div" data-actions="open_external_activity" data-uri="https://twitter.com/intent/tweet?url='+uri+'" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_48_div twitter_grey_48_img_div"></div></div><div title="Share on Google+" class="actions_div" data-actions="open_external_activity" data-uri="https://plus.google.com/share?url='+uri+'" data-highlightclass="light_grey_highlight" onclick="void(0)"><div class="img_div img_48_div googleplus_grey_48_img_div"></div></div>', button1: null, button2: null, cookie: null });
	}
}

// UI

function setCss()
{
	var style = $('style', 'head');

	style.empty();

	window_width = $(window).width();
	window_height = $(window).height();

	if(ua_is_android && !ua_is_standalone || ua_is_ios && !ua_is_standalone)
	{
		var min_height = window_height + 256;
		$('div#activity_div').css('min-height', min_height+'px');
	}

	$('div#menu_div').data('cssleft', $('div#menu_div').css('left'));
	$('div#nowplaying_div').css('bottom', '-'+window_height+'px').data('cssbottom', $('div#nowplaying_div').css('bottom'));
	$('div#activity_fab_div').data('cssbottom', $('div#activity_fab_div').css('bottom'));

	var css = '';

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		css += '.show_nowplaying_animation { transform: translate3d(0, -'+window_height+'px, 0); -webkit-transform: translate3d(0, -'+window_height+'px, 0); -moz-transform: translate3d(0, -'+window_height+'px, 0); -ms-transform: translate3d(0, -'+window_height+'px, 0); } ';

		if(!ua_is_ios) css += '.card_highlight { transform: scale3d(0.975, 0.975, 1); -webkit-transform: scale3d(0.975, 0.975, 1); -moz-transform: scale3d(0.975, 0.975, 1); -ms-transform: scale3d(0.975, 0.975, 1); } ';
	}

	style.html(css);
}

function setWidescreenCss()
{
	if(!isWidescreen()) return;

	$('div#menu_div').removeClass('show_menu_animation hide_menu_animation').css('visibility', '').css('left', '');
	$('div#top_actionbar_inner_left_div > div > div > div').removeClass('show_menu_img_animation hide_menu_img_animation').removeClass('forward_white_24_img_div back_white_24_img_div').addClass('menu_white_24_img_div');
	$('div#black_cover_activity_div').hide().removeClass('show_black_cover_activity_div_animation hide_black_cover_activity_div_animation').css('opacity', '');
}

function showDiv(div)
{
	$(div).css('opacity', '1');
}

function hideDiv(div)
{
	$(div).removeClass('show_div_animation hide_div_animation').css('opacity', '0');
}

function fadeInDiv(div)
{
	setTimeout(function()
	{
		if(ua_supports_csstransitions)
		{
			$(div).removeClass('hide_div_animation').addClass('show_div_animation');
		}
		else
		{
			$(div).stop().animate({ 'opacity': '1' }, 250, 'easeInCubic');
		}
	}, 25);
}

function fadeOutDiv(div)
{
	setTimeout(function()
	{
		if(ua_supports_csstransitions)
		{
			$(div).removeClass('show_div_animation').addClass('hide_div_animation');
		}
		else
		{
			$(div).stop().animate({ 'opacity': '0' }, 250, 'easeInCubic');
		}
	}, 25);
}

function setCardVerticalCoverArtSize()
{
	var cards_div = $('div.cards_vertical_div');

	if(!cards_div.length) return;

	var cover_art_divs = $('div.card_vertical_cover_art_div');
	var cover_art_divs_width = cover_art_divs.width();

	cover_art_divs.height(cover_art_divs_width);
	cards_div.css('visibility', 'visible');
}

function focusTextInput(id)
{
	if(ua_supports_touch)
	{
		blurTextInput();
	}
	else
	{
		$(id).focus();
	}
}

function blurTextInput()
{
	$('input:text').blur();
}

function scrollToTop(animate)
{
	if(getScrollPosition() != 0)
	{
		if(animate)
		{
			$('html, body').animate({ 'scrollTop': '0' }, 250);
		}
		else
		{
			setScrollPosition(0);
		}
	}
}

function showListItemActions(element)
{	
	var list_item_div = element.parent();
	var list_item_main_div = element;
	var list_item_icon_div = $('div.list_item_main_inner_icon_div > div', list_item_main_div);
	var list_item_main_actions_arrow_div = $('div.list_item_main_actions_arrow_div', list_item_main_div);
	var list_item_actions_div = $('div.list_item_actions_div', list_item_div);

	list_item_div.css('border-bottom', '0');

	list_item_icon_div.removeClass('unfold_more_grey_24_img_div').addClass('unfold_less_grey_24_img_div');

	list_item_main_actions_arrow_div.show();

	hideDiv(list_item_actions_div);
	list_item_actions_div.show();
	fadeInDiv(list_item_actions_div);
}

function hideListItemActions()
{
	$('div.list_item_div').css('border-bottom', '');
	$('div.list_item_main_inner_icon_div > div').removeClass('unfold_less_grey_24_img_div').addClass('unfold_more_grey_24_img_div');
	$('div.list_item_main_actions_arrow_div').hide().removeClass('up_arrow_dark_grey_highlight');
	$('div.list_item_actions_div').hide();
	$('div.list_item_actions_inner_div > div').removeClass('dark_grey_highlight');
}

function showActivityFab(button)
{
	var div = $('div#activity_fab_div');
	var timeout = 0;

	if(button != null)
	{
		div.removeClass().addClass('actions_div '+button.icon).attr('title', button.label).show();

		for(var i = 0; i < button.keys.length; i++)
		{
			div.data(button.keys[i], button.values[i]);
		}

		timeout = 500;
	}

	setTimeout(function()
	{
		if(div.is(':hidden')) return;

		if(ua_supports_csstransitions && ua_supports_csstransforms3d)
		{
			if(isDisplayed('div#toast_div') && !isWidescreen())
			{
				div.removeClass('hide_activity_fab_animation').addClass('show_activity_fab_animation show_activity_fab_bump_animation');
			}
			else
			{
				div.removeClass('hide_activity_fab_animation').addClass('show_activity_fab_animation');
			}
		}
		else
		{
			if(isDisplayed('div#toast_div') && !isWidescreen())
			{
				div.stop().animate({ 'bottom': '128px' }, 250, 'easeOutExpo');
			}
			else
			{
				div.stop().animate({ 'bottom': '68px' }, 250, 'easeOutExpo');
			}				
		}
	}, timeout);
}

function hideActivityFab()
{
	var div = $('div#activity_fab_div');

	if(div.is(':hidden') || isActivity('library', '')) return;

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		div.addClass('hide_activity_fab_animation');
	}
	else
	{
		div.stop().animate({ 'bottom': div.data('cssbottom') }, 250, 'easeOutQuad');
	}
}

function showCoverArtFabAnimation()
{
	var fab = $('div#cover_art_fab_div');

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		fab.addClass('prepare_cover_art_fab_animation');

		setTimeout(function()
		{
			fab.addClass('show_cover_art_fab_animation');
		}, 250);
	}
	else
	{
		setTimeout(function()
		{
			fadeInDiv(fab);
		}, 250);
	}
}

function showCoverArtFabActionAnimation()
{
	if(!ua_supports_csstransitions || !ua_supports_csstransforms3d) return;

	var div = $('div#cover_art_fab_div');

	div.addClass('show_cover_art_fab_action_animation').one(event_transitionend, function()
	{
		div.removeClass('show_cover_art_fab_animation show_cover_art_fab_action_animation');

		setTimeout(function()
		{
			div.addClass('show_cover_art_fab_animation');
		}, 1000);
	});
}

// Toasts

function showToast(text, duration)
{
	clearTimeout(timeout_show_toast);
	clearTimeout(timeout_hide_toast_1);
	clearTimeout(timeout_hide_toast_2);

	var toast_div = $('div#toast_div');
	var fab_div = $('div#activity_fab_div');

	toast_div.css('margin-left', '').show().html(text);

	if(!isWidescreen())
	{
		var width = toast_div.outerWidth();
		var margin = width / 2;

		toast_div.css('margin-left', '-'+margin+'px');

		if(fab_div.is(':visible'))
		{
			if(ua_supports_csstransitions && ua_supports_csstransforms3d)
			{
				if(!fab_div.hasClass('hide_activity_fab_animation')) fab_div.addClass('show_activity_fab_bump_animation');
			}
			else
			{
				if(fab_div.css('bottom') != fab_div.data('cssbottom')) fab_div.animate({ 'bottom': '128px' }, 250, 'easeOutExpo');
			}
		}
	}

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		setTimeout(function()
		{
			toast_div.addClass('show_toast_animation');
		}, 25);
	}
	else
	{
		toast_div.animate({ 'bottom': '64px', 'opacity': '1' }, 250, 'easeOutExpo');
	}

	var duration = parseInt(duration * 1000);

	timeout_show_toast = setTimeout(function()
	{
		clearTimeout(timeout_hide_toast_1);
		clearTimeout(timeout_hide_toast_2);

		timeout_hide_toast_1 = setTimeout(function()
		{
			if(ua_supports_csstransitions && ua_supports_csstransforms3d)
			{
				toast_div.addClass('hide_toast_animation');
			}
			else
			{
				toast_div.animate({ 'opacity': '0' }, 250, 'easeOutExpo');
			}

			timeout_hide_toast_2 = setTimeout(function()
			{
				toast_div.hide().removeClass('show_toast_animation hide_toast_animation').css('bottom', '').css('opacity', '');

				if(ua_supports_csstransitions && ua_supports_csstransforms3d)
				{
					fab_div.removeClass('show_activity_fab_bump_animation');
				}
				else
				{
					if(fab_div.css('bottom') != fab_div.data('cssbottom')) fab_div.animate({ 'bottom': '68px' }, 250, 'easeOutQuad');
				}
			}, 250);
		}, duration);
	}, 250);
}

// Dialogs

function showDialog(dialog)
{
	if(isDisplayed('div#dialog_div')) return;

	$('div#black_cover_div').show();

	setTimeout(function()
	{
		if(ua_supports_csstransitions)
		{
			$('div#black_cover_div').removeClass('show_black_cover_div_animation hide_black_cover_div_animation').addClass('show_black_cover_div_animation');
		}
		else
		{
			$('div#black_cover_div').stop().animate({ 'opacity': '0.5' }, 125, 'easeOutQuad');
		}
	}, 25);

	setTimeout(function()
	{
		$('div#dialog_div').html('<div title="'+dialog.title+'" id="dialog_head_div">'+dialog.title+'</div><div id="dialog_button3_div" class="actions_div img_div img_24_div close_grey_24_img_div" data-actions="hide_dialog" data-highlightclass="light_grey_highlight" onclick="void(0)"></div><div id="dialog_body_div"><div id="'+dialog.body_class+'">'+dialog.body_content+'</div></div>');

		if(dialog.button1 != null && dialog.button2 != null)
		{
			$('div#dialog_button3_div').hide();

			$('div#dialog_div').append('<div id="dialog_buttons_div"><div id="dialog_button1_div" class="actions_div" data-highlightclass="light_grey_highlight" onclick="void(0)">'+dialog.button1.text+'</div><div id="dialog_button2_div" class="actions_div" data-highlightclass="light_grey_highlight" onclick="void(0)">'+dialog.button2.text+'</div></div>');

			for(var i = 0; i < dialog.button1.keys.length; i++)
			{
				$('div#dialog_button1_div').data(dialog.button1.keys[i], dialog.button1.values[i]);
			}

			for(var i = 0; i < dialog.button2.keys.length; i++)
			{
				$('div#dialog_button2_div').data(dialog.button2.keys[i], dialog.button2.values[i]);
			}
		}

		$('div#dialog_div').show();

		var height = $('div#dialog_div').outerHeight();
		var margin = parseInt(height / 2);

		$('div#dialog_div').css('margin-top', '-'+margin+'px');

		var max_body_height = parseInt($('div#dialog_body_div').css('max-height'));
		var body_height = $('div#dialog_body_div')[0].scrollHeight;

		if(body_height > max_body_height)
		{
			scrolling_black_cover_div = true;

			$('div#dialog_body_div').css('overflow-y', 'scroll');
		}

		if(ua_supports_csstransitions && ua_supports_csstransforms3d)
		{
			$('div#dialog_div').addClass('prepare_dialog_animation');

			setTimeout(function()
			{
				$('div#dialog_div').addClass('show_dialog_animation');
			}, 25);
		}
		else
		{
			fadeInDiv('div#dialog_div');
		}

		if(dialog.cookie != null) $.cookie(dialog.cookie.id, dialog.cookie.value, { expires: dialog.cookie.expires });

		setNativeAppStatusBarColor('#000000');

		nativeAppCanCloseCover();
	}, 125);
}

function showActionsDialog(dialog)
{
	var title = dialog.title;
	var actions = dialog.actions;
	var body = '';

	for(var i = 0; i < actions.length; i++)
	{
		var data = '';

		for(var f = 0; f < actions[i].keys.length; f++)
		{
			data += 'data-'+actions[i].keys[f]+'="'+actions[i].values[f]+'" ';
		}

		body += '<div title="'+actions[i].text+'" class="actions_div" '+data+' data-highlightclass="light_grey_highlight" onclick="void(0)">'+actions[i].text+'</div>';
	}

	showDialog({ title: title, body_class: 'dialog_actions_div', body_content: body, button1: null, button2: null, cookie: null });
}

function showDetailsDialog(dialog)
{
	var title = dialog.title;
	var details = dialog.details;
	var body = '';

	for(var i = 0; i < details.length; i++)
	{
		body += '<div title="'+stripHtmlTags(details[i].value)+'">'+details[i].detail+': '+details[i].value+'</div>';
	}

	showDialog({ title: title, body_class: 'dialog_details_div', body_content: body, button1: null, button2: null, cookie: null });
}

function hideDialog()
{
	if(!isDisplayed('div#dialog_div')) return;

	scrolling_black_cover_div = false;

	$('div#dialog_div').hide();

	if(ua_supports_csstransitions && ua_supports_csstransforms3d)
	{
		$('div#dialog_div').removeClass('prepare_dialog_animation show_dialog_animation');
	}
	else
	{
		hideDiv('div#dialog_div');
	}

	setTimeout(function()
	{
		if(isDisplayed('div#dialog_div')) return;

		if(ua_supports_csstransitions)
		{
			$('div#black_cover_div').addClass('hide_black_cover_div_animation').one(event_transitionend, function()
			{
				if(!isDisplayed('div#dialog_div'))
				{
					$('div#black_cover_div').hide().removeClass('show_black_cover_div_animation hide_black_cover_div_animation');

					resetNativeAppStatusBarColor('dialog');
				}
			});
		}
		else
		{
			$('div#black_cover_div').stop().animate({ 'opacity': '0' }, 250, 'easeOutQuad', function()
			{
				if(!isDisplayed('div#dialog_div'))
				{
					$('div#black_cover_div').hide();

					resetNativeAppStatusBarColor('dialog');
				}
			});
		}
	}, 25);

	setTimeout(function()
	{
		checkForDialogs();
	}, 500);

	nativeAppCanCloseCover();
}

function closeDialog()
{
	if(!isDisplayed('div#dialog_div')) return;

	pointer_moved = false;
	$('div#dialog_button3_div').trigger(pointer_event);
}

function checkForDialogs()
{
	if(isDisplayed('div#dialog_div')) return;

	if(!ua_supports_csstransitions || !ua_supports_csstransforms3d)
	{
		var cookie = { id: 'hide_software_accelerated_animations_dialog_'+project_version, value: 'true', expires: 3650 };
		if(!isCookie(cookie.id)) showDialog({ title: 'Browser Warning', body_class: 'dialog_message_div', body_content: 'Your browser does not fully support hardware accelerated animations. Simple animations will be used instead, which may result in a less elegant experience.', button1: { text: 'CLOSE', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'HELP', keys : ['actions', 'uri'], values: ['open_external_activity', project_website+'?requirements'] }, cookie: cookie });
	}

	var latest_version = $.cookie('latest_version');

	if(parseFloat(latest_version) > project_version)
	{
		var cookie = { id: 'hide_update_available_dialog_'+project_version, value: 'true', expires: 7 };
		if(!isCookie(cookie.id)) showDialog({ title: 'Update Available', body_class: 'dialog_message_div', body_content: project_name+' '+latest_version+' has been released!', button1: { text: 'CLOSE', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'DOWNLOAD', keys : ['actions', 'uri'], values: ['open_external_activity', project_website+'?download'] }, cookie: cookie });
	}

	if(ua_is_android)
	{
		if(ua_is_android_app)
		{
			var cookie = { id: 'hide_android_app_versions_mismatch_dialog_'+project_version, value: 'true', expires: 1 };

			if(!isCookie(cookie.id))
			{
				var versions = $.parseJSON(Android.JSgetVersions());

				var app_version = project_version;
				var app_minimum_version = parseFloat(versions[1]);

				var android_app_version = parseFloat(versions[0]);
				var android_app_minimum_version = project_android_app_minimum_version;

				if(app_version < app_minimum_version || android_app_version < android_app_minimum_version) showDialog({ title: 'App Versions Mismatch', body_class: 'dialog_message_div', body_content: 'The '+project_name+' version you are running is not compatible with this Android app version. Make sure you are running the latest version of both '+project_name+' and the Android app.', button1: null, button2: null, cookie: cookie });
			}

			var cookie = { id: 'hide_android_hardware_buttons_dialog', value: 'true', expires: 3650 };
			if(!isCookie(cookie.id)) showDialog({ title: 'Android App Tip', body_class: 'dialog_message_div', body_content: 'You can use the hardware volume buttons on your device to control Spotify\'s volume.<br><br>There are also some extra features that can be enabled in Settings.', button1: null, button2: null, cookie: cookie });

			var installed = parseInt($.cookie('installed_'+project_version));
			var current_time = getCurrentTime();

			var cookie = { id: 'hide_rate_on_google_play_dialog_'+project_version, value: 'true', expires: 3650 };

			if(!isCookie(cookie.id) && current_time > installed + 1000 * 3600 * 24)
			{
				var package_name = Android.JSgetPackageName();
				
				showDialog({ title: 'Like this App?', body_class: 'dialog_message_div', body_content: 'Please rate '+project_name+' on Google Play.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'RATE', keys : ['actions', 'uri'], values: ['hide_dialog open_external_activity', 'market://details?id='+package_name] }, cookie: cookie });
			}

			var cookie = { id: 'hide_make_donation_'+project_version, value: 'true', expires: 3650 };
			if(!isCookie(cookie.id) && current_time > installed + 1000 * 3600 * 48) showDialog({ title: 'Want to Contribute?', body_class: 'dialog_message_div', body_content: 'Please consider making a donation to support the development of '+project_name+'.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'MAKE DONATION', keys : ['actions'], values: ['hide_dialog make_donation'] }, cookie: cookie });
		}
		else
		{
			var cookie = { id: 'hide_android_app_dialog', value: 'true', expires: 1 };
			if(!isCookie(cookie.id)) showDialog({ title: 'Android App', body_class: 'dialog_message_div', body_content: 'You should install the Android app. It will give you an experience much more similar to a native app, with many additional features.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'DOWNLOAD', keys : ['actions', 'uri'], values: ['open_external_activity', 'market://search?q=pub:'+encodeURIComponent(project_developer)] }, cookie: cookie });
		}
	}
	else if(ua_is_ios)
	{
		if(ua_is_standalone)
		{
			var cookie = { id: 'hide_ios_back_gesture_dialog', value: 'true', expires: 3650 };
			if(!isCookie(cookie.id)) showDialog({ title: 'iOS Tip', body_class: 'dialog_message_div', body_content: 'Since you are running fullscreen and your device has no back button, you can swipe in from the right to go back.<br><br>Avoid the multitasking button at the center right of the screen on devices that run iOS 9 or newer that support it.', button1: null, button2: null, cookie: cookie });
		}
		else
		{
			var cookie = { id: 'hide_ios_home_screen_dialog', value: 'true' };

			if(!isCookie(cookie.id))
			{
				if(shc(ua, 'iPad'))
				{
					cookie.expires = 28;
					showDialog({ title: 'iPad Tip', body_class: 'dialog_message_div', body_content: 'Add '+project_name+' to your home screen to get fullscreen like a native app.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'HELP', keys : ['actions', 'uri'], values: ['open_external_activity', project_website+'?add_to_home_screen'] }, cookie: cookie });
				}
				else
				{
					cookie.expires = 1;
					showDialog({ title: 'iPhone/iPod Warning', body_class: 'dialog_message_div', body_content: 'To function correctly, '+project_name+' should be added to your home screen.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'HELP', keys : ['actions', 'uri'], values: ['open_external_activity', project_website+'?add_to_home_screen'] }, cookie: cookie });
				}
			}
		}
	}
	else if(ua_is_os_x && !ua_is_standalone)
	{
		var cookie = { id: 'hide_ox_x_integration_dialog', value: 'true', expires: 3650 };
		if(!isCookie(cookie.id)) showDialog({ title: 'OS X Tip', body_class: 'dialog_message_div', body_content: 'Install Fluid to run '+project_name+' as a standalone app.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'HELP', keys : ['actions', 'uri'], values: ['hide_dialog open_external_activity', project_website+'?os_x_integration'] }, cookie: cookie });
	}
	else if(ua_is_msie && ua_is_pinnable_msie && !window.external.msIsSiteMode())
	{
		var cookie = { id: 'hide_windows_desktop_integration_dialog', value: 'true', expires: 3650 };
		if(!isCookie(cookie.id)) showDialog({ title: 'Windows Desktop Tip', body_class: 'dialog_message_div', body_content: 'Pin '+project_name+' to the taskbar to get additional features.', button1: { text: 'LATER', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'HELP', keys : ['actions', 'uri'], values: ['hide_dialog open_external_activity', project_website+'?windows_desktop_integration'] }, cookie: cookie });
	}

	var cookie = { id: 'hide_spotify_is_unsupported_dialog_'+project_version, value: 'true', expires: 7 };
	if(!isCookie(cookie.id) && project_spotify_is_unsupported) showDialog({ title: 'Unsupported Spotify', body_class: 'dialog_message_div', body_content: '&bull; Playing a single track will repeat it over and over<br>&bull; Manually skipping to the next track will in many cases not play queued tracks, because of the above problem<br>&bull; Starting track radios does not work<br>&bull; Manually playing local files does not work, but plays in playlists<br><br>Click the button below to download the recommended version.', button1: { text: 'CLOSE', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'DOWNLOAD', keys : ['actions', 'uri'], values: ['open_external_activity', project_website+'?downgrade_spotify'] }, cookie: cookie });
}

// Notifications

function requestNotificationsPermission()
{
	if(!ua_supports_notifications) return;

	if(Notification.permission != 'denied') Notification.requestPermission();
}

function showNotification(title, body, icon, onclick, duration)
{
	if(!settings_notifications || !ua_supports_notifications) return;

	if(notification != null) notification.close();

	notification = new Notification(title, { body: body, icon: icon });

	notification.onclick = function()
	{
		if(onclick == 'remote_control_next')
		{
				remoteControl('next');
		}
	}

	var duration = parseInt(duration * 1000);

	clearTimeout(timeout_notification);

	timeout_notification = setTimeout(function()
	{
		notification.close();
	}, duration);
}

// Scrolling

function getScrollPosition()
{
	return $(window).scrollTop();
}

function setScrollPosition(position)
{
	window.scrollTo(0, position);
}

function saveScrollPosition(action)
{
	if(scroll_position_save_disable) return;

	var a = getActivity();
	var time = a.time;

	var position = getScrollPosition();
	var action = (typeof scroll_position[time] != 'undefined' && scroll_position[time].action != null) ? scroll_position[time].action : action;

	scroll_position[time] = { position: position, action: action };

	if(ua_is_ios && ua_is_standalone)
	{
		var cookie = { id: 'current_activity_'+project_version, expires: 1 };

		if(isCookie(cookie.id))
		{
			var cookie_a = $.parseJSON($.cookie(cookie.id));
			cookie_a.scroll_position = position;

			$.cookie(cookie.id, JSON.stringify(cookie_a), { expires: cookie.expires });
		}
	}

	if(position > scrolling_last_position)
	{
		hideActivityFab();
	}
	else if(position < scrolling_last_position)
	{
		showActivityFab(null);
	}

	scrolling_last_position = position;
}

function restoreScrollPosition()
{
	var a = getActivity();
	var time = a.time;

	if($.isEmptyObject(scroll_position) && ua_is_ios && ua_is_standalone)
	{
		var cookie = { id: 'current_activity_'+project_version };

		if(isCookie(cookie.id))
		{
			var cookie_a = $.parseJSON($.cookie(cookie.id));

			setScrollPosition(cookie_a.scroll_position);
		}

		return;	
	}
	else if(typeof scroll_position[time] == 'undefined')
	{
		return;
	}
	
	var position = scroll_position[time].position;
	var action = scroll_position[time].action;

	if(action != null)
	{
		if(action.action == 'show_all_list_items')
		{
			$(action.showitems).show();
			$(action.hideitem).hide();
		}
	}

	setScrollPosition(position);
}

function scrollToNextListHeader()
{
	var length = $('div.list_header_div').length - 1;

	if(scrolling_last_list_header == null)
	{
		var i = 1;
	}
	else
	{
		if(scrolling_last_list_header == length)
		{
			scrolling_last_list_header = null;

			scrollToTop();

			return;
		}

		var i = scrolling_last_list_header + 1;
	}

	scrolling_last_list_header = i;

	var offset = $('div#list_header_'+scrolling_last_list_header+'_div').offset();

	setScrollPosition(offset.top - 64);
}

// Native apps

function nativeAppLoad(is_paused)
{
	if(ua_is_android_app)
	{
		if(settings_keep_screen_on)
		{
			Android.JSkeepScreenOn(true);
		}
		else
		{
			Android.JSkeepScreenOn(false);
		}

		if(settings_pause_on_incoming_call)
		{
			Android.JSsetSharedBoolean('PAUSE_ON_INCOMING_CALL', true);
		}
		else
		{
			Android.JSsetSharedBoolean('PAUSE_ON_INCOMING_CALL', false);
		}

		if(settings_pause_on_outgoing_call)
		{
			Android.JSsetSharedBoolean('PAUSE_ON_OUTGOING_CALL', true);
		}
		else
		{
			Android.JSsetSharedBoolean('PAUSE_ON_OUTGOING_CALL', false);
		}

		if(settings_flip_to_pause)
		{
			Android.JSsetSharedBoolean('FLIP_TO_PAUSE', true);
		}
		else
		{
			Android.JSsetSharedBoolean('FLIP_TO_PAUSE', false);
		}

		if(settings_shake_to_skip)
		{
			Android.JSsetSharedBoolean('SHAKE_TO_SKIP', true);
			Android.JSsetSharedString('SHAKE_TO_SKIP_SENSITIVITY', settings_shake_sensitivity);
		}
		else
		{
			Android.JSsetSharedBoolean('SHAKE_TO_SKIP', false);
		}

		if(settings_persistent_notification)
		{
			Android.JSsetSharedBoolean('PERSISTENT_NOTIFICATION', true);
		}
		else
		{
			Android.JSsetSharedBoolean('PERSISTENT_NOTIFICATION', false);
		}

		var shareUri = urlToUri(Android.JSgetSharedString('SHARE_URI'));

		if(shareUri != '')
		{
			var type = getUriType(shareUri);

			if(type == 'playlist')
			{
				$.get('profile.php?is_authorized_with_spotify', function(xhr_data)
				{
					browsePlaylist(shareUri, '', stringToBoolean(xhr_data));
				});
			}
			else if(type == 'track' || type == 'album')
			{
				browseAlbum(shareUri);
			}
			else if(type == 'artist')
			{
				browseArtist(shareUri);
			}
			else
			{
				showToast('Invalid URI', 4);
			}

			Android.JSsetSharedString('SHARE_URI', '');
		}

		if('JSstartService' in window.Android) Android.JSstartService();
	}
}

function setNativeAppStatusBarColor(color)
{
	if(ua_is_android_app && 'JSsetStatusBarColor' in window.Android) Android.JSsetStatusBarColor(color);
}

function setNativeAppStatusBarColorFromImage(uri)
{
	if(ua_is_android_app && 'JSsetStatusBarColorFromImage' in window.Android && !isWidescreen()) Android.JSsetStatusBarColorFromImage(uri);
}

function resetNativeAppStatusBarColor(hide)
{
	if(hide == 'dialog' && isVisible('div#nowplaying_div'))
	{
		setNativeAppStatusBarColor('#212121');
	}
	else if($('div#cover_art_art_div').length)
	{
		setNativeAppStatusBarColor('cover_art');
	}
	else
	{
		setNativeAppStatusBarColor('primary');
	}
}

function nativeAppAction(action)
{
	if(action == 'play_pause')
	{
		remoteControl('play_pause');
	}
	else if(action == 'pause')
	{
		remoteControl('pause');
	}
	else if(action == 'volume_down')
	{
		adjustVolume('down');
	}
	else if(action == 'volume_up')
	{
		adjustVolume('up');
	}
	else if(action == 'back')
	{
		goBack();
	}
	else if(action == 'menu')
	{
		toggleMenu();
	}
	else if(action == 'search')
	{
		changeActivity('search', '', '');
	}
}

function changeNativeAppComputer()
{
	if(ua_is_android_app) showDialog({ title: 'Change Computer', body_class: 'dialog_message_div', body_content: 'You can always go back to the list of computers by long-pressing the back button on your device.', button1: { text: 'CANCEL', keys : ['actions'], values: ['hide_dialog'] }, button2: { text: 'CONTINUE', keys: ['actions'], values: ['confirm_change_native_app_computer'] }, cookie: null });
}

function confirmChangeNativeAppComputer()
{
	if(ua_is_android_app) Android.JSfinishActivity();
}

function nativeAppCanCloseCover()
{
	if(!isWidescreen() && menuIsVisible() || isDisplayed('div#top_actionbar_overflow_actions_div') || isVisible('div#nowplaying_div') || isDisplayed('div#nowplaying_actionbar_overflow_actions_div') || isDisplayed('div#dialog_div'))
	{
		if(ua_is_android_app) Android.JSsetSharedBoolean('CAN_CLOSE_COVER', true);
	}
	else
	{
		if(ua_is_android_app) Android.JSsetSharedBoolean('CAN_CLOSE_COVER', false);
	}
}

// Desktop integration

function integrateInMSIE()
{
	try
	{
		ie_thumbnail_button_previous = window.external.msSiteModeAddThumbBarButton('img/previous.ico?'+project_serial, 'Previous');
		ie_thumbnail_button_play_pause = window.external.msSiteModeAddThumbBarButton('img/play.ico?'+project_serial, 'Play');
		ie_thumbnail_button_next = window.external.msSiteModeAddThumbBarButton('img/next.ico?'+project_serial, 'Next');
		ie_thumbnail_button_volume_mute = window.external.msSiteModeAddThumbBarButton('img/volume-mute.ico?'+project_serial, 'Mute');
		ie_thumbnail_button_volume_down = window.external.msSiteModeAddThumbBarButton('img/volume-down.ico?'+project_serial, 'Volume Down');
		ie_thumbnail_button_volume_up = window.external.msSiteModeAddThumbBarButton('img/volume-up.ico?'+project_serial, 'Volume Up');

		ie_thumbnail_button_style_play = 0;
		ie_thumbnail_button_style_pause = window.external.msSiteModeAddButtonStyle(ie_thumbnail_button_play_pause, 'img/pause.ico?'+project_serial, 'Pause');

		window.external.msSiteModeShowThumbBar();

		document.addEventListener('msthumbnailclick', onClickMSIEthumbnailButton, false);

		ua_is_integrated_msie = true;
	}
	catch(exception)
	{

	}
}

function onClickMSIEthumbnailButton(button)
{
	if(button.buttonID == ie_thumbnail_button_previous)
	{
		remoteControl('previous');
	}
	else if(button.buttonID == ie_thumbnail_button_play_pause)
	{
		remoteControl('play_pause');
	}
	else if(button.buttonID == ie_thumbnail_button_next)
	{
		remoteControl('next');
	}
	else if(button.buttonID == ie_thumbnail_button_volume_mute)
	{
		adjustVolume('mute');
	}
	else if(button.buttonID == ie_thumbnail_button_volume_down)
	{
		adjustVolume('down');
	}
	else if(button.buttonID == ie_thumbnail_button_volume_up)
	{
		adjustVolume('up');
	}
}

// Keyboard shortcuts

function enableKeyboardShortcuts()
{
	$.getScript('js/mousetrap.js?'+project_serial, function()
	{
		$.getScript('js/keyboard-shortcuts.js?'+project_serial, function()
		{
			Mousetrap.bind('1', function(event) { onKeyboardKeyPressed('1', event); }, 'keyup');
			Mousetrap.bind('2', function(event) { onKeyboardKeyPressed('2', event); }, 'keyup');
			Mousetrap.bind('3', function(event) { onKeyboardKeyPressed('3', event); }, 'keyup');
			Mousetrap.bind('q', function(event) { onKeyboardKeyPressed('q', event); }, 'keyup');
			Mousetrap.bind('w', function(event) { onKeyboardKeyPressed('w', event); }, 'keyup');
			Mousetrap.bind('e', function(event) { onKeyboardKeyPressed('e', event); }, 'keyup');
			Mousetrap.bind('r', function(event) { onKeyboardKeyPressed('r', event); }, 'keyup');
			Mousetrap.bind('a', function(event) { onKeyboardKeyPressed('a', event); }, 'keyup');
			Mousetrap.bind('s', function(event) { onKeyboardKeyPressed('s', event); }, 'keyup');
			Mousetrap.bind('d', function(event) { onKeyboardKeyPressed('d', event); }, 'keyup');
			Mousetrap.bind('z', function(event) { onKeyboardKeyPressed('z', event); }, 'keyup');
			Mousetrap.bind('x', function(event) { onKeyboardKeyPressed('x', event); }, 'keyup');
			Mousetrap.bind('c', function(event) { onKeyboardKeyPressed('c', event); }, 'keyup');
			Mousetrap.bind('tab', function(event) { onKeyboardKeyPressed('tab', event); }, 'keydown');
			Mousetrap.bind('enter', function(event) { onKeyboardKeyPressed('enter', event); }, 'keyup');
			Mousetrap.bind('esc', function(event) { onKeyboardKeyPressed('esc', event); }, 'keyup');

			Mousetrap.stopCallback = function(event, element, key)
			{
				if($(element).is('input:text'))
				{
					if(key == 'tab')
					{
						event.preventDefault();

						blurTextInput();
					}

					return true;
				}
			}
		});
	});
}

// Check stuff

function isWidescreen()
{
	return ($(window).width() >= 1024)
}

function checkForErrors()
{
	var cookie = { id: 'test', value: 'true' };
	$.cookie(cookie.id, cookie.value);

	var error_code = (!isCookie(cookie.id)) ? 5 : project_error_code;

	$.removeCookie(cookie.id);

	return error_code;
}

function checkForUpdates(type)
{
	var latest_version_cookie = { id: 'latest_version', expires: 3650 };
	var latest_version = parseFloat($.cookie(latest_version_cookie.id));

	var last_update_check_cookie = { id: 'last_update_check', value: getCurrentTime(), expires: 3650 };
	var last_update_check = $.cookie(last_update_check_cookie.id);

	if(type == 'manual')
	{
		activityLoading();

		xhr_activity = $.get('main.php?check_for_updates', function(xhr_data)
		{
			if(xhr_data == 'error')
			{
				$.removeCookie(latest_version_cookie.id);
			}
			else
			{
				$.cookie(latest_version_cookie.id, xhr_data, { expires: latest_version_cookie.expires });
			}

			changeActivity('settings', '', '');
		});

		$.cookie(last_update_check_cookie.id, last_update_check_cookie.value, { expires: last_update_check_cookie.expires });
	}
	else if(type == 'auto')
	{
		if(!isCookie(last_update_check_cookie.id) || !isNaN(last_update_check) && getCurrentTime() - last_update_check > 1000 * 3600 * 24)
		{
			$.get('main.php?check_for_updates', function(xhr_data)
			{
				if(xhr_data == 'error')
				{
					$.removeCookie(latest_version_cookie.id);
				}
				else
				{
					$.cookie(latest_version_cookie.id, xhr_data, { expires: latest_version_cookie.expires });
				}
			});

			$.cookie(last_update_check_cookie.id, last_update_check_cookie.value, { expires: last_update_check_cookie.expires });
		}

		if(latest_version > project_version) $('div#update_available_indicator_div').addClass('menu_small_item_update_available_indicator_div');
	}
}

function checkIfNewVersionIsInstalled(version)
{
	if(version > project_version) showDialog({ title: 'New Version Installed', body_class: 'dialog_message_div', body_content: 'A new version of '+project_name+' has been installed!<br><br>You must now start the daemon on the computer running Spotify. Tap Help below to find out how. After it is done, tap Reload.', button1: { text: 'RELOAD', keys : ['actions'], values: ['reload_app'] }, button2: { text: 'HELP', keys : ['actions', 'uri'], values: ['open_external_activity', project_website+'?start_daemon'] }, cookie: null });
}

function shc(string, characters)
{
	var string = string.toLowerCase();
	var characters = characters.toLowerCase();

	return (string.indexOf(characters) != -1);
}

function isDisplayed(id)
{
	return ($(id).is(':visible'));
}

function isVisible(id)
{
	return ($(id).css('visibility') == 'visible');
}

function textInputHasFocus()
{
	return ($('input:text').is(':focus'));
}

function getCurrentTime()
{
	return new Date().getTime();
}

function getMSIEVersion()
{
	var re = ua.match(/Mozilla\/\d+\.\d+ \(Windows NT \d+\.\d+;.*?Trident\/\d+\.\d+;.*?rv:(\d+)\.\d+\)/);
	return (re) ? parseInt(re[1]) : 0;
}

// Manipulate stuff

function booleanToString(bool)
{
	return (bool) ? 'true' : 'false';
}

function stringToBoolean(string)
{
	return (string == 'true');
}

function hsc(string)
{
	return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function stripHtmlTags(string)
{
	return String(string).replace(/<[^>]+>/g, '');
}

function ucfirst(string)
{
	return string.charAt(0).toUpperCase() + string.slice(1);
}

function hexToRgb(hex)
{
	var rgb = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

	return parseInt(rgb[1], 16)+', '+parseInt(rgb[2], 16)+', '+ parseInt(rgb[3], 16);
}

// URIs

function browseUri(uri, is_authorized_with_spotify)
{
	var uri = urlToUri(uri);
	var type = getUriType(uri);

	if(type == 'playlist')
	{
		browsePlaylist(uri, '', is_authorized_with_spotify);
	}
	else if(type == 'artist')
	{
		browseArtist(uri);
	}
	else if(type == 'album')
	{
		browseAlbum(uri);
	}
	else if(type == 'track')
	{
		playUri(uri);
	}
	else if(type == 'genre')
	{
		var genre = uri.replace(/^spotify:app:genre:(.*?)$/, '$1');

		changeActivity('browse', 'genres', 'name='+ucfirst(genre)+'&genre='+genre);
	}
	else if(uri.match(/^https?:\/\//))
	{
		openExternalActivity(uri);
	}
	else
	{
		showToast('Invalid URI', 4);
	}
}

function getUriType(uri)
{
	var type = 'unknown';

	if(uri.match(/^spotify:user:[^:]+:playlist:\w{22}$/) || uri.match(/^https?:\/\/[^\.]+\.spotify\.com\/user\/[^\/]+\/playlist\/\w{22}$/))
	{
		type = 'playlist';
	}
	else if(uri.match(/^spotify:artist:\w{22}$/) || uri.match(/^https?:\/\/[^\.]+\.spotify\.com\/artist\/\w{22}$/))
	{
		type = 'artist';
	}
	else if(uri.match(/^spotify:album:\w{22}$/) || uri.match(/^https?:\/\/[^\.]+\.spotify\.com\/album\/\w{22}$/))
	{
		type = 'album';
	}
	else if(uri.match(/^spotify:track:\w{22}$/) || uri.match(/^https?:\/\/[^\.]+\.spotify\.com\/track\/\w{22}$/))
	{
		type = 'track';
	}
	else if(uri.match(/^spotify:local:/) || uri.match(/^https?:\/\/[^\.]+\.spotify\.com\/local\//))
	{
		type = 'local';
	}
	else if(uri.match(/^spotify:user:[^:]+$/) || uri.match(/^https?:\/\/[^\.]+\.spotify\.com\/user\/[^\/]+$/))
	{
		type = 'user';
	}
	else if(uri.match(/^spotify:app:genre:\w+$/) || uri.match(/^https?:\/\/spotify:app:genre:\w+$/))
	{
		type = 'genre';
	}

	return type;
}

function uriToUrl(uri)
{
	var type = getUriType(uri);

	if(type == 'playlist')
	{
		uri = uri.replace(/^spotify:user:(.*?):playlist:(.*?)$/, 'http://open.spotify.com/user/$1/playlist/$2');
	}
	else if(type == 'artist')
	{
		uri = uri.replace(/^spotify:artist:(.*?)$/, 'http://open.spotify.com/artist/$1');
	}
	else if(type == 'album')
	{
		uri = uri.replace(/^spotify:album:(.*?)$/, 'http://open.spotify.com/album/$1');
	}
	else if(type == 'track')
	{
		uri = uri.replace(/^spotify:track:(.*?)$/, 'http://open.spotify.com/track/$1');
	}
	else if(type == 'local')
	{
		uri = uri.replace(/:/g, '/').replace(/^spotify\/local\/(.*?)$/, 'http://open.spotify.com/local/$1');
	}

	return uri;
}

function urlToUri(uri)
{
	var type = getUriType(uri);

	if(type == 'playlist')
	{
		uri = uri.replace(/^https?:\/\/[^\.]+\.spotify\.com\/user\/(.*?)\/playlist\/(.*?)$/, 'spotify:user:$1:playlist:$2');
	}
	else if(type == 'artist')
	{
		uri = uri.replace(/^https?:\/\/[^\.]+\.spotify\.com\/artist\/(.*?)$/, 'spotify:artist:$1');
	}
	else if(type == 'album')
	{
		uri = uri.replace(/^https?:\/\/[^\.]+\.spotify\.com\/album\/(.*?)$/, 'spotify:album:$1');
	}
	else if(type == 'track')
	{
		uri = uri.replace(/^https?:\/\/[^\.]+\.spotify\.com\/track\/(.*?)$/, 'spotify:track:$1');
	}
	else if(type == 'local')
	{
		uri = uri.replace(/^https?:\/\/[^\.]+\.spotify\.com\/local\/(.*?)$/, 'spotify:local:$1').replace(/\//g, ':');
	}

	return uri;
}

// Cookies

function isCookie(id)
{
	return (typeof $.cookie(id) != 'undefined');
}

function removeAllCookies()
{
	var cookies = $.cookie();

	for(var cookie in cookies)
	{
		if(cookies.hasOwnProperty(cookie)) $.removeCookie(cookie);
	}
}