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

public class WidgetLarge extends AppWidgetProvider
{
	public final static String WIDGET_LARGE_INTENT_EXTRA = "net.olejon.spotcommander.WIDGET_LARGE_INTENT_EXTRA";

	// Update
	@Override
	public void onUpdate(Context context, AppWidgetManager appWidgetManager, int[] appWidgetIds)
	{
		for(int appWidgetId : appWidgetIds)
		{
			String id = String.valueOf(appWidgetId);

			Intent launchActivityIntent = new Intent(context, MainActivity.class);
			launchActivityIntent.setAction("android.intent.action.MAIN");
			launchActivityIntent.addCategory("android.intent.category.LAUNCHER");
			PendingIntent launchActivityPendingIntent = PendingIntent.getActivity(context, appWidgetId, launchActivityIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent previousIntent = new Intent(context, WidgetLarge.class);
			previousIntent.setAction("previous");
			previousIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "previous", ""});
			PendingIntent previousPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, previousIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent seekBackIntent = new Intent(context, WidgetLarge.class);
			seekBackIntent.setAction("seek_back");
			seekBackIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "seek_back", ""});
			PendingIntent seekBackPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, seekBackIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent playPauseIntent = new Intent(context, WidgetLarge.class);
			playPauseIntent.setAction("play_pause");
			playPauseIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "play_pause", ""});
			PendingIntent playPausePendingIntent = PendingIntent.getBroadcast(context, appWidgetId, playPauseIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent seekForwardIntent = new Intent(context, WidgetLarge.class);
			seekForwardIntent.setAction("seek_forward");
			seekForwardIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "seek_forward", ""});
			PendingIntent seekForwardPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, seekForwardIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent nextIntent = new Intent(context, WidgetLarge.class);
			nextIntent.setAction("next");
			nextIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "next", ""});
			PendingIntent nextPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, nextIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent launchQuitIntent = new Intent(context, WidgetLarge.class);
			launchQuitIntent.setAction("launch_quit");
			launchQuitIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "launch_quit", ""});
			PendingIntent launchQuitPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, launchQuitIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent volumeMuteIntent = new Intent(context, WidgetLarge.class);
			volumeMuteIntent.setAction("adjust_volume_mute");
			volumeMuteIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "adjust_volume", "mute"});
			PendingIntent volumeMutePendingIntent = PendingIntent.getBroadcast(context, appWidgetId, volumeMuteIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent volumeDownIntent = new Intent(context, WidgetLarge.class);
			volumeDownIntent.setAction("adjust_volume_down");
			volumeDownIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "adjust_volume", "down"});
			PendingIntent volumeDownPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, volumeDownIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent volumeUpIntent = new Intent(context, WidgetLarge.class);
			volumeUpIntent.setAction("adjust_volume_up");
			volumeUpIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, new String[] {id, "adjust_volume", "up"});
			PendingIntent volumeUpPendingIntent = PendingIntent.getBroadcast(context, appWidgetId, volumeUpIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			Intent playlistsIntent = new Intent(context, PlaylistsActivity.class);
			playlistsIntent.putExtra(WIDGET_LARGE_INTENT_EXTRA, id);
			PendingIntent playlistsPendingIntent = PendingIntent.getActivity(context, appWidgetId, playlistsIntent, PendingIntent.FLAG_CANCEL_CURRENT);

			RemoteViews views = new RemoteViews(context.getPackageName(), R.layout.widget_large);

			views.setOnClickPendingIntent(R.id.widget_launcher_button, launchActivityPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_previous_button, previousPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_seek_back_button, seekBackPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_play_button, playPausePendingIntent);
			views.setOnClickPendingIntent(R.id.widget_seek_forward_button, seekForwardPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_next_button, nextPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_power_button, launchQuitPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_volume_mute_button, volumeMutePendingIntent);
			views.setOnClickPendingIntent(R.id.widget_volume_down_button, volumeDownPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_volume_up_button, volumeUpPendingIntent);
			views.setOnClickPendingIntent(R.id.widget_playlists_button, playlistsPendingIntent);

			appWidgetManager.updateAppWidget(appWidgetId, views);
		}
	}

	// Receive
	@Override
	public void onReceive(@NonNull Context context, @NonNull Intent intent)
	{
		super.onReceive(context, intent);

		MyTools mTools = new MyTools(context);

		if(intent.getAction() != null && !intent.getAction().contains("android"))
		{
			String[] action = intent.getStringArrayExtra(WIDGET_LARGE_INTENT_EXTRA);

			mTools.remoteControl(mTools.getSharedPreferencesLong("WIDGET_"+action[0]+"_COMPUTER_ID"), action[1], action[2]);
		}
	}
}