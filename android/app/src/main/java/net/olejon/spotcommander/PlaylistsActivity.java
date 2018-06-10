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

import android.content.Context;
import android.content.Intent;
import android.content.pm.ActivityInfo;
import android.os.Bundle;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.widget.Toolbar;
import android.util.Base64;
import android.view.MenuItem;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ListView;
import android.widget.ProgressBar;
import android.widget.TextView;

import com.android.volley.DefaultRetryPolicy;
import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.BasicNetwork;
import com.android.volley.toolbox.DiskBasedCache;
import com.android.volley.toolbox.HurlStack;
import com.android.volley.toolbox.JsonObjectRequest;

import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.HashMap;
import java.util.Iterator;

public class PlaylistsActivity extends AppCompatActivity
{
	private final Context mContext = this;

	private final MyTools mTools = new MyTools(mContext);

	private final ArrayList<String> mPlaylistUris = new ArrayList<>();

	private ProgressBar mProgressBar;
	private ListView mListView;

	// Create activity
	@Override
	protected void onCreate(Bundle savedInstanceState)
	{
		super.onCreate(savedInstanceState);

		// Connected?
		if(!mTools.isDeviceConnected())
		{
			mTools.showToast(getString(R.string.device_not_connected), 1);

			finish();

			return;
		}

		// Allow landscape?
		if(!mTools.allowLandscape()) setRequestedOrientation(ActivityInfo.SCREEN_ORIENTATION_PORTRAIT);

		// Intent
		Intent intent = getIntent();

		// Computer
		final long computerId = mTools.getSharedPreferencesLong("WIDGET_"+intent.getStringExtra(WidgetLarge.WIDGET_LARGE_INTENT_EXTRA)+"_COMPUTER_ID");

		final String[] computer = mTools.getComputer(computerId);

		// Layout
		setContentView(R.layout.activity_playlists);

		// Toolbar
		Toolbar toolbar = findViewById(R.id.playlists_toolbar);
		toolbar.setNavigationIcon(R.drawable.ic_close_white_24dp);
		toolbar.setTitle(getString(R.string.playlists_title));

		setSupportActionBar(toolbar);

		// Progress bar
		mProgressBar = findViewById(R.id.playlists_progressbar);
		mProgressBar.setVisibility(View.VISIBLE);

		// Listview
		mListView = findViewById(R.id.playlists_list);

		// Get playlists
		final RequestQueue requestQueue = new RequestQueue(new DiskBasedCache(getCacheDir(), 0), new BasicNetwork(new HurlStack()));

		final int activityPlaylistsListItem = R.layout.activity_playlists_list_item;

		requestQueue.start();

		JsonObjectRequest jsonObjectRequest = new JsonObjectRequest(Request.Method.GET, computer[0]+"/playlists.php?get_playlists_as_json", null, new Response.Listener<JSONObject>()
		{
			@Override
			public void onResponse(JSONObject response)
			{
				requestQueue.stop();

				try
				{
					ArrayList<String> playlistNames = new ArrayList<>();

					Iterator<?> iterator = response.keys();

					while(iterator.hasNext())
					{
						String key = (String) iterator.next();

						playlistNames.add(key);
					}

					Collections.sort(playlistNames, new Comparator<String>()
					{
						@Override
						public int compare(String string1, String string2)
						{
							return string1.compareToIgnoreCase(string2);
						}
					});

					for(String playlistName : playlistNames)
					{
						mPlaylistUris.add(response.getString(playlistName));
					}

					mProgressBar.setVisibility(View.GONE);

					mListView.setAdapter(new ArrayAdapter<>(mContext, activityPlaylistsListItem, playlistNames));
					mListView.setVisibility(View.VISIBLE);

					mListView.setOnItemClickListener(new AdapterView.OnItemClickListener()
					{
						@Override
						public void onItemClick(AdapterView<?> parent, View view, int position, long id)
						{
							mTools.remoteControl(computerId, "play_uri", mPlaylistUris.get(position));

							finish();
						}
					});
				}
				catch(Exception e)
				{
					mProgressBar.setVisibility(View.GONE);

					TextView textView = findViewById(R.id.playlists_error);
					textView.setVisibility(View.VISIBLE);
				}
			}
		}, new Response.ErrorListener()
		{
			@Override
			public void onErrorResponse(VolleyError error)
			{
				requestQueue.stop();

				mProgressBar.setVisibility(View.GONE);

				TextView textView = findViewById(R.id.playlists_error);
				textView.setVisibility(View.VISIBLE);
			}
		})
		{
			@Override
			public HashMap<String,String> getHeaders()
			{
				HashMap<String,String> hashMap = new HashMap<>();

				if(!computer[1].equals("") && !computer[2].equals("")) hashMap.put("Authorization", "Basic "+Base64.encodeToString((computer[1]+":"+computer[2]).getBytes(), Base64.NO_WRAP));

				return hashMap;
			}
		};

		jsonObjectRequest.setRetryPolicy(new DefaultRetryPolicy(2500, 0, 0));

		requestQueue.add(jsonObjectRequest);
	}

	// Menu
	@Override
	public boolean onOptionsItemSelected(MenuItem item)
	{
		switch(item.getItemId())
		{
			case android.R.id.home:
			{
				finish();
				return true;
			}
			default:
			{
				return super.onOptionsItemSelected(item);
			}
		}
	}
}