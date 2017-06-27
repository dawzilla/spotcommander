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
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.graphics.BitmapFactory;
import android.os.Build;
import android.support.v4.app.NotificationCompat;
import android.support.v4.app.NotificationManagerCompat;

import com.google.android.gms.wearable.MessageEvent;
import com.google.android.gms.wearable.WearableListenerService;

public class NotificationWearableListenerService extends WearableListenerService
{
    private final static String MESSAGE_PATH = "/notification";

    private final static int NOTIFICATION_ID = 1;

    private final Context mContext = this;

    @Override
    public void onMessageReceived(MessageEvent messageEvent)
    {
        if(messageEvent.getPath().equals(MESSAGE_PATH))
        {
            NotificationManagerCompat notificationManager = NotificationManagerCompat.from(mContext);
            NotificationCompat.Builder notificationBuilder = new NotificationCompat.Builder(mContext);

            String action = new String(messageEvent.getData());

            if(action.equals("show_notification"))
            {
                final Intent launchActivityIntent = new Intent(mContext, MainActivity.class);
                launchActivityIntent.setAction("android.intent.action.MAIN");
                launchActivityIntent.addCategory("android.intent.category.LAUNCHER");
                final PendingIntent launchActivityPendingIntent = PendingIntent.getActivity(mContext, 0, launchActivityIntent, PendingIntent.FLAG_CANCEL_CURRENT);

                notificationBuilder.setWhen(0)
                        .setOngoing(true)
                        .setContentTitle(getString(R.string.notification_title))
                        .setContentText(getString(R.string.notification_text))
                        .setContentIntent(launchActivityPendingIntent)
                        .setDefaults(Notification.DEFAULT_VIBRATE)
                        .setLargeIcon(BitmapFactory.decodeResource(getResources(), R.mipmap.ic_launcher))
                        .setSmallIcon(R.drawable.ic_play_arrow_white_24dp);

                if(Build.VERSION.SDK_INT >= Build.VERSION_CODES.JELLY_BEAN) notificationBuilder.setPriority(Notification.PRIORITY_HIGH);

                notificationManager.notify(NOTIFICATION_ID, notificationBuilder.build());
            }
            else
            {
                notificationManager.cancel(NOTIFICATION_ID);
            }
        }
        else
        {
            super.onMessageReceived(messageEvent);
        }
    }
}