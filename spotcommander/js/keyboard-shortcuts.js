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

function onKeyboardKeyPressed(key, event)
{
	if(key == '1')
	{
		adjustVolume('mute');
	}
	else if(key == '2')
	{
		adjustVolume('down');
	}
	else if(key == '3')
	{
		adjustVolume('up');
	}
	else if(key == 'q')
	{
		changeActivity('playlists', '', '');
	}
	else if(key == 'w')
	{
		changeActivity('library', '', '');
	}
	else if(key == 'e')
	{
		changeActivity('browse', '', '');
	}
	else if(key == 'r')
	{
		changeActivity('search', '', '');
	}
	else if(key == 'a')
	{
		toggleNowplaying();
	}
	else if(key == 's')
	{
		changeActivity('recently-played', '', '');
	}
	else if(key == 'd')
	{
		changeActivity('queue', '', '');
	}
	else if(key == 'z')
	{
		remoteControl('previous');
	}
	else if(key == 'x')
	{
		remoteControl('play_pause');
	}
	else if(key == 'c')
	{
		remoteControl('next');
	}
	else if(key == 'tab')
	{
		event.preventDefault();

		if($('input:text').length)
		{
			focusTextInput('input:text');
		}
	}
	else if(key == 'enter')
	{
		if(isDisplayed('div#dialog_div'))
		{
			event.preventDefault();

			if($('div#dialog_button2_div').length)
			{
				pointer_moved = false;
				$('div#dialog_button2_div').trigger(pointer_event);
			}
		}
	}
	else if(key == 'esc')
	{
		event.preventDefault();

		goBack();
	}
}