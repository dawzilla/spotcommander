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

import android.app.IntentService;
import android.app.NotificationManager;
import android.content.Context;
import android.content.Intent;

public class RemoteControlIntentService extends IntentService
{
	public final static String REMOTE_CONTROL_INTENT_SERVICE_EXTRA = "net.olejon.spotcommander.REMOTE_CONTROL_INTENT_SERVICE_EXTRA";

	private final MyTools mTools = new MyTools(this);

	public RemoteControlIntentService()
	{
		super("RemoteControlIntentService");
	}

	// Intent
	@Override
	protected void onHandleIntent(Intent intent)
	{
		final NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

		final long computerId = intent.getLongExtra(REMOTE_CONTROL_INTENT_SERVICE_EXTRA, 0);

		String action = intent.getAction();
		String subAction = "";

		if(action.equals("hide_notification"))
		{
			notificationManager.cancel(WebViewActivity.NOTIFICATION_ID);
		}
		else
		{
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

            mTools.remoteControl(computerId, action, subAction);
		}
	}
}