package net.olejon.spotcommander;

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

import com.google.android.gms.wearable.MessageEvent;
import com.google.android.gms.wearable.WearableListenerService;

public class RemoteControlWearableListenerService extends WearableListenerService
{
    private static final String MESSAGE_PATH = "/remote_control";

    private final MyTools mTools = new MyTools(this);

    @Override
    public void onMessageReceived(MessageEvent messageEvent)
    {
        if(messageEvent.getPath().equals(MESSAGE_PATH))
        {
            String action = new String(messageEvent.getData());
            String subAction = "";

            switch(action)
            {
                case "adjust_spotify_volume_mute":
                {
                    action = "adjust_spotify_volume";
                    subAction = "mute";
                    break;
                }
                case "adjust_spotify_volume_down":
                {
                    action = "adjust_spotify_volume";
                    subAction = "down";
                    break;
                }
                case "adjust_spotify_volume_up":
                {
                    action = "adjust_spotify_volume";
                    subAction = "up";
                    break;
                }
            }

            mTools.remoteControl(mTools.getSharedPreferencesLong("LAST_COMPUTER_ID"), action, subAction);
        }
        else
        {
            super.onMessageReceived(messageEvent);
        }
    }
}