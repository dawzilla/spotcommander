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

import android.app.PendingIntent;
import android.appwidget.AppWidgetManager;
import android.appwidget.AppWidgetProvider;
import android.content.Context;
import android.content.Intent;
import android.support.annotation.NonNull;
import android.widget.RemoteViews;

public class Widget extends AppWidgetProvider
{
	private final static String WIDGET_INTENT_EXTRA = "net.olejon.spotcommander.WIDGET_INTENT_EXTRA";

	@Override
    public void onUpdate(Context context, AppWidgetManager appWidgetManager, int[] appWidgetIds)
    {
        for(int appWidgetId : appWidgetIds)
        {
            String id = String.valueOf(appWidgetId);

            final Intent launchActivityIntent = new Intent(context, MainActivity.class);
            launchActivityIntent.setAction("android.intent.action.MAIN");
            launchActivityIntent.addCategory("android.intent.category.LAUNCHER");
            final PendingIntent launchActivityPendingIntent = PendingIntent.getActivity(context, appWidgetId, launchActivityIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent previousIntent = new Intent(context, Widget.class);
            previousIntent.setAction("previous");
            previousIntent.putExtra(WIDGET_INTENT_EXTRA, new String[] {id, "previous", ""});
            final PendingIntent previousPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, previousIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent seekBackIntent = new Intent(context, Widget.class);
            seekBackIntent.setAction("seek_back");
            seekBackIntent.putExtra(WIDGET_INTENT_EXTRA, new String[] {id, "seek_back", ""});
            final PendingIntent seekBackPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, seekBackIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent playPauseIntent = new Intent(context, Widget.class);
            playPauseIntent.setAction("play_pause");
            playPauseIntent.putExtra(WIDGET_INTENT_EXTRA, new String[] {id, "play_pause", ""});
            final PendingIntent playPausePendingIntent = PendingIntent.getBroadcast(context, appWidgetId, playPauseIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent seekForwardIntent = new Intent(context, Widget.class);
            seekForwardIntent.setAction("seek_forward");
            seekForwardIntent.putExtra(WIDGET_INTENT_EXTRA, new String[] {id, "seek_forward", ""});
            final PendingIntent seekForwardPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, seekForwardIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final Intent nextIntent = new Intent(context, Widget.class);
            nextIntent.setAction("next");
            nextIntent.putExtra(WIDGET_INTENT_EXTRA, new String[] {id, "next", ""});
            final PendingIntent nextPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, nextIntent, PendingIntent.FLAG_CANCEL_CURRENT);

            final RemoteViews views = new RemoteViews(context.getPackageName(), R.layout.widget);

            views.setOnClickPendingIntent(R.id.widget_launcher_button, launchActivityPendingIntent);
            views.setOnClickPendingIntent(R.id.widget_previous_button, previousPendingIntent);
            views.setOnClickPendingIntent(R.id.widget_seek_back_button, seekBackPendingIntent);
            views.setOnClickPendingIntent(R.id.widget_play_button, playPausePendingIntent);
            views.setOnClickPendingIntent(R.id.widget_seek_forward_button, seekForwardPendingIntent);
            views.setOnClickPendingIntent(R.id.widget_next_button, nextPendingIntent);

            appWidgetManager.updateAppWidget(appWidgetId, views);
        }
    }

	@Override
	public void onReceive(@NonNull Context context, @NonNull Intent intent)
	{
		super.onReceive(context, intent);

		final MyTools mTools = new MyTools(context);

        if(!intent.getAction().contains("android"))
        {
            final String[] action = intent.getStringArrayExtra(WIDGET_INTENT_EXTRA);

            mTools.remoteControl(mTools.getSharedPreferencesLong("WIDGET_"+action[0]+"_COMPUTER_ID"), action[1], action[2]);
        }
	}
}