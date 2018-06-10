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

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Build;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

import java.util.Map;

public class MyFirebaseMessagingService extends FirebaseMessagingService
{
	private final static String NOTIFICATION_CHANNEL_MESSAGE = "net.olejon.spotcommander.NOTIFICATION_CHANNEL_MESSAGE";

	private final static int NOTIFICATION_MESSAGE_ID = 1;

	private final Context mContext = this;

	@Override
	public void onMessageReceived(RemoteMessage remoteMessage)
	{
		if(remoteMessage.getData().size() > 0 && remoteMessage.getData().containsKey("title") && remoteMessage.getData().containsKey("text") && remoteMessage.getData().containsKey("big_text") && remoteMessage.getData().containsKey("uri_text") && remoteMessage.getData().containsKey("uri"))
		{
			long sentTime = remoteMessage.getSentTime();

			Map<String,String> data = remoteMessage.getData();

			String title = data.get("title");
			String text = data.get("text");
			String bigText = data.get("big_text");
			String uriText = data.get("uri_text");
			String uri = data.get("uri");

			NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

			if(notificationManager != null)
			{
				Notification.Builder notificationBuilder = new Notification.Builder(mContext);

				notificationBuilder.setWhen(sentTime)
						.setAutoCancel(true)
						.setContentTitle(title)
						.setContentText(text)
						.setStyle(new Notification.BigTextStyle().bigText(bigText))
						.setSmallIcon(R.drawable.ic_play_arrow_white_24dp)
						.setLargeIcon(BitmapFactory.decodeResource(getResources(), R.drawable.app_icon));

				if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) notificationBuilder.setColor(getResources().getColor(R.color.light_green));

				if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.O)
				{
					NotificationChannel notificationChannel = new NotificationChannel(NOTIFICATION_CHANNEL_MESSAGE, getString(R.string.notification_channel_message), NotificationManager.IMPORTANCE_HIGH);
					notificationChannel.setLockscreenVisibility(Notification.VISIBILITY_PUBLIC);
					notificationChannel.setShowBadge(true);
					notificationChannel.setDescription(text);
					notificationManager.createNotificationChannel(notificationChannel);
					notificationBuilder.setChannelId(NOTIFICATION_CHANNEL_MESSAGE);
				}
				else
				{
					notificationBuilder.setDefaults(Notification.DEFAULT_SOUND|Notification.DEFAULT_VIBRATE|Notification.DEFAULT_LIGHTS).setPriority(Notification.PRIORITY_HIGH);
				}

				Intent actionIntent;

				if(uri.equals(""))
				{
					actionIntent = new Intent(mContext, MainActivity.class);
					actionIntent.setAction("android.intent.action.MAIN");
					actionIntent.addCategory("android.intent.category.LAUNCHER");
				}
				else
				{
					actionIntent = new Intent(Intent.ACTION_VIEW, Uri.parse(uri));
				}

				PendingIntent actionPendingIntent = PendingIntent.getActivity(mContext, 0, actionIntent, PendingIntent.FLAG_CANCEL_CURRENT);

				notificationBuilder.setContentIntent(actionPendingIntent).addAction(R.drawable.ic_play_arrow_white_24dp, uriText, actionPendingIntent);

				notificationManager.notify(NOTIFICATION_MESSAGE_ID, notificationBuilder.build());
			}
		}
	}

	@Override
	public void onDeletedMessages()
	{
		NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

		if(notificationManager != null) notificationManager.cancel(NOTIFICATION_MESSAGE_ID);
	}
}